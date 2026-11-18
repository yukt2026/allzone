<?php

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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_signature')->nullable()->after('gateway_payment_id');
            $table->string('reconciliation_status', 32)->default('reconciled')->index()->after('status');
            $table->timestamp('reconciled_at')->nullable()->after('verified_at');
            $table->foreignId('reconciled_by')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            $table->text('reconciliation_notes')->nullable()->after('reconciled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn([
                'gateway_signature',
                'reconciliation_status',
                'reconciled_at',
                'reconciled_by',
                'reconciliation_notes',
            ]);
        });
    }
};
