<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AddressType;
use App\Enums\CommissionRuleScope;
use App\Enums\DeliveryZoneType;
use App\Enums\InventoryLocationType;
use App\Enums\SellerDocumentStatus;
use App\Enums\SellerDocumentType;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\StoreStaffRole;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InventoryLocation;
use App\Models\Seller;
use App\Models\SellerBankAccount;
use App\Models\SellerDocument;
use App\Models\SellerPickupLocation;
use App\Models\Setting;
use App\Models\Store;
use App\Models\StoreDeliveryZone;
use App\Models\StoreLocation;
use App\Models\StoreStaff;
use App\Models\StoreTiming;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class CleanMarketplaceDummyDataCommand extends Command
{
    protected $signature = 'marketplace:clean-dummy-data {--force : Force cleanup without confirmation}';
    protected $description = 'Purge all marketplace dummy data, reset all transactional tables to empty, and keep only clean login accounts';

    public function handle(): int
    {
        $this->info('Starting ALLZONE Marketplace Dummy Data Purge...');

        // 1. Disable Foreign Key Checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Transactional & Dummy Tables to Truncate
        $dummyTables = [
            // Orders & Fulfilment
            'orders',
            'fulfilment_orders',
            'order_items',
            'order_status_histories',
            'payments',
            'payment_webhook_logs',
            'refunds',
            'cod_remittances',

            // Logistics & Tracking
            'shipments',
            'shipment_tracking',
            'shipment_webhook_logs',
            'ndr_cases',
            'rto_cases',

            // Catalog & Inventory
            'products',
            'product_variants',
            'product_attributes',
            'product_attribute_values',
            'product_media',
            'product_specifications',
            'product_price_histories',
            'product_qa',
            'catalog_imports',
            'inventory',
            'inventory_reservations',
            'stock_movements',

            // Returns & Claims
            'order_returns',
            'seller_claims',
            'claim_messages',

            // Marketing, Ads & Promotions
            'campaigns',
            'campaign_seller_participations',
            'seller_promotions',
            'seller_ad_campaigns',
            'influencer_profiles',
            'influencer_collaborations',
            'coupons',
            'coupon_usages',
            'banners',

            // Financials
            'commission_entries',
            'seller_settlements',
            'seller_finance_applications',
            'seller_finance_transactions',

            // Support & Notices
            'support_tickets',
            'ticket_messages',
            'announcements',
            'announcement_reads',

            // Customer interactions
            'carts',
            'cart_items',
            'wishlists',
            'reviews',
            'notifications',

            // Logs
            'activity_log',
            'ai_action_logs',
            'failed_jobs',
        ];

        foreach ($dummyTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ Truncated table: <comment>{$table}</comment>");
            }
        }

        // 3. Remove non-primary users & demo entities
        $keepEmails = [
            'admin@allzone.in',
            'seller@allzone.in',
            'customer@allzone.in',
            'customer1@example.com',
        ];

        $usersToDelete = User::whereNotIn('email', $keepEmails)->get();
        foreach ($usersToDelete as $user) {
            DB::table('model_has_roles')->where('model_id', $user->id)->delete();
            DB::table('model_has_permissions')->where('model_id', $user->id)->delete();
            $user->delete();
        }

        // Delete stores, sellers, customers not tied to keepEmails
        $keepUserIds = User::whereIn('email', $keepEmails)->pluck('id')->all();
        CustomerAddress::truncate();
        Customer::truncate();
        StoreDeliveryZone::truncate();
        StoreLocation::truncate();
        StoreTiming::truncate();
        StoreStaff::truncate();
        Store::truncate();
        InventoryLocation::truncate();
        SellerPickupLocation::truncate();
        SellerBankAccount::truncate();
        SellerDocument::truncate();
        Seller::truncate();

        // 4. Ensure Roles and Permissions exist
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (UserRole::cases() as $roleEnum) {
            Role::firstOrCreate(['name' => $roleEnum->value, 'guard_name' => 'web']);
        }
        $this->call(RolesAndPermissionsSeeder::class);
        Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 5. Seed / Ensure Base Platform Settings, Categories, Brands & Commission Rules
        $this->call(ProductionSeeder::class);

        // 6. Setup Primary Clean Login Accounts with standard password "Password@123"
        $commonPassword = Hash::make('Password@123');

        // A. Super Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@allzone.in'],
            [
                'name' => 'ALLZONE Super Admin',
                'phone' => '+919999900001',
                'password' => $commonPassword,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['super_admin', 'admin']);
        $this->line("  ✓ Ready Admin Login: <info>admin@allzone.in</info> / <info>Password@123</info>");

        // B. Verified Seller
        $sellerUser = User::updateOrCreate(
            ['email' => 'seller@allzone.in'],
            [
                'name' => 'Apex Retail Merchant',
                'phone' => '+919999900002',
                'password' => $commonPassword,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        $sellerUser->syncRoles(['seller', 'seller_owner']);

        $seller = Seller::create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Apex Retail Enterprises',
            'legal_name' => 'Apex Retail Enterprises India Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'pan' => 'AAACA1234F',
            'gstin' => '29AAACA1234F1Z5',
            'status' => SellerStatus::APPROVED,
            'commission_override' => null,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        SellerDocument::create([
            'seller_id' => $seller->id,
            'type' => SellerDocumentType::GST_CERTIFICATE,
            'file_path' => 'documents/sellers/gst_cert.pdf',
            'status' => SellerDocumentStatus::APPROVED,
            'remarks' => 'Verified with GSTN Registry',
        ]);

        SellerDocument::create([
            'seller_id' => $seller->id,
            'type' => SellerDocumentType::PAN_CARD,
            'file_path' => 'documents/sellers/pan.pdf',
            'status' => SellerDocumentStatus::APPROVED,
            'remarks' => 'Verified with NSDL database',
        ]);

        SellerBankAccount::create([
            'seller_id' => $seller->id,
            'account_holder' => 'Apex Retail Enterprises India Pvt Ltd',
            'account_no' => '50200012345678',
            'ifsc' => 'HDFC0000053',
            'bank_name' => 'HDFC Bank',
            'branch_name' => 'Indiranagar 100ft Road',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Apex Retail - Indiranagar Flagship',
            'slug' => 'apex-retail-indiranagar',
            'description' => 'Flagship store connected for quick commerce and parcel dispatches.',
            'phone' => '08025251122',
            'email' => 'indiranagar@apexretail.in',
            'status' => StoreStatus::ACTIVE,
            'is_onestall_owned' => false,
            'accepts_quick_commerce' => true,
            'accepts_pickup' => true,
            'rating_avg' => 5.00,
            'rating_count' => 0,
        ]);

        StoreLocation::create([
            'store_id' => $store->id,
            'address' => 'Plot 482, 100 Feet Road, Indiranagar',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
            'lat' => 12.9784,
            'lng' => 77.6408,
        ]);

        for ($day = 0; $day <= 6; $day++) {
            StoreTiming::create([
                'store_id' => $store->id,
                'day_of_week' => $day,
                'opens_at' => '07:00:00',
                'closes_at' => '23:00:00',
                'is_closed' => false,
            ]);
        }

        StoreDeliveryZone::create([
            'store_id' => $store->id,
            'type' => DeliveryZoneType::RADIUS,
            'radius_km' => 8.00,
            'pincodes' => ['560038', '560008', '560075', '560071', '560001'],
            'min_order_value' => 99.00,
            'delivery_fee' => 40.00,
            'is_quick' => true,
        ]);

        StoreStaff::create([
            'store_id' => $store->id,
            'user_id' => $sellerUser->id,
            'role' => StoreStaffRole::MANAGER,
            'permissions' => ['orders.manage', 'inventory.sync', 'pos.connect', 'dispatch.assign'],
        ]);

        InventoryLocation::create([
            'store_id' => $store->id,
            'seller_id' => $seller->id,
            'type' => InventoryLocationType::STORE->value,
            'name' => 'Apex Retail Indiranagar Store Stock',
            'lat' => 12.9784,
            'lng' => 77.6408,
            'is_active' => true,
        ]);

        SellerPickupLocation::create([
            'seller_id' => $seller->id,
            'nickname' => 'Primary Indiranagar Warehouse',
            'contact_person' => 'Operations Manager',
            'contact_phone' => '+919999900002',
            'contact_email' => 'indiranagar@apexretail.in',
            'address_line1' => 'Plot 482, 100 Feet Road, HAL 2nd Stage',
            'address_line2' => 'Indiranagar',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
            'is_default' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $this->line("  ✓ Ready Seller Login: <info>seller@allzone.in</info> / <info>Password@123</info>");

        // C. Customer Login
        $custConfigs = [
            ['email' => 'customer@allzone.in', 'phone' => '+919999900003', 'name' => 'Verified Customer'],
            ['email' => 'customer1@example.com', 'phone' => '+919999900004', 'name' => 'Customer Test User'],
        ];
        foreach ($custConfigs as $cConf) {
            $customerUser = User::updateOrCreate(
                ['email' => $cConf['email']],
                [
                    'name' => $cConf['name'],
                    'phone' => $cConf['phone'],
                    'password' => $commonPassword,
                    'status' => UserStatus::ACTIVE,
                    'email_verified_at' => now(),
                ]
            );
            $customerUser->syncRoles(['customer']);

            $customer = Customer::create([
                'user_id' => $customerUser->id,
                'gender' => 'other',
            ]);

            CustomerAddress::create([
                'customer_id' => $customer->id,
                'user_id' => $customerUser->id,
                'type' => AddressType::HOME,
                'name' => 'Home Address',
                'phone' => $cConf['phone'],
                'line1' => 'Flat 101, Indiranagar High Street',
                'line2' => 'Near Metro Station',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560038',
                'lat' => 12.9784,
                'lng' => 77.6408,
                'is_default' => true,
            ]);
        }
        $this->line("  ✓ Ready Customer Login: <info>customer@allzone.in</info> / <info>Password@123</info>");

        // 7. Re-enable Foreign Key Checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info('================================================================');
        $this->info('DATABASE PURGE COMPLETE: ALL DUMMY DATA REMOVED SUCCESSFULLY');
        $this->info('================================================================');
        $this->table(
            ['Portal', 'Login Email', 'Password', 'Role', 'Status'],
            [
                ['Admin Panel', 'admin@allzone.in', 'Password@123', 'super_admin', 'Active (0 Orders, 0 Dummy Records)'],
                ['Seller Panel', 'seller@allzone.in', 'Password@123', 'seller', 'Active & Approved (0 Products, 0 Dummy Orders)'],
                ['Customer Storefront', 'customer@allzone.in', 'Password@123', 'customer', 'Active (Empty Cart, Empty Orders)'],
                ['Customer (Test Alias)', 'customer1@example.com', 'Password@123', 'customer', 'Active (Empty Cart, Empty Orders)'],
            ]
        );

        return self::SUCCESS;
    }
}
