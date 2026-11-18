<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ALLZONE Data-Driven Navigation Menus
    |--------------------------------------------------------------------------
    |
    | Used by layouts/dashboard.blade.php to dynamically render sidebar
    | navigation for both Admin and Seller portals with role gating.
    |
    */

    'admin' => [
        [
            'section' => 'Overview & Command',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'route' => 'admin.dashboard',
                    'active' => 'admin.dashboard*',
                    'icon' => 'home',
                    'badge' => null,
                ],
                [
                    'title' => 'Reports & Analytics',
                    'route' => 'admin.reports.index',
                    'active' => 'admin.reports*',
                    'icon' => 'chart-bar',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Catalogue & Merchandising',
            'items' => [
                [
                    'title' => 'Product Moderation',
                    'route' => 'admin.catalogue.moderation.index',
                    'active' => 'admin.catalogue.moderation*',
                    'icon' => 'cube',
                    'badge' => null,
                ],
                [
                    'title' => 'Categories Tree',
                    'route' => 'admin.catalogue.categories.index',
                    'active' => 'admin.catalogue.categories*',
                    'icon' => 'puzzle',
                    'badge' => null,
                ],
                [
                    'title' => 'Brands Registry',
                    'route' => 'admin.catalogue.brands.index',
                    'active' => 'admin.catalogue.brands*',
                    'icon' => 'tag',
                    'badge' => null,
                ],
                [
                    'title' => 'Attributes Manager',
                    'route' => 'admin.catalogue.attributes.index',
                    'active' => 'admin.catalogue.attributes*',
                    'icon' => 'cog',
                    'badge' => null,
                ],
                [
                    'title' => 'Banners & Carousels',
                    'route' => 'admin.banners.index',
                    'active' => 'admin.banners*',
                    'icon' => 'photograph',
                    'badge' => null,
                ],
                [
                    'title' => 'Coupons & Discounts',
                    'route' => 'admin.coupons.index',
                    'active' => 'admin.coupons*',
                    'icon' => 'ticket',
                    'badge' => null,
                ],
                [
                    'title' => 'Featured Campaigns',
                    'route' => 'admin.campaigns.index',
                    'active' => 'admin.campaigns*',
                    'icon' => 'sparkles',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Commerce & Operations',
            'items' => [
                [
                    'title' => 'Quick Commerce Ops',
                    'route' => 'admin.quick.index',
                    'active' => 'admin.quick*',
                    'icon' => 'bolt',
                    'badge' => 'Live',
                    'badge_color' => 'amber',
                ],
                [
                    'title' => 'Orders Central',
                    'route' => 'admin.orders.index',
                    'active' => 'admin.orders*',
                    'icon' => 'truck',
                    'badge' => null,
                ],
                [
                    'title' => 'Returns & RMA',
                    'route' => 'admin.returns.index',
                    'active' => 'admin.returns*',
                    'icon' => 'refresh',
                    'badge' => null,
                ],
                [
                    'title' => 'Customers',
                    'route' => 'admin.customers.index',
                    'active' => 'admin.customers*',
                    'icon' => 'users',
                    'badge' => null,
                ],
                [
                    'title' => 'Sellers & Vendors',
                    'route' => 'admin.sellers.index',
                    'active' => 'admin.sellers*',
                    'icon' => 'users',
                    'badge' => null,
                    'badge_color' => 'slate',
                ],
                [
                    'title' => 'Stores & Dark Stores',
                    'route' => 'admin.stores.index',
                    'active' => 'admin.stores*',
                    'icon' => 'store',
                    'badge' => null,
                ],
                [
                    'title' => 'Stores Live Map',
                    'route' => 'admin.stores.map',
                    'active' => 'admin.stores.map',
                    'icon' => 'map-pin',
                    'badge' => 'Geo',
                    'badge_color' => 'emerald',
                ],
                [
                    'title' => 'Global Inventory',
                    'route' => 'admin.inventory.index',
                    'active' => 'admin.inventory*',
                    'icon' => 'cube',
                    'badge' => null,
                ],
                [
                    'title' => 'Integration Sync Logs',
                    'route' => 'admin.inventory.sync_logs',
                    'active' => 'admin.inventory.sync_logs*',
                    'icon' => 'refresh',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Finance & Settlements',
            'items' => [
                [
                    'title' => 'Payments & Transactions',
                    'route' => 'admin.payments.index',
                    'active' => 'admin.payments.index',
                    'icon' => 'credit-card',
                    'badge' => null,
                ],
                [
                    'title' => 'Refunds Audit Ledger',
                    'route' => 'admin.payments.refunds',
                    'active' => 'admin.payments.refunds',
                    'icon' => 'refresh',
                    'badge' => null,
                ],
                [
                    'title' => 'Commission Rules & Simulator',
                    'route' => 'admin.commissions.index',
                    'active' => 'admin.commissions*',
                    'icon' => 'tag',
                    'badge' => null,
                ],
                [
                    'title' => 'Seller Settlements',
                    'route' => 'admin.settlements.index',
                    'active' => 'admin.settlements*',
                    'icon' => 'currency-rupee',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Support & Content',
            'items' => [
                [
                    'title' => 'Support Desk Tickets',
                    'route' => 'admin.support.index',
                    'active' => 'admin.support*',
                    'icon' => 'chat-bubble',
                    'badge' => null,
                ],
                [
                    'title' => 'Static CMS Pages',
                    'route' => 'admin.cms.index',
                    'active' => 'admin.cms*',
                    'icon' => 'document-text',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Governance & Configuration',
            'items' => [
                [
                    'title' => 'Platform Settings',
                    'route' => 'admin.settings.index',
                    'active' => 'admin.settings*',
                    'icon' => 'cog',
                    'badge' => null,
                ],
                [
                    'title' => 'Shipping & Logistics',
                    'route' => 'admin.shipping.index',
                    'active' => 'admin.shipping*',
                    'icon' => 'truck',
                    'badge' => null,
                ],
                [
                    'title' => 'Staff & Permissions',
                    'route' => 'admin.staff.index',
                    'active' => 'admin.staff*',
                    'icon' => 'shield-check',
                    'badge' => null,
                ],
                [
                    'title' => 'Security Audit Logs',
                    'route' => 'admin.audit-logs.index',
                    'active' => 'admin.audit-logs*',
                    'icon' => 'lock-closed',
                    'badge' => null,
                ],
                [
                    'title' => 'Notification Templates',
                    'route' => 'admin.notifications.templates.index',
                    'active' => 'admin.notifications.templates*',
                    'icon' => 'document-text',
                    'badge' => null,
                ],
                [
                    'title' => 'Notification Logs',
                    'route' => 'admin.notifications.logs.index',
                    'active' => 'admin.notifications.logs*',
                    'icon' => 'chat-bubble',
                    'badge' => null,
                ],
                [
                    'title' => 'SEO & 301 Redirects',
                    'route' => 'admin.redirects.index',
                    'active' => 'admin.redirects*',
                    'icon' => 'arrow-path',
                    'badge' => null,
                ],
            ],
        ],
    ],

    'seller' => [
        [
            'section' => '',
            'items' => [
                [
                    'title' => 'Home',
                    'route' => 'seller.dashboard',
                    'active' => 'seller.dashboard*',
                    'icon' => 'home',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Manage Business',
            'items' => [
                [
                    'title' => 'Orders',
                    'route' => 'seller.orders.index',
                    'active' => 'seller.orders*',
                    'icon' => 'truck',
                    'badge' => 'NEW',
                    'badge_color' => 'pink',
                    'children' => [
                        [
                            'title' => 'Manage Orders',
                            'route' => 'seller.orders.index',
                            'active' => 'seller.orders.index',
                        ],
                        [
                            'title' => 'Dispatch Performance',
                            'route' => 'seller.orders.dispatch_performance',
                            'active' => 'seller.orders.dispatch_performance',
                            'badge' => 'NEW',
                            'badge_color' => 'pink',
                        ],
                    ],
                ],
                [
                    'title' => 'Returns',
                    'route' => 'seller.returns.index',
                    'active' => 'seller.returns*',
                    'icon' => 'refresh',
                    'badge' => null,
                ],
                [
                    'title' => 'Pricing',
                    'route' => 'seller.pricing.index',
                    'active' => 'seller.pricing*',
                    'icon' => 'currency-rupee',
                    'badge' => null,
                    'children' => [
                        [
                            'title' => 'Manage Pricing',
                            'route' => 'seller.pricing.index',
                            'active' => 'seller.pricing.index',
                        ],
                        [
                            'title' => 'Reduce RTOs & Returns',
                            'route' => 'seller.pricing.reduce_rto',
                            'active' => 'seller.pricing.reduce_rto',
                        ],
                    ],
                ],
                [
                    'title' => 'Claims',
                    'route' => 'seller.claims.index',
                    'active' => 'seller.claims*',
                    'icon' => 'document-text',
                    'badge' => null,
                ],
                [
                    'title' => 'Inventory',
                    'route' => 'seller.inventory.index',
                    'active' => 'seller.inventory*',
                    'icon' => 'cube',
                    'badge' => 'Out of Stock (5)',
                    'badge_color' => 'rose',
                ],
                [
                    'title' => 'Catalog Uploads',
                    'route' => 'seller.products.create',
                    'active' => 'seller.products.create*',
                    'icon' => 'arrow-up-tray',
                    'badge' => null,
                ],
                [
                    'title' => 'Image Bulk Upload',
                    'route' => 'seller.products.bulk_upload',
                    'active' => 'seller.products.bulk_upload*',
                    'icon' => 'photograph',
                    'badge' => null,
                ],
                [
                    'title' => 'Quality',
                    'route' => 'seller.quality.index',
                    'active' => 'seller.quality*',
                    'icon' => 'sparkles',
                    'badge' => null,
                ],
                [
                    'title' => 'Payments',
                    'route' => 'seller.payments.index',
                    'active' => 'seller.payments*',
                    'icon' => 'currency-rupee',
                    'badge' => null,
                ],
                [
                    'title' => 'Warehouse',
                    'route' => 'seller.warehouse.index',
                    'active' => 'seller.warehouse*',
                    'icon' => 'building-storefront',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Boost Sales',
            'items' => [
                [
                    'title' => 'Influencer Marketing',
                    'route' => 'seller.influencer.index',
                    'active' => 'seller.influencer*',
                    'icon' => 'sparkles',
                    'badge' => null,
                ],
                [
                    'title' => 'Advertisement',
                    'route' => 'seller.ads.index',
                    'active' => 'seller.ads*',
                    'icon' => 'megaphone',
                    'badge' => null,
                ],
                [
                    'title' => 'Promotions',
                    'route' => 'seller.pricing.index',
                    'active' => 'seller.promotions*',
                    'icon' => 'ticket',
                    'badge' => null,
                ],
                [
                    'title' => 'Instant Cash',
                    'route' => 'seller.wallet.index',
                    'active' => 'seller.wallet*',
                    'icon' => 'currency-rupee',
                    'badge' => null,
                ],
                [
                    'title' => 'Pay Later Lite',
                    'route' => 'seller.orders.index',
                    'active' => 'seller.pay_later*',
                    'icon' => 'credit-card',
                    'badge' => 'NEW',
                    'badge_color' => 'pink',
                ],
            ],
        ],
        [
            'section' => 'Performance',
            'items' => [
                [
                    'title' => 'Business Dashboard',
                    'route' => 'seller.stores.reports',
                    'active' => 'seller.stores.reports*',
                    'icon' => 'chart-bar',
                    'badge' => null,
                ],
            ],
        ],
    ],

];
