<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Admin\AdminCustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function __construct(
        protected AdminCustomerService $customerService
    ) {}

    /**
     * Display searchable & filterable customer list.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $filters = [
            'tab' => $request->query('tab', 'all'),
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'sort' => $request->query('sort', 'latest'),
        ];

        $customers = $this->customerService->getFilteredCustomers($filters, 15);
        $tabCounts = $this->customerService->getCustomerTabCounts();
        $metrics = $this->customerService->getCustomerMetrics();

        return view('admin.customers.index', [
            'customers' => $customers,
            'tabCounts' => $tabCounts,
            'metrics' => $metrics,
            'filters' => $filters,
            'activeTab' => $filters['tab'],
        ]);
    }

    /**
     * Display customer detail dossier with all sub-modules and tabs.
     */
    public function show(Request $request, Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->loadMissing(['user', 'addresses', 'defaultAddress']);

        $orders = $customer->orders()
            ->with(['items.product', 'fulfilmentOrders'])
            ->latest('id')
            ->paginate(10, ['*'], 'orders_page')
            ->appends(array_merge($request->query(), ['tab' => 'orders']));

        $payments = Payment::whereIn('order_id', $customer->orders()->select('id'))
            ->with('order')
            ->latest('id')
            ->paginate(10, ['*'], 'payments_page')
            ->appends(array_merge($request->query(), ['tab' => 'payments']));

        $refunds = Refund::whereHas('payment.order', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->with(['payment.order', 'fulfilmentOrder'])
            ->latest('id')
            ->paginate(10, ['*'], 'refunds_page')
            ->appends(array_merge($request->query(), ['tab' => 'refunds']));

        $returns = OrderReturn::whereHas('orderItem.fulfilmentOrder.order', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->with(['orderItem.product', 'pickupShipment', 'refund'])
            ->latest('id')
            ->paginate(10, ['*'], 'returns_page')
            ->appends(array_merge($request->query(), ['tab' => 'returns']));

        $tickets = SupportTicket::where('user_id', $customer->user_id)
            ->with(['categoryRelation', 'assignedAgent'])
            ->latest('id')
            ->paginate(10, ['*'], 'tickets_page')
            ->appends(array_merge($request->query(), ['tab' => 'tickets']));

        $reviews = Review::where('user_id', $customer->user_id)
            ->with('product')
            ->latest('id')
            ->paginate(10, ['*'], 'reviews_page')
            ->appends(array_merge($request->query(), ['tab' => 'reviews']));

        $activityLogs = Activity::where(function ($q) use ($customer) {
                $q->where('subject_type', Customer::class)->where('subject_id', $customer->id);
            })
            ->orWhere(function ($q) use ($customer) {
                $q->where('subject_type', User::class)->where('subject_id', $customer->user_id);
            })
            ->latest('id')
            ->take(30)
            ->get();

        $details = $this->customerService->getCustomerDetails($customer);

        $activeTab = (string) $request->query('tab', '');
        if (empty($activeTab)) {
            if ($request->has('orders_page')) {
                $activeTab = 'orders';
            } elseif ($request->has('payments_page')) {
                $activeTab = 'payments';
            } elseif ($request->has('refunds_page')) {
                $activeTab = 'refunds';
            } elseif ($request->has('returns_page')) {
                $activeTab = 'returns';
            } elseif ($request->has('tickets_page')) {
                $activeTab = 'tickets';
            } elseif ($request->has('reviews_page')) {
                $activeTab = 'reviews';
            } else {
                $activeTab = 'profile';
            }
        }

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'payments' => $payments,
            'refunds' => $refunds,
            'returns' => $returns,
            'tickets' => $tickets,
            'reviews' => $reviews,
            'activityLogs' => $activityLogs,
            'details' => $details,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Restrict or suspend customer account with mandatory administrative reason.
     */
    public function restrict(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('restrict', $customer);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->customerService->restrictCustomer($customer, $validated['reason'], $request->user());

        return redirect()
            ->route('admin.customers.show', ['customer' => $customer, 'tab' => 'profile'])
            ->with('success', "Customer account #{$customer->id} has been restricted.");
    }

    /**
     * Reactivate previously restricted customer account.
     */
    public function reactivate(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('reactivate', $customer);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->customerService->reactivateCustomer($customer, $validated['reason'], $request->user());

        return redirect()
            ->route('admin.customers.show', ['customer' => $customer, 'tab' => 'profile'])
            ->with('success', "Customer account #{$customer->id} has been reactivated.");
    }

    /**
     * Legacy toggle status support.
     */
    public function toggleStatus(Customer $customer): RedirectResponse
    {
        $this->authorize('restrict', $customer);

        $user = $customer->user;
        if (!$user) {
            return back()->with('error', 'Associated user account not found.');
        }

        $isSuspended = ($user->status === \App\Enums\UserStatus::SUSPENDED || $user->status === 'suspended');
        if ($isSuspended) {
            $this->customerService->reactivateCustomer($customer, 'Administrative status toggle from listing.', auth()->user());
            return back()->with('success', "Customer {$user->name} has been reactivated.");
        }

        $this->customerService->restrictCustomer($customer, 'Administrative status toggle from listing.', auth()->user());
        return back()->with('success', "Customer {$user->name} has been restricted.");
    }

    /**
     * Record administrative review of suspicious customer signals.
     */
    public function reviewSuspicious(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('reviewSuspicious', $customer);

        $validated = $request->validate([
            'decision' => ['required', 'string', 'max:100'],
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->customerService->recordSuspiciousReview($customer, $validated, $request->user());

        return redirect()
            ->route('admin.customers.show', ['customer' => $customer, 'tab' => 'profile'])
            ->with('success', 'Security and suspicious activity review recorded.');
    }

    /**
     * Export customer list as authorized CSV report.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Customer::class);

        $canViewSensitive = $request->user()->can('viewSensitive', Customer::class);

        return $this->customerService->exportCsv($request->all(), $canViewSensitive);
    }
}
