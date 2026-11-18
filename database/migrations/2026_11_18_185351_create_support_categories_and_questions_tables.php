<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('support_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('icon', 50)->default('help-circle');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('support_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_category_id')->constrained('support_categories')->cascadeOnDelete();
            $table->string('question', 255);
            $table->string('slug', 255);
            $table->text('short_answer')->nullable();
            $table->boolean('requires_order')->default(false);
            $table->boolean('requires_product')->default(false);
            $table->boolean('requires_shipment')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['support_category_id', 'is_active', 'sort_order']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('support_category_id')->nullable()->after('category')->constrained('support_categories')->nullOnDelete();
            $table->foreignId('support_question_id')->nullable()->after('support_category_id')->constrained('support_questions')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->after('order_id')->constrained('products')->nullOnDelete();
            $table->string('awb_number', 100)->nullable()->after('product_id');
            $table->string('callback_phone', 30)->nullable()->after('awb_number');
            $table->timestamp('reopened_at')->nullable()->after('assigned_to');
            $table->timestamp('resolved_at')->nullable()->after('reopened_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
        });

        // Seed default 10 issue categories with questions
        $defaultCategories = [
            [
                'name' => 'Orders and Shipping',
                'slug' => 'orders-and-shipping',
                'icon' => 'truck',
                'description' => 'Inquiries related to order processing, courier pickup, tracking, and Shiprocket delivery status.',
                'sort_order' => 1,
                'questions' => [
                    ['question' => 'My courier pickup was missed or delayed by Shiprocket partner', 'requires_order' => true, 'requires_shipment' => true],
                    ['question' => 'Unable to generate shipping label or manifest', 'requires_order' => true],
                    ['question' => 'Order marked delivered but customer claims non-receipt', 'requires_order' => true],
                ],
            ],
            [
                'name' => 'Returns and RTO',
                'slug' => 'returns-and-rto',
                'icon' => 'refresh',
                'description' => 'Return shipments, Return-to-Origin (RTO) tracking, damaged deliveries, and return disputes.',
                'sort_order' => 2,
                'questions' => [
                    ['question' => 'I received a damaged, wrong, or empty return parcel', 'requires_order' => true, 'requires_shipment' => true],
                    ['question' => 'RTO parcel delayed in transit beyond expected delivery date', 'requires_order' => true, 'requires_shipment' => true],
                    ['question' => 'How to raise an RTO return dispute / claim', 'requires_order' => true],
                ],
            ],
            [
                'name' => 'Products and Catalog',
                'slug' => 'products-and-catalog',
                'icon' => 'tag',
                'description' => 'Product listings, catalog moderation approval, image quality, and variant creation.',
                'sort_order' => 3,
                'questions' => [
                    ['question' => 'Why was my product listing or image rejected in moderation?', 'requires_product' => true],
                    ['question' => 'How do I update brand or category for an active product?', 'requires_product' => true],
                    ['question' => 'Errors during CSV / Excel bulk product upload', 'requires_product' => false],
                ],
            ],
            [
                'name' => 'Pricing',
                'slug' => 'pricing',
                'icon' => 'currency-rupee',
                'description' => 'Competitive pricing recommendations, price updates, discounts, and margin calculations.',
                'sort_order' => 4,
                'questions' => [
                    ['question' => 'How do I set separate pricing for Single Price vs Special Discount?', 'requires_product' => true],
                    ['question' => 'Product price update not reflecting on live marketplace', 'requires_product' => true],
                ],
            ],
            [
                'name' => 'Inventory',
                'slug' => 'inventory',
                'icon' => 'cube',
                'description' => 'Stock tracking, warehouse location management, low-stock threshold alerts, and sync issues.',
                'sort_order' => 5,
                'questions' => [
                    ['question' => 'Inventory stock not deducting automatically after order placement', 'requires_product' => true],
                    ['question' => 'How do I restock zero-inventory or out-of-stock items in bulk?', 'requires_product' => false],
                ],
            ],
            [
                'name' => 'Payments',
                'slug' => 'payments',
                'icon' => 'credit-card',
                'description' => 'Settlement disbursements, Razorpay payout cycles, deduction disputes, and GST invoices.',
                'sort_order' => 6,
                'questions' => [
                    ['question' => 'Weekly settlement payment delayed or not credited to bank', 'requires_order' => false],
                    ['question' => 'Discrepancy in commission or shipping fee deduction in settlement invoice', 'requires_order' => true],
                    ['question' => 'How to download monthly GST tax invoice and commission summary', 'requires_order' => false],
                ],
            ],
            [
                'name' => 'Claims',
                'slug' => 'claims',
                'icon' => 'shield-check',
                'description' => 'Dispute compensation claims, lost in transit claims, and damaged return reimbursements.',
                'sort_order' => 7,
                'questions' => [
                    ['question' => 'Claim status inquiry for lost-in-transit Shiprocket shipment', 'requires_order' => true, 'requires_shipment' => true],
                    ['question' => 'Dispute rejection appeal for damaged return reimbursement', 'requires_order' => true],
                ],
            ],
            [
                'name' => 'Promotions',
                'slug' => 'promotions',
                'icon' => 'sparkles',
                'description' => 'Platform sale participation, coupon codes, festival promotions, and banner features.',
                'sort_order' => 8,
                'questions' => [
                    ['question' => 'How do I enrol my catalog in upcoming festival mega sale events?', 'requires_product' => false],
                    ['question' => 'Creating custom seller discount coupons for my store', 'requires_product' => false],
                ],
            ],
            [
                'name' => 'Account and Profile',
                'slug' => 'account-and-profile',
                'icon' => 'user',
                'description' => 'Bank account verification, GST/PAN updates, store name, phone numbers, and login credentials.',
                'sort_order' => 9,
                'questions' => [
                    ['question' => 'How do I update verified bank account for settlements?', 'requires_order' => false],
                    ['question' => 'Updating GSTIN or business address on invoice', 'requires_order' => false],
                    ['question' => 'Adding store staff members and role permissions', 'requires_order' => false],
                ],
            ],
            [
                'name' => 'Other Issues',
                'slug' => 'other-issues',
                'icon' => 'chat-bubble',
                'description' => 'General queries, platform bug reports, feature suggestions, or policy clarifications.',
                'sort_order' => 10,
                'questions' => [
                    ['question' => 'Reporting technical portal bug or performance issue', 'requires_order' => false],
                    ['question' => 'General platform policy or merchant terms clarification', 'requires_order' => false],
                ],
            ],
        ];

        foreach ($defaultCategories as $catData) {
            $questions = $catData['questions'];
            unset($catData['questions']);

            $catId = DB::table('support_categories')->insertGetId(array_merge($catData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach ($questions as $idx => $qData) {
                DB::table('support_questions')->insert([
                    'support_category_id' => $catId,
                    'question' => $qData['question'],
                    'slug' => Str::slug($qData['question']),
                    'short_answer' => 'Our merchant support team typically reviews and resolves this issue within 24 hours. Submit a ticket below with order or product references for priority processing.',
                    'requires_order' => $qData['requires_order'] ?? false,
                    'requires_product' => $qData['requires_product'] ?? false,
                    'requires_shipment' => $qData['requires_shipment'] ?? false,
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['support_category_id']);
            $table->dropForeign(['support_question_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'support_category_id',
                'support_question_id',
                'product_id',
                'awb_number',
                'callback_phone',
                'reopened_at',
                'resolved_at',
                'closed_at',
            ]);
        });

        Schema::dropIfExists('support_questions');
        Schema::dropIfExists('support_categories');
    }
};
