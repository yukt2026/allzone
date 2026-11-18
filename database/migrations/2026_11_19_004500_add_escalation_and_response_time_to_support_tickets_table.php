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
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable()->after('reopened_at');
            $table->boolean('is_escalated')->default(false)->after('first_response_at');
            $table->timestamp('escalated_at')->nullable()->after('is_escalated');
            $table->text('escalation_reason')->nullable()->after('escalated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['first_response_at', 'is_escalated', 'escalated_at', 'escalation_reason']);
        });
    }
};
