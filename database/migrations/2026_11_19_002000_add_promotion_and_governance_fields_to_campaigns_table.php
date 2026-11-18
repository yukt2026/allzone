<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('status', 30)->default('active')->index()->after('type'); // draft, scheduled, active, paused, ended
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            $table->decimal('max_discount', 10, 2)->nullable()->after('max_discount_percentage');
            $table->decimal('budget_limit', 12, 2)->nullable()->after('max_seller_products');
            $table->integer('usage_limit')->nullable()->after('budget_limit');
            $table->integer('usage_count')->default(0)->after('usage_limit');
            $table->json('eligible_seller_ids')->nullable()->after('applicable_category_id');
            $table->json('eligible_product_ids')->nullable()->after('eligible_seller_ids');
            $table->boolean('participation_requires_approval')->default(false)->after('allow_opt_out');
            $table->string('coupon_code', 50)->nullable()->after('budget_limit');
        });

        Schema::table('seller_campaign_participations', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('opted_out_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_campaign_participations', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_at', 'approved_by', 'rejection_reason']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'discount_value',
                'max_discount',
                'budget_limit',
                'usage_limit',
                'usage_count',
                'eligible_seller_ids',
                'eligible_product_ids',
                'participation_requires_approval',
                'coupon_code',
            ]);
        });
    }
};
