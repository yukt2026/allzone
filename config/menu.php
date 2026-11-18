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
                    'title' => 'Catalog Standards',
                    'route' => 'admin.catalogue.standards.index',
                    'active' => 'admin.catalogue.standards*',
                    'icon' => 'document-text',
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
                [
                    'title' => 'Seller Ad Campaigns',
                    'route' => 'admin.ads.index',
                    'active' => 'admin.ads*',
                    'icon' => 'sparkles',
                    'badge' => null,
                ],
                [
                    'title' => 'Influencer Management',
                    'route' => 'admin.influencers.index',
                    'active' => 'admin.influencers*',
                    'icon' => 'users',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Commerce & Operations',
            'items' => [
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
                    'title' => 'Seller Claims',
                    'route' => 'admin.claims.index',
                    'active' => 'admin.claims*',
                    'icon' => 'shield-check',
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
                    'title' => 'Global Inventory',
                    'route' => 'admin.inventory.index',
                    'active' => 'admin.inventory*',
                    'icon' => 'cube',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Financials & Settlements',
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
                    'active' => 'admin.support.index',
                    'icon' => 'chat-bubble',
                    'badge' => null,
                ],
                [
                    'title' => 'Support Topics & FAQs',
                    'route' => 'admin.support.categories.index',
                    'active' => 'admin.support.categories*',
                    'icon' => 'question-mark-circle',
                    'badge' => null,
                ],
                [
                    'title' => 'Notices & Announcements',
                    'route' => 'admin.announcements.index',
                    'active' => 'admin.announcements*',
                    'icon' => 'speakerphone',
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
                    'badge' => 'Shiprocket',
                    'badge_color' => 'indigo',
                ],
                [
                    'title' => 'Staff & Role Manager',
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
                    'title' => 'Security & Sessions',
                    'route' => 'admin.security.index',
                    'active' => 'admin.security*',
                    'icon' => 'shield-check',
                    'badge' => null,
                ],
                [
                    'title' => 'Notifications & Alerts',
                    'route' => 'admin.notifications.index',
                    'active' => 'admin.notifications.index',
                    'icon' => 'bell',
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
            'section' => 'Overview',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'route' => 'seller.dashboard',
                    'active' => 'seller.dashboard',
                    'icon' => 'home',
                    'badge' => null,
                ],
                [
                    'title' => 'Notifications',
                    'route' => 'seller.notifications.index',
                    'active' => 'seller.notifications*',
                    'icon' => 'bell',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Orders & Logistics',
            'items' => [
                [
                    'title' => 'Orders Console',
                    'route' => 'seller.orders.index',
                    'active' => 'seller.orders*',
                    'icon' => 'truck',
                    'badge' => null,
                ],
                [
                    'title' => 'Shipments & Logistics',
                    'route' => 'seller.shipments.index',
                    'active' => 'seller.shipments*',
                    'icon' => 'truck',
                    'badge' => 'Shiprocket',
                    'badge_color' => 'sky',
                ],
                [
                    'title' => 'Returns & RMA',
                    'route' => 'seller.returns.index',
                    'active' => 'seller.returns*',
                    'icon' => 'refresh',
                    'badge' => null,
                ],
                [
                    'title' => 'Claims Management',
                    'route' => 'seller.claims.index',
                    'active' => 'seller.claims*',
                    'icon' => 'document-text',
                    'badge' => null,
                ],
                [
                    'title' => 'Quality & Dispatch SLA',
                    'route' => 'seller.quality.index',
                    'active' => 'seller.quality*',
                    'icon' => 'sparkles',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Catalogue & Stock',
            'items' => [
                [
                    'title' => 'Product Catalogue',
                    'route' => 'seller.products.index',
                    'active' => 'seller.products.index',
                    'icon' => 'cube',
                    'badge' => null,
                ],
                [
                    'title' => 'Add New Product',
                    'route' => 'seller.products.create',
                    'active' => 'seller.products.create*',
                    'icon' => 'arrow-up-tray',
                    'badge' => null,
                ],
                [
                    'title' => 'Bulk Product Upload',
                    'route' => 'seller.products.bulk_upload',
                    'active' => 'seller.products.bulk_upload*',
                    'icon' => 'photograph',
                    'badge' => null,
                ],
                [
                    'title' => 'Inventory & Balances',
                    'route' => 'seller.inventory.index',
                    'active' => 'seller.inventory*',
                    'icon' => 'cube',
                    'badge' => null,
                ],
                [
                    'title' => 'Pricing & Recommendations',
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
            ],
        ],
        [
            'section' => 'Growth & Marketing',
            'items' => [
                [
                    'title' => 'Promotions Hub',
                    'route' => 'seller.promotions.sales',
                    'active' => 'seller.promotions*',
                    'icon' => 'ticket',
                    'badge' => null,
                    'children' => [
                        [
                            'title' => 'Sale Opt-in',
                            'route' => 'seller.promotions.sales',
                            'active' => 'seller.promotions.sales',
                        ],
                        [
                            'title' => 'Offer Opt-in',
                            'route' => 'seller.promotions.offers',
                            'active' => 'seller.promotions.offers',
                        ],
                    ],
                ],
                [
                    'title' => 'Advertisements',
                    'route' => 'seller.ads.index',
                    'active' => 'seller.ads*',
                    'icon' => 'megaphone',
                    'badge' => null,
                ],
                [
                    'title' => 'Influencer Marketing',
                    'route' => 'seller.influencer.index',
                    'active' => 'seller.influencer*',
                    'icon' => 'sparkles',
                    'badge' => null,
                ],
                [
                    'title' => 'Warehousing Services',
                    'route' => 'seller.warehouse.index',
                    'active' => 'seller.warehouse*',
                    'icon' => 'building-storefront',
                    'badge' => null,
                ],
            ],
        ],
        [
            'section' => 'Payments & Support',
            'items' => [
                [
                    'title' => 'Earnings & Payouts',
                    'route' => 'seller.payments.index',
                    'active' => 'seller.payments*',
                    'icon' => 'currency-rupee',
                    'badge' => null,
                ],
                [
                    'title' => 'Disbursements & Batches',
                    'route' => 'seller.settlements.index',
                    'active' => 'seller.settlements*',
                    'icon' => 'document-text',
                    'badge' => null,
                ],
                [
                    'title' => 'Seller Finance',
                    'route' => 'seller.finance.index',
                    'active' => 'seller.finance*',
                    'icon' => 'credit-card',
                    'badge' => null,
                ],
                [
                    'title' => 'Support Desk',
                    'route' => 'seller.support.index',
                    'active' => 'seller.support*',
                    'icon' => 'chat-bubble',
                    'badge' => null,
                ],
                [
                    'title' => 'Notices & Announcements',
                    'route' => 'seller.notices.index',
                    'active' => 'seller.notices*',
                    'icon' => 'bell',
                    'badge' => null,
                ],
                [
                    'title' => 'Settings & Bank Details',
                    'route' => 'seller.settings.bank',
                    'active' => 'seller.settings*',
                    'icon' => 'cog',
                    'badge' => null,
                ],
            ],
        ],
    ],

];
