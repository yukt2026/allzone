<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFraudAssessment;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCustomerService
{
    /**
     * Get paginated and filtered customers with live SQL aggregates.
     *
     * @param array<string, mixed> $filters
     */
    public function getFilteredCustomers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($filters)
            ->with(['user', 'defaultAddress'])
            ->withCount('orders')
            ->withSum('orders as total_purchase_value', 'total')
            ->withMax('orders as last_order_date', 'created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Build filtered base query.
     *
     * @param array<string, mixed> $filters
     */
    public function buildFilteredQuery(array $filters = []): Builder
    {
        $query = Customer::query();

        // 1. Text Search across Customer ID, Name, Email, and Phone
        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $cleanId = ltrim($search, '#');

            $query->where(function (Builder $sub) use ($search, $cleanId) {
                if (is_numeric($cleanId)) {
                    $sub->where('customers.id', (int) $cleanId);
                }

                $sub->orWhereHas('user', function (Builder $userSub) use ($search) {
                    $userSub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        // 2. Tab Filter (all, active, suspended/restricted)
        if (!empty($filters['tab']) && $filters['tab'] !== 'all') {
            match ($filters['tab']) {
                'active' => $query->whereHas('user', fn (Builder $q) => $q->where('status', UserStatus::ACTIVE)),
                'restricted', 'suspended' => $query->whereHas('user', fn (Builder $q) => $q->whereIn('status', [UserStatus::SUSPENDED, UserStatus::INACTIVE])),
                default => null,
            };
        } elseif (!empty($filters['status']) && $filters['status'] !== 'all') {
            $status = $filters['status'];
            $query->whereHas('user', function (Builder $q) use ($status) {
                if ($status === 'blocked' || $status === 'restricted' || $status === 'suspended') {
                    $q->whereIn('status', [UserStatus::SUSPENDED, UserStatus::INACTIVE]);
                } else {
                    $q->where('status', $status);
                }
            });
        }

        // 3. Date Range Filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // 4. Sorting
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'orders_desc' => $query->orderByDesc(
                Order::selectRaw('count(*)')
                    ->whereColumn('customer_id', 'customers.id')
            ),
            'spend_desc' => $query->orderByDesc(
                Order::selectRaw('coalesce(sum(total), 0)')
                    ->whereColumn('customer_id', 'customers.id')
            ),
            default => $query->latest('id'),
        };

        return $query;
    }

    /**
     * Get tab counts for customer listing.
     *
     * @return array<string, int>
     */
    public function getCustomerTabCounts(): array
    {
        return [
            'all' => Customer::count(),
            'active' => Customer::whereHas('user', fn (Builder $q) => $q->where('status', UserStatus::ACTIVE))->count(),
            'restricted' => Customer::whereHas('user', fn (Builder $q) => $q->whereIn('status', [UserStatus::SUSPENDED, UserStatus::INACTIVE]))->count(),
        ];
    }

    /**
     * Get top-level customer KPI metrics.
     *
     * @return array<string, mixed>
     */
    public function getCustomerMetrics(): array
    {
        $totalCustomers = Customer::count();
        $totalRevenue = (float) Order::where('payment_status', PaymentStatus::PAID)->sum('total');
        $totalOrders = Order::count();

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => Customer::whereHas('user', fn (Builder $q) => $q->where('status', UserStatus::ACTIVE))->count(),
            'blocked_customers' => Customer::whereHas('user', fn (Builder $q) => $q->whereIn('status', [UserStatus::SUSPENDED, UserStatus::INACTIVE]))->count(),
            'new_this_month' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0.0,
        ];
    }

    /**
     * Compute comprehensive customer metrics and risk signals.
     *
     * @return array<string, mixed>
     */
    public function getCustomerDetails(Customer $customer): array
    {
        $ordersCount = $customer->orders()->count();
        $deliveredOrdersCount = $customer->orders()->where('status', OrderStatus::DELIVERED)->count();
        $cancelledOrdersCount = $customer->orders()->where('status', OrderStatus::CANCELLED)->count();
        $lifetimeSpend = (float) $customer->orders()->whereIn('payment_status', [PaymentStatus::PAID, 'paid'])->sum('total');
        if ($lifetimeSpend <= 0) {
            $lifetimeSpend = (float) $customer->orders()->sum('total');
        }

        $orderIds = $customer->orders()->pluck('id');
        $returnsCount = OrderReturn::whereHas('orderItem.fulfilmentOrder.order', function (Builder $q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })->count();

        $refundsTotal = (float) Refund::whereHas('payment', function (Builder $q) use ($orderIds) {
            $q->whereIn('order_id', $orderIds);
        })->sum('amount');

        $reviewsCount = Review::where('user_id', $customer->user_id)->count();
        $ticketsCount = SupportTicket::where('user_id', $customer->user_id)->count();

        // Suspicious activity signals evaluation
        $fraudSignals = [];
        if ($ordersCount >= 3 && ($cancelledOrdersCount / $ordersCount) > 0.40) {
            $fraudSignals[] = [
                'type' => 'high_cancellation_rate',
                'severity' => 'warning',
                'message' => 'High cancellation rate: ' . round(($cancelledOrdersCount / $ordersCount) * 100) . '% of orders cancelled.',
            ];
        }

        if ($ordersCount >= 2 && ($returnsCount / $ordersCount) > 0.50) {
            $fraudSignals[] = [
                'type' => 'high_return_frequency',
                'severity' => 'danger',
                'message' => 'High return frequency: ' . $returnsCount . ' return claim(s) filed across ' . $ordersCount . ' orders.',
            ];
        }

        $highRiskOrdersCount = OrderFraudAssessment::whereIn('order_id', $orderIds)
            ->where(function (Builder $q) {
                $q->where('risk_level', 'high')->orWhere('risk_score', '>=', 70);
            })->count();

        if ($highRiskOrdersCount > 0) {
            $fraudSignals[] = [
                'type' => 'high_fraud_score_order',
                'severity' => 'danger',
                'message' => "Detected {$highRiskOrdersCount} order(s) flagged as high risk by automated anti-fraud checks.",
            ];
        }

        return [
            'lifetime_spend' => $lifetimeSpend,
            'orders_count' => $ordersCount,
            'delivered_orders_count' => $deliveredOrdersCount,
            'cancelled_orders_count' => $cancelledOrdersCount,
            'returns_count' => $returnsCount,
            'refunds_total' => $refundsTotal,
            'reviews_count' => $reviewsCount,
            'tickets_count' => $ticketsCount,
            'fraud_signals' => $fraudSignals,
        ];
    }

    /**
     * Restrict customer account with mandatory policy justification.
     */
    public function restrictCustomer(Customer $customer, string $reason, User $admin): void
    {
        $user = $customer->user;
        if (!$user) {
            throw new \RuntimeException('Associated user record not found.');
        }

        DB::transaction(function () use ($customer, $user, $reason, $admin) {
            $oldStatus = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;
            $user->update(['status' => UserStatus::SUSPENDED]);

            if (function_exists('activity')) {
                activity('customer')
                    ->performedOn($customer)
                    ->causedBy($admin)
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => UserStatus::SUSPENDED->value,
                        'reason' => $reason,
                        'admin_id' => $admin->id,
                        'admin_name' => $admin->name,
                    ])
                    ->log("Restricted customer account #{$customer->id} ({$user->name}). Reason: {$reason}");
            }
        });
    }

    /**
     * Reactivate previously restricted customer account.
     */
    public function reactivateCustomer(Customer $customer, string $reason, User $admin): void
    {
        $user = $customer->user;
        if (!$user) {
            throw new \RuntimeException('Associated user record not found.');
        }

        DB::transaction(function () use ($customer, $user, $reason, $admin) {
            $oldStatus = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;
            $user->update(['status' => UserStatus::ACTIVE]);

            if (function_exists('activity')) {
                activity('customer')
                    ->performedOn($customer)
                    ->causedBy($admin)
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => UserStatus::ACTIVE->value,
                        'reason' => $reason,
                        'admin_id' => $admin->id,
                        'admin_name' => $admin->name,
                    ])
                    ->log("Reactivated customer account #{$customer->id} ({$user->name}). Reason: {$reason}");
            }
        });
    }

    /**
     * Record administrative suspicious activity review.
     *
     * @param array<string, mixed> $data
     */
    public function recordSuspiciousReview(Customer $customer, array $data, User $admin): void
    {
        $decision = $data['decision'] ?? 'reviewed';
        $notes = $data['notes'] ?? 'Administrative security review conducted.';

        if (function_exists('activity')) {
            activity('customer')
                ->performedOn($customer)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'suspicious_activity_review',
                    'decision' => $decision,
                    'notes' => $notes,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                ])
                ->log("Reviewed suspicious activity for customer #{$customer->id} ({$customer->user?->name}). Decision: {$decision}. Notes: {$notes}");
        }
    }

    /**
     * Export authorized customer report as a downloadable CSV stream.
     *
     * @param array<string, mixed> $filters
     */
    public function exportCsv(array $filters = [], bool $canViewSensitive = true): StreamedResponse
    {
        $customers = $this->buildFilteredQuery($filters)
            ->with(['user', 'defaultAddress'])
            ->withCount('orders')
            ->withSum('orders as total_purchase_value', 'total')
            ->withMax('orders as last_order_date', 'created_at')
            ->get();

        $filename = "allzone_customers_export_" . date('Y_m_d_His') . ".csv";

        return new StreamedResponse(function () use ($customers, $canViewSensitive) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Customer ID',
                'Name',
                'Email',
                'Phone',
                'Account Status',
                'Total Orders',
                'Lifetime Spend (INR)',
                'Last Order Date',
                'Primary City',
                'Registered Date',
            ]);

            foreach ($customers as $c) {
                $status = $c->user?->status;
                $statusStr = $status instanceof UserStatus ? $status->label() : (string) ($status ?? 'Active');

                $email = $c->user?->email;
                $phone = $c->user?->phone;

                if (!$canViewSensitive) {
                    $email = $this->maskEmail($email);
                    $phone = $this->maskPhone($phone);
                }

                fputcsv($handle, [
                    $c->id,
                    $c->user?->name ?? 'Guest',
                    $email ?? 'N/A',
                    $phone ?? 'N/A',
                    $statusStr,
                    $c->orders_count ?? 0,
                    number_format((float) ($c->total_purchase_value ?? 0), 2, '.', ''),
                    $c->last_order_date ? date('Y-m-d H:i', strtotime((string) $c->last_order_date)) : 'Never',
                    $c->defaultAddress?->city ?? 'N/A',
                    $c->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Mask email address for privacy protection.
     */
    public function maskEmail(?string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return 'N/A';
        }

        [$name, $domain] = explode('@', $email, 2);
        $len = strlen($name);

        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '***';
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', min(4, $len - 2)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }

    /**
     * Mask phone number for privacy protection.
     */
    public function maskPhone(?string $phone): string
    {
        if (empty($phone)) {
            return 'N/A';
        }

        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        $len = strlen($cleaned);

        if ($len <= 4) {
            return '••••';
        }

        $last4 = substr($cleaned, -4);
        return '•••••• ' . $last4;
    }
}
