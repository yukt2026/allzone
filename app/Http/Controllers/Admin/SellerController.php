<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSellerActionRequest;
use App\Http\Requests\Admin\RejectSellerRequest;
use App\Http\Requests\Admin\ReviewSellerDocumentRequest;
use App\Http\Requests\Admin\SuspendSellerRequest;
use App\Http\Requests\Admin\UpdateSellerCommissionRequest;
use App\Models\Seller;
use App\Models\SellerBankAccount;
use App\Models\SellerDocument;
use App\Services\Admin\AdminSellerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellerController extends Controller
{
    public function __construct(
        protected AdminSellerService $sellerService
    ) {}

    /**
     * Display the paginated and filtered list of sellers.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Seller::class);

        $filters = [
            'tab' => $request->query('tab', 'all'),
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'city' => $request->query('city'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $sellers = $this->sellerService->getFilteredSellers($filters, 15);
        $tabCounts = $this->sellerService->getSellerTabCounts();

        return view('admin.sellers.index', [
            'sellers' => $sellers,
            'filters' => $filters,
            'statuses' => SellerStatus::cases(),
            'types' => SellerType::cases(),
            'tabCounts' => $tabCounts,
            'activeTab' => $filters['tab'],
        ]);
    }

    /**
     * Stream CSV export of filtered sellers.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Seller::class);

        return $this->sellerService->exportCsv($request->all());
    }

    /**
     * Display seller detail page with all tabs.
     */
    public function show(Request $request, Seller $seller): View
    {
        $this->authorize('view', $seller);

        $seller->load([
            'user',
            'stores.locations',
            'stores.timings',
            'stores.deliveryZones',
            'documents',
            'bankAccounts',
            'brands',
            'pickupLocations',
            'primaryBankAccount',
        ]);

        $products = $seller->products()
            ->with(['variants', 'category', 'brand'])
            ->latest('id')
            ->paginate(10, ['*'], 'products_page')
            ->appends(array_merge($request->query(), ['tab' => 'products']));

        $orders = $seller->fulfilmentOrders()
            ->with(['order.customer.user'])
            ->latest('id')
            ->paginate(10, ['*'], 'orders_page')
            ->appends(array_merge($request->query(), ['tab' => 'orders']));

        $settlements = $seller->settlements()
            ->latest('id')
            ->paginate(10, ['*'], 'settlements_page')
            ->appends(array_merge($request->query(), ['tab' => 'payments']));

        $commissionEntries = $seller->commissionEntries()
            ->select('commission_entries.*')
            ->with('order')
            ->latest('commission_entries.id')
            ->paginate(10, ['*'], 'commission_page')
            ->appends(array_merge($request->query(), ['tab' => 'commission']));

        $tickets = \App\Models\SupportTicket::where(function ($q) use ($seller) {
                $q->where('seller_id', $seller->id)
                  ->orWhere('user_id', $seller->user_id);
            })
            ->with(['categoryRelation', 'assignedAgent'])
            ->latest('id')
            ->paginate(10, ['*'], 'tickets_page')
            ->appends(array_merge($request->query(), ['tab' => 'tickets']));

        $pickupLocations = $seller->pickupLocations()->latest('id')->get();
        $bankAccounts = $seller->bankAccounts()->latest('id')->get();
        $activityLogs = $this->sellerService->getSellerActivityLogs($seller);
        $stats = $this->sellerService->getSellerPerformanceMetrics($seller);

        $activeTab = (string) $request->query('tab', '');
        if (empty($activeTab)) {
            if ($request->has('products_page')) {
                $activeTab = 'products';
            } elseif ($request->has('orders_page')) {
                $activeTab = 'orders';
            } elseif ($request->has('settlements_page')) {
                $activeTab = 'payments';
            } elseif ($request->has('commission_page')) {
                $activeTab = 'commission';
            } elseif ($request->has('tickets_page')) {
                $activeTab = 'tickets';
            } else {
                $activeTab = 'overview';
            }
        }

        return view('admin.sellers.show', [
            'seller' => $seller,
            'products' => $products,
            'orders' => $orders,
            'settlements' => $settlements,
            'commissionEntries' => $commissionEntries,
            'tickets' => $tickets,
            'pickupLocations' => $pickupLocations,
            'bankAccounts' => $bankAccounts,
            'activityLogs' => $activityLogs,
            'stats' => $stats,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Approve seller KYC application.
     */
    public function approve(Request $request, Seller $seller): RedirectResponse
    {
        $this->authorize('approve', $seller);

        $store = $this->sellerService->approveSeller($seller, $request->user());

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'overview'])
            ->with('success', "Merchant '{$seller->business_name}' approved successfully! Store '{$store->name}' initialized.");
    }

    /**
     * Reject seller KYC application with mandatory remarks.
     */
    public function reject(RejectSellerRequest $request, Seller $seller): RedirectResponse
    {
        $this->authorize('reject', $seller);

        $this->sellerService->rejectSeller($seller, (string) $request->validated('reason'), $request->user());

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'kyc'])
            ->with('success', "Merchant '{$seller->business_name}' has been rejected with recorded remarks.");
    }

    /**
     * Suspend seller account with mandatory reason.
     */
    public function suspend(SuspendSellerRequest $request, Seller $seller): RedirectResponse
    {
        $this->authorize('suspend', $seller);

        $this->sellerService->suspendSeller($seller, (string) $request->validated('reason'), $request->user());

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'overview'])
            ->with('success', "Merchant '{$seller->business_name}' has been suspended. Public storefronts and catalogue hidden.");
    }

    /**
     * Reactivate suspended seller account.
     */
    public function reactivate(Request $request, Seller $seller): RedirectResponse
    {
        $this->authorize('reactivate', $seller);

        $this->sellerService->reactivateSeller($seller, $request->input('reason'), $request->user());

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'overview'])
            ->with('success', "Merchant '{$seller->business_name}' reactivated. Storefronts and catalogue restored.");
    }

    /**
     * Update custom seller commission override percentage.
     */
    public function updateCommission(UpdateSellerCommissionRequest $request, Seller $seller): RedirectResponse
    {
        $this->authorize('updateCommission', $seller);

        $override = $request->validated('commission_override') !== null
            ? (float) $request->validated('commission_override')
            : null;

        $this->sellerService->updateCommissionOverride(
            $seller,
            $override,
            $request->validated('remarks'),
            $request->user()
        );

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'commission'])
            ->with('success', 'Commission override updated successfully.');
    }

    /**
     * Download or view a seller KYC document.
     */
    public function downloadDocument(Seller $seller, SellerDocument $document): StreamedResponse
    {
        $this->authorize('view', $seller);
        abort_if($document->seller_id !== $seller->id, 404);

        if (!\Illuminate\Support\Facades\Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Document file not found on private storage.');
        }

        return \Illuminate\Support\Facades\Storage::disk('private')->response($document->file_path);
    }

    /**
     * Review individual KYC document (approve or reject with remarks).
     */
    public function reviewDocument(ReviewSellerDocumentRequest $request, Seller $seller, SellerDocument $document): RedirectResponse
    {
        $this->authorize('reviewDocument', $seller);

        $this->sellerService->reviewDocument(
            $document,
            (string) $request->validated('status'),
            $request->validated('remarks'),
            $request->user()
        );

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'documents'])
            ->with('success', "KYC Document '{$document->type->label()}' marked as " . ucfirst((string) $request->validated('status')) . '.');
    }

    /**
     * Verify or unverify seller bank account.
     */
    public function verifyBankAccount(Request $request, Seller $seller, SellerBankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('verifyBank', $seller);

        $verified = filter_var($request->input('verified', true), FILTER_VALIDATE_BOOLEAN);

        $this->sellerService->verifyBankAccount($bankAccount, $verified, $request->user());

        $msg = $verified ? 'Bank account verified successfully for payouts.' : 'Bank account unverified.';

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'bank'])
            ->with('success', $msg);
    }

    /**
     * Perform bulk actions on multiple merchants.
     */
    public function bulkAction(BulkSellerActionRequest $request): RedirectResponse
    {
        $sellerIds = (array) $request->validated('seller_ids');
        $action = (string) $request->validated('action');
        $reason = $request->validated('reason');

        $processed = $this->sellerService->performBulkAction($sellerIds, $action, $reason, $request->user());

        return redirect()
            ->route('admin.sellers.index')
            ->with('success', "Bulk {$action} action applied to {$processed} merchant(s).");
    }

    /**
     * Request additional verification documents from seller.
     */
    public function requestDocuments(Request $request, Seller $seller): RedirectResponse
    {
        $this->authorize('requestDocuments', $seller);

        $validated = $request->validate([
            'document_type' => ['required', 'string'],
            'instructions' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $this->sellerService->requestAdditionalDocuments(
            $seller,
            (string) $validated['document_type'],
            (string) $validated['instructions'],
            $request->user()
        );

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'documents'])
            ->with('success', "Requested additional document '{$validated['document_type']}' from merchant.");
    }

    /**
     * Update permitted seller operational settings.
     */
    public function updateSettings(Request $request, Seller $seller): RedirectResponse
    {
        $this->authorize('update', $seller);

        $validated = $request->validate([
            'seller_type' => ['nullable', 'string'],
            'category_focus' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'commission_override' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'accepts_quick_commerce' => ['nullable'],
            'accepts_pickup' => ['nullable'],
        ]);

        $this->sellerService->updateSellerSettings($seller, $validated, $request->user());

        return redirect()
            ->route('admin.sellers.show', ['seller' => $seller, 'tab' => 'overview'])
            ->with('success', 'Merchant settings updated successfully.');
    }
}
