<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Models\ClaimMessage;
use App\Models\SellerClaim;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AdminClaimService
{
    /**
     * Compute platform-wide claims and disputes KPIs.
     *
     * @return array<string, mixed>
     */
    public function getClaimMetrics(): array
    {
        $all = SellerClaim::query()->get(['id', 'status', 'claimed_amount', 'approved_amount', 'compensated_at']);

        return [
            'total' => $all->count(),
            'open' => $all->where('status', ClaimStatus::OPEN)->count(),
            'under_review' => $all->whereIn('status', [ClaimStatus::UNDER_REVIEW, ClaimStatus::MORE_INFO_NEEDED])->count(),
            'approved' => $all->where('status', ClaimStatus::APPROVED)->count(),
            'rejected' => $all->where('status', ClaimStatus::REJECTED)->count(),
            'closed' => $all->where('status', ClaimStatus::CLOSED)->count(),
            'total_claimed' => (float) $all->sum('claimed_amount'),
            'total_compensated' => (float) $all->where('status', ClaimStatus::APPROVED)->sum('approved_amount'),
        ];
    }

    /**
     * Aggregate claim distribution by category / root cause reason.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getClaimReasonsDistribution(): array
    {
        $claims = SellerClaim::query()->get(['id', 'claim_type', 'claimed_amount', 'approved_amount', 'status']);
        $totalCount = $claims->count() ?: 1;

        $results = [];
        foreach (ClaimType::cases() as $type) {
            $matching = $claims->where('claim_type', $type);
            $count = $matching->count();

            $results[] = [
                'type' => $type,
                'label' => $type->label(),
                'count' => $count,
                'percentage' => round(($count / $totalCount) * 100, 1),
                'claimed_amount' => (float) $matching->sum('claimed_amount'),
                'approved_amount' => (float) $matching->where('status', ClaimStatus::APPROVED)->sum('approved_amount'),
            ];
        }

        // Sort by count descending
        usort($results, fn($a, $b) => $b['count'] <=> $a['count']);

        return $results;
    }

    /**
     * Get recent claim trend distribution (e.g. past 14 days and top claiming merchants).
     *
     * @return array<string, mixed>
     */
    public function getClaimTrends(): array
    {
        $recentDays = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $recentDays[$date] = [
                'date' => now()->subDays($i)->format('d M'),
                'count' => 0,
                'amount' => 0.0,
            ];
        }

        $records = SellerClaim::query()
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->get(['created_at', 'claimed_amount']);

        foreach ($records as $record) {
            $key = $record->created_at->format('Y-m-d');
            if (isset($recentDays[$key])) {
                $recentDays[$key]['count']++;
                $recentDays[$key]['amount'] += (float) $record->claimed_amount;
            }
        }

        // Top claiming sellers
        $topSellers = SellerClaim::query()
            ->with('seller')
            ->select('seller_id', DB::raw('count(*) as claims_count'), DB::raw('sum(claimed_amount) as total_claimed'))
            ->groupBy('seller_id')
            ->orderByDesc('claims_count')
            ->limit(5)
            ->get();

        return [
            'daily' => array_values($recentDays),
            'top_sellers' => $topSellers,
        ];
    }

    /**
     * Build the claims database query with full filtering.
     *
     * @param array<string, mixed> $filters
     */
    public function buildClaimsQuery(array $filters = []): Builder
    {
        $query = SellerClaim::query()
            ->with([
                'seller.user',
                'order.user',
                'orderItem.variant.product',
                'fulfilmentOrder.shipment',
                'orderReturn',
            ]);

        $status = $filters['status'] ?? 'all';
        if ($status !== 'all' && !empty($status)) {
            if ($status === 'under_review') {
                $query->whereIn('status', [ClaimStatus::UNDER_REVIEW, ClaimStatus::MORE_INFO_NEEDED]);
            } else {
                $query->where('status', $status);
            }
        }

        if (!empty($filters['claim_type'])) {
            $query->where('claim_type', $filters['claim_type']);
        }

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('claim_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('order', fn(Builder $oq) => $oq->where('order_number', 'like', "%{$search}%"))
                  ->orWhereHas('seller', fn(Builder $sq) => $sq->where('business_name', 'like', "%{$search}%")
                      ->orWhere('legal_name', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->latest('id');
    }

    /**
     * Add staff-only internal note to claim audit trail.
     */
    public function addInternalNote(SellerClaim $claim, string $note, User $admin): ClaimMessage
    {
        $message = ClaimMessage::create([
            'seller_claim_id' => $claim->id,
            'user_id' => $admin->id,
            'is_internal' => true,
            'action_type' => 'internal_note',
            'message' => $note,
        ]);

        if (function_exists('activity')) {
            activity('claims')
                ->performedOn($claim)
                ->causedBy($admin)
                ->withProperties(['note' => $note])
                ->log("Admin added internal staff note on Claim #{$claim->claim_number}");
        }

        return $message;
    }

    /**
     * Request additional information/evidence from seller.
     */
    public function requestMoreInfo(SellerClaim $claim, string $instructions, User $admin): ClaimMessage
    {
        return DB::transaction(function () use ($claim, $instructions, $admin) {
            $claim->status = ClaimStatus::MORE_INFO_NEEDED;
            $claim->save();

            $message = ClaimMessage::create([
                'seller_claim_id' => $claim->id,
                'user_id' => $admin->id,
                'is_internal' => false,
                'action_type' => 'more_info_requested',
                'message' => "Action Required: {$instructions}",
            ]);

            if (function_exists('activity')) {
                activity('claims')
                    ->performedOn($claim)
                    ->causedBy($admin)
                    ->withProperties(['instructions' => $instructions])
                    ->log("Admin requested additional info for Claim #{$claim->claim_number}");
            }

            return $message;
        });
    }

    /**
     * Approve claim and record financial adjustment in seller wallet ledger.
     * Prevents duplicate compensation strictly.
     */
    public function approveClaim(SellerClaim $claim, float $approvedAmount, ?string $notes, User $admin): SellerClaim
    {
        // 1. Strict Duplicate Compensation Check
        if ($claim->compensated_at !== null || $claim->status === ClaimStatus::APPROVED) {
            throw new RuntimeException("Compensation has already been recorded for Claim #{$claim->claim_number}. Duplicate compensation is strictly prohibited.");
        }

        if ($approvedAmount <= 0) {
            throw new InvalidArgumentException("Approved compensation amount must be greater than zero.");
        }

        if ($approvedAmount > ((float) $claim->claimed_amount + 0.01)) {
            throw new InvalidArgumentException("Approved amount cannot exceed the claimed amount of ₹" . number_format((float) $claim->claimed_amount, 2));
        }

        $approvalNotes = $notes ?: 'Claim approved after evidence verification.';

        return DB::transaction(function () use ($claim, $approvedAmount, $approvalNotes, $admin) {
            $ref = 'CR-' . strtoupper(now()->format('ymd')) . '-' . strtoupper(substr(uniqid(), -4));

            $claim->status = ClaimStatus::APPROVED;
            $claim->approved_amount = $approvedAmount;
            $claim->compensation_reference = $ref;
            $claim->admin_notes = $approvalNotes;
            $claim->compensated_at = now();
            $claim->closed_at = now();
            $claim->save();

            // Credit seller wallet ledger
            $sellerUser = $claim->seller?->user;
            if ($sellerUser && $approvedAmount > 0) {
                $newBalance = (float) $sellerUser->wallet_balance + $approvedAmount;
                $sellerUser->wallet_balance = $newBalance;
                $sellerUser->save();

                WalletTransaction::create([
                    'user_id' => $sellerUser->id,
                    'type' => 'credit',
                    'amount' => $approvedAmount,
                    'balance_after' => $newBalance,
                    'reference_type' => SellerClaim::class,
                    'reference_id' => $claim->id,
                    'description' => "Compensation for Claim #{$claim->claim_number} ({$ref})",
                ]);
            }

            ClaimMessage::create([
                'seller_claim_id' => $claim->id,
                'user_id' => $admin->id,
                'is_internal' => false,
                'action_type' => 'approved',
                'message' => "Claim approved for compensation of ₹" . number_format($approvedAmount, 2) . ". Ref: {$ref}. Notes: {$approvalNotes}",
            ]);

            if (function_exists('activity')) {
                activity('claims')
                    ->performedOn($claim)
                    ->causedBy($admin)
                    ->withProperties([
                        'approved_amount' => $approvedAmount,
                        'reference' => $ref,
                    ])
                    ->log("Admin approved Claim #{$claim->claim_number} with ₹{$approvedAmount} compensation.");
            }

            return $claim;
        });
    }

    /**
     * Reject claim with mandatory reason explanation.
     */
    public function rejectClaim(SellerClaim $claim, string $rejectionReason, User $admin): SellerClaim
    {
        if (empty(trim($rejectionReason))) {
            throw new InvalidArgumentException("A valid rejection reason is mandatory.");
        }

        return DB::transaction(function () use ($claim, $rejectionReason, $admin) {
            $claim->status = ClaimStatus::REJECTED;
            $claim->rejection_reason = $rejectionReason;
            $claim->closed_at = now();
            $claim->save();

            ClaimMessage::create([
                'seller_claim_id' => $claim->id,
                'user_id' => $admin->id,
                'is_internal' => false,
                'action_type' => 'rejected',
                'message' => "Claim rejected. Reason: {$rejectionReason}",
            ]);

            if (function_exists('activity')) {
                activity('claims')
                    ->performedOn($claim)
                    ->causedBy($admin)
                    ->withProperties(['rejection_reason' => $rejectionReason])
                    ->log("Admin rejected Claim #{$claim->claim_number}: {$rejectionReason}");
            }

            return $claim;
        });
    }

    /**
     * Close claim with formal closure log.
     */
    public function closeClaim(SellerClaim $claim, ?string $reason, User $admin): SellerClaim
    {
        $closeReason = $reason ?: 'Dispute formally closed by administrator.';

        return DB::transaction(function () use ($claim, $closeReason, $admin) {
            $claim->status = ClaimStatus::CLOSED;
            $claim->closed_at = now();
            $claim->save();

            ClaimMessage::create([
                'seller_claim_id' => $claim->id,
                'user_id' => $admin->id,
                'is_internal' => false,
                'action_type' => 'closed',
                'message' => "Dispute closed: {$closeReason}",
            ]);

            if (function_exists('activity')) {
                activity('claims')
                    ->performedOn($claim)
                    ->causedBy($admin)
                    ->withProperties(['close_reason' => $closeReason])
                    ->log("Admin closed Claim #{$claim->claim_number}");
            }

            return $claim;
        });
    }

    /**
     * Escalate dispute for senior review / carrier inquiry.
     */
    public function escalateDispute(SellerClaim $claim, string $escalationReason, User $admin): SellerClaim
    {
        return DB::transaction(function () use ($claim, $escalationReason, $admin) {
            $claim->status = ClaimStatus::UNDER_REVIEW;
            $timestamp = now()->format('d M Y, h:i A');
            $escalationNote = "[{$timestamp} by {$admin->name} - DISPUTE ESCALATED]: {$escalationReason}";
            $claim->admin_notes = ($claim->admin_notes ? $claim->admin_notes . "\n\n" : '') . $escalationNote;
            $claim->save();

            ClaimMessage::create([
                'seller_claim_id' => $claim->id,
                'user_id' => $admin->id,
                'is_internal' => true,
                'action_type' => 'escalated',
                'message' => "Escalated to senior carrier review: {$escalationReason}",
            ]);

            if (function_exists('activity')) {
                activity('claims')
                    ->performedOn($claim)
                    ->causedBy($admin)
                    ->withProperties(['escalation_reason' => $escalationReason])
                    ->log("Admin escalated Claim #{$claim->claim_number} for senior review.");
            }

            return $claim;
        });
    }
}
