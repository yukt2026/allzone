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
        // 1. Seller Ad Campaigns
        Schema::create('seller_ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 50)->default('sponsored_listing'); // sponsored_listing, banner_display, featured_category
            $table->string('status', 30)->default('pending_approval')->index(); // draft, pending_approval, active, paused, completed, rejected
            $table->decimal('daily_budget', 10, 2);
            $table->decimal('total_budget', 10, 2);
            $table->decimal('spent_amount', 10, 2)->default(0.00);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('targeting_type', 50)->default('category'); // category, search_keywords, storewide
            $table->json('targeting_payload')->nullable();
            $table->string('creative_image_url')->nullable();
            $table->string('headline')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('impressions_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->integer('attributed_orders_count')->default(0);
            $table->decimal('attributed_revenue', 10, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Products associated with Seller Ad Campaign
        Schema::create('seller_ad_campaign_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained('seller_ad_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('bid_amount', 8, 2)->nullable();
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('attributed_orders')->default(0);
            $table->decimal('attributed_revenue', 10, 2)->default(0.00);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['ad_campaign_id', 'product_id'], 'ad_campaign_product_unique');
        });

        // 3. Platform Approved Influencer Profiles
        Schema::create('influencer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('handle', 100);
            $table->string('platform', 50)->default('instagram'); // instagram, youtube, blog
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('bio')->nullable();
            $table->string('profile_photo')->nullable();
            $table->integer('verified_followers')->default(0);
            $table->decimal('collaboration_fee', 10, 2)->nullable();
            $table->boolean('is_approved')->default(true);
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        // 4. Influencer Collaborations / Requests
        Schema::create('influencer_collaborations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('influencer_profile_id')->constrained('influencer_profiles')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('campaign_title', 150);
            $table->text('proposal_notes')->nullable();
            $table->decimal('proposed_budget', 10, 2);
            $table->string('status', 30)->default('pending')->index(); // pending, accepted, declined, in_progress, completed, cancelled
            $table->string('deliverable_url')->nullable();
            $table->text('admin_notes')->nullable();
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('attributed_orders')->default(0);
            $table->decimal('attributed_revenue', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('influencer_collaborations');
        Schema::dropIfExists('influencer_profiles');
        Schema::dropIfExists('seller_ad_campaign_products');
        Schema::dropIfExists('seller_ad_campaigns');
    }
};
