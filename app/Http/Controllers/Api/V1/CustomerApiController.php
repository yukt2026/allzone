<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Order\PlaceOrderAction;
use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use App\Services\Storefront\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerApiController extends ApiController
{
    public function __construct(
        protected CartService $cartService,
        protected PlaceOrderAction $placeOrderAction,
        protected PaymentService $paymentService,
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Customer Registration: creates User + Customer profile and returns Sanctum Bearer token.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $validated = $validator->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);

            // Assign customer role if Spatie roles are present
            if (method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole('customer');
                } catch (\Throwable) {}
            }

            Customer::create([
                'user_id' => $user->id,
            ]);

            return $user;
        });

        $deviceName = $request->input('device_name', 'Android Customer App');
        $token = $user->createToken($deviceName, ['customer'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'wallet_balance' => (float) ($user->wallet_balance ?? 0.0),
            ],
        ], 'Customer registered successfully.', 201);
    }

    /**
     * Customer Login via Email or Phone + Password.
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
            return $this->error('Invalid credentials. Please check your login details and try again.', 401);
        }

        if (isset($user->status) && $user->status === 'suspended') {
            return $this->error('Your account is currently suspended. Please contact customer support.', 403);
        }

        // Ensure customer record exists
        if (!$user->customer) {
            Customer::firstOrCreate(['user_id' => $user->id]);
        }

        $deviceName = $request->input('device_name', 'Android Customer App');
        $token = $user->createToken($deviceName, ['customer'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'wallet_balance' => (float) ($user->wallet_balance ?? 0.0),
            ],
        ], 'Signed in successfully.');
    }

    /**
     * Revoke current Sanctum access token (Logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Customer Profile & Account Overview.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $ordersCount = Order::where('customer_id', $customer->id)->count();
        $activeOrdersCount = Order::where('customer_id', $customer->id)
            ->whereIn('status', [
                OrderStatus::PENDING,
                OrderStatus::PLACED,
                OrderStatus::CONFIRMED,
                OrderStatus::PROCESSING,
                OrderStatus::READY_FOR_PICKUP,
                OrderStatus::OUT_FOR_DELIVERY,
            ])
            ->count();

        return $this->success([
            'id' => $user->id,
            'customer_id' => $customer->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $customer->gender,
            'dob' => $customer->dob,
            'wallet_balance' => (float) ($user->wallet_balance ?? 0.0),
            'stats' => [
                'total_orders' => $ordersCount,
                'active_orders' => $activeOrdersCount,
                'saved_addresses' => CustomerAddress::where('customer_id', $customer->id)->count(),
                'wishlist_count' => Wishlist::where('customer_id', $customer->id)->count(),
            ],
        ]);
    }

    /**
     * Update customer personal details.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone,' . $user->id],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before:today'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $validated = $validator->validated();

        $user->name = $validated['name'];
        if (!empty($validated['phone'])) {
            $user->phone = $validated['phone'];
        }
        $user->save();

        $customer->update([
            'gender' => $validated['gender'] ?? $customer->gender,
            'dob' => $validated['dob'] ?? $customer->dob,
        ]);

        return $this->success([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $customer->gender,
            'dob' => $customer->dob,
        ], 'Profile updated successfully.');
    }

    /**
     * List saved customer delivery addresses.
     */
    public function addresses(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orWhere('user_id', $user->id)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get()
            ->map(fn ($addr) => [
                'id' => $addr->id,
                'name' => $addr->name,
                'phone' => $addr->phone,
                'line1' => $addr->line1,
                'line2' => $addr->line2,
                'landmark' => $addr->landmark,
                'city' => $addr->city,
                'state' => $addr->state,
                'pincode' => $addr->pincode,
                'type' => $addr->type instanceof AddressType ? $addr->type->value : (string) $addr->type,
                'is_default' => (bool) $addr->is_default,
            ]);

        return $this->success($addresses);
    }

    /**
     * Add a new delivery address.
     */
    public function storeAddress(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'type' => ['required', 'string', 'in:home,work,other'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $validated = $validator->validated();
        $isDefault = $request->boolean('is_default');

        $hasExisting = CustomerAddress::where('customer_id', $customer->id)->exists();
        if (!$hasExisting) {
            $isDefault = true;
        }

        if ($isDefault) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
            CustomerAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = CustomerAddress::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'pincode' => $validated['pincode'],
            'type' => AddressType::tryFrom($validated['type']) ?? AddressType::HOME,
            'is_default' => $isDefault,
        ]);

        return $this->success($address, 'Address saved successfully.', 201);
    }

    /**
     * Delete an address.
     */
    public function deleteAddress(Request $request, CustomerAddress $address): JsonResponse
    {
        $user = $request->user();
        if ($address->user_id !== $user->id && $address->customer_id !== $user->customer?->id) {
            return $this->error('Unauthorized address access.', 403);
        }

        $address->delete();

        return $this->success(null, 'Address removed successfully.');
    }

    /**
     * Get active shopping cart.
     */
    public function cart(Request $request): JsonResponse
    {
        $user = $request->user();
        $cart = $this->cartService->getActiveCart($user, $request->header('X-Session-ID', (string) $user->id));

        $items = $cart->activeItems()->with(['variant.product.media', 'variant.inventories'])->get()->map(function ($item) {
            $variant = $item->variant;
            $product = $variant?->product;
            $sellingPrice = (float) ($variant?->selling_price ?? 0.0);
            $mrp = (float) ($variant?->mrp ?? $sellingPrice);

            return [
                'id' => $item->id,
                'variant_id' => $item->variant_id,
                'product_id' => $product?->id,
                'title' => $product?->title ?? 'Product',
                'variant_name' => $variant?->name ?? 'Default',
                'sku' => $variant?->sku ?? '',
                'unit_price' => $sellingPrice,
                'mrp' => $mrp,
                'quantity' => (int) $item->quantity,
                'total_price' => round($sellingPrice * (int) $item->quantity, 2),
                'thumbnail_url' => $product?->featured_image_url ?? asset('images/placeholder-product.svg'),
                'is_in_stock' => $variant ? $variant->isInStock() : false,
            ];
        });

        $summary = $this->cartService->getSummary($cart, $user);

        return $this->success([
            'items' => $items,
            'items_count' => $items->count(),
            'subtotal' => (float) $summary['subtotal'],
            'discount' => (float) $summary['coupon_discount'],
            'estimated_tax' => (float) $summary['estimated_tax'],
            'total' => max(0.0, round((float) $summary['subtotal'] - (float) $summary['coupon_discount'], 2)),
            'coupon_code' => $cart->coupon_code,
        ]);
    }

    /**
     * Add item to cart.
     */
    public function addToCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $user = $request->user();
        $cart = $this->cartService->getActiveCart($user, $request->header('X-Session-ID', (string) $user->id));

        try {
            $this->cartService->addItem($cart, (int) $request->input('variant_id'), (int) $request->input('quantity'));
            return $this->cart($request);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update cart item quantity.
     */
    public function updateCartItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:cart_items,id'],
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $user = $request->user();
        $cart = $this->cartService->getActiveCart($user, $request->header('X-Session-ID', (string) $user->id));

        $item = $cart->items()->where('id', $request->input('item_id'))->first();
        if (!$item) {
            return $this->error('Cart item not found.', 404);
        }

        $qty = (int) $request->input('quantity');
        if ($qty <= 0) {
            $item->delete();
        } else {
            $item->quantity = $qty;
            $item->save();
        }

        return $this->cart($request);
    }

    /**
     * Remove item from cart.
     */
    public function removeCartItem(Request $request, int $itemId): JsonResponse
    {
        $user = $request->user();
        $cart = $this->cartService->getActiveCart($user, $request->header('X-Session-ID', (string) $user->id));

        $item = $cart->items()->where('id', $itemId)->first();
        if ($item) {
            $item->delete();
        }

        return $this->cart($request);
    }

    /**
     * Customer Wishlist listing.
     */
    public function wishlist(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $items = Wishlist::where('customer_id', $customer->id)
            ->with(['product.media', 'product.defaultVariant'])
            ->latest('id')
            ->get()
            ->map(function ($w) {
                $p = $w->product;
                $v = $p?->defaultVariant;
                return [
                    'id' => $w->id,
                    'product_id' => $p?->id,
                    'title' => $p?->title ?? '',
                    'slug' => $p?->slug ?? '',
                    'selling_price' => (float) ($v?->selling_price ?? 0.0),
                    'mrp' => (float) ($v?->mrp ?? 0.0),
                    'thumbnail_url' => $p?->featured_image_url ?? asset('images/placeholder-product.svg'),
                    'is_in_stock' => $v ? $v->isInStock() : false,
                ];
            });

        return $this->success($items);
    }

    /**
     * Toggle item in/out of wishlist.
     */
    public function toggleWishlist(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);
        $productId = (int) $request->input('product_id');

        $existing = Wishlist::where('customer_id', $customer->id)->where('product_id', $productId)->first();

        if ($existing) {
            $existing->delete();
            return $this->success(['in_wishlist' => false], 'Removed from wishlist.');
        }

        Wishlist::create([
            'customer_id' => $customer->id,
            'product_id' => $productId,
        ]);

        return $this->success(['in_wishlist' => true], 'Added to wishlist.');
    }

    /**
     * Checkout Calculation Summary: Itemizes packages, delivery fee, taxes, wallet balance, and payment methods.
     */
    public function checkoutSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);
        $cart = $this->cartService->getActiveCart($user, $request->header('X-Session-ID', (string) $user->id));

        if ($cart->activeItems()->count() === 0) {
            return $this->error('Your cart is empty.', 400);
        }

        $addressId = $request->input('address_id');
        $address = $addressId 
            ? CustomerAddress::where('customer_id', $customer->id)->where('id', $addressId)->first()
            : CustomerAddress::where('customer_id', $customer->id)->where('is_default', true)->first();

        $address = $address ?: CustomerAddress::where('customer_id', $customer->id)->first();

        $summary = $this->cartService->getSummary($cart, $user);
        $subtotal = (float) $summary['subtotal'];
        $discount = (float) $summary['coupon_discount'];
        $deliveryFee = 40.00; // Standard base delivery fee fallback
        $total = max(0.0, round($subtotal - $discount + $deliveryFee, 2));

        $maxCod = (float) setting('max_cod_amount', 5000.00);
        $isCodEligible = ($total <= $maxCod);

        $walletBalance = (float) ($user->wallet_balance ?? 0.0);
        $isWalletEligible = ($walletBalance >= $total);

        return $this->success([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery_fee' => $deliveryFee,
            'total_payable' => $total,
            'selected_address' => $address,
            'wallet_balance' => $walletBalance,
            'payment_options' => [
                [
                    'id' => 'online',
                    'title' => 'Online Payment (UPI, Cards, NetBanking)',
                    'subtitle' => 'Secured via Razorpay Instant Checkout',
                    'is_available' => true,
                ],
                [
                    'id' => 'wallet',
                    'title' => 'ALLZONE Digital Wallet',
                    'subtitle' => "Available Balance: ₹" . number_format($walletBalance, 2),
                    'is_available' => $isWalletEligible,
                ],
                [
                    'id' => 'cod',
                    'title' => 'Cash on Delivery (COD)',
                    'subtitle' => $isCodEligible ? 'Pay cash on doorstep delivery' : "Exceeds ₹" . number_format($maxCod) . " COD limit",
                    'is_available' => $isCodEligible,
                ],
            ],
        ]);
    }

    /**
     * Place Order via Mobile App.
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id' => ['required', 'exists:customer_addresses,id'],
            'payment_method' => ['required', 'string', 'in:online,cod,wallet,upi'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);
        $cart = $this->cartService->getActiveCart($user, $request->header('X-Session-ID', (string) $user->id));

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->input('address_id'))
            ->firstOrFail();

        $idempotencyKey = $request->header('X-Idempotency-Key', 'app_ord_' . Str::random(12));

        try {
            $order = $this->placeOrderAction->execute(
                customer: $customer,
                address: $address,
                cart: $cart,
                paymentMethod: $request->input('payment_method'),
                idempotencyKey: $idempotencyKey,
                notes: $request->input('notes')
            );

            // If Online Payment, initiate Razorpay order payload
            $paymentData = null;
            if (in_array(strtolower($request->input('payment_method')), ['online', 'upi'], true)) {
                $paymentData = $this->paymentService->initiateOnlinePayment($order);
            }

            return $this->success([
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'status' => $order->status->value,
                'payment_status' => $order->payment_status->value,
                'payment_method' => $order->payment_method->value,
                'packages_count' => $order->fulfilmentOrders()->count(),
                'payment_gateway' => $paymentData,
            ], 'Order placed successfully.', 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Customer Order History.
     */
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $orders = Order::where('customer_id', $customer->id)
            ->with(['fulfilmentOrders.items.variant.product', 'fulfilmentOrders.seller.stores', 'payments'])
            ->latest('id')
            ->paginate(15);

        $transformed = collect($orders->items())->map(function ($order) {
            return [
                'order_number' => $order->order_number,
                'created_at' => $order->created_at->toIso8601String(),
                'status' => $order->status->value,
                'status_label' => ucfirst(str_replace('_', ' ', $order->status->value)),
                'payment_status' => $order->payment_status->value,
                'payment_method' => $order->payment_method->value,
                'total' => (float) $order->total,
                'packages' => $order->fulfilmentOrders->map(fn ($fo) => [
                    'package_number' => $fo->fulfilment_order_number,
                    'seller_name' => 'ALLZONE Verified Partner',
                    'status' => $fo->status instanceof OrderStatus ? $fo->status->value : (string) $fo->status,
                    'items_count' => $fo->items->count(),
                    'items' => $fo->items->map(fn ($it) => [
                        'title' => $it->title_snapshot ?? $it->variant?->name ?? 'Product',
                        'qty' => $it->qty,
                        'unit_price' => (float) $it->unit_price,
                    ]),
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
     * Order Details with consignment tracking.
     */
    public function orderDetail(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $order = Order::where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->with([
                'fulfilmentOrders.items.variant.product',
                'fulfilmentOrders.shipments.tracking',
                'payments',
            ])
            ->first();

        if (!$order) {
            return $this->error('Order not found.', 404);
        }

        $packages = $order->fulfilmentOrders->map(function ($fo) {
            $shipment = $fo->shipments->first();
            $tracking = $shipment ? $shipment->tracking->map(fn ($t) => [
                'status' => $t->status,
                'location' => $t->location,
                'time' => $t->event_time->toIso8601String(),
            ]) : [];

            return [
                'package_number' => $fo->fulfilment_order_number,
                'status' => $fo->status instanceof OrderStatus ? $fo->status->value : (string) $fo->status,
                'courier' => $shipment?->courier ?? 'ALLZONE Express (Shiprocket)',
                'seller_name' => 'ALLZONE Verified Partner',
                'fulfilled_by' => 'ALLZONE Assured',
                'awb' => $shipment?->awb,
                'tracking' => $tracking,
                'items' => $fo->items->map(fn ($i) => [
                    'id' => $i->id,
                    'title' => $i->title_snapshot ?? $i->variant?->name ?? 'Item',
                    'qty' => $i->qty,
                    'unit_price' => (float) $i->unit_price,
                ]),
            ];
        });

        return $this->success([
            'order_number' => $order->order_number,
            'created_at' => $order->created_at->toIso8601String(),
            'status' => $order->status->value,
            'payment_status' => $order->payment_status->value,
            'payment_method' => $order->payment_method->value,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'delivery_fee' => (float) $order->delivery_fee,
            'total' => (float) $order->total,
            'address' => $order->address_snapshot,
            'packages' => $packages,
        ]);
    }

    /**
     * Cancel an eligible order.
     */
    public function cancelOrder(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::firstOrCreate(['user_id' => $user->id]);

        $order = Order::where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$order) {
            return $this->error('Order not found.', 404);
        }

        if (!in_array($order->status, [OrderStatus::PENDING, OrderStatus::PLACED, OrderStatus::CONFIRMED], true)) {
            return $this->error('Order cannot be cancelled in its current status.', 400);
        }

        $order->status = OrderStatus::CANCELLED;
        $order->save();

        return $this->success(null, 'Order cancelled successfully.');
    }

    /**
     * Register customer device push token (Firebase Cloud Messaging).
     */
    public function registerFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => ['required', 'string', 'max:500'],
            'device_type' => ['nullable', 'string', 'in:android,ios'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $user = $request->user();
        \Illuminate\Support\Facades\Cache::put("user_fcm_token_{$user->id}", $request->input('fcm_token'), now()->addDays(90));

        return $this->success(null, 'Device registered for push notifications.');
    }

    /**
     * Customer in-app notifications.
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();

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
