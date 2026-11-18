<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CatalogStandardsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\CommissionRuleController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductAttributeController;
use App\Http\Controllers\Admin\ProductModerationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\ShippingSettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SupportCategoryController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\UiKitController;
use App\Http\Controllers\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

// Dedicated Admin Login (Separate portal screen)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
});

// Protected Administration Console
Route::middleware(['auth', 'role:super_admin|admin|operations_admin|support_admin|seller_support|catalog_manager|finance_admin|finance_manager|support_staff'])->group(function () {
    Route::match(['get', 'post'], '/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart_data');
    Route::get('/dashboard/export', [DashboardController::class, 'exportSummary'])->name('dashboard.export');
    Route::get('/ui-kit', [UiKitController::class, 'index'])->name('uikit');

    // Sellers Management & KYC Governance
    Route::prefix('sellers')->name('sellers.')->group(function () {
        Route::get('/', [SellerController::class, 'index'])->name('index');
        Route::get('/export', [SellerController::class, 'export'])->name('export');
        Route::post('/bulk-action', [SellerController::class, 'bulkAction'])->name('bulk_action');
        Route::get('/{seller}', [SellerController::class, 'show'])->name('show');
        Route::post('/{seller}/approve', [SellerController::class, 'approve'])->name('approve');
        Route::post('/{seller}/reject', [SellerController::class, 'reject'])->name('reject');
        Route::post('/{seller}/suspend', [SellerController::class, 'suspend'])->name('suspend');
        Route::post('/{seller}/reactivate', [SellerController::class, 'reactivate'])->name('reactivate');
        Route::post('/{seller}/commission', [SellerController::class, 'updateCommission'])->name('commission.update');
        Route::get('/{seller}/documents/{document}/download', [SellerController::class, 'downloadDocument'])->name('documents.download');
        Route::post('/{seller}/documents/{document}/review', [SellerController::class, 'reviewDocument'])->name('documents.review');
        Route::post('/{seller}/request-documents', [SellerController::class, 'requestDocuments'])->name('request_documents');
        Route::post('/{seller}/settings', [SellerController::class, 'updateSettings'])->name('settings.update');
        Route::post('/{seller}/bank-accounts/{bankAccount}/verify', [SellerController::class, 'verifyBankAccount'])->name('bank.verify');
    });

    // Catalogue, Categories, Brands & Moderation
    Route::prefix('catalogue')->name('catalogue.')->group(function () {
        // Categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // Brands
        Route::prefix('brands')->name('brands.')->group(function () {
            Route::get('/', [BrandController::class, 'index'])->name('index');
            Route::post('/', [BrandController::class, 'store'])->name('store');
            Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
            Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
            Route::post('/{brand}/toggle-verify', [BrandController::class, 'toggleVerify'])->name('toggle_verify');
        });

        // Attributes & Values
        Route::prefix('attributes')->name('attributes.')->group(function () {
            Route::get('/', [ProductAttributeController::class, 'index'])->name('index');
            Route::post('/', [ProductAttributeController::class, 'store'])->name('store');
            Route::put('/{attribute}', [ProductAttributeController::class, 'update'])->name('update');
            Route::delete('/{attribute}', [ProductAttributeController::class, 'destroy'])->name('destroy');
            Route::post('/{attribute}/values', [ProductAttributeController::class, 'storeValue'])->name('values.store');
            Route::delete('/{attribute}/values/{value}', [ProductAttributeController::class, 'destroyValue'])->name('values.destroy');
        });

        // Catalog Standards & Governance (Units, Tax Slabs, Specifications, Image Rules)
        Route::prefix('standards')->name('standards.')->group(function () {
            Route::get('/', [CatalogStandardsController::class, 'index'])->name('index');
            Route::post('/units', [CatalogStandardsController::class, 'storeUnit'])->name('units.store');
            Route::delete('/units/{code}', [CatalogStandardsController::class, 'deleteUnit'])->name('units.destroy');
            Route::post('/image-requirements', [CatalogStandardsController::class, 'updateImageRequirements'])->name('images.update');
            Route::post('/tax-rules', [CatalogStandardsController::class, 'updateTaxConfiguration'])->name('taxes.update');
        });

        // Product Moderation Queue & Catalog Approvals
        Route::prefix('moderation')->name('moderation.')->group(function () {
            Route::get('/export', [ProductModerationController::class, 'export'])->name('export');
            Route::get('/import-template', [ProductModerationController::class, 'importTemplate'])->name('import_template');
            Route::get('/', [ProductModerationController::class, 'index'])->name('index');
            Route::post('/bulk-approve', [ProductModerationController::class, 'bulkApprove'])->name('bulk_approve');
            Route::post('/bulk-status', [ProductModerationController::class, 'bulkUpdateStatus'])->name('bulk_status');
            Route::post('/bulk-category', [ProductModerationController::class, 'bulkAssignCategory'])->name('bulk_category');
            Route::get('/{product}', [ProductModerationController::class, 'show'])->name('show');
            Route::get('/{product}/edit', [ProductModerationController::class, 'edit'])->name('edit');
            Route::put('/{product}', [ProductModerationController::class, 'update'])->name('update');
            Route::post('/{product}/approve', [ProductModerationController::class, 'approve'])->name('approve');
            Route::post('/{product}/reject', [ProductModerationController::class, 'reject'])->name('reject');
            Route::post('/{product}/block', [ProductModerationController::class, 'block'])->name('block');
            Route::post('/{product}/request-corrections', [ProductModerationController::class, 'requestCorrections'])->name('request_corrections');
        });
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::post('/test-integration', [SettingController::class, 'testIntegration'])->name('test_integration');
    });

    // Global Inventory Overview & Sync Logs
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/export', [InventoryController::class, 'export'])->name('export');
        Route::get('/movements/export', [InventoryController::class, 'exportMovements'])->name('movements.export');
        Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
        Route::post('/{inventory}/adjust', [InventoryController::class, 'adjust'])->name('adjust');
        Route::get('/sync-logs/export', [InventoryController::class, 'exportSyncLogs'])->name('sync_logs.export');
        Route::post('/sync-logs/prune', [InventoryController::class, 'pruneSyncLogs'])->name('sync_logs.prune');
        Route::get('/sync-logs', [InventoryController::class, 'syncLogs'])->name('sync_logs');
        Route::get('/', [InventoryController::class, 'index'])->name('index');
    });

    // Orders Central & Governance (Blueprint Section 21)
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/export', [\App\Http\Controllers\Admin\OrderController::class, 'export'])->name('export');
        Route::get('/', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('show');
        Route::post('/{order}/override-status', [\App\Http\Controllers\Admin\OrderController::class, 'overrideStatus'])->name('override_status');
        Route::post('/{order}/add-note', [\App\Http\Controllers\Admin\OrderController::class, 'addNote'])->name('add_note');
        Route::post('/{order}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('cancel');
        Route::post('/{order}/resolve-dispute', [\App\Http\Controllers\Admin\OrderController::class, 'resolveDispute'])->name('resolve_dispute');
        Route::post('/{order}/returns/{return}/approve', [\App\Http\Controllers\Admin\OrderController::class, 'approveReturn'])->name('approve_return');
        Route::post('/fulfilment-orders/{fulfilmentOrder}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancelSubOrder'])->name('cancel_sub_order');
        Route::get('/{order}/invoice', [\App\Http\Controllers\Admin\OrderController::class, 'invoice'])->name('invoice');
    });

    // Returns & RMA Governance (Blueprint Section 26)
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReturnController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\ReturnController::class, 'export'])->name('export');
        Route::get('/{return}', [\App\Http\Controllers\Admin\ReturnController::class, 'show'])->name('show');
        Route::post('/{return}/approve', [\App\Http\Controllers\Admin\ReturnController::class, 'approve'])->name('approve');
        Route::post('/{return}/reject', [\App\Http\Controllers\Admin\ReturnController::class, 'reject'])->name('reject');
        Route::post('/{return}/request-info', [\App\Http\Controllers\Admin\ReturnController::class, 'requestInfo'])->name('request_info');
        Route::post('/{return}/record-qc', [\App\Http\Controllers\Admin\ReturnController::class, 'recordQc'])->name('record_qc');
        Route::post('/{return}/initiate-refund', [\App\Http\Controllers\Admin\ReturnController::class, 'initiateRefund'])->name('initiate_refund');
        Route::post('/{return}/update-status', [\App\Http\Controllers\Admin\ReturnController::class, 'updateStatus'])->name('update_status');
        Route::post('/{return}/override', [\App\Http\Controllers\Admin\ReturnController::class, 'override'])->name('override');
        Route::post('/{return}/force-refund', [\App\Http\Controllers\Admin\ReturnController::class, 'forceRefund'])->name('force_refund');
    });

    // Commission Engine & Simulator (Blueprint Section 18)
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'store'])->name('store');
        Route::post('/controls', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'updateControls'])->name('controls.update');
        Route::get('/export-report', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'exportReport'])->name('export_report');
        Route::put('/{commission}', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'update'])->name('update');
        Route::delete('/{commission}', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'destroy'])->name('destroy');
        Route::post('/{commission}/toggle', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'toggle'])->name('toggle');
        Route::post('/simulate', [\App\Http\Controllers\Admin\CommissionRuleController::class, 'simulate'])->name('simulate');
    });

    // Seller Settlements & Payouts (Blueprint Section 18)
    Route::prefix('settlements')->name('settlements.')->group(function () {
        Route::get('/export', [SettlementController::class, 'exportSummary'])->name('export');
        Route::get('/', [SettlementController::class, 'index'])->name('index');
        Route::post('/generate', [SettlementController::class, 'generate'])->name('generate');
        Route::get('/{settlement}', [SettlementController::class, 'show'])->name('show');
        Route::post('/{settlement}/approve', [SettlementController::class, 'approve'])->name('approve');
        Route::post('/{settlement}/pay', [SettlementController::class, 'markPaid'])->name('pay');
        Route::post('/{settlement}/fail', [SettlementController::class, 'fail'])->name('fail');
        Route::post('/{settlement}/hold', [SettlementController::class, 'hold'])->name('hold');
        Route::post('/{settlement}/release', [SettlementController::class, 'release'])->name('release');
        Route::post('/{settlement}/adjustment', [SettlementController::class, 'addAdjustment'])->name('adjustment');
        Route::get('/{settlement}/pdf', [SettlementController::class, 'exportPdf'])->name('pdf');
        Route::get('/{settlement}/csv', [SettlementController::class, 'exportCsv'])->name('csv');
    });

    // Customer Relationship Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/export', [CustomerController::class, 'export'])->name('export');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::post('/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('toggle_status');
        Route::post('/{customer}/restrict', [CustomerController::class, 'restrict'])->name('restrict');
        Route::post('/{customer}/reactivate', [CustomerController::class, 'reactivate'])->name('reactivate');
        Route::post('/{customer}/suspicious-review', [CustomerController::class, 'reviewSuspicious'])->name('suspicious_review');
    });

    // Physical Dark Stores & Outlets
    Route::prefix('stores')->name('stores.')->group(function () {
        Route::get('/', [StoreController::class, 'index'])->name('index');
        Route::get('/export', [StoreController::class, 'export'])->name('export');
        Route::get('/map', [StoreController::class, 'map'])->name('map');
        Route::get('/{store}', [StoreController::class, 'show'])->name('show');
        Route::post('/{store}/approve', [StoreController::class, 'approve'])->name('approve');
        Route::post('/{store}/suspend', [StoreController::class, 'suspend'])->name('suspend');
        Route::post('/{store}/toggle-quick', [StoreController::class, 'toggleQuick'])->name('toggle_quick');
        Route::post('/{store}/update-radius', [StoreController::class, 'updateRadius'])->name('update_radius');
    });

    // Payments & Refunds Governance
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/export', [PaymentController::class, 'export'])->name('export');
        Route::get('/export-financials', [PaymentController::class, 'exportFinancials'])->name('export_financials');
        Route::get('/refunds', [PaymentController::class, 'refunds'])->name('refunds');
        Route::get('/refunds/export', [PaymentController::class, 'exportRefunds'])->name('refunds.export');
        Route::get('/webhooks/{webhookLog}', [PaymentController::class, 'webhookPayload'])->name('webhooks.payload');
        Route::post('/reconcile-batch', [PaymentController::class, 'reconcileBatch'])->name('reconcile_batch');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
        Route::post('/{payment}/reconcile', [PaymentController::class, 'reconcile'])->name('reconcile');
    });

    // Coupons & Promotional Discounts
    Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::get('/export', [CouponController::class, 'export'])->name('export');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
        Route::post('/{coupon}/toggle', [CouponController::class, 'toggle'])->name('toggle');
    });

    // Featured Marketing Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
        Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
        Route::post('/{campaign}/toggle', [CampaignController::class, 'toggle'])->name('toggle');
        Route::post('/{campaign}/publish', [CampaignController::class, 'publish'])->name('publish');
        Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/end', [CampaignController::class, 'end'])->name('end');
        Route::post('/{campaign}/participations/{participation}/approve', [CampaignController::class, 'approveParticipation'])->name('participations.approve');
        Route::post('/{campaign}/participations/{participation}/reject', [CampaignController::class, 'rejectParticipation'])->name('participations.reject');
    });

    // Visual Merchandising & Banners
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::post('/', [BannerController::class, 'store'])->name('store');
        Route::put('/{banner}', [BannerController::class, 'update'])->name('update');
        Route::delete('/{banner}', [BannerController::class, 'destroy'])->name('destroy');
        Route::post('/{banner}/toggle', [BannerController::class, 'toggle'])->name('toggle');
    });

    // Static CMS Content Pages
    Route::prefix('cms')->name('cms.')->group(function () {
        Route::get('/', [CmsPageController::class, 'index'])->name('index');
        Route::get('/create', [CmsPageController::class, 'create'])->name('create');
        Route::post('/', [CmsPageController::class, 'store'])->name('store');
        Route::get('/{page}/edit', [CmsPageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [CmsPageController::class, 'update'])->name('update');
        Route::delete('/{page}', [CmsPageController::class, 'destroy'])->name('destroy');
        Route::post('/{page}/toggle', [CmsPageController::class, 'toggle'])->name('toggle');
    });

    // Seller Advertising Management (Step 16)
    Route::prefix('ads')->name('ads.')->group(function () {
        Route::get('/export', [\App\Http\Controllers\Admin\AdManagementController::class, 'export'])->name('export');
        Route::get('/', [\App\Http\Controllers\Admin\AdManagementController::class, 'index'])->name('index');
        Route::get('/{campaign}', [\App\Http\Controllers\Admin\AdManagementController::class, 'show'])->name('show');
        Route::post('/{campaign}/approve', [\App\Http\Controllers\Admin\AdManagementController::class, 'approve'])->name('approve');
        Route::post('/{campaign}/reject', [\App\Http\Controllers\Admin\AdManagementController::class, 'reject'])->name('reject');
        Route::post('/{campaign}/status', [\App\Http\Controllers\Admin\AdManagementController::class, 'updateStatus'])->name('status');
        Route::put('/{campaign}/budget', [\App\Http\Controllers\Admin\AdManagementController::class, 'updateBudget'])->name('budget');
        Route::put('/{campaign}/metrics', [\App\Http\Controllers\Admin\AdManagementController::class, 'updateMetrics'])->name('metrics.update');
    });

    // Influencer Marketing Governance (Step 16)
    Route::prefix('influencers')->name('influencers.')->group(function () {
        Route::get('/export', [\App\Http\Controllers\Admin\InfluencerManagementController::class, 'export'])->name('export');
        Route::get('/', [\App\Http\Controllers\Admin\InfluencerManagementController::class, 'index'])->name('index');
        Route::post('/profiles', [\App\Http\Controllers\Admin\InfluencerManagementController::class, 'storeProfile'])->name('profiles.store');
        Route::put('/profiles/{profile}', [\App\Http\Controllers\Admin\InfluencerManagementController::class, 'updateProfile'])->name('profiles.update');
        Route::post('/profiles/{profile}/toggle-verification', [\App\Http\Controllers\Admin\InfluencerManagementController::class, 'toggleProfileVerification'])->name('profiles.toggle_verification');
        Route::put('/collaborations/{collaboration}/status', [\App\Http\Controllers\Admin\InfluencerManagementController::class, 'updateCollaborationStatus'])->name('collaborations.status');
        Route::put('/collaborations/{collaboration}/payment', [\App\Http\Controllers\Admin\InfluencerManagementController::class, 'updateCollaborationPayment'])->name('collaborations.payment');
    });

    // Seller Finance Governance (Step 17)
    Route::prefix('seller-finance')->name('finance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FinanceManagementController::class, 'index'])->name('index');
        Route::post('/settings', [\App\Http\Controllers\Admin\FinanceManagementController::class, 'updateSettings'])->name('settings.update');
        Route::put('/applications/{application}', [\App\Http\Controllers\Admin\FinanceManagementController::class, 'updateApplication'])->name('applications.update');
        Route::post('/applications/{application}/transactions', [\App\Http\Controllers\Admin\FinanceManagementController::class, 'recordTransaction'])->name('transactions.record');
    });

    // Platform Announcements & Seller Notices
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::get('/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('edit');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
        Route::post('/{announcement}/toggle', [AnnouncementController::class, 'togglePublish'])->name('toggle');
        Route::post('/{announcement}/archive', [AnnouncementController::class, 'archive'])->name('archive');
        Route::get('/{announcement}/reads', [AnnouncementController::class, 'reads'])->name('reads');
    });

    // Logistics & Shipping Rules
    Route::prefix('shipping')->name('shipping.')->group(function () {
        Route::get('/', [ShippingSettingController::class, 'index'])->name('index');
        Route::post('/', [ShippingSettingController::class, 'update'])->name('update');
        Route::post('/test-shiprocket', [ShippingSettingController::class, 'testShiprocket'])->name('test_shiprocket');
        Route::get('/failed-queue', [ShippingSettingController::class, 'failedQueue'])->name('failed_queue');
        Route::post('/shipments/{shipment}/retry', [ShippingSettingController::class, 'retryShipment'])->name('retry_shipment');
        Route::post('/sync-all', [ShippingSettingController::class, 'syncAllTracking'])->name('sync_all');
        Route::get('/shipments/{shipment}', [ShippingSettingController::class, 'showShipment'])->name('show_shipment');
        Route::post('/orders/{fulfilmentOrder}/create-shipment', [ShippingSettingController::class, 'createShipment'])->name('create_shipment');
        Route::post('/shipments/{shipment}/track', [ShippingSettingController::class, 'trackShipment'])->name('track_shipment');
        Route::post('/shipments/{shipment}/cancel', [ShippingSettingController::class, 'cancelShipment'])->name('cancel_shipment');
        Route::post('/serviceability-check', [ShippingSettingController::class, 'checkServiceability'])->name('check_serviceability');

        // Seller Pickup Locations Management
        Route::get('/pickup-locations', [\App\Http\Controllers\Admin\SellerPickupLocationController::class, 'index'])->name('pickup_locations.index');
        Route::post('/pickup-locations/{location}/verify', [\App\Http\Controllers\Admin\SellerPickupLocationController::class, 'verify'])->name('pickup_locations.verify');
        Route::post('/pickup-locations/{location}/reject', [\App\Http\Controllers\Admin\SellerPickupLocationController::class, 'reject'])->name('pickup_locations.reject');
        Route::put('/pickup-locations/{location}', [\App\Http\Controllers\Admin\SellerPickupLocationController::class, 'update'])->name('pickup_locations.update');
    });

    // Analytics & Business Intelligence
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
        Route::get('/export/{type}/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/{type}/xlsx', [ReportController::class, 'exportXlsx'])->name('export.xlsx');
    });

    // Support Desk & Escalations
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/export', [SupportTicketController::class, 'export'])->name('export');

        // Configurable Support Categories & Questions
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [SupportCategoryController::class, 'index'])->name('index');
            Route::post('/', [SupportCategoryController::class, 'store'])->name('store');
            Route::put('/{category}', [SupportCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [SupportCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/{category}/questions', [SupportCategoryController::class, 'storeQuestion'])->name('questions.store');
            Route::put('/questions/{question}', [SupportCategoryController::class, 'updateQuestion'])->name('questions.update');
            Route::delete('/questions/{question}', [SupportCategoryController::class, 'destroyQuestion'])->name('questions.destroy');
        });

        Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/request-info', [SupportTicketController::class, 'requestInfo'])->name('request_info');
        Route::post('/{ticket}/escalate', [SupportTicketController::class, 'escalate'])->name('escalate');
        Route::post('/{ticket}/assign', [SupportTicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/status', [SupportTicketController::class, 'status'])->name('status');
        Route::post('/{ticket}/priority', [SupportTicketController::class, 'updatePriority'])->name('priority');
        Route::post('/{ticket}/ai-reply', [SupportTicketController::class, 'generateAiReply'])->name('ai_reply');
    });

    // Seller Claims & Compensation Management
    Route::prefix('claims')->name('claims.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'index'])->name('index');
        Route::get('/{claim}', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'show'])->name('show');
        Route::post('/{claim}/internal-note', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'addInternalNote'])->name('internal_note');
        Route::post('/{claim}/request-info', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'requestInfo'])->name('request_info');
        Route::post('/{claim}/approve', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'approve'])->name('approve');
        Route::post('/{claim}/reject', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'reject'])->name('reject');
        Route::post('/{claim}/close', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'close'])->name('close');
        Route::post('/{claim}/escalate', [\App\Http\Controllers\Admin\ClaimManagementController::class, 'escalate'])->name('escalate');
    });

    // Admin Staff & RBAC Permissions
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/roles', [StaffController::class, 'roles'])->name('roles');
        Route::put('/roles/{role}', [StaffController::class, 'updateRolePermissions'])->name('roles.update');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
    });

    // Quick Commerce Operations & Mission Control (Blueprint Section 11)
    Route::prefix('quick-commerce')->name('quick.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\QuickCommerceOpsController::class, 'index'])->name('index');
        Route::get('/api-feed', [\App\Http\Controllers\Admin\QuickCommerceOpsController::class, 'apiFeed'])->name('api_feed');
        Route::post('/scan-timeouts', [\App\Http\Controllers\Admin\QuickCommerceOpsController::class, 'scanTimeouts'])->name('scan_timeouts');
        Route::post('/{fulfilmentOrder}/reroute', [\App\Http\Controllers\Admin\QuickCommerceOpsController::class, 'reroute'])->name('reroute');
    });


    // Security & Operational Audit Trail
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export', [AuditLogController::class, 'export'])->name('export');
        Route::post('/purge', [AuditLogController::class, 'purge'])->name('purge');
    });

    // Platform Security, Active Sessions & Incident Monitoring
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SecurityController::class, 'index'])->name('index');
        Route::post('/terminate-session/{sessionId}', [\App\Http\Controllers\Admin\SecurityController::class, 'terminateSession'])->name('terminate_session');
        Route::post('/terminate-all-other', [\App\Http\Controllers\Admin\SecurityController::class, 'terminateAllOtherSessions'])->name('terminate_all_other');
    });

    // Centralized Admin Notifications, In-App Alerts & Preferences
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('index');
        Route::get('/{id}/go', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'goToUrl'])->name('go');
        Route::post('/{id}/read', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'markRead'])->name('read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'markAllRead'])->name('mark_all_read');
        Route::delete('/clear-all', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'clearAll'])->name('clear_all');
        Route::post('/preferences', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'updatePreferences'])->name('preferences.update');

        // Notification Templates & Audit Logs (Blueprint Section 27)
        Route::get('/templates', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'index'])->name('templates.index');
        Route::put('/templates/{template}', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'update'])->name('templates.update');
        Route::post('/templates/{template}/toggle', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'toggle'])->name('templates.toggle');
        Route::post('/templates/{template}/preview', [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'preview'])->name('templates.preview');
        Route::get('/logs/export', [\App\Http\Controllers\Admin\NotificationLogController::class, 'export'])->name('logs.export');
        Route::post('/logs/retry-all', [\App\Http\Controllers\Admin\NotificationLogController::class, 'retryAllFailed'])->name('logs.retry_all');
        Route::get('/logs', [\App\Http\Controllers\Admin\NotificationLogController::class, 'index'])->name('logs.index');
        Route::post('/logs/{log}/retry', [\App\Http\Controllers\Admin\NotificationLogController::class, 'retry'])->name('logs.retry');
    });

    // SEO 301/302 Redirect Manager (Blueprint Section 32)
    Route::prefix('redirects')->name('redirects.')->group(function () {
        Route::get('/export', [\App\Http\Controllers\Admin\RedirectRuleController::class, 'export'])->name('export');
        Route::get('/', [\App\Http\Controllers\Admin\RedirectRuleController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\RedirectRuleController::class, 'store'])->name('store');
        Route::put('/{rule}', [\App\Http\Controllers\Admin\RedirectRuleController::class, 'update'])->name('update');
        Route::delete('/{rule}', [\App\Http\Controllers\Admin\RedirectRuleController::class, 'destroy'])->name('destroy');
        Route::post('/{rule}/toggle', [\App\Http\Controllers\Admin\RedirectRuleController::class, 'toggle'])->name('toggle');
        Route::post('/import-csv', [\App\Http\Controllers\Admin\RedirectRuleController::class, 'importCsv'])->name('import');
    });
});
