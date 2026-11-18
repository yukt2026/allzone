<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Shipment;
use App\Enums\ShipmentStatus;
use App\Services\Commission\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PricingController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService
    ) {}

    /**
     * Display Manage Pricing screen with real overview metrics, tab filters, and earnings estimates.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $tab = $request->query('tab', 'all'); // 'all', 'issues', 'low_sales', 'history'
        $categoryId = $request->query('category_id');
        $search = $request->query('search');

        if ($request->query('export') === 'csv') {
            return $this->export($request);
        }

        // Query base variants belonging strictly to this seller
        $baseQuery = ProductVariant::query()
            ->whereHas('product', function ($q) use ($seller, $categoryId) {
                $q->where('seller_id', $seller->id);
                if ($categoryId) {
                    $q->where('category_id', $categoryId);
                }
            })
            ->with(['product.category', 'inventories']);

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($p) => $p->where('title', 'like', "%{$search}%"));
            });
        }

        $allVariants = $baseQuery->get();
        $variantIds = $allVariants->pluck('id')->all();

        // 30-day sales per variant
        $thirtyDaysAgo = now()->subDays(30);
        $sales30d = OrderItem::whereIn('variant_id', $variantIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('variant_id, sum(qty) as total_qty, count(id) as total_orders')
            ->groupBy('variant_id')
            ->pluck('total_qty', 'variant_id');

        // Map variants with real financial, stock, and status logic
        $mapped = $allVariants->map(function ($variant) use ($seller, $sales30d) {
            $currentPrice = (float) ($variant->selling_price ?? 0);
            $mrp = (float) ($variant->mrp ?? 0);
            $costPrice = (float) ($variant->cost_price ?? 0);
            $stock = $variant->inventories ? (int) $variant->inventories->sum('quantity_on_hand') : 0;
            $unitsSold30d = (int) ($sales30d[$variant->id] ?? 0);
            $taxPercent = (float) ($variant->product?->tax_percent ?? 18.0);

            // Calculate actual commission breakdown & seller earnings
            $commission = $this->commissionService->calculate(
                $currentPrice,
                $seller->id,
                $variant->product?->category_id
            );

            // Pricing Issue Detection
            $pricingIssue = null;
            if ($currentPrice <= 0) {
                $pricingIssue = 'Price is ₹0 or negative';
            } elseif ($mrp > 0 && $currentPrice > $mrp) {
                $pricingIssue = 'Selling price exceeds MRP';
            } elseif ($costPrice > 0 && $currentPrice < $costPrice) {
                $pricingIssue = 'Selling price below unit cost';
            }

            // Low Sales Detection (0 orders in past 30 days)
            $isLowSales = ($unitsSold30d === 0);

            // Data-backed recommendation
            $recommendation = null;
            if ($pricingIssue) {
                $recommendation = "Action Required: {$pricingIssue}. Adjust price immediately.";
            } elseif ($isLowSales && $mrp > $currentPrice * 1.05) {
                $recommendation = '0 units sold in 30 days. Consider a 5% promotional discount to stimulate orders.';
            } elseif ($mrp > 0 && $currentPrice >= $mrp) {
                $recommendation = 'Currently priced at full MRP. Adding a competitive seller discount may boost listing visibility.';
            }

            // Discount percentage from MRP
            $discountPct = ($mrp > $currentPrice && $mrp > 0)
                ? round((($mrp - $currentPrice) / $mrp) * 100, 1)
                : 0.0;

            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'title' => $variant->product?->title ?? $variant->name ?? 'Product',
                'sku' => $variant->sku,
                'category_name' => $variant->product?->category?->name ?? 'General',
                'category_id' => $variant->product?->category_id,
                'image' => $variant->image_url ?? $variant->product?->primary_image_url ?? asset('images/placeholder.png'),
                'size' => $variant->name ?: 'Standard',
                'stock' => $stock,
                'current_price' => $currentPrice,
                'mrp' => $mrp,
                'cost_price' => $costPrice,
                'discount_pct' => $discountPct,
                'tax_percent' => $taxPercent,
                'net_payable' => (float) $commission['net_payable'],
                'commission_rate' => (float) $commission['rate'],
                'total_commission' => (float) $commission['total_commission'],
                'gateway_fee' => (float) $commission['gateway_fee'],
                'units_sold_30d' => $unitsSold30d,
                'has_issue' => $pricingIssue !== null,
                'pricing_issue' => $pricingIssue,
                'is_low_sales' => $isLowSales,
                'recommendation' => $recommendation,
                'updated_at' => $variant->updated_at,
            ];
        });

        // Price changes in last 30 days
        $priceChanges30d = ProductPriceHistory::where('seller_id', $seller->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        // High-level overview metrics
        $metrics = [
            'total_active_products' => $mapped->count(),
            'pricing_issues_count' => $mapped->where('has_issue', true)->count(),
            'low_sales_count' => $mapped->where('is_low_sales', true)->count(),
            'avg_selling_price' => $mapped->count() > 0 ? round($mapped->avg('current_price'), 2) : 0.0,
            'price_changes_30d' => $priceChanges30d,
        ];

        // Unique data-backed recommendations
        $recommendations = $mapped->pluck('recommendation')->filter()->unique()->values()->all();

        // Tab counts
        $counts = [
            'all' => $mapped->count(),
            'issues' => $metrics['pricing_issues_count'],
            'low_sales' => $metrics['low_sales_count'],
            'history' => ProductPriceHistory::where('seller_id', $seller->id)->count(),
        ];

        // Filter items based on active tab
        $historyRecords = null;
        if ($tab === 'history') {
            $historyRecords = ProductPriceHistory::where('seller_id', $seller->id)
                ->with(['variant.product', 'user'])
                ->latest()
                ->paginate(15)
                ->withQueryString();
            $items = collect([]);
        } else {
            $items = match ($tab) {
                'issues' => $mapped->where('has_issue', true)->values(),
                'low_sales' => $mapped->where('is_low_sales', true)->values(),
                default => $mapped->values(),
            };
        }

        $categories = Category::query()->orderBy('name')->get();

        return view('seller.pricing.index', [
            'tab' => $tab,
            'counts' => $counts,
            'metrics' => $metrics,
            'recommendations' => $recommendations,
            'items' => $items,
            'historyRecords' => $historyRecords,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
        ]);
    }

    /**
     * Display Reduce RTO & Returns screen backed by real seller statistics.
     */
    public function reduceRto(): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        // Actual Seller Delivery & RTO performance
        $shippedShipments = Shipment::whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->with('fulfilmentOrder.order')
            ->get();

        $codShipments = $shippedShipments->filter(function ($s) {
            $pm = $s->fulfilmentOrder?->order?->payment_method;
            return $pm === \App\Enums\PaymentMethod::COD 
                || ($pm instanceof \BackedEnum && $pm->value === 'cod') 
                || $pm === 'cod';
        });

        $prepaidShipments = $shippedShipments->filter(function ($s) {
            $pm = $s->fulfilmentOrder?->order?->payment_method;
            return $pm !== \App\Enums\PaymentMethod::COD 
                && (!($pm instanceof \BackedEnum) || $pm->value !== 'cod') 
                && $pm !== 'cod';
        });

        $totalCod = $codShipments->count();
        $totalPrepaid = $prepaidShipments->count();

        $codRto = $codShipments->filter(fn($s) => in_array($s->status, [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED]))->count();
        $prepaidRto = $prepaidShipments->filter(fn($s) => in_array($s->status, [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED]))->count();

        $codRtoRate = $totalCod > 0 ? round(($codRto / $totalCod) * 100, 1) : 0.0;
        $prepaidRtoRate = $totalPrepaid > 0 ? round(($prepaidRto / $totalPrepaid) * 100, 1) : 0.0;

        // Admin-configured prepaid incentive settings
        $prepaidDiscountEnabled = (bool) Setting::get('prepaid_discount_enabled', false);
        $prepaidDiscountPct = (float) Setting::get('prepaid_discount_pct', 0.0);

        // Product-specific seller coupons
        $coupons = Coupon::where('seller_id', $seller->id)->latest()->get();

        return view('seller.pricing.reduce-rto', [
            'seller' => $seller,
            'totalCod' => $totalCod,
            'totalPrepaid' => $totalPrepaid,
            'codRto' => $codRto,
            'prepaidRto' => $prepaidRto,
            'codRtoRate' => $codRtoRate,
            'prepaidRtoRate' => $prepaidRtoRate,
            'prepaidDiscountEnabled' => $prepaidDiscountEnabled,
            'prepaidDiscountPct' => $prepaidDiscountPct,
            'coupons' => $coupons,
        ]);
    }

    /**
     * Update individual product price and record price history.
     */
    public function updatePrice(Request $request, ProductVariant $variant): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $variant->product?->seller_id !== $seller->id, 403, 'Unauthorized product variant.');

        $validated = $request->validate([
            'selling_price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'mrp' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $newPrice = (float) $validated['selling_price'];
        $newMrp = isset($validated['mrp']) ? (float) $validated['mrp'] : (float) ($variant->mrp ?? $newPrice);

        if ($newMrp < $newPrice) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selling price cannot exceed Maximum Retail Price (MRP).',
                ], 422);
            }
            return back()->with('error', 'Selling price cannot exceed Maximum Retail Price (MRP).');
        }

        $oldPrice = (float) ($variant->selling_price ?? 0);
        $oldMrp = (float) ($variant->mrp ?? 0);

        DB::transaction(function () use ($variant, $seller, $newPrice, $newMrp, $oldPrice, $oldMrp, $validated) {
            ProductPriceHistory::create([
                'product_variant_id' => $variant->id,
                'seller_id' => $seller->id,
                'user_id' => Auth::id(),
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'old_mrp' => $oldMrp > 0 ? $oldMrp : null,
                'new_mrp' => $newMrp,
                'change_type' => 'manual',
                'reason' => $validated['reason'] ?? 'Manual price update',
            ]);

            $variant->selling_price = $newPrice;
            $variant->mrp = $newMrp;
            $variant->save();
        });

        // Recalculate earnings
        $commission = $this->commissionService->calculate(
            $newPrice,
            $seller->id,
            $variant->product?->category_id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Price updated successfully to ₹{$newPrice}!",
                'new_price' => $newPrice,
                'new_mrp' => $newMrp,
                'net_payable' => (float) $commission['net_payable'],
                'commission_rate' => (float) $commission['rate'],
            ]);
        }

        return back()->with('success', "Price for {$variant->sku} updated to ₹{$newPrice}.");
    }

    /**
     * Preview bulk price adjustments before saving.
     */
    public function bulkPreview(Request $request): JsonResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $validated = $request->validate([
            'variant_ids' => ['required', 'array', 'min:1'],
            'variant_ids.*' => ['required', 'integer'],
            'adjustment_type' => ['required', 'in:percent_discount,percent_increase,fixed_discount,set_fixed_price'],
            'adjustment_value' => ['required', 'numeric', 'min:0.01'],
        ]);

        $variants = ProductVariant::whereIn('id', $validated['variant_ids'])
            ->whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->with(['product.category'])
            ->get();

        $preview = [];
        $adjType = $validated['adjustment_type'];
        $adjVal = (float) $validated['adjustment_value'];

        foreach ($variants as $variant) {
            $oldPrice = (float) ($variant->selling_price ?? 0);
            $mrp = (float) ($variant->mrp ?? $oldPrice);

            $newPrice = match ($adjType) {
                'percent_discount' => round($oldPrice * (1 - ($adjVal / 100)), 2),
                'percent_increase' => round($oldPrice * (1 + ($adjVal / 100)), 2),
                'fixed_discount' => round($oldPrice - $adjVal, 2),
                'set_fixed_price' => round($adjVal, 2),
            };

            $warning = null;
            if ($newPrice < 1.0) {
                $warning = 'Price would fall below minimum threshold (₹1.00).';
            } elseif ($mrp > 0 && $newPrice > $mrp) {
                $warning = "New price (₹{$newPrice}) exceeds MRP (₹{$mrp}).";
            }

            $oldCalc = $this->commissionService->calculate($oldPrice, $seller->id, $variant->product?->category_id);
            $newCalc = $this->commissionService->calculate(max(1.0, $newPrice), $seller->id, $variant->product?->category_id);

            $diff = round($newPrice - $oldPrice, 2);
            $diffPct = $oldPrice > 0 ? round(($diff / $oldPrice) * 100, 1) : 0.0;

            $preview[] = [
                'id' => $variant->id,
                'title' => $variant->product?->title ?? $variant->name ?? 'Product',
                'sku' => $variant->sku,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'mrp' => $mrp,
                'difference' => $diff,
                'percentage' => $diffPct,
                'old_earnings' => (float) $oldCalc['net_payable'],
                'new_earnings' => (float) $newCalc['net_payable'],
                'warning' => $warning,
            ];
        }

        return response()->json([
            'success' => true,
            'count' => count($preview),
            'items' => $preview,
        ]);
    }

    /**
     * Execute bulk price update with validation and history audit log.
     */
    public function bulkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $validated = $request->validate([
            'variant_ids' => ['required', 'array', 'min:1'],
            'variant_ids.*' => ['required', 'integer'],
            'adjustment_type' => ['required', 'in:percent_discount,percent_increase,fixed_discount,set_fixed_price'],
            'adjustment_value' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $variants = ProductVariant::whereIn('id', $validated['variant_ids'])
            ->whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->get();

        $adjType = $validated['adjustment_type'];
        $adjVal = (float) $validated['adjustment_value'];
        $reason = $validated['reason'] ?? 'Bulk price adjustment';
        $updatedCount = 0;

        DB::transaction(function () use ($variants, $seller, $adjType, $adjVal, $reason, &$updatedCount) {
            foreach ($variants as $variant) {
                $oldPrice = (float) ($variant->selling_price ?? 0);
                $mrp = (float) ($variant->mrp ?? $oldPrice);

                $newPrice = match ($adjType) {
                    'percent_discount' => round($oldPrice * (1 - ($adjVal / 100)), 2),
                    'percent_increase' => round($oldPrice * (1 + ($adjVal / 100)), 2),
                    'fixed_discount' => round($oldPrice - $adjVal, 2),
                    'set_fixed_price' => round($adjVal, 2),
                };

                // Enforce safety constraints
                if ($newPrice < 1.0) {
                    $newPrice = 1.0;
                }
                if ($mrp > 0 && $newPrice > $mrp) {
                    $newPrice = $mrp;
                }

                ProductPriceHistory::create([
                    'product_variant_id' => $variant->id,
                    'seller_id' => $seller->id,
                    'user_id' => Auth::id(),
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'old_mrp' => $mrp > 0 ? $mrp : null,
                    'new_mrp' => $mrp > 0 ? $mrp : null,
                    'change_type' => 'bulk',
                    'reason' => $reason,
                ]);

                $variant->selling_price = $newPrice;
                $variant->save();
                $updatedCount++;
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully updated prices for {$updatedCount} products.",
                'updated_count' => $updatedCount,
            ]);
        }

        return back()->with('success', "Bulk price update applied to {$updatedCount} products.");
    }

    /**
     * Real-time calculation endpoint for dynamic UI modals.
     */
    public function calculateEarnings(Request $request): JsonResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $price = (float) $request->query('price', 0);
        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;

        $calc = $this->commissionService->calculate(max(0, $price), $seller->id, $categoryId);

        return response()->json([
            'success' => true,
            'selling_price' => $price,
            'commission_rate' => (float) $calc['rate'],
            'commission_amount' => (float) $calc['commission_pre_tax'],
            'fixed_fee' => (float) $calc['fixed_fee'],
            'tax_on_commission' => (float) $calc['tax_on_commission'],
            'total_commission' => (float) $calc['total_commission'],
            'gateway_fee' => (float) $calc['gateway_fee'],
            'net_payable' => (float) $calc['net_payable'],
        ]);
    }

    /**
     * Export pricing records or price change history as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $tab = $request->query('tab', 'all');
        $filename = ($tab === 'history' ? 'price-history' : 'product-pricing') . "-seller-{$seller->id}-" . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($seller, $tab) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            if ($tab === 'history') {
                fputcsv($handle, [
                    'Date & Time',
                    'Product Title',
                    'SKU',
                    'Old Selling Price (INR)',
                    'New Selling Price (INR)',
                    'Difference (INR)',
                    'Change (%)',
                    'Change Type',
                    'Reason',
                    'Changed By',
                ]);

                ProductPriceHistory::where('seller_id', $seller->id)
                    ->with(['variant.product', 'user'])
                    ->latest()
                    ->chunk(200, function ($records) use ($handle) {
                        foreach ($records as $rec) {
                            fputcsv($handle, [
                                $rec->created_at->format('Y-m-d H:i:s'),
                                $rec->variant?->product?->title ?? 'N/A',
                                $rec->variant?->sku ?? 'N/A',
                                number_format((float) $rec->old_price, 2),
                                number_format((float) $rec->new_price, 2),
                                number_format($rec->price_difference, 2),
                                $rec->percentage_change . '%',
                                ucfirst($rec->change_type),
                                $rec->reason ?? '-',
                                $rec->user?->name ?? 'Seller',
                            ]);
                        }
                    });
            } else {
                fputcsv($handle, [
                    'Product Title',
                    'SKU',
                    'Category',
                    'Current Stock',
                    'Selling Price (INR)',
                    'MRP (INR)',
                    'Discount (%)',
                    'GST Tax (%)',
                    'Estimated Seller Payout (INR)',
                    'Commission Rate (%)',
                    '30-Day Sold Units',
                    'Pricing Status',
                    'Last Updated',
                ]);

                $variants = ProductVariant::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
                    ->with(['product.category', 'inventories'])
                    ->get();

                $variantIds = $variants->pluck('id')->all();
                $sales30d = OrderItem::whereIn('variant_id', $variantIds)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('variant_id, sum(qty) as total_qty')
                    ->groupBy('variant_id')
                    ->pluck('total_qty', 'variant_id');

                foreach ($variants as $v) {
                    $price = (float) ($v->selling_price ?? 0);
                    $mrp = (float) ($v->mrp ?? 0);
                    $stock = $v->inventories ? (int) $v->inventories->sum('quantity_on_hand') : 0;
                    $sold30d = (int) ($sales30d[$v->id] ?? 0);
                    $calc = $this->commissionService->calculate($price, $seller->id, $v->product?->category_id);
                    $discount = ($mrp > $price && $mrp > 0) ? round((($mrp - $price) / $mrp) * 100, 1) : 0.0;

                    $status = 'Normal';
                    if ($price <= 0 || ($mrp > 0 && $price > $mrp)) {
                        $status = 'Pricing Issue';
                    } elseif ($sold30d === 0) {
                        $status = 'Low Sales';
                    }

                    fputcsv($handle, [
                        $v->product?->title ?? $v->name ?? 'Product',
                        $v->sku,
                        $v->product?->category?->name ?? 'General',
                        $stock,
                        number_format($price, 2),
                        number_format($mrp, 2),
                        $discount . '%',
                        ($v->product?->tax_percent ?? 18.0) . '%',
                        number_format((float) $calc['net_payable'], 2),
                        $calc['rate'] . '%',
                        $sold30d,
                        $status,
                        $v->updated_at->format('Y-m-d H:i'),
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
