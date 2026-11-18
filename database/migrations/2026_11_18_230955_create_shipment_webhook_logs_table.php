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
        Schema::create('shipment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->nullable()->index();
            $table->string('awb')->nullable()->index();
            $table->string('order_id')->nullable()->index();
            $table->string('current_status')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('source_ip', 45)->nullable();
            $table->boolean('is_verified')->default(true);
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_webhook_logs');
    }
};
