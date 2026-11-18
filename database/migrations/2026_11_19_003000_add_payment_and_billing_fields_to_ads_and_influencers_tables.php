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
        Schema::table('influencer_collaborations', function (Blueprint $table) {
            $table->string('payment_status', 30)->default('unpaid')->index()->after('proposed_budget'); // unpaid, in_escrow, paid, cancelled
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->string('payment_reference', 100)->nullable()->after('paid_at');
        });

        Schema::table('seller_ad_campaigns', function (Blueprint $table) {
            $table->string('billing_status', 30)->default('active_wallet')->after('spent_amount'); // active_wallet, deducted_from_payout, settled
            $table->text('billing_notes')->nullable()->after('billing_status');
        });

        Schema::table('influencer_profiles', function (Blueprint $table) {
            $table->string('contact_email', 150)->nullable()->after('handle');
            $table->text('verification_notes')->nullable()->after('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('influencer_profiles', function (Blueprint $table) {
            $table->dropColumn(['contact_email', 'verification_notes']);
        });

        Schema::table('seller_ad_campaigns', function (Blueprint $table) {
            $table->dropColumn(['billing_status', 'billing_notes']);
        });

        Schema::table('influencer_collaborations', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_at', 'payment_reference']);
        });
    }
};
