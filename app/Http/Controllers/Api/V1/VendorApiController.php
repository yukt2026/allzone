<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\SettlementStatus;
use App\Models\CommissionEntry;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\NotificationLog;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\SellerSettlement;
use App\Models\Store;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Settlement\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorApiController extends ApiController
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    /**
     * Resolve authenticated Seller entity from Sanctum User.
     */
    protected function getSeller(Request $request): Seller
    {
        $user = $request->user();
        $seller = $user->seller;

        if (!$seller) {
            abort(403, 'Merchant seller profile not found for this account.');
        }

        return $seller;
    }

    /**
     * Compute matured balance, escrow hold, and lifetime settled totals for a seller.
     */
    protected function getSellerBalances(Seller $seller): array
    {
        $holdingPeriodDays = (int) setting('settlement_holding_days', 7);
        $eligibleCutoff = now()->subDays($holdingPeriodDays);

        $unsettledOrders = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->where('status', OrderStatus::DELIVERED)
            ->whereDoesntHave('settlementItem')
            ->with(['commissionEntry'])
            ->get();

        $maturedOrders = $unsettledOrders->filter(fn ($fo) => $fo->delivered_at && $fo->delivered_at->lte($eligibleCutoff));
        $escrowOrders = $unsettledOrders->filter(fn ($fo) => $fo->delivered_at && $fo->delivered_at->gt($eligibleCutoff));

        $maturedBalance = (float) $maturedOrders->sum(fn ($fo) => (float) ($fo->commissionEntry?->net_payable ?? $fo->seller_payable));
        $escrowBalance = (float) $escrowOrders->sum(fn ($fo) => (float) ($fo->commissionEntry?->net_payable ?? $fo->seller_payable));

        $lifetimeSettled = (float) SellerSettlement::where('seller_id', $seller->id)
            ->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])
            ->sum('net');

        return [
            'matured_balance' => round($maturedBalance, 2),
            'escrow_balance' => round($escrowBalance, 2),
            'settled_total' => round($lifetimeSettled, 2),
        ];
    }

    /**
     * Vendor Login: validates seller status and returns Sanctum Bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $login = trim((string) $request->input('login'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check((string) $request->input('password'), (string) $user->password)) {
            return $this->error('Invalid merchant credentials.', 401);
        }

        $seller = $user->seller;
        if (!$seller) {
            return $this->error('Access denied. No merchant seller account associated with this login.', 403);
        }

        $sellerStatus = $seller->status instanceof \BackedEnum ? $seller->status->value : (string) ($seller->status ?? 'pending');
        if (strtolower($sellerStatus) === 'suspended') {
            return $this->error('Your merchant account is suspended. Please contact marketplace administration.', 403);
        }

        $deviceName = $request->input('device_name', 'Android Vendor App');
        $token = $user->createToken($deviceName, ['seller'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'seller' => [
                'id' => $seller->id,
                'company_name' => $seller->company_name,
                'status' => $sellerStatus,
                'is_approved' => strtolower($sellerStatus) === 'approved',
                'store_name' => $seller->primaryStore?->name ?? $seller->company_name,
                'logo_url' => $seller->primaryStore?->logo_url ?? asset('images/placeholder-store.svg'),
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ], 'Merchant authenticated successfully.');
    }

    /**
     * Vendor Registration / Merchant Onboarding Application.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'pan_number' => ['nullable', 'string', 'max:20'],
            'gstin' => ['nullable', 'string', 'max:30'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $validated = $validator->validated();

        $seller = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);

            if (method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole('seller');
                } catch (\Throwable) {}
            }

            $seller = Seller::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'pan_number' => $validated['pan_number'] ?? null,
                'gst_number' => $validated['gstin'] ?? null,
                'status' => 'pending',
            ]);

            // Create primary store
            Store::create([
                'seller_id' => $seller->id,
                'name' => $validated['company_name'],
                'slug' => Str::slug($validated['company_name']) . '-' . rand(100, 999),
                'phone' => $validated['phone'],
                'status' => 'pending',
                'is_active' => true,
            ]);

            return $seller;
        });

        $user = $seller->user;
        $deviceName = $request->input('device_name', 'Android Vendor App');
        $token = $user->createToken($deviceName, ['seller'])->plainTextToken;
        $sellerStatus = $seller->status instanceof \BackedEnum ? $seller->status->value : (string) ($seller->status ?? 'pending');

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'seller' => [
                'id' => $seller->id,
                'company_name' => $seller->company_name,
                'status' => $sellerStatus,
                'is_approved' => false,
            ],
        ], 'Merchant onboarding submitted for review.', 201);
    }

    /**
     * Vendor Logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Merchant logged out successfully.');
    }

    /**
     * Vendor Dashboard KPI Overview.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $totalOrders = FulfilmentOrder::where('seller_id', $seller->id)->count();
        $pendingOrders = FulfilmentOrder::where('seller_id', $seller->id)
            ->whereIn('status', [OrderStatus::PLACED, OrderStatus::CONFIRMED])
            ->count();
        $processingOrders = FulfilmentOrder::where('seller_id', $seller->id)
            ->whereIn('status', [OrderStatus::PROCESSING, OrderStatus::READY_FOR_PICKUP])
            ->count();
        $deliveredOrders = FulfilmentOrder::where('seller_id', $seller->id)
            ->where('status', OrderStatus::DELIVERED)
            ->count();

        // Revenue calculations
        $deliveredSales = (float) FulfilmentOrder::where('seller_id', $seller->id)
            ->where('status', OrderStatus::DELIVERED)
            ->sum('subtotal');

        $earnings = $this->getSellerBalances($seller);

        $lowStockCount = Inventory::whereHas('variant.product', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold')
            ->count();

        return $this->success([
            'company_name' => $seller->company_name,
            'status' => $seller->status instanceof \BackedEnum ? $seller->status->value : (string) ($seller->status ?? 'pending'),
            'kpis' => [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'processing_orders' => $processingOrders,
                'delivered_orders' => $deliveredOrders,
                'delivered_sales' => $deliveredSales,
                'low_stock_products' => $lowStockCount,
            ],
            'financials' => [
                'matured_balance' => (float) ($earnings['matured_balance'] ?? 0.0),
                'escrow_hold' => (float) ($earnings['escrow_balance'] ?? 0.0),
                'settled_total' => (float) ($earnings['settled_total'] ?? 0.0),
            ],
        ]);
    }

    /**
     * Seller Profile Details.
     */
    public function profile(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);
        $user = $seller->user;

        return $this->success([
            'id' => $seller->id,
            'company_name' => $seller->company_name,
            'contact_name' => $user?->name,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'gstin' => $seller->gst_number,
            'pan_number' => $seller->pan_number,
            'bank_account_number' => $seller->bank_account_number,
            'bank_ifsc' => $seller->bank_ifsc,
            'bank_name' => $seller->bank_name,
            'status' => $seller->status instanceof \BackedEnum ? $seller->status->value : (string) ($seller->status ?? 'pending'),
            'commission_rate_pct' => (float) ($seller->commission_override_pct ?? setting('default_commission_pct', 10.0)),
        ]);
    }

    /**
     * Storefront Outlet Profile.
     */
    public function store(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);
        $store = $seller->primaryStore ?? $seller->stores()->first();

        if (!$store) {
            return $this->error('No store found for this seller.', 404);
        }

        return $this->success([
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'phone' => $store->phone,
            'email' => $store->email,
            'address' => $store->address,
            'city' => $store->city,
            'state' => $store->state,
            'pincode' => $store->pincode,
            'logo_url' => $store->logo_url,
            'banner_url' => $store->banner_url,
            'is_open' => (bool) $store->is_active,
        ]);
    }

    /**
     * Update Storefront Outlet Profile.
     */
    public function updateStore(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);
        $store = $seller->primaryStore ?? $seller->stores()->first();

        if (!$store) {
            return $this->error('No store found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $store->update($validator->validated());

        return $this->success($store, 'Store profile updated successfully.');
    }

    /**
     * List Vendor Products.
     */
    public function products(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $products = Product::where('seller_id', $seller->id)
            ->with(['category', 'brand', 'defaultVariant.inventories', 'media'])
            ->latest('id')
            ->paginate(15);

        $transformed = collect($products->items())->map(function ($p) {
            $v = $p->defaultVariant;
            return [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'status' => $p->status instanceof ProductStatus ? $p->status->value : (string) $p->status,
                'category' => $p->category?->name,
                'brand' => $p->brand?->name,
                'selling_price' => (float) ($v?->selling_price ?? 0.0),
                'mrp' => (float) ($v?->mrp ?? 0.0),
                'stock' => $v ? (int) $v->inventories->sum('quantity_on_hand') : 0,
                'image_url' => $p->featured_image_url ?? asset('images/placeholder-product.svg'),
            ];
        });

        return $this->success([
            'products' => $transformed,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Vendor Inventory Stock Overview.
     */
    public function inventory(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $inventories = Inventory::whereHas('variant.product', fn ($q) => $q->where('seller_id', $seller->id))
            ->with(['variant.product', 'location'])
            ->paginate(20);

        $transformed = collect($inventories->items())->map(function ($inv) {
            $variant = $inv->variant;
            $product = $variant?->product;
            $available = max(0, (int) $inv->quantity_on_hand - (int) $inv->quantity_reserved);

            return [
                'inventory_id' => $inv->id,
                'variant_id' => $variant?->id,
                'product_title' => $product?->title ?? '',
                'variant_name' => $variant?->name ?? 'Default',
                'sku' => $variant?->sku ?? '',
                'quantity_on_hand' => (int) $inv->quantity_on_hand,
                'quantity_reserved' => (int) $inv->quantity_reserved,
                'quantity_available' => $available,
                'low_stock_threshold' => (int) $inv->low_stock_threshold,
                'is_low_stock' => $available <= (int) $inv->low_stock_threshold,
            ];
        });

        return $this->success([
            'inventory' => $transformed,
            'pagination' => [
                'current_page' => $inventories->currentPage(),
                'last_page' => $inventories->lastPage(),
                'total' => $inventories->total(),
            ],
        ]);
    }

    /**
     * Adjust Inventory Stock Quantity.
     */
    public function updateStock(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $validator = Validator::make($request->all(), [
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity_on_hand' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $variant = ProductVariant::where('id', $request->input('variant_id'))
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->firstOrFail();

        $inventory = Inventory::firstOrCreate(
            ['variant_id' => $variant->id, 'location_id' => 1],
            ['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'low_stock_threshold' => 5]
        );

        $inventory->quantity_on_hand = (int) $request->input('quantity_on_hand');
        $inventory->save();

        return $this->success([
            'variant_id' => $variant->id,
            'sku' => $variant->sku,
            'new_quantity_on_hand' => $inventory->quantity_on_hand,
        ], 'Inventory stock quantity updated successfully.');
    }

    /**
     * Vendor Fulfilment Orders list.
     */
    public function orders(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $query = FulfilmentOrder::where('seller_id', $seller->id)
            ->with(['order.customerAddress', 'items.variant', 'shipments']);

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $orders = $query->latest('id')->paginate(15);

        $transformed = collect($orders->items())->map(function ($fo) {
            $dest = $fo->order?->customerAddress;
            $st = $fo->status instanceof OrderStatus ? $fo->status->value : (string) $fo->status;
            return [
                'id' => $fo->id,
                'package_number' => $fo->fulfilment_order_number,
                'order_number' => $fo->order?->order_number,
                'created_at' => $fo->created_at->toIso8601String(),
                'status' => $st,
                'status_label' => ucfirst(str_replace('_', ' ', $st)),
                'items_count' => $fo->items->count(),
                'total_amount' => (float) ($fo->total ?? $fo->subtotal),
                'customer_city' => $dest?->city ?? 'Bengaluru',
                'items' => $fo->items->map(fn ($it) => [
                    'title' => $it->title_snapshot ?? 'Item',
                    'qty' => $it->qty,
                    'price' => (float) $it->unit_price,
                ]),
            ];
        });

        return $this->success([
            'orders' => $transformed,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Update Fulfilment Order status (Accept, Process, Ready for Pickup).
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $seller = $this->getSeller($request);

        $fo = FulfilmentOrder::where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$fo) {
            return $this->error('Order package not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:processing,ready_for_pickup,dispatched'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $newStatus = match ($request->input('status')) {
            'processing' => OrderStatus::PROCESSING,
            'ready_for_pickup' => OrderStatus::READY_FOR_PICKUP,
            'dispatched' => OrderStatus::OUT_FOR_DELIVERY,
        };

        $fo->status = $newStatus;
        $fo->save();

        OrderStatusHistory::create([
            'orderable_type' => FulfilmentOrder::class,
            'orderable_id' => $fo->id,
            'from_status' => 'seller_api',
            'to_status' => $newStatus->value,
            'note' => $request->input('note') ?? "Package updated to {$newStatus->value} by merchant via Mobile App",
            'changed_by' => $request->user()->id,
        ]);

        return $this->success([
            'package_number' => $fo->fulfilment_order_number,
            'status' => $fo->status instanceof OrderStatus ? $fo->status->value : (string) $fo->status,
        ], 'Package status updated successfully.');
    }

    /**
     * Seller Commission Breakdown & Accounting.
     */
    public function commissions(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $entries = CommissionEntry::whereHas('fulfilmentOrder', fn ($q) => $q->where('seller_id', $seller->id))
            ->with(['fulfilmentOrder.order'])
            ->latest('id')
            ->paginate(20);

        $transformed = collect($entries->items())->map(function ($c) {
            return [
                'id' => $c->id,
                'package_number' => $c->fulfilmentOrder?->fulfilment_order_number,
                'order_number' => $c->fulfilmentOrder?->order?->order_number,
                'created_at' => $c->created_at->toIso8601String(),
                'base_amount' => (float) $c->base_amount,
                'commission_amount' => (float) $c->commission,
                'gst_on_commission' => (float) $c->tax_on_commission,
                'gateway_fee' => (float) $c->gateway_fee,
                'net_payable' => (float) $c->net_payable,
            ];
        });

        return $this->success([
            'commissions' => $transformed,
            'pagination' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    /**
     * Seller Earnings & Settlement History.
     */
    public function earnings(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);
        $balances = $this->getSellerBalances($seller);

        $settlements = SellerSettlement::where('seller_id', $seller->id)
            ->latest('id')
            ->take(15)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'settlement_number' => $s->settlement_number,
                'amount' => (float) $s->net_payable,
                'status' => $s->status instanceof \BackedEnum ? $s->status->value : (string) ($s->status ?? 'pending'),
                'period_start' => $s->period_start?->toDateString(),
                'period_end' => $s->period_end?->toDateString(),
                'paid_at' => $s->paid_at?->toIso8601String(),
            ]);

        return $this->success([
            'matured_balance' => (float) ($balances['matured_balance'] ?? 0.0),
            'escrow_hold' => (float) ($balances['escrow_balance'] ?? 0.0),
            'settled_total' => (float) ($balances['settled_total'] ?? 0.0),
            'settlements' => $settlements,
        ]);
    }

    /**
     * Submit payout withdrawal request.
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $amount = (float) $request->input('amount');
        $balances = $this->getSellerBalances($seller);
        $matured = (float) ($balances['matured_balance'] ?? 0.0);

        if ($amount > $matured) {
            return $this->error("Requested payout of ₹{$amount} exceeds available matured balance of ₹{$matured}.", 400);
        }

        $withdrawal = WithdrawalRequest::create([
            'seller_id' => $seller->id,
            'amount' => $amount,
            'status' => 'pending',
            'requested_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        return $this->success($withdrawal, 'Withdrawal payout request submitted successfully.', 201);
    }

    /**
     * Vendor In-App Notifications.
     */
    public function notifications(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);
        $user = $seller->user;

        $notifications = NotificationLog::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->latest('id')
            ->take(30)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'event_key' => $n->event_key,
                'content' => $n->content,
                'sent_at' => $n->sent_at?->toIso8601String() ?? $n->created_at->toIso8601String(),
            ]);

        return $this->success($notifications);
    }
}
