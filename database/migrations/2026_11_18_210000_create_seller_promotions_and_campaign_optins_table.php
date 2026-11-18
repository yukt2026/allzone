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
        // 1. Add promotional configuration to campaigns
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('banner')->nullable()->after('description');
            $table->string('discount_type', 20)->default('percentage')->after('type'); // percentage, flat
            $table->decimal('min_discount_percentage', 5, 2)->default(5.00)->after('discount_type');
            $table->decimal('max_discount_percentage', 5, 2)->nullable()->after('min_discount_percentage');
            $table->decimal('min_product_rating', 3, 2)->nullable()->after('max_discount_percentage');
            $table->timestamp('opt_in_deadline')->nullable()->after('ends_at');
            $table->boolean('allow_opt_out')->default(true)->after('opt_in_deadline');
            $table->text('terms_and_conditions')->nullable()->after('allow_opt_out');
            $table->foreignId('applicable_category_id')->nullable()->after('terms_and_conditions')->constrained('categories')->nullOnDelete();
            $table->integer('max_seller_products')->nullable()->after('applicable_category_id');
        });

        // 2. Seller campaign participations (opt-in history & status)
        Schema::create('seller_campaign_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('status', 30)->default('enrolled')->index(); // enrolled, opted_out
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->boolean('agreed_to_terms')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'seller_id'], 'seller_campaign_unique');
        });

        // 3. Seller campaign opted-in products
        Schema::create('seller_campaign_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('original_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->decimal('campaign_price', 10, 2);
            $table->string('status', 30)->default('enrolled')->index(); // enrolled, opted_out, rejected
            $table->timestamps();

            $table->unique(['campaign_id', 'product_id'], 'campaign_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_campaign_products');
        Schema::dropIfExists('seller_campaign_participations');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['applicable_category_id']);
            $table->dropColumn([
                'banner',
                'discount_type',
                'min_discount_percentage',
                'max_discount_percentage',
                'min_product_rating',
                'opt_in_deadline',
                'allow_opt_out',
                'terms_and_conditions',
                'applicable_category_id',
                'max_seller_products',
            ]);
        });
    }
};
