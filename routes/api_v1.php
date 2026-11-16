<?php

use App\Http\Controllers\Api\V1\StorefrontApiController;
use Illuminate\Support\Facades\Route;

Route::get('/config', [StorefrontApiController::class, 'config'])->name('config');
Route::get('/health', [\App\Http\Controllers\HealthCheckController::class, 'check'])->name('api.health');

// Storefront Mobile App & External Catalogue Endpoints
Route::get('/home', [StorefrontApiController::class, 'home'])->name('api.storefront.home');
Route::get('/categories', [StorefrontApiController::class, 'categories'])->name('api.storefront.categories');
Route::get('/brands', [StorefrontApiController::class, 'brands'])->name('api.storefront.brands');
Route::get('/products', [StorefrontApiController::class, 'products'])->name('api.storefront.products');
Route::get('/products/{slug}', [StorefrontApiController::class, 'productDetail'])->name('api.storefront.product.detail');

// AI Semantic Search Query Parsing & Recommendations
Route::get('/search/parse', [\App\Http\Controllers\Storefront\SearchController::class, 'parseQueryApi'])->name('api.search.parse');
Route::get('/products/{product}/recommendations', function (\App\Models\Product $product, \App\Services\AI\Contracts\RecommendationServiceInterface $recService) {
    return response()->json([
        'similar' => $recService->getSimilarProducts($product),
        'frequently_bought_together' => $recService->getFrequentlyBoughtTogether($product),
    ]);
})->name('api.products.recommendations');
Route::get('/recommendations/personalized', function (\Illuminate\Http\Request $request, \App\Services\AI\Contracts\RecommendationServiceInterface $recService) {
    return response()->json([
        'personalized' => $recService->getPersonalizedRecommendations($request->user()),
    ]);
})->name('api.recommendations.personalized');

// Quick Commerce Hyperlocal Store Discovery
Route::get('/quick/stores', [\App\Http\Controllers\Storefront\QuickCommerceController::class, 'nearbyStoresApi'])->name('api.quick.stores');

// ALLZONE Cargo & Shiprocket Logistics Webhook Receivers
Route::post('/cargo/webhook', [\App\Http\Controllers\Api\CargoWebhookController::class, 'handle'])->name('cargo.webhook');
Route::post('/shiprocket/webhook', [\App\Http\Controllers\Api\ShiprocketWebhookController::class, 'handle'])->name('shiprocket.webhook');

// Connect My Shop — Public Integration Endpoints & Webhooks
Route::prefix('integrations')->middleware(['throttle:60,1'])->group(function () {
    Route::middleware('integration.auth')->group(function () {
        Route::get('/products', [\App\Http\Controllers\Api\IntegrationController::class, 'listProducts'])->name('integrations.products.list');
        Route::post('/products', [\App\Http\Controllers\Api\IntegrationController::class, 'upsertProduct'])->name('integrations.products.upsert');
        Route::get('/inventory', [\App\Http\Controllers\Api\IntegrationController::class, 'getInventory'])->name('integrations.inventory.list');
        Route::post('/inventory/adjust', [\App\Http\Controllers\Api\IntegrationController::class, 'adjustInventory'])->name('integrations.inventory.adjust');
        Route::get('/orders', [\App\Http\Controllers\Api\IntegrationController::class, 'listOrders'])->name('integrations.orders.list');
        Route::post('/orders/{id}/status', [\App\Http\Controllers\Api\IntegrationController::class, 'updateOrderStatus'])->name('integrations.orders.status');
    });

    // Inward Webhooks
    Route::post('/woocommerce/webhook/{connection}', [\App\Http\Controllers\Api\IntegrationController::class, 'woocommerceWebhook'])->name('integrations.woocommerce.webhook');
    Route::post('/shopify/webhook/{connection}', [\App\Http\Controllers\Api\IntegrationController::class, 'shopifyWebhook'])->name('integrations.shopify.webhook');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return $request->user();
    })->name('user');
});

// Customer Mobile App Routes (ALLZONE Customer Android)
Route::prefix('customer')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'register'])->name('api.customer.register');
    Route::post('/login', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'login'])->name('api.customer.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'logout'])->name('api.customer.logout');
        Route::get('/profile', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'profile'])->name('api.customer.profile');
        Route::put('/profile', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'updateProfile'])->name('api.customer.profile.update');
        Route::get('/addresses', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'addresses'])->name('api.customer.addresses');
        Route::post('/addresses', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'storeAddress'])->name('api.customer.addresses.store');
        Route::delete('/addresses/{address}', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'deleteAddress'])->name('api.customer.addresses.delete');

        // Cart & Checkout
        Route::get('/cart', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'cart'])->name('api.customer.cart');
        Route::post('/cart/add', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'addToCart'])->name('api.customer.cart.add');
        Route::put('/cart/update', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'updateCartItem'])->name('api.customer.cart.update');
        Route::delete('/cart/items/{itemId}', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'removeCartItem'])->name('api.customer.cart.remove');
        Route::get('/wishlist', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'wishlist'])->name('api.customer.wishlist');
        Route::post('/wishlist/toggle', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'toggleWishlist'])->name('api.customer.wishlist.toggle');
        Route::get('/checkout/summary', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'checkoutSummary'])->name('api.customer.checkout.summary');
        Route::post('/checkout/place-order', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'placeOrder'])->name('api.customer.checkout.place_order');

        // Orders & Tracking
        Route::get('/orders', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'orders'])->name('api.customer.orders');
        Route::get('/orders/{orderNumber}', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'orderDetail'])->name('api.customer.orders.detail');
        Route::post('/orders/{orderNumber}/cancel', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'cancelOrder'])->name('api.customer.orders.cancel');

        // Notifications
        Route::post('/fcm-token', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'registerFcmToken'])->name('api.customer.fcm_token');
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\CustomerApiController::class, 'notifications'])->name('api.customer.notifications');
    });
});

// Vendor / Merchant Mobile App Routes (ALLZONE Seller Partner Android)
Route::prefix('vendor')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'login'])->name('api.vendor.login');
    Route::post('/register', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'register'])->name('api.vendor.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'logout'])->name('api.vendor.logout');
        Route::get('/dashboard', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'dashboard'])->name('api.vendor.dashboard');
        Route::get('/profile', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'profile'])->name('api.vendor.profile');
        Route::get('/store', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'store'])->name('api.vendor.store');
        Route::put('/store', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'updateStore'])->name('api.vendor.store.update');

        // Products & Inventory
        Route::get('/products', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'products'])->name('api.vendor.products');
        Route::get('/inventory', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'inventory'])->name('api.vendor.inventory');
        Route::post('/inventory/update', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'updateStock'])->name('api.vendor.inventory.update');

        // Orders & Dispatch
        Route::get('/orders', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'orders'])->name('api.vendor.orders');
        Route::post('/orders/{id}/status', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'updateOrderStatus'])->name('api.vendor.orders.status');

        // Financials & Settlements
        Route::get('/commissions', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'commissions'])->name('api.vendor.commissions');
        Route::get('/earnings', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'earnings'])->name('api.vendor.earnings');
        Route::post('/withdrawals', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'requestWithdrawal'])->name('api.vendor.withdrawals.request');

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\VendorApiController::class, 'notifications'])->name('api.vendor.notifications');
    });
});
