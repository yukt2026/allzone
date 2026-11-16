<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely decommission internal rider tables and references.
     * Shiprocket is now the sole delivery and logistics partner.
     */
    public function up(): void
    {
        // 1. Drop rider telemetry history table
        Schema::dropIfExists('rider_locations_history');

        // 2. Drop rider wallet transactions & wallets
        Schema::dropIfExists('rider_wallet_transactions');
        Schema::dropIfExists('rider_wallets');

        // 3. Drop rider earnings
        Schema::dropIfExists('rider_earnings');

        // 4. Drop rider documents
        Schema::dropIfExists('delivery_documents');

        // 5. Drop internal delivery assignments
        Schema::dropIfExists('delivery_assignments');

        // 6. Drop delivery partners
        Schema::dropIfExists('delivery_partners');

        // 7. Remove quick_auto_assign_rider from stores if present
        if (Schema::hasColumn('stores', 'quick_auto_assign_rider')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('quick_auto_assign_rider');
            });
        }

        // 8. Clean up demo rider users
        DB::table('users')->whereIn('email', [
            'rider1@allzone.in',
            'rider2@allzone.in',
            'rider3@allzone.in',
        ])->delete();

        // 9. Clean up rider payout settings
        DB::table('settings')->where('group', 'rider_payout')->orWhere('key', 'like', 'rider_%')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-creating internal rider tables is not permitted as Shiprocket is the exclusive delivery partner.
    }
};
