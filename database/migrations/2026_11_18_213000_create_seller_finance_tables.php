<?php

declare(strict_types=1);

use App\Models\Setting;
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
        // 1. Seller Finance Applications / Inquiries
        Schema::create('seller_finance_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('application_type', 50)->default('waitlist'); // waitlist, credit_line_inquiry, working_capital
            $table->decimal('requested_amount', 12, 2)->nullable();
            $table->decimal('approved_limit', 12, 2)->default(0.00);
            $table->decimal('outstanding_balance', 12, 2)->default(0.00);
            $table->string('pan_number', 20)->nullable();
            $table->string('gstin', 30)->nullable();
            $table->decimal('annual_turnover', 14, 2)->nullable();
            $table->string('status', 30)->default('registered')->index(); // registered, under_review, approved, rejected, closed
            $table->string('provider_name', 100)->nullable();
            $table->string('provider_reference_id', 100)->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->text('seller_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Seller Finance Transactions (ledger for disbursements and repayments)
        Schema::create('seller_finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('finance_application_id');
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('transaction_type', 50); // disbursement, repayment, fee, adjustment
            $table->decimal('amount', 12, 2);
            $table->string('reference_id', 100)->unique();
            $table->string('status', 30)->default('completed'); // pending, completed, failed
            $table->text('notes')->nullable();
            $table->timestamp('transacted_at');
            $table->timestamps();

            $table->foreign('finance_application_id', 'fk_sft_app_id')
                ->references('id')
                ->on('seller_finance_applications')
                ->cascadeOnDelete();
        });

        // 3. Seed Default Admin Controls in Settings
        $defaults = [
            [
                'key' => 'seller_finance_status',
                'value' => 'not_available', // 'not_available', 'coming_soon', 'active_partner'
                'type' => 'string',
                'group' => 'finance',
                'label' => 'Seller Finance Feature Status',
                'description' => 'Controls whether seller financing is Not Available, Coming Soon, or Live with an authorized banking/NBFC partner.',
                'is_public' => true,
            ],
            [
                'key' => 'seller_finance_provider_name',
                'value' => '',
                'type' => 'string',
                'group' => 'finance',
                'label' => 'Authorized Finance Partner',
                'description' => 'Name of the licensed RBI-regulated financial institution partner.',
                'is_public' => true,
            ],
            [
                'key' => 'seller_finance_disclaimer',
                'value' => 'ALLZONE is an e-commerce technology marketplace and does not operate as a bank, NBFC, or credit institution. Any prospective seller financing services will be extended exclusively by licensed, RBI-regulated lending partners subject to their credit underwriting and regulatory approvals.',
                'type' => 'text',
                'group' => 'finance',
                'label' => 'Regulatory Disclaimer',
                'description' => 'Mandatory legal and regulatory disclosure displayed to all sellers.',
                'is_public' => true,
            ],
            [
                'key' => 'seller_finance_min_months_active',
                'value' => '3',
                'type' => 'integer',
                'group' => 'finance',
                'label' => 'Minimum Active Months on Platform',
                'description' => 'Minimum tenure on ALLZONE to be considered eligible for partner referral.',
                'is_public' => false,
            ],
            [
                'key' => 'seller_finance_min_orders',
                'value' => '25',
                'type' => 'integer',
                'group' => 'finance',
                'label' => 'Minimum Delivered Orders',
                'description' => 'Minimum delivered orders required for finance partner consideration.',
                'is_public' => false,
            ],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_finance_transactions');
        Schema::dropIfExists('seller_finance_applications');

        Setting::whereIn('key', [
            'seller_finance_status',
            'seller_finance_provider_name',
            'seller_finance_disclaimer',
            'seller_finance_min_months_active',
            'seller_finance_min_orders',
        ])->delete();
    }
};
