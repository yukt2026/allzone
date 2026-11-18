<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Seller;
use App\Models\StoreConnection;
use App\Models\SyncLog;
use App\Services\Admin\AdminInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(
        protected AdminInventoryService $inventoryService
    ) {}

    /**
     * Read-only global inventory overview, movement ledger, and audit across all platform merchants.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Inventory::class);

        $tab = $request->query('tab', 'registry');
        $metrics = $this->inventoryService->getOverviewMetrics();
        $sellers = Seller::orderBy('business_name')->get(['id', 'business_name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        if ($tab === 'discrepancies') {
            $request->merge(['stock_status' => 'discrepancy']);
        }

        if ($tab === 'movements') {
            $movements = $this->inventoryService->buildMovementsQuery($request)->paginate(20)->withQueryString();
            $inventories = Inventory::query()->whereRaw('1=0')->paginate(20);
        } else {
            $query = $this->inventoryService->buildInventoryQuery($request);
            $inventories = $query->paginate(20)->withQueryString();
            $movements = null;
        }

        return view('admin.inventory.index', [
            'tab' => $tab,
            'inventories' => $inventories,
            'movements' => $movements,
            'sellers' => $sellers,
            'categories' => $categories,
            'filters' => $request->only([
                'seller_id',
                'category_id',
                'location_type',
                'stock_status',
                'quick_available',
                'search',
                'type',
                'from_date',
                'to_date',
                'inventory_id',
            ]),
            'metrics' => $metrics,
        ]);
    }

    /**
     * View dedicated stock movement ledger.
     */
    public function movements(Request $request): View
    {
        $this->authorize('viewAny', Inventory::class);

        $request->merge(['tab' => 'movements']);

        return $this->index($request);
    }

    /**
     * Authorized manual stock adjustment with mandatory audit reason.
     */
    public function adjust(Request $request, Inventory $inventory): RedirectResponse
    {
        $this->authorize('update', $inventory);

        $validated = $request->validate([
            'new_quantity_on_hand' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $oldQuantity = $inventory->quantity_on_hand;
            $this->inventoryService->adjustStock(
                $inventory,
                (int) $validated['new_quantity_on_hand'],
                $validated['reason'],
                (int) Auth::id()
            );

            return redirect()->back()->with(
                'success',
                "Inventory stock adjusted from {$oldQuantity} to {$validated['new_quantity_on_hand']} units with audit reason logged."
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Export global inventory balances to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Inventory::class);

        return $this->inventoryService->exportInventoryCsv($request);
    }

    /**
     * Export stock movement ledger to CSV.
     */
    public function exportMovements(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Inventory::class);

        return $this->inventoryService->exportMovementsCsv($request);
    }

    /**
     * Read-only bi-directional POS and store integration sync logs.
     */
    public function syncLogs(Request $request): View
    {
        $this->authorize('viewAny', Inventory::class);

        $query = $this->buildSyncLogsQuery($request);
        $logs = $query->paginate(25)->withQueryString();

        $metrics = [
            'total_logs' => SyncLog::count(),
            'successful_syncs' => SyncLog::where('status', 'success')->count(),
            'failed_syncs' => SyncLog::where('status', 'failed')->count(),
            'partial_syncs' => SyncLog::where('status', 'partial')->count(),
            'last_24h_syncs' => SyncLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        $connections = StoreConnection::with('store:id,name')->orderBy('name')->get(['id', 'store_id', 'name', 'type']);

        return view('admin.inventory.sync_logs', [
            'logs' => $logs,
            'metrics' => $metrics,
            'connections' => $connections,
            'filters' => $request->only(['status', 'entity', 'direction', 'search', 'connection_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Build filtered query for sync logs.
     */
    protected function buildSyncLogsQuery(Request $request)
    {
        $query = SyncLog::query()
            ->with(['connection.store.seller:id,business_name,legal_name'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('entity', 'like', "%{$search}%")
                  ->orWhereHas('connection', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhereHas('store', function ($sq) use ($search) {
                             $sq->where('name', 'like', "%{$search}%")
                                ->orWhereHas('seller', fn ($selq) => $selq->where('business_name', 'like', "%{$search}%")->orWhere('legal_name', 'like', "%{$search}%"));
                         });
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('entity') && $request->entity !== 'all') {
            $query->where('entity', $request->entity);
        }

        if ($request->filled('direction') && $request->direction !== 'all') {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('connection_id') && $request->connection_id !== 'all') {
            $query->where('connection_id', (int) $request->connection_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * Export integration sync logs to CSV.
     */
    public function exportSyncLogs(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $filename = 'inventory-sync-logs-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $query = $this->buildSyncLogsQuery($request);

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Log ID',
                'Connection Name',
                'Store Name',
                'Seller Name',
                'Entity',
                'Direction',
                'Status',
                'Message',
                'Created At',
            ]);

            $query->chunk(200, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    $store = $log->connection?->store;
                    $seller = $store?->seller;

                    fputcsv($handle, [
                        $log->id,
                        $log->connection?->name ?? 'Direct API',
                        $store?->name ?? 'N/A',
                        $seller?->business_name ?? $seller?->legal_name ?? 'N/A',
                        strtoupper((string) $log->entity),
                        strtoupper((string) $log->direction),
                        strtoupper((string) $log->status),
                        $log->message ?? '',
                        $log->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Prune historical sync logs older than given retention window.
     */
    public function pruneSyncLogs(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $days = max(1, (int) $request->input('days', 30));
        $status = $request->input('status', 'all');

        $query = SyncLog::where('created_at', '<', now()->subDays($days));
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $count = $query->delete();

        return redirect()->route('admin.inventory.sync_logs')
            ->with('success', "Cleaned up {$count} historical sync logs older than {$days} days.");
    }
}
