<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\StockMovement;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminInventoryService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Compute platform-wide inventory health metrics.
     */
    public function getOverviewMetrics(): array
    {
        $totalItems = Inventory::count();
        $totalSkus = ProductVariant::count();
        $totalOnHand = (int) Inventory::sum('quantity_on_hand');
        $totalReserved = (int) Inventory::sum('quantity_reserved');
        $availableStock = max(0, $totalOnHand - $totalReserved);

        $outOfStock = Inventory::whereRaw('(quantity_on_hand - quantity_reserved) <= 0')->count();
        $lowStock = Inventory::whereRaw('(quantity_on_hand - quantity_reserved) > 0')
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold')
            ->count();

        $discrepancies = Inventory::where(function (Builder $q) {
            $q->whereRaw('quantity_on_hand < quantity_reserved')
              ->orWhereRaw('quantity_on_hand < 0')
              ->orWhereRaw('quantity_reserved < 0');
        })->count();

        return [
            'total_items' => $totalItems,
            'total_skus' => $totalSkus,
            'total_on_hand' => $totalOnHand,
            'total_reserved' => $totalReserved,
            'available_stock' => $availableStock,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * Build filtered query for global inventory balances.
     */
    public function buildInventoryQuery(Request $request): Builder
    {
        $query = Inventory::query()
            ->with([
                'variant.product.seller:id,business_name,legal_name',
                'variant.product.category:id,name',
                'location:id,name,type,seller_id',
            ])
            ->latest('id');

        // Filter by Seller
        if ($request->filled('seller_id') && $request->seller_id !== 'all') {
            $query->whereHas('location', fn ($q) => $q->where('seller_id', (int) $request->seller_id));
        }

        // Filter by Category
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->whereHas('variant.product', fn ($q) => $q->where('category_id', (int) $request->category_id));
        }

        // Filter by Location Type
        if ($request->filled('location_type') && $request->location_type !== 'all') {
            $query->whereHas('location', fn ($q) => $q->where('type', $request->location_type));
        }

        // Filter by Stock Health
        if ($request->filled('stock_status') && $request->stock_status !== 'all') {
            match ($request->stock_status) {
                'out_of_stock' => $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0'),
                'low_stock' => $query->whereRaw('(quantity_on_hand - quantity_reserved) > 0')
                    ->whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold'),
                'in_stock' => $query->whereRaw('(quantity_on_hand - quantity_reserved) > low_stock_threshold'),
                'discrepancy' => $query->where(function (Builder $sq) {
                    $sq->whereRaw('quantity_on_hand < quantity_reserved')
                       ->orWhereRaw('quantity_on_hand < 0')
                       ->orWhereRaw('quantity_reserved < 0');
                }),
                default => null,
            };
        }

        // Quick Commerce candidate
        if ($request->filled('quick_available') && $request->quick_available !== 'all') {
            $val = $request->quick_available;
            if ($val === '1' || $val === 'true') {
                $query->where('is_quick_available', true);
            } elseif ($val === '0' || $val === 'false') {
                $query->where('is_quick_available', false);
            }
        }

        // Search by Product title, SKU, location name, or Seller business name
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('variant', function ($vq) use ($search) {
                    $vq->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($pq) => $pq->where('title', 'like', "%{$search}%"));
                })->orWhereHas('location', function ($lq) use ($search) {
                    $lq->where('name', 'like', "%{$search}%")
                       ->orWhereHas('seller', fn ($sq) => $sq->where('business_name', 'like', "%{$search}%"));
                });
            });
        }

        return $query;
    }

    /**
     * Build filtered query for stock movement ledger.
     */
    public function buildMovementsQuery(Request $request): Builder
    {
        $query = StockMovement::query()
            ->with([
                'inventory.variant.product.seller:id,business_name',
                'inventory.location:id,name',
                'creator:id,name,email',
            ])
            ->latest('id');

        if ($inventoryId = $request->query('inventory_id')) {
            $query->where('inventory_id', (int) $inventoryId);
        }

        if ($type = $request->query('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        if ($sellerId = $request->query('seller_id')) {
            if ($sellerId !== 'all') {
                $query->whereHas('inventory.location', fn ($q) => $q->where('seller_id', (int) $sellerId));
            }
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                  ->orWhereHas('inventory.variant', fn ($vq) => $vq->where('sku', 'like', "%{$search}%"))
                  ->orWhereHas('creator', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Perform an authorized manual inventory adjustment.
     * Prevents negative stock and logs an immutable stock ledger entry.
     */
    public function adjustStock(Inventory $inventory, int $newQuantityOnHand, string $reason, int $adminId): Inventory
    {
        if ($newQuantityOnHand < 0) {
            throw new InvalidArgumentException("Stock quantity on hand cannot be negative.");
        }

        if (empty(trim($reason))) {
            throw new InvalidArgumentException("A valid authorization reason is required for manual stock adjustments.");
        }

        return DB::transaction(function () use ($inventory, $newQuantityOnHand, $reason, $adminId) {
            $updated = $this->inventoryService->adjust(
                $inventory,
                $newQuantityOnHand,
                trim($reason),
                null,
                $adminId
            );

            if (function_exists('activity')) {
                activity('inventory_adjustment')
                    ->performedOn($inventory)
                    ->causedBy($adminId)
                    ->withProperties([
                        'inventory_id' => $inventory->id,
                        'new_quantity' => $newQuantityOnHand,
                        'reason' => $reason,
                    ])
                    ->log("Admin adjusted stock on hand to {$newQuantityOnHand} for SKU " . ($inventory->variant?->sku ?? 'N/A'));
            }

            return $updated;
        });
    }

    /**
     * Export global inventory balances to CSV.
     */
    public function exportInventoryCsv(Request $request): StreamedResponse
    {
        $filename = 'global-inventory-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $query = $this->buildInventoryQuery($request);

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Inventory ID',
                'SKU',
                'Product Title',
                'Category',
                'Variation',
                'Merchant Business',
                'Location Name',
                'Location Type',
                'Quantity On Hand',
                'Quantity Reserved',
                'Quantity Available',
                'Low Stock Threshold',
                'Stock Health',
                'Quick Commerce Eligible',
                'Last Updated',
            ]);

            $query->chunk(200, function ($inventories) use ($handle) {
                foreach ($inventories as $inv) {
                    $available = $inv->quantity_on_hand - $inv->quantity_reserved;
                    $status = $available <= 0 ? 'Out of Stock' : ($available <= $inv->low_stock_threshold ? 'Low Stock' : 'In Stock');

                    fputcsv($handle, [
                        $inv->id,
                        $inv->variant?->sku ?? 'N/A',
                        $inv->variant?->product?->title ?? 'N/A',
                        $inv->variant?->product?->category?->name ?? 'N/A',
                        $inv->variant?->name ?? 'Default',
                        $inv->location?->seller?->business_name ?? 'ALLZONE Direct',
                        $inv->location?->name ?? 'N/A',
                        strtoupper((string) ($inv->location?->type?->value ?? $inv->location?->type ?? 'N/A')),
                        $inv->quantity_on_hand,
                        $inv->quantity_reserved,
                        max(0, $available),
                        $inv->low_stock_threshold,
                        $status,
                        $inv->is_quick_available ? 'Yes' : 'No',
                        $inv->updated_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export stock movements ledger to CSV.
     */
    public function exportMovementsCsv(Request $request): StreamedResponse
    {
        $filename = 'stock-movements-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $query = $this->buildMovementsQuery($request);

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Movement ID',
                'Inventory ID',
                'SKU',
                'Product Title',
                'Merchant',
                'Location',
                'Movement Type',
                'Delta Quantity',
                'Authorization Notes / Reason',
                'Created By',
                'Timestamp',
            ]);

            $query->chunk(200, function ($movements) use ($handle) {
                foreach ($movements as $m) {
                    fputcsv($handle, [
                        $m->id,
                        $m->inventory_id,
                        $m->inventory?->variant?->sku ?? 'N/A',
                        $m->inventory?->variant?->product?->title ?? 'N/A',
                        $m->inventory?->location?->seller?->business_name ?? 'ALLZONE',
                        $m->inventory?->location?->name ?? 'N/A',
                        $m->type instanceof StockMovementType ? $m->type->label() : (string) $m->type,
                        $m->quantity > 0 ? "+{$m->quantity}" : (string) $m->quantity,
                        $m->note ?? '',
                        $m->creator?->name ?? 'System Event',
                        $m->created_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
