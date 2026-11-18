<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\SellerAuthController;
use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\OnboardingController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\StoreController;
use Illuminate\Support\Facades\Route;

// Seller Guest Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [SellerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [SellerAuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [SellerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [SellerAuthController::class, 'register'])->name('register.submit');
});

// Authenticated Seller Operations
Route::middleware(['auth', 'seller.status'])->group(function () {
    Route::match(['get', 'post'], '/logout', [SellerAuthController::class, 'logout'])->name('logout');

    // Onboarding Wizard Routes
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding/send-phone-otp', [OnboardingController::class, 'sendPhoneOtp'])->name('onboarding.send_otp');
    Route::post('/onboarding/verify-phone-otp', [OnboardingController::class, 'verifyPhoneOtp'])->name('onboarding.verify_otp');
    Route::post('/onboarding/verify-email', [OnboardingController::class, 'verifyEmail'])->name('onboarding.verify_email');
    Route::post('/onboarding/step/2', [OnboardingController::class, 'saveStep2'])->name('onboarding.step2');
    Route::post('/onboarding/step/3', [OnboardingController::class, 'saveStep3'])->name('onboarding.step3');
    Route::post('/onboarding/preview-bank', [OnboardingController::class, 'previewBankVerification'])->name('onboarding.preview_bank');
    Route::post('/onboarding/step/4', [OnboardingController::class, 'saveStep4'])->name('onboarding.step4');
    Route::post('/onboarding/step/5', [OnboardingController::class, 'saveStep5'])->name('onboarding.step5');
    Route::post('/onboarding/step/6', [OnboardingController::class, 'saveStep6'])->name('onboarding.step6');
    Route::post('/onboarding/submit', [OnboardingController::class, 'submit'])->name('onboarding.submit');
    Route::post('/onboarding/resubmit', [OnboardingController::class, 'resubmit'])->name('onboarding.resubmit');

    // Suspended seller notification screen
    Route::get('/suspended', [SellerAuthController::class, 'suspended'])->name('suspended');

    // Active Seller Portal & Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/chart-data', [DashboardController::class, 'chartData'])->name('chart_data');
    Route::post('/switch-store', [DashboardController::class, 'switchStore'])->name('switch_store');
    Route::post('/store/toggle-status', [DashboardController::class, 'toggleStoreStatus'])->name('store.toggle_status');
    Route::get('/quick-orders', [\App\Http\Controllers\Seller\QuickOrderController::class, 'liveConsole'])->name('quick.live');
    Route::get('/quick-orders/api/pending', [\App\Http\Controllers\Seller\QuickOrderController::class, 'pendingOrdersApi'])->name('quick.orders_api');
    Route::post('/inventory/{inventory}/quick-restock', [DashboardController::class, 'quickRestock'])->name('inventory.quick_restock');

    // Seller Order Management Console (Blueprint Section 21)
    Route::get('/dispatch-performance', [\App\Http\Controllers\Seller\DispatchPerformanceController::class, 'index'])->name('dispatch_performance');
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\OrderController::class, 'index'])->name('index');
        Route::get('/dispatch-performance', [\App\Http\Controllers\Seller\DispatchPerformanceController::class, 'index'])->name('dispatch_performance');
        Route::post('/{fulfilmentOrder}/create-shipment', [\App\Http\Controllers\Seller\DispatchPerformanceController::class, 'createShipment'])->name('create_shipment');
        Route::get('/{fulfilmentOrder}/check-rates', [\App\Http\Controllers\Seller\DispatchPerformanceController::class, 'checkRates'])->name('check_rates');
        Route::get('/{fulfilmentOrder}', [\App\Http\Controllers\Seller\OrderController::class, 'show'])->name('show');
        Route::post('/{fulfilmentOrder}/transition', [\App\Http\Controllers\Seller\OrderController::class, 'transition'])->name('transition');
        Route::post('/{fulfilmentOrder}/accept', [\App\Http\Controllers\Seller\OrderController::class, 'accept'])->name('accept');
        Route::post('/{fulfilmentOrder}/pack', [\App\Http\Controllers\Seller\QuickOrderController::class, 'pack'])->name('pack');
        Route::post('/{fulfilmentOrder}/ready', [\App\Http\Controllers\Seller\QuickOrderController::class, 'ready'])->name('ready');
        Route::post('/{fulfilmentOrder}/reject', [\App\Http\Controllers\Seller\QuickOrderController::class, 'reject'])->name('reject');
        Route::post('/{fulfilmentOrder}/ready-to-ship', [\App\Http\Controllers\Seller\OrderController::class, 'readyToShip'])->name('ready_to_ship');
        Route::get('/{fulfilmentOrder}/packing-slip', [\App\Http\Controllers\Seller\OrderController::class, 'packingSlip'])->name('packing_slip');
        Route::get('/{fulfilmentOrder}/invoice', [\App\Http\Controllers\Seller\OrderController::class, 'invoice'])->name('invoice');
        Route::get('/{fulfilmentOrder}/shipping-label', [\App\Http\Controllers\Seller\OrderController::class, 'shippingLabel'])->name('shipping_label');
    });
    Route::get('/api/orders/pending-count', [\App\Http\Controllers\Seller\OrderController::class, 'pendingCount'])->name('orders.pending_count');

    // Pricing Management & Return Reduction
    Route::prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\PricingController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Seller\PricingController::class, 'export'])->name('export');
        Route::get('/reduce-rto', [\App\Http\Controllers\Seller\PricingController::class, 'reduceRto'])->name('reduce_rto');
        Route::get('/calculate-earnings', [\App\Http\Controllers\Seller\PricingController::class, 'calculateEarnings'])->name('calculate_earnings');
        Route::post('/bulk-preview', [\App\Http\Controllers\Seller\PricingController::class, 'bulkPreview'])->name('bulk_preview');
        Route::post('/bulk-update', [\App\Http\Controllers\Seller\PricingController::class, 'bulkUpdate'])->name('bulk_update');
        Route::post('/{variant}/update-price', [\App\Http\Controllers\Seller\PricingController::class, 'updatePrice'])->name('update_price');
    });

    // Claims Management & Claim Tracking
    Route::prefix('claims')->name('claims.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\ClaimController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Seller\ClaimController::class, 'create'])->name('create');
        Route::get('/search-order', [\App\Http\Controllers\Seller\ClaimController::class, 'searchOrder'])->name('search_order');
        Route::post('/', [\App\Http\Controllers\Seller\ClaimController::class, 'store'])->name('store');
        Route::get('/export', [\App\Http\Controllers\Seller\ClaimController::class, 'export'])->name('export');
        Route::get('/{claim}', [\App\Http\Controllers\Seller\ClaimController::class, 'show'])->name('show');
        Route::post('/{claim}/reply', [\App\Http\Controllers\Seller\ClaimController::class, 'reply'])->name('reply');
    });

    // Catalog Quality & Recommendations (Step 12)
    Route::prefix('quality')->name('quality.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\QualityController::class, 'index'])->name('index');
        Route::get('/products/{product}/reviews', [\App\Http\Controllers\Seller\QualityController::class, 'productReviews'])->name('product_reviews');
        Route::post('/reviews/{review}/reply', [\App\Http\Controllers\Seller\QualityController::class, 'replyReview'])->name('reply_review');
        Route::post('/products/{product}/request-review', [\App\Http\Controllers\Seller\QualityController::class, 'requestReReview'])->name('request_review');
    });

    // Image Bulk Upload & Gallery Management (Step 11)
    Route::prefix('images')->name('images.')->group(function () {
        Route::get('/bulk-upload', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'index'])->name('bulk_upload');
        Route::post('/upload', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'upload'])->name('upload');
        Route::post('/map-csv', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'mapCsv'])->name('map_csv');
        Route::get('/mapping-template', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'downloadMappingTemplate'])->name('template');
        Route::get('/gallery/{product}', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'productGallery'])->name('gallery');
        Route::post('/reorder/{product}', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'reorderGallery'])->name('reorder');
        Route::post('/set-primary/{media}', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'setPrimary'])->name('set_primary');
        Route::post('/replace/{media}', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'replace'])->name('replace');
        Route::delete('/{media}', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'destroy'])->name('destroy');
    });

    // Alias for legacy /products-bulk-upload
    Route::get('/products-bulk-upload', [\App\Http\Controllers\Seller\ImageBulkUploadController::class, 'index'])->name('products.bulk_upload');

    // Warehouse & Pickup Location Management
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\WarehouseController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Seller\WarehouseController::class, 'store'])->name('store');
        Route::get('/{location}', [\App\Http\Controllers\Seller\WarehouseController::class, 'show'])->name('show');
        Route::put('/{location}', [\App\Http\Controllers\Seller\WarehouseController::class, 'update'])->name('update');
        Route::delete('/{location}', [\App\Http\Controllers\Seller\WarehouseController::class, 'destroy'])->name('destroy');
        Route::post('/{location}/set-default', [\App\Http\Controllers\Seller\WarehouseController::class, 'setDefault'])->name('set_default');
        Route::post('/{location}/check-serviceability', [\App\Http\Controllers\Seller\WarehouseController::class, 'checkServiceability'])->name('check_serviceability');
        Route::post('/package-config', [\App\Http\Controllers\Seller\WarehouseController::class, 'updatePackageConfig'])->name('package_config');
    });

    // Influencer Marketing (Step 16)
    Route::prefix('influencer')->name('influencer.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\InfluencerController::class, 'index'])->name('index');
        Route::post('/collaborate', [\App\Http\Controllers\Seller\InfluencerController::class, 'collaborate'])->name('collaborate');
        Route::get('/{collaboration}', [\App\Http\Controllers\Seller\InfluencerController::class, 'show'])->name('show');
    });

    // Advertisements Console (Step 16)
    Route::prefix('ads')->name('ads.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\AdController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Seller\AdController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Seller\AdController::class, 'store'])->name('store');
        Route::get('/{campaign}', [\App\Http\Controllers\Seller\AdController::class, 'show'])->name('show');
        Route::post('/{campaign}/toggle', [\App\Http\Controllers\Seller\AdController::class, 'toggleStatus'])->name('toggle');
        Route::delete('/{campaign}', [\App\Http\Controllers\Seller\AdController::class, 'destroy'])->name('destroy');
    });

    // Seller Finance & Capital Facilitation (Step 17)
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\FinanceController::class, 'index'])->name('index');
        Route::post('/apply', [\App\Http\Controllers\Seller\FinanceController::class, 'apply'])->name('apply');
    });

    // Promotions Console: Sale Opt-in, Campaigns & Offer Management (Step 15)
    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\PromotionController::class, 'index'])->name('index');
        Route::get('/sales', [\App\Http\Controllers\Seller\PromotionController::class, 'sales'])->name('sales');
        Route::get('/offers', [\App\Http\Controllers\Seller\PromotionController::class, 'offers'])->name('offers');
        Route::get('/campaigns/{campaign}', [\App\Http\Controllers\Seller\PromotionController::class, 'show'])->name('show');
        Route::get('/campaigns/{campaign}/eligible-products', [\App\Http\Controllers\Seller\PromotionController::class, 'eligibleProducts'])->name('eligible_products');
        Route::post('/campaigns/{campaign}/opt-in', [\App\Http\Controllers\Seller\PromotionController::class, 'optIn'])->name('opt_in');
        Route::post('/campaigns/{campaign}/opt-out', [\App\Http\Controllers\Seller\PromotionController::class, 'optOut'])->name('opt_out');
        Route::delete('/campaigns/{campaign}/products/{product}', [\App\Http\Controllers\Seller\PromotionController::class, 'removeProduct'])->name('remove_product');
    });

    // Business Dashboard & Analytics (Step 18)
    Route::prefix('business-dashboard')->name('business_dashboard.')->group(function () {
        Route::get('/export', [\App\Http\Controllers\Seller\BusinessDashboardController::class, 'export'])->name('export');
        Route::get('/chart-data', [\App\Http\Controllers\Seller\BusinessDashboardController::class, 'chartData'])->name('chart_data');
    });
    Route::get('/business-dashboard', [\App\Http\Controllers\Seller\BusinessDashboardController::class, 'index'])->name('business_dashboard');

    // Notices & Store Leave Request (Screenshot 6)
    Route::prefix('notices')->name('notices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\NoticeController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [\App\Http\Controllers\Seller\NoticeController::class, 'markAllAsRead'])->name('mark_all_read');
        Route::post('/leave-request', [\App\Http\Controllers\Seller\NoticeController::class, 'submitLeaveRequest'])->name('leave_request');
        Route::post('/{announcement}/mark-read', [\App\Http\Controllers\Seller\NoticeController::class, 'markAsRead'])->name('mark_read');
        Route::get('/{announcement}/download', [\App\Http\Controllers\Seller\NoticeController::class, 'downloadAttachment'])->name('download');
        Route::get('/{announcement}', [\App\Http\Controllers\Seller\NoticeController::class, 'show'])->name('show');
    });

    // Settings Hub: Bank Details, Tax, Password, Notifications (Screenshot 8)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/bank', [\App\Http\Controllers\Seller\SettingController::class, 'bank'])->name('bank');
        Route::put('/bank', [\App\Http\Controllers\Seller\SettingController::class, 'updateBank'])->name('bank.update');
        Route::get('/tax', [\App\Http\Controllers\Seller\SettingController::class, 'tax'])->name('tax');
        Route::put('/tax', [\App\Http\Controllers\Seller\SettingController::class, 'updateTax'])->name('tax.update');
        Route::get('/password', [\App\Http\Controllers\Seller\SettingController::class, 'password'])->name('password');
        Route::put('/password', [\App\Http\Controllers\Seller\SettingController::class, 'updatePassword'])->name('password.update');
        Route::get('/signature', [\App\Http\Controllers\Seller\SettingController::class, 'signature'])->name('signature');
        Route::get('/notifications', [\App\Http\Controllers\Seller\SettingController::class, 'notifications'])->name('notifications');
    });

    // My Store Settings & Public Profile Management
    Route::get('/store', [StoreController::class, 'edit'])->name('store.edit');
    Route::put('/store', [StoreController::class, 'update'])->name('store.update');
    Route::match(['get', 'post'], '/store/check-slug', [StoreController::class, 'checkSlug'])->name('store.check_slug');
    Route::get('/store/qr-code', [StoreController::class, 'qrCode'])->name('store.qr_code');

    // Multi-Store Outlets Management & Store-Level Reports
    Route::prefix('stores')->name('stores.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\StoreOutletController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Seller\StoreOutletController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Seller\StoreOutletController::class, 'store'])->name('store');
        Route::get('/reports', [\App\Http\Controllers\Seller\StoreOutletController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [\App\Http\Controllers\Seller\StoreOutletController::class, 'export'])->name('reports.export');
    });

    // Staff & Permissions (Blueprint Section 20)
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\StoreStaffController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Seller\StoreStaffController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Seller\StoreStaffController::class, 'store'])->name('store');
        Route::put('/{staff}', [\App\Http\Controllers\Seller\StoreStaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [\App\Http\Controllers\Seller\StoreStaffController::class, 'destroy'])->name('destroy');
    });

    // Connect My Shop Wizard & Channels (/seller/connections)
    Route::prefix('connections')->name('connections.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\ConnectionController::class, 'index'])->name('index');

        // CSV / Excel
        Route::get('/csv', [\App\Http\Controllers\Seller\ConnectionController::class, 'csvIndex'])->name('csv');
        Route::get('/csv/template', [\App\Http\Controllers\Seller\ConnectionController::class, 'csvTemplate'])->name('csv.template');
        Route::post('/csv/upload', [\App\Http\Controllers\Seller\ConnectionController::class, 'csvUpload'])->name('csv.upload');
        Route::post('/csv/{import}/process', [\App\Http\Controllers\Seller\ConnectionController::class, 'csvImport'])->name('csv.import');

        // WooCommerce
        Route::get('/woocommerce', [\App\Http\Controllers\Seller\ConnectionController::class, 'woocommerceIndex'])->name('woocommerce');
        Route::post('/woocommerce/connect', [\App\Http\Controllers\Seller\ConnectionController::class, 'woocommerceConnect'])->name('woocommerce.connect');
        Route::post('/woocommerce/test', [\App\Http\Controllers\Seller\ConnectionController::class, 'woocommerceTest'])->name('woocommerce.test');
        Route::post('/woocommerce/sync', [\App\Http\Controllers\Seller\ConnectionController::class, 'woocommerceSync'])->name('woocommerce.sync');

        // Shopify
        Route::get('/shopify', [\App\Http\Controllers\Seller\ConnectionController::class, 'shopifyIndex'])->name('shopify');
        Route::post('/shopify/connect', [\App\Http\Controllers\Seller\ConnectionController::class, 'shopifyConnect'])->name('shopify.connect');
        Route::post('/shopify/test', [\App\Http\Controllers\Seller\ConnectionController::class, 'shopifyTest'])->name('shopify.test');
        Route::post('/shopify/sync', [\App\Http\Controllers\Seller\ConnectionController::class, 'shopifySync'])->name('shopify.sync');

        // Custom Website API
        Route::get('/custom-api', [\App\Http\Controllers\Seller\ConnectionController::class, 'customApiIndex'])->name('custom_api');
        Route::post('/custom-api/generate', [\App\Http\Controllers\Seller\ConnectionController::class, 'customApiGenerate'])->name('custom_api.generate');
        Route::post('/custom-api/webhooks', [\App\Http\Controllers\Seller\ConnectionController::class, 'customApiWebhooks'])->name('custom_api.webhooks');

        // Sync logs retry
        Route::post('/sync-logs/{syncLog}/retry', [\App\Http\Controllers\Seller\ConnectionController::class, 'retrySync'])->name('sync_logs.retry');
    });

    // Products & Catalogue Management
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('/products/variants/{variant}/quick-update', [ProductController::class, 'quickUpdateVariant'])->name('products.quick_update_variant');
    Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulk_action');
    Route::get('/products-export', [ProductController::class, 'export'])->name('products.export');
    Route::match(['get', 'post'], '/products/check-sku', [ProductController::class, 'checkSku'])->name('products.check_sku');
    Route::get('/products/bulk-template', [ProductController::class, 'downloadTemplateCsv'])->name('products.bulk_template');
    Route::post('/products/bulk-preview', [ProductController::class, 'bulkUploadPreview'])->name('products.bulk_preview');
    Route::post('/products/bulk-import/{import}/confirm', [ProductController::class, 'bulkImportConfirm'])->name('products.bulk_confirm');
    Route::get('/products/bulk-import/{import}/errors', [ProductController::class, 'bulkImportErrors'])->name('products.bulk_errors');

    // Inventory & Stock Balances (Blueprint Section 9)
    Route::get('/inventory', [\App\Http\Controllers\Seller\InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory-export', [\App\Http\Controllers\Seller\InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/bulk-update', [\App\Http\Controllers\Seller\InventoryController::class, 'bulkUpdate'])->name('inventory.bulk_update');
    Route::match(['put', 'post'], '/inventory/{inventory}', [\App\Http\Controllers\Seller\InventoryController::class, 'update'])->name('inventory.update');
    Route::get('/inventory/{inventory}/movements', [\App\Http\Controllers\Seller\InventoryController::class, 'movements'])->name('inventory.movements');
    Route::post('/inventory/transfer', [\App\Http\Controllers\Seller\InventoryController::class, 'transfer'])->name('inventory.transfer');
    Route::post('/inventory/import-csv', [\App\Http\Controllers\Seller\InventoryController::class, 'importCsv'])->name('inventory.import_csv');
    Route::get('/inventory-sample-csv', [\App\Http\Controllers\Seller\InventoryController::class, 'sampleCsv'])->name('inventory.sample_csv');

    // Financials, Earnings Ledger & Settlements (Blueprint Section 18)
    Route::get('/payments', [\App\Http\Controllers\Seller\PaymentController::class, 'payments'])->name('payments.index');
    Route::get('/payments/export', [\App\Http\Controllers\Seller\PaymentController::class, 'exportPayments'])->name('payments.export');
    Route::get('/payments/transactions/{entry}', [\App\Http\Controllers\Seller\PaymentController::class, 'transactionDetails'])->name('payments.transaction_details');
    Route::get('/settlements', [\App\Http\Controllers\Seller\PaymentController::class, 'settlements'])->name('settlements.index');
    Route::get('/settlements/export', [\App\Http\Controllers\Seller\PaymentController::class, 'exportSettlements'])->name('settlements.export');
    Route::get('/settlements/{settlement}', [\App\Http\Controllers\Seller\PaymentController::class, 'showSettlement'])->name('settlements.show');
    Route::get('/settlements/{settlement}/pdf', [\App\Http\Controllers\Seller\PaymentController::class, 'downloadPdf'])->name('settlements.pdf');
    Route::get('/settlements/{settlement}/csv', [\App\Http\Controllers\Seller\PaymentController::class, 'downloadCsv'])->name('settlements.csv');

    // Returns & RMA Management (Blueprint Section 26)
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\ReturnController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Seller\ReturnController::class, 'export'])->name('export');
        Route::get('/{return}', [\App\Http\Controllers\Seller\ReturnController::class, 'show'])->name('show');
        Route::post('/{return}/approve', [\App\Http\Controllers\Seller\ReturnController::class, 'approve'])->name('approve');
        Route::post('/{return}/reject', [\App\Http\Controllers\Seller\ReturnController::class, 'reject'])->name('reject');
        Route::post('/{return}/schedule-pickup', [\App\Http\Controllers\Seller\ReturnController::class, 'schedulePickup'])->name('schedule_pickup');
        Route::post('/{return}/receive', [\App\Http\Controllers\Seller\ReturnController::class, 'receive'])->name('receive');
        Route::post('/{return}/qc', [\App\Http\Controllers\Seller\ReturnController::class, 'recordQc'])->name('record_qc');
        Route::post('/{return}/refund', [\App\Http\Controllers\Seller\ReturnController::class, 'refund'])->name('refund');
    });

    // Shipments & Cargo Logistics Management
    Route::prefix('shipments')->name('shipments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\ShipmentController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Seller\ShipmentController::class, 'export'])->name('export');
        Route::post('/bulk-labels', [\App\Http\Controllers\Seller\ShipmentController::class, 'bulkLabels'])->name('bulk_labels');
        Route::post('/bulk-create', [\App\Http\Controllers\Seller\ShipmentController::class, 'bulkCreate'])->name('bulk_create');
        Route::get('/{shipment}', [\App\Http\Controllers\Seller\ShipmentController::class, 'show'])->name('show');
        Route::get('/{shipment}/label', [\App\Http\Controllers\Seller\ShipmentController::class, 'downloadLabel'])->name('label');
        Route::post('/{shipment}/pickup', [\App\Http\Controllers\Seller\ShipmentController::class, 'requestPickup'])->name('request_pickup');
        Route::post('/{shipment}/sync-tracking', [\App\Http\Controllers\Seller\ShipmentController::class, 'syncTracking'])->name('sync_tracking');
        Route::post('/{shipment}/ndr-action', [\App\Http\Controllers\Seller\ShipmentController::class, 'resolveNdr'])->name('ndr_action');
    });

    // Seller AI Assistant & Inventory Forecasting (Blueprint AI & Automation)
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('/generate-catalogue', [\App\Http\Controllers\Seller\SellerAiController::class, 'generateCatalogue'])->name('generate_catalogue');
        Route::post('/suggest-pricing/{variant}', [\App\Http\Controllers\Seller\SellerAiController::class, 'suggestPricing'])->name('suggest_pricing');
        Route::get('/demand-forecast', [\App\Http\Controllers\Seller\SellerAiController::class, 'demandForecast'])->name('demand_forecast');
        Route::get('/action-logs', [\App\Http\Controllers\Seller\SellerAiController::class, 'actionLogs'])->name('action_logs');
    });

    // Seller Profile & KYC Credentials
    Route::get('/profile', [\App\Http\Controllers\Seller\ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Seller\ProfileController::class, 'update'])->name('profile.update');

    // Merchant Wallet & Withdrawal Requests
    Route::get('/wallet', [\App\Http\Controllers\Seller\WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/withdraw', [\App\Http\Controllers\Seller\WalletController::class, 'requestWithdrawal'])->name('wallet.withdraw');

    // Vendor Support Desk
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\SupportTicketController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Seller\SupportTicketController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Seller\SupportTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [\App\Http\Controllers\Seller\SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [\App\Http\Controllers\Seller\SupportTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/reopen', [\App\Http\Controllers\Seller\SupportTicketController::class, 'reopen'])->name('reopen');
    });

    // In-App Notifications Center & Alerts
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/{id}/go', [\App\Http\Controllers\NotificationController::class, 'goToUrl'])->name('go');
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark_all_read');
        Route::delete('/clear-all', [\App\Http\Controllers\NotificationController::class, 'clearAll'])->name('clear_all');
        Route::post('/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('preferences.update');
    });

    // Sales Reports Direct Route
    Route::get('/reports', [\App\Http\Controllers\Seller\StoreOutletController::class, 'reports'])->name('reports.index');
});

// Signed route for private document downloads
Route::middleware(['auth', 'signed'])->get('/onboarding/documents/{document}', [OnboardingController::class, 'downloadDocument'])
    ->name('onboarding.document.view');
