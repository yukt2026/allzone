<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionRuleScope;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Setting;
use App\Services\Admin\AdminPricingCommissionService;
use App\Services\Commission\CommissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommissionRuleController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService,
        protected AdminPricingCommissionService $pricingService
    ) {}

    /**
     * Display commission rules, charges, pricing controls, history, and financial reports.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'rules');
        $metrics = $this->pricingService->getSummaryMetrics();
        $pricingControls = $this->pricingService->getPricingControls();

        $categories = Category::orderBy('name')->get();
        $sellers = Seller::whereIn('status', [\App\Enums\SellerStatus::APPROVED, 'approved', 'active'])->orderBy('business_name')->get();
        $products = Product::where('is_active', true)->orderBy('title')->limit(100)->get(['id', 'title', 'seller_id']);

        $rules = null;
        $history = null;
        $priceHistory = null;

        if ($tab === 'rules') {
            $filters = [
                'scope' => $request->query('scope', 'all'),
                'category_id' => $request->query('category_id'),
                'seller_id' => $request->query('seller_id'),
                'product_id' => $request->query('product_id'),
                'search' => $request->query('search'),
            ];
            $rules = $this->pricingService->buildCommissionRulesQuery($filters)->paginate(15)->withQueryString();
        } elseif ($tab === 'history' || $tab === 'reports') {
            $filters = [
                'entry_type' => $request->query('entry_type', 'all'),
                'seller_id' => $request->query('seller_id'),
                'from_date' => $request->query('from_date'),
                'to_date' => $request->query('to_date'),
                'search' => $request->query('search'),
            ];
            $history = $this->pricingService->buildCommissionHistoryQuery($filters)->paginate(20)->withQueryString();
        } elseif ($tab === 'price_history') {
            $filters = [
                'seller_id' => $request->query('seller_id'),
                'change_type' => $request->query('change_type', 'all'),
                'from_date' => $request->query('from_date'),
                'to_date' => $request->query('to_date'),
                'search' => $request->query('search'),
            ];
            $priceHistory = $this->pricingService->buildPriceHistoryQuery($filters)->paginate(20)->withQueryString();
        }

        $defaultCommissionPct = $pricingControls['default_commission_pct'];
        $commissionGstPct = $pricingControls['commission_gst_pct'];
        $paymentGatewayFeePct = $pricingControls['payment_gateway_fee_pct'];

        return view('admin.commissions.index', compact(
            'tab',
            'metrics',
            'pricingControls',
            'rules',
            'history',
            'priceHistory',
            'categories',
            'sellers',
            'products',
            'defaultCommissionPct',
            'commissionGstPct',
            'paymentGatewayFeePct'
        ));
    }

    /**
     * Store a new commission rule (Global, Category, Seller, Product exception, or Promo).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'string', 'in:global,category,seller,product,promo'],
            'category_id' => ['nullable', 'required_if:scope,category', 'exists:categories,id'],
            'seller_id' => ['nullable', 'required_if:scope,seller', 'exists:sellers,id'],
            'product_id' => ['nullable', 'required_if:scope,product', 'exists:products,id'],
            'percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['fixed_fee'] = $validated['fixed_fee'] ?? 0.00;

        CommissionRule::create($validated);

        return redirect()->route('admin.commissions.index')
            ->with('success', "Commission rule '{$validated['name']}' created successfully.");
    }

    /**
     * Update an existing commission rule.
     */
    public function update(Request $request, CommissionRule $commission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'string', 'in:global,category,seller,product,promo'],
            'category_id' => ['nullable', 'required_if:scope,category', 'exists:categories,id'],
            'seller_id' => ['nullable', 'required_if:scope,seller', 'exists:sellers,id'],
            'product_id' => ['nullable', 'required_if:scope,product', 'exists:products,id'],
            'percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['fixed_fee'] = $validated['fixed_fee'] ?? 0.00;

        $commission->update($validated);

        return redirect()->route('admin.commissions.index')
            ->with('success', "Commission rule '{$commission->name}' updated successfully.");
    }

    /**
     * Delete a commission rule.
     */
    public function destroy(CommissionRule $commission): RedirectResponse
    {
        $name = $commission->name;
        $commission->delete();

        return redirect()->route('admin.commissions.index')
            ->with('success', "Commission rule '{$name}' deleted successfully.");
    }

    /**
     * Toggle active status.
     */
    public function toggle(CommissionRule $commission): JsonResponse
    {
        $commission->is_active = !$commission->is_active;
        $commission->save();

        return response()->json([
            'success' => true,
            'is_active' => $commission->is_active,
            'message' => "Rule status changed to " . ($commission->is_active ? 'active' : 'inactive'),
        ]);
    }

    /**
     * Update platform pricing controls and fee structures.
     */
    public function updateControls(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_commission_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_gst_pct' => ['required', 'numeric', 'min:0', 'max:50'],
            'payment_gateway_fee_pct' => ['required', 'numeric', 'min:0', 'max:20'],
            'gateway_fee_borne_by_seller' => ['nullable', 'boolean'],
            'pass_delivery_fee_to_seller' => ['nullable', 'boolean'],
            'fixed_platform_fee_per_order' => ['required', 'numeric', 'min:0'],
            'min_product_price' => ['required', 'numeric', 'min:0'],
            'max_product_price' => ['required', 'numeric', 'gt:min_product_price'],
            'max_discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_coupon_discount_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_coupon_discount_flat' => ['required', 'numeric', 'min:0'],
            'require_admin_approval_for_large_price_drop' => ['nullable', 'boolean'],
        ]);

        $validated['gateway_fee_borne_by_seller'] = $request->boolean('gateway_fee_borne_by_seller');
        $validated['pass_delivery_fee_to_seller'] = $request->boolean('pass_delivery_fee_to_seller');
        $validated['require_admin_approval_for_large_price_drop'] = $request->boolean('require_admin_approval_for_large_price_drop');

        $this->pricingService->updatePricingControls($validated, Auth::user());

        return redirect()->route('admin.commissions.index', ['tab' => 'charges'])
            ->with('success', 'Platform charges and pricing control policies updated successfully.');
    }

    /**
     * Stream financial reconciliation report CSV.
     */
    public function exportReport(Request $request): StreamedResponse
    {
        $filters = [
            'entry_type' => $request->query('entry_type', 'all'),
            'seller_id' => $request->query('seller_id'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'search' => $request->query('search'),
        ];

        return $this->pricingService->exportFinancialReportCsv($filters);
    }

    /**
     * Interactive Simulator endpoint.
     */
    public function simulate(Request $request): JsonResponse
    {
        $sellerId = $request->input('seller_id') ? (int) $request->input('seller_id') : null;
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $productId = $request->input('product_id') ? (int) $request->input('product_id') : null;
        $amount = (float) $request->input('amount', 1000.00);
        $shipping = (float) $request->input('shipping_adjustment', 0.00);
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        $calculation = $this->commissionService->calculate(
            baseAmount: $amount,
            sellerId: $sellerId,
            categoryId: $categoryId,
            shippingAdjustment: $shipping,
            date: $date,
            productId: $productId
        );

        return response()->json([
            'success' => true,
            'base_amount' => $calculation['base_amount'],
            'tier' => $calculation['tier'],
            'tier_label' => match($calculation['tier']) {
                'product' => 'Product-Specific Exception Rule',
                'seller' => 'Seller Specific Rule',
                'seller_override_column' => 'Seller Direct Column Override',
                'promo' => 'Promotional Window Rule',
                'category' => 'Category Specific Rule',
                'global' => 'Global Default Rule',
                'setting_fallback' => 'System Setting Fallback',
                default => 'Default',
            },
            'rule_name' => $calculation['rule']?->name ?? 'System Default Setting',
            'rule_id' => $calculation['rule']?->id,
            'rate_percent' => $calculation['rate_percent'],
            'fixed_fee' => $calculation['fixed_fee'],
            'commission_amount' => $calculation['commission_amount'],
            'tax_on_commission' => $calculation['tax_on_commission'],
            'tax_rate_percent' => $calculation['tax_rate_percent'],
            'gateway_fee' => $calculation['gateway_fee'],
            'gateway_fee_percent' => $calculation['gateway_fee_percent'],
            'shipping_adjustment' => $calculation['shipping_adjustment'],
            'total_platform_deduction' => $calculation['total_platform_deduction'],
            'net_payable' => $calculation['net_payable'],
            'formatted' => [
                'base_amount' => format_currency($calculation['base_amount']),
                'commission_amount' => format_currency($calculation['commission_amount']),
                'tax_on_commission' => format_currency($calculation['tax_on_commission']),
                'gateway_fee' => format_currency($calculation['gateway_fee']),
                'shipping_adjustment' => format_currency($calculation['shipping_adjustment']),
                'total_platform_deduction' => format_currency($calculation['total_platform_deduction']),
                'net_payable' => format_currency($calculation['net_payable']),
            ],
        ]);
    }
}
