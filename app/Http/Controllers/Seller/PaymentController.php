<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Models\CommissionEntry;
use App\Models\FulfilmentOrder;
use App\Models\SellerSettlement;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * Payments Dashboard & Seller Earnings Ledger.
     */
    public function payments(Request $request): View
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Unauthorized seller account.');
        }

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $dateRange = (string) $request->query('date_range', '');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $storeId = $request->query('store_id');

        // Base query for seller's commission entries
        $baseQuery = CommissionEntry::query()
            ->forSeller($seller->id);

        if ($storeId) {
            $baseQuery->whereHas('fulfilmentOrder', fn ($q) => $q->where('store_id', (int) $storeId));
        }

        // Tab counts
        $countAll = (clone $baseQuery)->count();
        $countSettled = (clone $baseQuery)->whereHas('settlementItem.settlement', function ($q) {
            $q->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED]);
        })->count();
        $countPending = (clone $baseQuery)->where(function ($q) {
            $q->whereDoesntHave('settlementItem')
              ->orWhereHas('settlementItem.settlement', fn ($sq) => $sq->whereIn('status', [SettlementStatus::PENDING, SettlementStatus::PROCESSING]));
        })->count();
        $countOnHold = (clone $baseQuery)->whereHas('settlementItem.settlement', function ($q) {
            $q->where('status', SettlementStatus::ON_HOLD);
        })->count();
        $countRefunds = (clone $baseQuery)->where('entry_type', 'reversal')->count();

        // Filtered listing query
        $query = (clone $baseQuery)
            ->with([
                'fulfilmentOrder.order',
                'fulfilmentOrder.store',
                'fulfilmentOrder.items.variant.product',
                'rule',
                'settlementItem.settlement',
            ])
            ->latest('id');

        // Search filter: Order #, Sub-order #, Transaction ID, Settlement #, or Bank UTR
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $cleanId = preg_replace('/[^0-9]/', '', $search);
                if (!empty($cleanId)) {
                    $q->orWhere('id', (int) $cleanId);
                }
                $q->orWhereHas('fulfilmentOrder', function ($foq) use ($search) {
                    $foq->where('fulfilment_order_number', 'like', "%{$search}%")
                        ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$search}%"));
                })
                ->orWhereHas('settlementItem.settlement', function ($sq) use ($search) {
                    $sq->where('settlement_number', 'like', "%{$search}%")
                       ->orWhere('reference', 'like', "%{$search}%");
                });
            });
        }

        // Status tab filter
        if ($entryType = $request->query('entry_type')) {
            if (in_array($entryType, ['standard', 'reversal'], true)) {
                $query->where('entry_type', $entryType);
            }
        }

        if ($entryType = $request->query('entry_type')) {
            if (in_array($entryType, ['standard', 'reversal'], true)) {
                $query->where('entry_type', $entryType);
            }
        }

        if ($status === 'settled') {
            $query->whereHas('settlementItem.settlement', fn ($q) => $q->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED]));
        } elseif ($status === 'pending') {
            $query->where(function ($q) {
                $q->whereDoesntHave('settlementItem')
                  ->orWhereHas('settlementItem.settlement', fn ($sq) => $sq->whereIn('status', [SettlementStatus::PENDING, SettlementStatus::PROCESSING]));
            });
        } elseif ($status === 'on_hold') {
            $query->whereHas('settlementItem.settlement', fn ($q) => $q->where('status', SettlementStatus::ON_HOLD));
        } elseif ($status === 'refunds') {
            $query->where('entry_type', 'reversal');
        }

        // Date range filter
        if ($dateRange === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($dateRange === '7d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(7));
        } elseif ($dateRange === '30d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        } elseif ($dateRange === 'this_month') {
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        } elseif ($dateTo) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $entries = $query->paginate(15)->withQueryString();

        // -------------------------------------------------------------------------
        // FINANCIAL KPI METRICS (AUDITABLE FROM ACTUAL DATABASE RECORDS)
        // -------------------------------------------------------------------------

        // 1. Upcoming settlements (Delivered orders not yet in a settlement)
        $returnWindowDays = (int) Setting::get('return_window_days', 7);
        $eligibleCutoff = Carbon::now()->subDays($returnWindowDays);

        $unsettledOrdersQuery = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->whereDoesntHave('settlementItem')
            ->with(['commissionEntry', 'store']);

        if ($storeId) {
            $unsettledOrdersQuery->where('store_id', (int) $storeId);
        }

        $unsettledOrders = $unsettledOrdersQuery->get();

        $upcomingMatured = $unsettledOrders->filter(fn ($fo) => $fo->delivered_at && $fo->delivered_at->lte($eligibleCutoff));
        $upcomingMaturing = $unsettledOrders->filter(fn ($fo) => $fo->delivered_at && $fo->delivered_at->gt($eligibleCutoff));

        $upcomingMaturedAmount = (float) $upcomingMatured->sum(fn ($fo) => (float) ($fo->commissionEntry?->net_payable ?? $fo->seller_payable));
        $upcomingMaturingAmount = (float) $upcomingMaturing->sum(fn ($fo) => (float) ($fo->commissionEntry?->net_payable ?? $fo->seller_payable));
        $upcomingSettlements = round($upcomingMaturedAmount + $upcomingMaturingAmount, 2);

        // 2. Completed settlements (Paid / Settled)
        $completedSettlements = (float) SellerSettlement::where('seller_id', $seller->id)
            ->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])
            ->sum('net');
        $completedBatchesCount = SellerSettlement::where('seller_id', $seller->id)
            ->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])
            ->count();

        // 3. Pending settlements (Batch created, awaiting processing/payout)
        $pendingSettlements = (float) SellerSettlement::where('seller_id', $seller->id)
            ->whereIn('status', [SettlementStatus::PENDING, SettlementStatus::PROCESSING])
            ->sum('net');
        $pendingBatchesCount = SellerSettlement::where('seller_id', $seller->id)
            ->whereIn('status', [SettlementStatus::PENDING, SettlementStatus::PROCESSING])
            ->count();

        // 4. On-hold amounts
        $onHoldAmounts = (float) SellerSettlement::where('seller_id', $seller->id)
            ->where('status', SettlementStatus::ON_HOLD)
            ->sum('net');
        $onHoldBatchesCount = SellerSettlement::where('seller_id', $seller->id)
            ->where('status', SettlementStatus::ON_HOLD)
            ->count();

        // 5. Refund adjustments (Reversal commission entries + negative return adjustments)
        $refundAdjustments = (float) (clone $baseQuery)->where('entry_type', 'reversal')->sum('base_amount');

        // 6. Commission deductions
        $commissionDeductions = (float) (clone $baseQuery)->where('entry_type', 'standard')->sum('commission');

        // 7. Shipping deductions
        $shippingDeductions = (float) (clone $baseQuery)->sum('shipping_adjustment');

        // 8. Other authorized fees (GST on Commission + Gateway Processing Fees)
        $taxOnCommission = (float) (clone $baseQuery)->sum('tax_on_commission');
        $gatewayFees = (float) (clone $baseQuery)->sum('gateway_fee');
        $otherAuthorizedFees = round($taxOnCommission + $gatewayFees, 2);

        // 9. Net seller payable & Gross sales
        $netSellerPayable = (float) (clone $baseQuery)->sum('net_payable');
        $grossSales = (float) (clone $baseQuery)->where('entry_type', 'standard')->sum('base_amount');

        // Payout bank account
        $primaryBank = $seller->primaryBankAccount ?: $seller->bankAccounts()->first();
        $stores = $seller->stores()->orderBy('name')->get();

        return view('seller.payments.index', compact(
            'entries',
            'seller',
            'stores',
            'primaryBank',
            'upcomingSettlements',
            'upcomingMaturedAmount',
            'upcomingMaturingAmount',
            'upcomingMatured',
            'upcomingMaturing',
            'completedSettlements',
            'completedBatchesCount',
            'pendingSettlements',
            'pendingBatchesCount',
            'onHoldAmounts',
            'onHoldBatchesCount',
            'refundAdjustments',
            'commissionDeductions',
            'shippingDeductions',
            'otherAuthorizedFees',
            'taxOnCommission',
            'gatewayFees',
            'netSellerPayable',
            'grossSales',
            'returnWindowDays',
            'countAll',
            'countSettled',
            'countPending',
            'countOnHold',
            'countRefunds',
            'status',
            'search',
            'dateRange',
            'dateFrom',
            'dateTo',
            'storeId'
        ));
    }

    /**
     * Fetch Transaction Details JSON for Modal Breakdown.
     */
    public function transactionDetails(CommissionEntry $entry): JsonResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Unauthorized seller account.');
        }

        // Multi-vendor tenant isolation check
        abort_unless(
            $entry->fulfilmentOrder && $entry->fulfilmentOrder->seller_id === $seller->id,
            403,
            'Unauthorized transaction inspection.'
        );

        $entry->load([
            'fulfilmentOrder.order.customer',
            'fulfilmentOrder.store',
            'fulfilmentOrder.items.variant.product',
            'rule',
            'settlementItem.settlement',
        ]);

        $fo = $entry->fulfilmentOrder;
        $settlement = $entry->settlementItem?->settlement;

        return response()->json([
            'id' => $entry->id,
            'transaction_id' => 'TXN-' . str_pad((string) $entry->id, 6, '0', STR_PAD_LEFT),
            'entry_type' => $entry->entry_type,
            'type_label' => $entry->entry_type === 'reversal' ? 'Refund Adjustment' : 'Order Settlement',
            'order_number' => $fo?->order?->order_number ?? 'N/A',
            'sub_order_number' => $fo?->fulfilment_order_number ?? 'N/A',
            'store_name' => $fo?->store?->name ?? 'Default Store',
            'created_at' => $entry->created_at->format('d M Y, h:i A'),
            'gross_amount' => (float) $entry->base_amount,
            'commission' => (float) $entry->commission,
            'rule_name' => $entry->rule?->name ?? 'Admin Standard Rate',
            'commission_rate' => $entry->rule ? ((float) $entry->rule->percent . '%') : 'Admin Rate',
            'tax_on_commission' => (float) $entry->tax_on_commission,
            'gateway_fee' => (float) $entry->gateway_fee,
            'shipping_adjustment' => (float) $entry->shipping_adjustment,
            'refund_deduction' => $entry->entry_type === 'reversal' ? (float) $entry->base_amount : 0.0,
            'net_payable' => (float) $entry->net_payable,
            'settlement' => $settlement ? [
                'number' => $settlement->settlement_number,
                'status' => $settlement->status->value,
                'status_label' => $settlement->status->label(),
                'reference' => $settlement->reference ?: 'Pending Bank UTR',
                'paid_at' => $settlement->paid_at ? $settlement->paid_at->format('d M Y, h:i A') : 'Unpaid',
            ] : null,
            'items' => $fo?->items->map(fn ($item) => [
                'title' => $item->title ?? ($item->variant?->product?->title ?? 'Product Item'),
                'sku' => $item->sku ?? ($item->variant?->sku ?? 'N/A'),
                'quantity' => (int) ($item->qty ?? $item->quantity ?? 1),
                'unit_price' => (float) ($item->price ?? $item->unit_price ?? 0),
                'subtotal' => (float) ($item->total ?? (($item->qty ?? 1) * ($item->price ?? 0))),
            ]) ?? [],
        ]);
    }

    /**
     * Export Payments Ledger to CSV with all required financial columns.
     */
    public function exportPayments(Request $request): StreamedResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Unauthorized seller account.');
        }

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $dateRange = (string) $request->query('date_range', '');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $storeId = $request->query('store_id');

        $query = CommissionEntry::query()
            ->forSeller($seller->id)
            ->with([
                'fulfilmentOrder.order',
                'fulfilmentOrder.store',
                'settlementItem.settlement',
            ])
            ->latest('id');

        if ($storeId) {
            $query->whereHas('fulfilmentOrder', fn ($q) => $q->where('store_id', (int) $storeId));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $cleanId = preg_replace('/[^0-9]/', '', $search);
                if (!empty($cleanId)) {
                    $q->orWhere('id', (int) $cleanId);
                }
                $q->orWhereHas('fulfilmentOrder', function ($foq) use ($search) {
                    $foq->where('fulfilment_order_number', 'like', "%{$search}%")
                        ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$search}%"));
                })
                ->orWhereHas('settlementItem.settlement', function ($sq) use ($search) {
                    $sq->where('settlement_number', 'like', "%{$search}%")
                       ->orWhere('reference', 'like', "%{$search}%");
                });
            });
        }

        if ($entryType = $request->query('entry_type')) {
            if (in_array($entryType, ['standard', 'reversal'], true)) {
                $query->where('entry_type', $entryType);
            }
        }

        if ($status === 'settled') {
            $query->whereHas('settlementItem.settlement', fn ($q) => $q->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED]));
        } elseif ($status === 'pending') {
            $query->where(function ($q) {
                $q->whereDoesntHave('settlementItem')
                  ->orWhereHas('settlementItem.settlement', fn ($sq) => $sq->whereIn('status', [SettlementStatus::PENDING, SettlementStatus::PROCESSING]));
            });
        } elseif ($status === 'on_hold') {
            $query->whereHas('settlementItem.settlement', fn ($q) => $q->where('status', SettlementStatus::ON_HOLD));
        } elseif ($status === 'refunds') {
            $query->where('entry_type', 'reversal');
        }

        if ($dateRange === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($dateRange === '7d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(7));
        } elseif ($dateRange === '30d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        } elseif ($dateRange === 'this_month') {
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        } elseif ($dateTo) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ALLZONE-Payments-Ledger-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Transaction ID',
                'Date & Time',
                'Order #',
                'Sub-Order / Package #',
                'Store / Outlet',
                'Transaction Type',
                'Gross Amount (INR)',
                'Commission Fee (INR)',
                'Shipping Charge (INR)',
                'Tax & Gateway Fees (INR)',
                'Refund / Adjustment (INR)',
                'Net Amount (INR)',
                'Settlement Status',
                'Reference / UTR ID',
            ]);

            $query->chunk(100, function ($entries) use ($handle) {
                foreach ($entries as $entry) {
                    $settlement = $entry->settlementItem?->settlement;
                    $statusLabel = $settlement ? $settlement->status->label() : 'Pending Batch';
                    $reference = $settlement?->reference ?: ($settlement?->settlement_number ?? 'Pending Batch');
                    $taxAndFees = (float) $entry->tax_on_commission + (float) $entry->gateway_fee;
                    $refundVal = $entry->entry_type === 'reversal' ? (float) $entry->base_amount : 0.0;

                    fputcsv($handle, [
                        'TXN-' . str_pad((string) $entry->id, 6, '0', STR_PAD_LEFT),
                        $entry->created_at->format('Y-m-d H:i:s'),
                        $entry->fulfilmentOrder?->order?->order_number ?? 'N/A',
                        $entry->fulfilmentOrder?->fulfilment_order_number ?? 'N/A',
                        $entry->fulfilmentOrder?->store?->name ?? 'Default Outlet',
                        $entry->entry_type === 'reversal' ? 'Refund Adjustment' : 'Order Settlement',
                        number_format((float) $entry->base_amount, 2, '.', ''),
                        number_format((float) $entry->commission, 2, '.', ''),
                        number_format((float) $entry->shipping_adjustment, 2, '.', ''),
                        number_format($taxAndFees, 2, '.', ''),
                        number_format($refundVal, 2, '.', ''),
                        number_format((float) $entry->net_payable, 2, '.', ''),
                        $statusLabel,
                        $reference,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Seller Settlements History & Statements.
     */
    public function settlements(Request $request): View
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Unauthorized seller account.');
        }

        $query = SellerSettlement::query()
            ->where('seller_id', $seller->id)
            ->with(['items'])
            ->latest('id');

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('settlement_number', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $settlements = $query->paginate(15)->withQueryString();

        $metrics = [
            'total_settlements' => SellerSettlement::where('seller_id', $seller->id)->count(),
            'paid_amount' => (float) SellerSettlement::where('seller_id', $seller->id)->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])->sum('net'),
            'paid_count' => SellerSettlement::where('seller_id', $seller->id)->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])->count(),
            'processing_amount' => (float) SellerSettlement::where('seller_id', $seller->id)->where('status', SettlementStatus::PROCESSING)->sum('net'),
            'processing_count' => SellerSettlement::where('seller_id', $seller->id)->where('status', SettlementStatus::PROCESSING)->count(),
            'pending_amount' => (float) SellerSettlement::where('seller_id', $seller->id)->where('status', SettlementStatus::PENDING)->sum('net'),
            'pending_count' => SellerSettlement::where('seller_id', $seller->id)->where('status', SettlementStatus::PENDING)->count(),
            'on_hold_amount' => (float) SellerSettlement::where('seller_id', $seller->id)->where('status', SettlementStatus::ON_HOLD)->sum('net'),
            'on_hold_count' => SellerSettlement::where('seller_id', $seller->id)->where('status', SettlementStatus::ON_HOLD)->count(),
        ];

        $primaryBank = $seller->primaryBankAccount ?: $seller->bankAccounts()->first();

        return view('seller.settlements.index', compact('settlements', 'metrics', 'seller', 'primaryBank'));
    }

    /**
     * Export Settlements Summary to CSV.
     */
    public function exportSettlements(Request $request): StreamedResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Unauthorized seller account.');
        }

        $query = SellerSettlement::query()
            ->where('seller_id', $seller->id)
            ->latest('id');

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('settlement_number', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ALLZONE-Settlements-Summary-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Settlement #',
                'Period Start',
                'Period End',
                'Gross Sales (INR)',
                'Marketplace Fee (INR)',
                'GST on Fee (18%) (INR)',
                'COD Deducted (INR)',
                'Adjustments (INR)',
                'Net Payout (INR)',
                'Status',
                'Bank Reference (UTR)',
                'Disbursed At',
            ]);

            $query->chunk(100, function ($settlements) use ($handle) {
                foreach ($settlements as $s) {
                    fputcsv($handle, [
                        $s->settlement_number,
                        $s->period_start->toDateString(),
                        $s->period_end->toDateString(),
                        number_format((float) $s->gross, 2, '.', ''),
                        number_format((float) $s->commission, 2, '.', ''),
                        number_format((float) $s->tax_on_commission, 2, '.', ''),
                        number_format((float) $s->cod_collected, 2, '.', ''),
                        number_format((float) $s->adjustments, 2, '.', ''),
                        number_format((float) $s->net, 2, '.', ''),
                        $s->status->label(),
                        $s->reference ?: 'Pending',
                        $s->paid_at ? $s->paid_at->toDateTimeString() : 'Unpaid',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * View individual settlement statement.
     */
    public function showSettlement(SellerSettlement $settlement): View
    {
        $seller = Auth::user()->seller;
        if (!$seller || $settlement->seller_id !== $seller->id) {
            abort(403, 'Unauthorized settlement access.');
        }

        $settlement->load(['items.fulfilmentOrder.order', 'items.fulfilmentOrder.store', 'items.commissionEntry']);
        $primaryBank = $seller->primaryBankAccount ?: $seller->bankAccounts()->first();

        return view('seller.settlements.show', compact('settlement', 'seller', 'primaryBank'));
    }

    /**
     * Download Settlement PDF Statement.
     */
    public function downloadPdf(SellerSettlement $settlement): Response
    {
        $seller = Auth::user()->seller;
        if (!$seller || $settlement->seller_id !== $seller->id) {
            abort(403, 'Unauthorized settlement statement access.');
        }

        $settlement->load(['seller', 'items.fulfilmentOrder.order', 'items.commissionEntry']);

        $pdf = Pdf::loadView('pdf.settlement-statement', compact('settlement'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Settlement-Statement-{$settlement->settlement_number}.pdf");
    }

    /**
     * Download Settlement CSV.
     */
    public function downloadCsv(SellerSettlement $settlement): StreamedResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller || $settlement->seller_id !== $seller->id) {
            abort(403, 'Unauthorized settlement export access.');
        }

        $settlement->load(['items.fulfilmentOrder', 'items.commissionEntry']);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"Settlement-{$settlement->settlement_number}.csv\"",
        ];

        return response()->stream(function () use ($settlement) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Item ID',
                'Type',
                'Description',
                'Package #',
                'Gross Amount',
                'Net Amount',
                'Date',
            ]);

            foreach ($settlement->items as $item) {
                fputcsv($handle, [
                    $item->id,
                    $item->type,
                    $item->description,
                    $item->fulfilmentOrder?->fulfilment_order_number ?? 'N/A',
                    $item->commissionEntry?->base_amount ?? 'N/A',
                    $item->amount,
                    $item->created_at->toDateString(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
