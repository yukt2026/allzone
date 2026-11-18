<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\SellerClaim;
use App\Models\ClaimMessage;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimController extends Controller
{
    /**
     * Display Claims Management Dashboard with real KPI metrics, rejection reasons, and tracking table.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        if ($request->query('export') === 'csv') {
            return $this->export($request);
        }

        $tab = $request->query('tab', 'all');
        $search = $request->query('search');
        $claimType = $request->query('claim_type');

        $baseQuery = SellerClaim::query()
            ->where('seller_id', $seller->id)
            ->with(['order', 'fulfilmentOrder', 'orderItem.variant.product', 'orderReturn']);

        // Tab Filtering
        match ($tab) {
            'open' => $baseQuery->where('status', ClaimStatus::OPEN),
            'under_review' => $baseQuery->whereIn('status', [ClaimStatus::UNDER_REVIEW, ClaimStatus::MORE_INFO_NEEDED]),
            'approved' => $baseQuery->where('status', ClaimStatus::APPROVED),
            'rejected' => $baseQuery->where('status', ClaimStatus::REJECTED),
            'closed' => $baseQuery->where('status', ClaimStatus::CLOSED),
            default => null,
        };

        if ($claimType) {
            $baseQuery->where('claim_type', $claimType);
        }

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('claim_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('order', fn($oq) => $oq->where('order_number', 'like', "%{$search}%"))
                  ->orWhereHas('fulfilmentOrder', fn($fq) => $fq->where('fulfilment_order_number', 'like', "%{$search}%"))
                  ->orWhereHas('orderItem', fn($iq) => $iq->where('title', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        $claims = $baseQuery->latest()->paginate(15)->withQueryString();

        // Real Overview Metrics for authenticated seller
        $allSellerClaims = SellerClaim::where('seller_id', $seller->id)->get();
        $totalClaims = $allSellerClaims->count();
        $openClaims = $allSellerClaims->whereIn('status', [ClaimStatus::OPEN, ClaimStatus::UNDER_REVIEW, ClaimStatus::MORE_INFO_NEEDED])->count();
        $approvedClaims = $allSellerClaims->where('status', ClaimStatus::APPROVED)->count();
        $rejectedClaims = $allSellerClaims->where('status', ClaimStatus::REJECTED)->count();
        $closedClaims = $allSellerClaims->where('status', ClaimStatus::CLOSED)->count();

        // Total delivered customer returns and delivered courier RTOs for this seller
        $customerReturnsCount = OrderReturn::whereHas('orderItem.fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->where('type', '!=', 'rto')
            ->count();

        $courierRtoCount = Shipment::whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
            ->count();

        $totalReturnsAndRtos = $customerReturnsCount + $courierRtoCount;
        $claimRate = $totalReturnsAndRtos > 0 ? round(($totalClaims / $totalReturnsAndRtos) * 100, 1) : 0.0;

        $decidedClaims = $approvedClaims + $rejectedClaims;
        $approvalRate = $decidedClaims > 0 ? round(($approvedClaims / $decidedClaims) * 100, 1) : 0.0;

        // Rejection reasons based strictly on actual rejected claims
        $rejectionReasons = $allSellerClaims->where('status', ClaimStatus::REJECTED)
            ->pluck('rejection_reason')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(3)
            ->all();

        // Claim Categories breakdown
        $categoryBreakdown = $allSellerClaims->pluck('claim_type')
            ->filter()
            ->countBy(fn($type) => $type instanceof ClaimType ? $type->label() : (string)$type)
            ->all();

        // 14-day trends for Claims Raised vs Claims Approved
        $dates = collect(range(13, 0))->map(fn($d) => now()->subDays($d)->format('Y-m-d'));
        $claimsRaisedTrend = [];
        $claimsApprovedTrend = [];
        $trendLabels = [];

        foreach ($dates as $date) {
            $trendLabels[] = date('d M', strtotime($date));
            $claimsRaisedTrend[] = $allSellerClaims->filter(fn($c) => $c->created_at->format('Y-m-d') === $date)->count();
            $claimsApprovedTrend[] = $allSellerClaims->filter(fn($c) => $c->status === ClaimStatus::APPROVED && $c->closed_at?->format('Y-m-d') === $date)->count();
        }

        $claimStats = [
            'total' => $totalClaims,
            'open' => $openClaims,
            'approved' => $approvedClaims,
            'rejected' => $rejectedClaims,
            'closed' => $closedClaims,
            'claim_rate' => $claimRate,
            'approval_rate' => $approvalRate,
            'total_returns_and_rtos' => $totalReturnsAndRtos,
            'rejection_reasons' => $rejectionReasons,
            'category_breakdown' => $categoryBreakdown,
            'trend' => [
                'labels' => $trendLabels,
                'raised' => $claimsRaisedTrend,
                'approved' => $claimsApprovedTrend,
            ],
        ];

        $counts = [
            'all' => $totalClaims,
            'open' => $allSellerClaims->where('status', ClaimStatus::OPEN)->count(),
            'under_review' => $allSellerClaims->whereIn('status', [ClaimStatus::UNDER_REVIEW, ClaimStatus::MORE_INFO_NEEDED])->count(),
            'approved' => $approvedClaims,
            'rejected' => $rejectedClaims,
            'closed' => $closedClaims,
        ];

        return view('seller.claims.index', [
            'tab' => $tab,
            'claimStats' => $claimStats,
            'counts' => $counts,
            'claims' => $claims,
            'search' => $search,
            'claimType' => $claimType,
        ]);
    }

    /**
     * Show Raise Claim creation form with order lookup and eligibility checks.
     */
    public function create(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $prefillOrderId = $request->query('order_id');
        $prefillReturnId = $request->query('return_id');
        $prefillItemId = $request->query('item_id');

        $prefillItem = null;
        if ($prefillItemId) {
            $prefillItem = OrderItem::where('id', $prefillItemId)
                ->whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
                ->with(['fulfilmentOrder.order', 'variant.product'])
                ->first();
        } elseif ($prefillReturnId) {
            $return = OrderReturn::where('id', $prefillReturnId)
                ->whereHas('orderItem.fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
                ->with('orderItem.variant.product')
                ->first();
            $prefillItem = $return?->orderItem;
        }

        return view('seller.claims.create', [
            'prefillItem' => $prefillItem,
            'prefillReturnId' => $prefillReturnId,
            'categories' => ClaimType::cases(),
        ]);
    }

    /**
     * AJAX endpoint to search orders and sub-orders for claim eligibility.
     */
    public function searchOrder(Request $request): JsonResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $term = trim((string) $request->query('q', ''));
        if (strlen($term) < 3) {
            return response()->json(['items' => []]);
        }

        $items = OrderItem::query()
            ->whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->where(function ($q) use ($term) {
                $q->where('sku', 'like', "%{$term}%")
                  ->orWhere('title', 'like', "%{$term}%")
                  ->orWhereHas('fulfilmentOrder', fn($fq) => $fq->where('fulfilment_order_number', 'like', "%{$term}%"))
                  ->orWhereHas('fulfilmentOrder.order', fn($oq) => $oq->where('order_number', 'like', "%{$term}%"));
            })
            ->with(['fulfilmentOrder.order', 'variant.product', 'returns'])
            ->latest('id')
            ->take(10)
            ->get();

        $results = $items->map(function ($item) {
            $fo = $item->fulfilmentOrder;
            $order = $fo?->order;
            
            // Check if active claim already exists
            $existingClaim = SellerClaim::where('order_item_id', $item->id)
                ->whereNotIn('status', [ClaimStatus::REJECTED, ClaimStatus::CLOSED])
                ->first();

            $isEligible = ($existingClaim === null);
            $reason = null;

            if ($existingClaim) {
                $reason = "Claim #{$existingClaim->claim_number} is already active for this item.";
            }

            return [
                'id' => $item->id,
                'order_id' => $order?->id,
                'order_number' => $order?->order_number,
                'fulfilment_order_id' => $fo?->id,
                'fulfilment_order_number' => $fo?->fulfilment_order_number,
                'title' => $item->title,
                'sku' => $item->sku,
                'variant_name' => $item->variant_name,
                'qty' => $item->qty,
                'price' => (float) $item->price,
                'total' => (float) $item->total,
                'is_eligible' => $isEligible,
                'ineligibility_reason' => $reason,
                'image_url' => $item->variant?->image_url ?? $item->variant?->product?->primary_image_url ?? asset('images/placeholder.png'),
            ];
        });

        return response()->json(['items' => $results]);
    }

    /**
     * Store newly submitted seller claim with evidence file uploads.
     */
    public function store(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $validated = $request->validate([
            'order_item_id' => ['required', 'integer'],
            'claim_type' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'claimed_amount' => ['required', 'numeric', 'min:1'],
            'order_return_id' => ['nullable', 'integer'],
            'evidence_files' => ['nullable', 'array', 'max:5'],
            'evidence_files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,mp4', 'max:10240'], // 10MB max per file
        ]);

        $item = OrderItem::where('id', $validated['order_item_id'])
            ->whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->with(['fulfilmentOrder.order'])
            ->firstOrFail();

        // Guard against duplicate active claims on the same order item
        $existing = SellerClaim::where('order_item_id', $item->id)
            ->whereNotIn('status', [ClaimStatus::REJECTED, ClaimStatus::CLOSED])
            ->first();

        if ($existing) {
            return back()->with('error', "An active claim (#{$existing->claim_number}) is already registered for this order item.")
                ->withInput();
        }

        // Cap claimed amount to item total
        $maxAllowedAmount = (float) $item->total;
        $claimedAmount = min((float) $validated['claimed_amount'], $maxAllowedAmount);

        // Upload evidence files
        $evidenceUrls = [];
        if ($request->hasFile('evidence_files')) {
            foreach ($request->file('evidence_files') as $file) {
                $path = $file->store('claims/evidence/' . $seller->id, 'public');
                $evidenceUrls[] = Storage::disk('public')->url($path);
            }
        }

        $claim = DB::transaction(function () use ($validated, $seller, $item, $claimedAmount, $evidenceUrls) {
            $claimNumber = SellerClaim::generateClaimNumber();

            $claim = SellerClaim::create([
                'claim_number' => $claimNumber,
                'seller_id' => $seller->id,
                'order_id' => $item->fulfilmentOrder->order_id,
                'fulfilment_order_id' => $item->fulfilmentOrder->id,
                'order_item_id' => $item->id,
                'order_return_id' => $validated['order_return_id'] ?? null,
                'claim_type' => $validated['claim_type'],
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'status' => ClaimStatus::OPEN,
                'claimed_amount' => $claimedAmount,
                'approved_amount' => 0.00,
                'evidence_urls' => $evidenceUrls,
            ]);

            ClaimMessage::create([
                'seller_claim_id' => $claim->id,
                'user_id' => Auth::id(),
                'is_internal' => false,
                'action_type' => 'created',
                'message' => "Claim registered: {$claim->reason}. Claimed amount: ₹" . number_format($claimedAmount, 2),
                'attachments' => $evidenceUrls,
            ]);

            return $claim;
        });

        return redirect()->route('seller.claims.show', $claim)
            ->with('success', "Claim #{$claim->claim_number} has been submitted successfully for verification.");
    }

    /**
     * Display Claim Detail Screen with conversation stream and evidence preview.
     */
    public function show(SellerClaim $claim): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $claim->seller_id !== $seller->id, 403, 'Unauthorized claim record.');

        $claim->load([
            'orderItem.variant.product',
            'order.user',
            'fulfilmentOrder.shipment',
            'orderReturn',
            'publicMessages.user',
        ]);

        return view('seller.claims.show', [
            'claim' => $claim,
        ]);
    }

    /**
     * Seller replies to support/admin with clarification or additional documentation.
     */
    public function reply(Request $request, SellerClaim $claim): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $claim->seller_id !== $seller->id, 403, 'Unauthorized claim record.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,mp4', 'max:10240'],
        ]);

        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('claims/evidence/' . $seller->id, 'public');
                $attachmentUrls[] = Storage::disk('public')->url($path);
            }
        }

        DB::transaction(function () use ($claim, $validated, $attachmentUrls) {
            ClaimMessage::create([
                'seller_claim_id' => $claim->id,
                'user_id' => Auth::id(),
                'is_internal' => false,
                'action_type' => 'info_submitted',
                'message' => $validated['message'],
                'attachments' => $attachmentUrls,
            ]);

            // If claim was waiting on seller info, transition back to under_review
            if ($claim->status === ClaimStatus::MORE_INFO_NEEDED) {
                $claim->status = ClaimStatus::UNDER_REVIEW;
                $claim->save();
            }
        });

        return back()->with('success', 'Your clarification has been submitted to the claims review team.');
    }

    /**
     * Stream CSV export of seller claims.
     */
    public function export(Request $request): StreamedResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $filename = "claims-seller-{$seller->id}-" . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($seller) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Claim Number',
                'Order Number',
                'Sub-order Package',
                'Product Title',
                'SKU',
                'Claim Category',
                'Reason',
                'Claimed Amount (INR)',
                'Approved Amount (INR)',
                'Status',
                'Rejection Reason',
                'Compensation Ref',
                'Created Date',
                'Closed Date',
            ]);

            SellerClaim::where('seller_id', $seller->id)
                ->with(['order', 'fulfilmentOrder', 'orderItem'])
                ->latest()
                ->chunk(100, function ($claims) use ($handle) {
                    foreach ($claims as $c) {
                        fputcsv($handle, [
                            $c->claim_number,
                            $c->order?->order_number ?? 'N/A',
                            $c->fulfilmentOrder?->fulfilment_order_number ?? 'N/A',
                            $c->orderItem?->title ?? 'N/A',
                            $c->orderItem?->sku ?? 'N/A',
                            $c->claim_type?->label() ?? (string)$c->claim_type,
                            $c->reason,
                            number_format((float)$c->claimed_amount, 2),
                            number_format((float)$c->approved_amount, 2),
                            $c->status?->label() ?? (string)$c->status,
                            $c->rejection_reason ?? '-',
                            $c->compensation_reference ?? '-',
                            $c->created_at->format('Y-m-d H:i'),
                            $c->closed_at ? $c->closed_at->format('Y-m-d H:i') : '-',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
