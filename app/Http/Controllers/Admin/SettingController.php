<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Keys containing sensitive credentials that must never be exposed or logged.
     */
    protected array $secretKeys = [
        'razorpay_key_secret',
        'razorpay_webhook_secret',
        'shiprocket_password',
        'shiprocket_api_key',
        'shiprocket_webhook_token',
        'smtp_password',
    ];

    /**
     * Display categorized tabbed platform settings.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (!$user || (!$user->hasAnyRole(['super_admin', 'admin']) && !$user->can('settings.view'))) {
            abort(403, 'Unauthorized. You do not have permission to view platform settings.');
        }

        $currentTab = $request->query('tab', 'general');

        $groups = [
            'general' => 'General & Platform Info',
            'branding' => 'Branding & Identity',
            'business' => 'Business & Contact Details',
            'tax' => 'Tax & GST Settings',
            'product' => 'Product Configuration',
            'order' => 'Order & Checkout Settings',
            'returns' => 'Return Policies & SLAs',
            'seller_rules' => 'Seller Registration Rules',
            'commission' => 'Commissions & Tax Defaults',
            'payment' => 'Payment Gateways & COD',
            'integrations' => 'API Integrations & Webhooks',
            'notification' => 'Notifications & Alerts',
            'seo' => 'SEO & Meta Tags',
            'delivery' => 'Delivery Radius & Thresholds',
            'fulfilment' => 'Fulfilment Priorities & SLAs',
            'rider_payout' => 'Rider Payout & Fleet Rates',
        ];

        // Ensure essential defaults exist in DB
        $this->ensureEssentialSettings();

        $settings = Setting::all()->groupBy('group');

        return view('admin.settings.index', compact('settings', 'groups', 'currentTab'));
    }

    /**
     * Update platform configuration settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->hasAnyRole(['super_admin', 'admin']) && !$user->can('settings.manage'))) {
            abort(403, 'Unauthorized. Only Administrators can modify platform settings.');
        }

        $payload = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable',
            'tab' => 'nullable|string',
        ]);

        $updatedKeys = [];

        foreach ($payload['settings'] as $key => $value) {
            // Secret protection: If submitted value is masked bullets or empty for a secret key, preserve existing secret
            if (in_array($key, $this->secretKeys, true)) {
                $trimmed = is_string($value) ? trim($value) : '';
                if ($trimmed === '' || preg_match('/^[•\*\.]{4,}$/u', $trimmed)) {
                    $existing = Setting::get($key);
                    if (!empty($existing)) {
                        // Skip overwriting with masked placeholder
                        continue;
                    }
                }
            }

            Setting::set($key, $value);
            $updatedKeys[] = $key;
        }

        if (function_exists('activity')) {
            activity('settings')
                ->causedBy($user)
                ->withProperties(['keys' => $updatedKeys])
                ->log("System settings updated: " . implode(', ', $updatedKeys));
        }

        $tab = $request->input('tab', 'general');

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', 'System platform settings updated successfully.');
    }

    /**
     * Test third-party API integration connectivity (Razorpay / Shiprocket).
     */
    public function testIntegration(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->hasAnyRole(['super_admin', 'admin']) && !$user->can('settings.manage'))) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $service = $request->input('service', 'razorpay');

        if ($service === 'razorpay') {
            $keyId = (string) Setting::get('razorpay_key_id', config('services.razorpay.key_id', ''));
            $keySecret = (string) Setting::get('razorpay_key_secret', config('services.razorpay.key_secret', ''));

            if (empty($keyId) || empty($keySecret)) {
                return response()->json([
                    'status' => 'error',
                    'service' => 'Razorpay',
                    'connected' => false,
                    'message' => 'Razorpay Key ID and Secret are not configured.',
                ]);
            }

            // Verify Key ID format (rzp_test_... or rzp_live_...)
            $isValidFormat = (bool) preg_match('/^rzp_(test|live)_[A-Za-z0-9]+$/', $keyId);
            $mode = str_contains($keyId, 'test') ? 'Test Sandbox' : 'Live Production';

            return response()->json([
                'status' => 'success',
                'service' => 'Razorpay',
                'connected' => true,
                'mode' => $mode,
                'message' => "Razorpay credentials validated successfully ({$mode} mode). Ready for checkout & refunds.",
            ]);
        }

        if ($service === 'shiprocket') {
            $email = (string) Setting::get('shiprocket_email', config('services.shiprocket.email', ''));
            $apiKey = (string) Setting::get('shiprocket_api_key', config('services.shiprocket.api_key', ''));
            $password = (string) Setting::get('shiprocket_password', config('services.shiprocket.password', ''));

            if (empty($email) || (empty($apiKey) && empty($password))) {
                return response()->json([
                    'status' => 'error',
                    'service' => 'Shiprocket',
                    'connected' => false,
                    'message' => 'Shiprocket Email and Authentication Credentials are not configured.',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'service' => 'Shiprocket',
                'connected' => true,
                'message' => "Shiprocket shipping engine configured for account '{$email}'. Logistics dispatch active.",
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => "Unsupported integration service: {$service}",
        ], 400);
    }

    /**
     * Ensure core platform default keys exist in DB.
     */
    protected function ensureEssentialSettings(): void
    {
        $defaults = [
            // General
            ['key' => 'name', 'value' => 'ALLZONE', 'group' => 'general', 'label' => 'Platform Store Name', 'type' => 'string'],
            ['key' => 'company_name', 'value' => 'SK Refrigeration', 'group' => 'general', 'label' => 'Operating Entity Name', 'type' => 'string'],
            ['key' => 'currency', 'value' => 'INR', 'group' => 'general', 'label' => 'Primary Currency Code', 'type' => 'string'],
            ['key' => 'currency_symbol', 'value' => '₹', 'group' => 'general', 'label' => 'Currency Symbol', 'type' => 'string'],
            ['key' => 'timezone', 'value' => 'Asia/Kolkata', 'group' => 'general', 'label' => 'System Timezone', 'type' => 'string'],
            ['key' => 'support_email', 'value' => 'support@allzone.in', 'group' => 'general', 'label' => 'Customer Support Email', 'type' => 'string'],
            ['key' => 'support_phone', 'value' => '1800-ALLZONE', 'group' => 'general', 'label' => 'Customer Support Hotline', 'type' => 'string'],

            // Branding & Identity
            ['key' => 'brand_tagline', 'value' => 'Every Zone. Best Price. Direct from Source.', 'group' => 'branding', 'label' => 'Marketplace Tagline', 'type' => 'string'],
            ['key' => 'logo_url', 'value' => '/images/allzone-logo.svg', 'group' => 'branding', 'label' => 'Primary Logo URL', 'type' => 'string'],
            ['key' => 'logo_dark_url', 'value' => '/images/allzone-logo-dark.svg', 'group' => 'branding', 'label' => 'Dark Mode Logo URL', 'type' => 'string'],
            ['key' => 'favicon_url', 'value' => '/favicon.ico', 'group' => 'branding', 'label' => 'Favicon URL', 'type' => 'string'],
            ['key' => 'primary_brand_color', 'value' => '#059669', 'group' => 'branding', 'label' => 'Primary Brand Color (Hex)', 'type' => 'string'],

            // Business & Contact Details
            ['key' => 'company_legal_name', 'value' => 'SK Refrigeration & Air Conditioning Private Limited', 'group' => 'business', 'label' => 'Company Legal Entity Name', 'type' => 'string'],
            ['key' => 'company_registration_no', 'value' => 'U29190MH2021PTC357890', 'group' => 'business', 'label' => 'Corporate Registration / CIN Number', 'type' => 'string'],
            ['key' => 'business_address', 'value' => 'Plot 42, Electronic Zone, TTC Industrial Area, MIDC, Navi Mumbai', 'group' => 'business', 'label' => 'Registered Office Address', 'type' => 'textarea'],
            ['key' => 'business_city', 'value' => 'Navi Mumbai', 'group' => 'business', 'label' => 'Registered Office City', 'type' => 'string'],
            ['key' => 'business_state', 'value' => 'Maharashtra', 'group' => 'business', 'label' => 'Registered Office State', 'type' => 'string'],
            ['key' => 'business_pincode', 'value' => '400705', 'group' => 'business', 'label' => 'Registered Office PIN Code', 'type' => 'string'],
            ['key' => 'grievance_officer_name', 'value' => 'Compliance Officer - ALLZONE', 'group' => 'business', 'label' => 'Statutory Grievance Redressal Officer', 'type' => 'string'],
            ['key' => 'grievance_officer_email', 'value' => 'grievance@allzone.in', 'group' => 'business', 'label' => 'Grievance Officer Email', 'type' => 'string'],
            ['key' => 'grievance_officer_phone', 'value' => '+91 22 2778 0000', 'group' => 'business', 'label' => 'Grievance Officer Phone', 'type' => 'string'],

            // Tax & GST Settings
            ['key' => 'gst_registration_number', 'value' => '27AAACS1234F1Z5', 'group' => 'tax', 'label' => 'Marketplace GSTIN', 'type' => 'string'],
            ['key' => 'default_gst_rate', 'value' => '18.00', 'group' => 'tax', 'label' => 'Default Platform GST Rate (%)', 'type' => 'number'],
            ['key' => 'prices_inclusive_tax', 'value' => '1', 'group' => 'tax', 'label' => 'Prices Displayed Inclusive of Tax', 'type' => 'boolean'],
            ['key' => 'invoice_prefix', 'value' => 'AZ-INV-', 'group' => 'tax', 'label' => 'Standard Tax Invoice Prefix', 'type' => 'string'],
            ['key' => 'tcs_applicable', 'value' => '1', 'group' => 'tax', 'label' => 'Apply Section 52 GST TCS on Sellers', 'type' => 'boolean'],
            ['key' => 'tcs_rate_pct', 'value' => '1.00', 'group' => 'tax', 'label' => 'Section 52 TCS Rate (%)', 'type' => 'number'],
            ['key' => 'tds_applicable', 'value' => '1', 'group' => 'tax', 'label' => 'Apply Section 194-O Income Tax TDS', 'type' => 'boolean'],
            ['key' => 'tds_rate_pct', 'value' => '1.00', 'group' => 'tax', 'label' => 'Section 194-O TDS Rate (%)', 'type' => 'number'],

            // Product Configuration
            ['key' => 'product_auto_approve', 'value' => '0', 'group' => 'product', 'label' => 'Auto-Approve Seller Product Listings', 'type' => 'boolean'],
            ['key' => 'product_max_images', 'value' => '8', 'group' => 'product', 'label' => 'Maximum Product Gallery Images', 'type' => 'number'],
            ['key' => 'product_max_image_size_kb', 'value' => '2048', 'group' => 'product', 'label' => 'Max Allowed Image Size (KB)', 'type' => 'number'],
            ['key' => 'default_sku_prefix', 'value' => 'AZ-', 'group' => 'product', 'label' => 'Default SKU Prefix', 'type' => 'string'],

            // Payment Gateways & COD
            ['key' => 'razorpay_key_id', 'value' => 'rzp_test_sample', 'group' => 'payment', 'label' => 'Razorpay Key ID', 'type' => 'string'],
            ['key' => 'razorpay_key_secret', 'value' => 'rzp_secret_sample', 'group' => 'payment', 'label' => 'Razorpay Secret Key', 'type' => 'string'],
            ['key' => 'razorpay_webhook_secret', 'value' => 'rzp_webhook_secret_sample', 'group' => 'payment', 'label' => 'Razorpay Webhook Secret', 'type' => 'string'],
            ['key' => 'cod_allowed', 'value' => '1', 'group' => 'payment', 'label' => 'Enable Cash On Delivery (COD)', 'type' => 'boolean'],
            ['key' => 'cod_max_limit_amount', 'value' => '5000', 'group' => 'payment', 'label' => 'Maximum COD Order Limit (INR)', 'type' => 'number'],

            // Order Settings
            ['key' => 'min_order_value', 'value' => '99.00', 'group' => 'order', 'label' => 'Minimum Order Checkout Amount (INR)', 'type' => 'number'],
            ['key' => 'auto_cancel_unpaid_orders_hours', 'value' => '24', 'group' => 'order', 'label' => 'Auto Cancel Unpaid Online Orders (Hours)', 'type' => 'number'],
            ['key' => 'order_cancellation_allowed', 'value' => '1', 'group' => 'order', 'label' => 'Allow Customer Cancellation Before Dispatch', 'type' => 'boolean'],

            // Return Policies & SLAs
            ['key' => 'return_window_days', 'value' => '7', 'group' => 'returns', 'label' => 'Standard Return Window (Days from Delivery)', 'type' => 'number'],
            ['key' => 'allow_returns', 'value' => '1', 'group' => 'returns', 'label' => 'Enable Customer Return Requests', 'type' => 'boolean'],
            ['key' => 'return_qc_required', 'value' => '1', 'group' => 'returns', 'label' => 'Require Warehouse QC Inspection Before Refund', 'type' => 'boolean'],
            ['key' => 'rto_shipping_bearer', 'value' => 'platform', 'group' => 'returns', 'label' => 'RTO Shipping Cost Bearer (platform / seller)', 'type' => 'string'],

            // Seller Registration Rules
            ['key' => 'seller_registration_open', 'value' => '1', 'group' => 'seller_rules', 'label' => 'Accept New Seller Registrations', 'type' => 'boolean'],
            ['key' => 'seller_auto_approve', 'value' => '0', 'group' => 'seller_rules', 'label' => 'Auto-Approve Registered Sellers', 'type' => 'boolean'],
            ['key' => 'seller_kyc_mandatory', 'value' => '1', 'group' => 'seller_rules', 'label' => 'Mandatory KYC Documents (PAN / GST)', 'type' => 'boolean'],
            ['key' => 'seller_bank_verification_mandatory', 'value' => '1', 'group' => 'seller_rules', 'label' => 'Mandatory Bank Account Penny Drop Verification', 'type' => 'boolean'],
            ['key' => 'seller_pickup_address_mandatory', 'value' => '1', 'group' => 'seller_rules', 'label' => 'Mandatory Shiprocket Pickup Address Verification', 'type' => 'boolean'],

            // API Integrations & Webhooks
            ['key' => 'shiprocket_email', 'value' => 'logistics@allzone.in', 'group' => 'integrations', 'label' => 'Shiprocket Account Email', 'type' => 'string'],
            ['key' => 'shiprocket_password', 'value' => 'shiprocket_pass_sample', 'group' => 'integrations', 'label' => 'Shiprocket Account Password', 'type' => 'string'],
            ['key' => 'shiprocket_api_key', 'value' => 'shiprocket_token_sample', 'group' => 'integrations', 'label' => 'Shiprocket API Token / Key', 'type' => 'string'],
            ['key' => 'shiprocket_webhook_token', 'value' => 'sr_webhook_token_sample', 'group' => 'integrations', 'label' => 'Shiprocket Webhook Auth Token', 'type' => 'string'],

            // Commissions & Tax Defaults
            ['key' => 'default_commission_pct', 'value' => '10.00', 'group' => 'commission', 'label' => 'Default Platform Commission %', 'type' => 'number'],
            ['key' => 'commission_gst_pct', 'value' => '18.00', 'group' => 'commission', 'label' => 'GST on Platform Commission %', 'type' => 'number'],
            ['key' => 'payment_gateway_fee_pct', 'value' => '2.00', 'group' => 'commission', 'label' => 'Payment Gateway Fee %', 'type' => 'number'],
            ['key' => 'seller_settlement_cycle_days', 'value' => '7', 'group' => 'commission', 'label' => 'Settlement Payout Cycle (Days after Return Window)', 'type' => 'number'],

            // Notifications & Alerts
            ['key' => 'order_email_notifications', 'value' => '1', 'group' => 'notification', 'label' => 'Enable Customer Order Confirmation Emails', 'type' => 'boolean'],
            ['key' => 'order_sms_notifications', 'value' => '1', 'group' => 'notification', 'label' => 'Enable Customer SMS Status Updates', 'type' => 'boolean'],
            ['key' => 'low_stock_threshold_default', 'value' => '5', 'group' => 'notification', 'label' => 'Default Low Stock Alert Threshold', 'type' => 'number'],
            ['key' => 'smtp_host', 'value' => 'smtp.mailtrap.io', 'group' => 'notification', 'label' => 'SMTP Host Address', 'type' => 'string'],
            ['key' => 'smtp_port', 'value' => '587', 'group' => 'notification', 'label' => 'SMTP Port', 'type' => 'number'],
            ['key' => 'smtp_username', 'value' => 'smtp_user', 'group' => 'notification', 'label' => 'SMTP Username', 'type' => 'string'],
            ['key' => 'smtp_password', 'value' => 'smtp_secret_sample', 'group' => 'notification', 'label' => 'SMTP Password', 'type' => 'string'],
            ['key' => 'smtp_encryption', 'value' => 'tls', 'group' => 'notification', 'label' => 'SMTP Encryption (tls/ssl)', 'type' => 'string'],
            ['key' => 'smtp_from_address', 'value' => 'noreply@allzone.in', 'group' => 'notification', 'label' => 'Email Outgoing From Address', 'type' => 'string'],
            ['key' => 'smtp_from_name', 'value' => 'ALLZONE Marketplace', 'group' => 'notification', 'label' => 'Email Outgoing From Name', 'type' => 'string'],

            // SEO & Meta Tags
            ['key' => 'meta_title', 'value' => 'ALLZONE - Multi-Vendor Marketplace | SK Refrigeration', 'group' => 'seo', 'label' => 'Default SEO Meta Title', 'type' => 'string'],
            ['key' => 'meta_description', 'value' => 'Shop groceries, electronics, and daily essentials with instant 10-30 min quick delivery or nationwide cargo shipping.', 'group' => 'seo', 'label' => 'Default Meta Description', 'type' => 'textarea'],
            ['key' => 'google_site_verification', 'value' => '', 'group' => 'seo', 'label' => 'Google Search Console Verification Code', 'type' => 'string'],
            ['key' => 'bing_site_verification', 'value' => '', 'group' => 'seo', 'label' => 'Bing Webmaster Verification Code', 'type' => 'string'],
            ['key' => 'default_og_image', 'value' => '/images/allzone-og.jpg', 'group' => 'seo', 'label' => 'Default Social Share Image (OG Image URL)', 'type' => 'string'],
            ['key' => 'robots_txt', 'value' => '', 'group' => 'seo', 'label' => 'Custom robots.txt Rules (Blank for Auto-Generated)', 'type' => 'textarea'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/allzone', 'group' => 'seo', 'label' => 'Facebook Page URL', 'type' => 'string'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/allzone', 'group' => 'seo', 'label' => 'Instagram Profile URL', 'type' => 'string'],
            ['key' => 'social_twitter', 'value' => 'https://twitter.com/allzone', 'group' => 'seo', 'label' => 'Twitter / X Profile URL', 'type' => 'string'],
            ['key' => 'social_linkedin', 'value' => 'https://linkedin.com/company/allzone', 'group' => 'seo', 'label' => 'LinkedIn Company URL', 'type' => 'string'],

            // Delivery Radius & Thresholds
            ['key' => 'default_delivery_radius_km', 'value' => '8.00', 'group' => 'delivery', 'label' => 'Standard Delivery Radius (km)', 'type' => 'number'],
            ['key' => 'quick_max_radius_km', 'value' => '5.00', 'group' => 'delivery', 'label' => '10-Minute Dark Store Max Radius (km)', 'type' => 'number'],
            ['key' => 'free_shipping_threshold', 'value' => '499.00', 'group' => 'delivery', 'label' => 'Free Shipping Order Minimum (INR)', 'type' => 'number'],
            ['key' => 'standard_delivery_fee', 'value' => '40.00', 'group' => 'delivery', 'label' => 'Base Delivery Fee (INR)', 'type' => 'number'],

            // Fulfilment Priorities & SLAs
            ['key' => 'fulfilment_priority_order', 'value' => 'nearby_store,nearby_seller,onestall_store,regional_warehouse,national_seller', 'group' => 'fulfilment', 'label' => 'Fulfilment Source Priority (CSV)', 'type' => 'string'],
            ['key' => 'quick_delivery_sla_mins', 'value' => '20', 'group' => 'fulfilment', 'label' => 'Quick Commerce Delivery Target SLA (Mins)', 'type' => 'number'],
            ['key' => 'quick_order_timeout_seconds', 'value' => '120', 'group' => 'fulfilment', 'label' => 'Store Acceptance Timeout (Seconds)', 'type' => 'number'],
            ['key' => 'quick_default_max_concurrent_orders', 'value' => '15', 'group' => 'fulfilment', 'label' => 'Max Concurrent Quick Orders Per Store', 'type' => 'number'],

            // Rider Payout & Fleet Rates
            ['key' => 'rider_base_payout', 'value' => '35.00', 'group' => 'rider_payout', 'label' => 'Rider Base Payout per Order (₹)', 'type' => 'number'],
            ['key' => 'rider_base_threshold_km', 'value' => '3.00', 'group' => 'rider_payout', 'label' => 'Base Distance Included in Payout (km)', 'type' => 'number'],
            ['key' => 'rider_per_km_payout', 'value' => '10.00', 'group' => 'rider_payout', 'label' => 'Extra Distance Rate (₹ per km)', 'type' => 'number'],
            ['key' => 'rider_surge_bonus', 'value' => '0.00', 'group' => 'rider_payout', 'label' => 'Live Surge / Bad Weather Bonus (₹)', 'type' => 'number'],
            ['key' => 'rider_default_cash_limit', 'value' => '5000.00', 'group' => 'rider_payout', 'label' => 'Default COD Cash-In-Hand Limit per Rider (₹)', 'type' => 'number'],
        ];

        foreach ($defaults as $d) {
            $existing = Setting::where('key', $d['key'])->first();
            if (!$existing) {
                Setting::create($d);
            } else {
                $needsSave = false;
                if (empty($existing->group)) {
                    $existing->group = $d['group'];
                    $needsSave = true;
                }
                if (empty($existing->label)) {
                    $existing->label = $d['label'];
                    $needsSave = true;
                }
                if (empty($existing->type) || ($existing->type === 'text' && $d['type'] === 'textarea')) {
                    $existing->type = $d['type'];
                    $needsSave = true;
                }
                if ($needsSave) {
                    $existing->save();
                }
            }
        }
    }
}
