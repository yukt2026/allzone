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
        Schema::create('seller_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('fulfilment_order_id')->nullable()->constrained('fulfilment_orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('order_return_id')->nullable()->constrained('returns')->nullOnDelete();
            $table->string('claim_type'); // rto_damage, rto_missing, return_wrong_item, return_used_damaged, courier_loss, other
            $table->string('reason');
            $table->text('description');
            $table->string('status')->default('open'); // open, under_review, more_info_needed, approved, rejected, closed
            $table->decimal('claimed_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->default(0.00);
            $table->json('evidence_urls')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('compensation_reference')->nullable();
            $table->timestamp('compensated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index('order_item_id');
            $table->index('claim_number');
        });

        Schema::create('claim_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_claim_id')->constrained('seller_claims')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_internal')->default(false);
            $table->text('message');
            $table->string('action_type')->default('message'); // message, status_change, more_info_requested, approved, rejected, info_submitted
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['seller_claim_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_messages');
        Schema::dropIfExists('seller_claims');
    }
};
