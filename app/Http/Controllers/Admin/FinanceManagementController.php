<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerFinanceApplication;
use App\Models\SellerFinanceTransaction;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FinanceManagementController extends Controller
{
    /**
     * Oversee platform seller finance status, partner settings, and seller applications.
     */
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        // Current platform settings
        $settings = [
            'status' => Setting::get('seller_finance_status', 'not_available'),
            'provider_name' => Setting::get('seller_finance_provider_name', ''),
            'disclaimer' => Setting::get('seller_finance_disclaimer', ''),
            'min_months' => (int) Setting::get('seller_finance_min_months_active', 3),
            'min_orders' => (int) Setting::get('seller_finance_min_orders', 25),
        ];

        // Applications Query
        $query = SellerFinanceApplication::query()
            ->with(['seller.user'])
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pan_number', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%")
                  ->orWhereHas('seller', fn ($sq) => $sq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->paginate(20)->withQueryString();

        $metrics = [
            'total' => SellerFinanceApplication::count(),
            'waitlist' => SellerFinanceApplication::where('application_type', 'waitlist')->count(),
            'under_review' => SellerFinanceApplication::where('status', 'under_review')->count(),
            'approved' => SellerFinanceApplication::where('status', 'approved')->count(),
            'total_approved_limit' => (float) SellerFinanceApplication::sum('approved_limit'),
            'total_outstanding' => (float) SellerFinanceApplication::sum('outstanding_balance'),
        ];

        return view('admin.finance.index', compact('settings', 'applications', 'metrics', 'status', 'search'));
    }

    /**
     * Update platform finance partner settings and feature availability status.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'seller_finance_status' => ['required', 'string', 'in:not_available,coming_soon,active_partner'],
            'seller_finance_provider_name' => ['nullable', 'string', 'max:150'],
            'seller_finance_disclaimer' => ['required', 'string', 'max:2000'],
            'seller_finance_min_months_active' => ['required', 'integer', 'min:0', 'max:60'],
            'seller_finance_min_orders' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value);
        }

        Setting::clearRuntimeCache();

        return back()->with('success', 'Seller finance governance settings updated successfully.');
    }

    /**
     * Review and update seller financing application / referral status.
     */
    public function updateApplication(Request $request, SellerFinanceApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:registered,under_review,approved,rejected,closed'],
            'approved_limit' => ['nullable', 'numeric', 'min:0'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
            'provider_reference_id' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validated['status'] === 'rejected' && empty($validated['rejection_reason'])) {
            return back()->with('error', 'Rejection reason is mandatory when declining an application.');
        }

        $application->update([
            'status' => $validated['status'],
            'approved_limit' => $validated['approved_limit'] ?? $application->approved_limit,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'rejection_reason' => $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null,
            'provider_reference_id' => $validated['provider_reference_id'] ?? $application->provider_reference_id,
        ]);

        return back()->with('success', "Finance application #{$application->id} updated to " . ucfirst(str_replace('_', ' ', $validated['status'])) . '.');
    }

    /**
     * Record a verified ledger transaction (disbursement or repayment) for an approved facility.
     */
    public function recordTransaction(Request $request, SellerFinanceApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_type' => ['required', 'string', 'in:disbursement,repayment,fee,adjustment'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reference_id' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($application, $validated) {
            $amount = (float) $validated['amount'];

            // Adjust outstanding balance safely
            if ($validated['transaction_type'] === 'disbursement') {
                $application->outstanding_balance = (float) $application->outstanding_balance + $amount;
            } elseif ($validated['transaction_type'] === 'repayment') {
                $application->outstanding_balance = max(0.00, (float) $application->outstanding_balance - $amount);
            }

            $application->save();

            SellerFinanceTransaction::create([
                'finance_application_id' => $application->id,
                'seller_id' => $application->seller_id,
                'transaction_type' => $validated['transaction_type'],
                'amount' => $amount,
                'reference_id' => $validated['reference_id'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'transacted_at' => now(),
            ]);
        });

        return back()->with('success', 'Verified ledger transaction recorded successfully.');
    }
}
