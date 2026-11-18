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
        Schema::create('seller_pickup_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('nickname', 100);
            $table->string('contact_person', 100);
            $table->string('contact_phone', 20);
            $table->string('contact_email', 150)->nullable();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('pincode', 10)->index();
            $table->string('country', 50)->default('India');
            $table->string('gstin', 20)->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->string('status', 32)->default('active')->index(); // active, pending_verification, rejected, inactive
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->string('shiprocket_pickup_name', 100)->nullable();
            $table->boolean('is_serviceable')->default(true);
            $table->json('serviceability_details')->nullable();
            $table->json('package_types')->nullable();
            $table->string('operating_hours', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['seller_id', 'is_default']);
            $table->index(['seller_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_pickup_locations');
    }
};
