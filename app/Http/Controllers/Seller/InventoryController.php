<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\InventoryLocationType;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Seller inventory table by location with on-hand, reserved, available, and threshold metrics.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403, 'No seller profile associated.');

        // Ensure seller has at least one inventory location
        $primaryLocation = InventoryLocation::firstOrCreate(
            ['seller_id' => $seller->id],
            [
                'name' => ($seller->business_name ?? 'Main') . ' Warehouse',
                'type' => InventoryLocationType::STORE,
                'is_active' => true,
            ]
        );

        $sellerLocationIds = InventoryLocation::where('seller_id', $seller->id)->pluck('id');

        // Automatically ensure all existing seller variants have an inventory record
        $sellerVariants = ProductVariant::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))->get();
        foreach ($sellerVariants as $v) {
            Inventory::firstOrCreate(
                ['variant_id' => $v->id, 'location_id' => $primaryLocation->id],
                ['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'low_stock_threshold' => 5, 'is_quick_available' => true]
            );
        }

        $tab = $request->query('tab', 'all'); // 'all', 'active', 'out_of_stock', 'low_stock', 'pending', 'blocked', 'paused'
        $categoryId = $request->query('category_id');
        $storeId = $request->query('store_id');
        $search = $request->query('search');
        $sort = $request->query('sort', 'latest');

        $baseQuery = Inventory::query()
            ->whereIn('location_id', $sellerLocationIds)
            ->whereHas('variant.product', fn($q) => $q->where('seller_id', $seller->id))
            ->with([
                'variant.product.category',
                'location.store',
            ]);

        // Filter by Tab
        match ($tab) {
            'active' => $baseQuery->whereHas('variant.product', fn($pq) => $pq->where('status', ProductStatus::ACTIVE))
                ->whereHas('variant', fn($vq) => $vq->where('is_active', true)),
            'out_of_stock' => $baseQuery->whereRaw('(quantity_on_hand - quantity_reserved) <= 0'),
            'low_stock' => $baseQuery->whereRaw('(quantity_on_hand - quantity_reserved) > 0')
                ->whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold'),
            'pending' => $baseQuery->whereHas('variant.product', fn($pq) => $pq->where('status', ProductStatus::PENDING_REVIEW)),
            'blocked' => $baseQuery->whereHas('variant.product', fn($pq) => $pq->where('status', ProductStatus::REJECTED)),
            'paused' => $baseQuery->where(function ($q) {
                $q->whereHas('variant.product', fn($pq) => $pq->whereIn('status', [ProductStatus::INACTIVE, ProductStatus::DRAFT]))
                  ->orWhereHas('variant', fn($vq) => $vq->where('is_active', false));
            }),
            default => null, // 'all'
        };

        // Category filter
        if ($categoryId) {
            $baseQuery->whereHas('variant.product', fn($pq) => $pq->where('category_id', $categoryId));
        }

        // Store outlet filter
        if ($storeId) {
            $storeLocationIds = InventoryLocation::where('seller_id', $seller->id)
                ->where('store_id', (int) $storeId)
                ->pluck('id');
            $baseQuery->whereIn('location_id', $storeLocationIds);
        }

        // Search by Product title, SKU, or barcode
        if ($search) {
            $searchTerm = trim((string) $search);
            $baseQuery->whereHas('variant', function ($vq) use ($searchTerm) {
                $vq->where('sku', 'like', "%{$searchTerm}%")
                    ->orWhere('barcode', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('title', 'like', "%{$searchTerm}%"));
            });
        }

        // Sorting
        match ($sort) {
            'stock_asc' => $baseQuery->orderByRaw('(quantity_on_hand - quantity_reserved) ASC'),
            'stock_desc' => $baseQuery->orderByRaw('(quantity_on_hand - quantity_reserved) DESC'),
            'sku_asc' => $baseQuery->join('product_variants', 'inventory.variant_id', '=', 'product_variants.id')->orderBy('product_variants.sku', 'ASC')->select('inventory.*'),
            default => $baseQuery->latest('inventory.updated_at'),
        };

        $inventories = $baseQuery->paginate(15)->withQueryString();

        // Calculate KPI Aggregates across all seller inventory items
        $allSellerInventories = Inventory::whereIn('location_id', $sellerLocationIds)
            ->whereHas('variant.product', fn($q) => $q->where('seller_id', $seller->id))
            ->with('variant.product')
            ->get();

        $inventoryIds = $allSellerInventories->pluck('id')->all();

        $totalOnHand = (int) $allSellerInventories->sum('quantity_on_hand');
        $totalReserved = (int) $allSellerInventories->sum('quantity_reserved');
        $totalAvailable = max(0, $totalOnHand - $totalReserved);

        $outOfStockCount = $allSellerInventories->filter(fn ($i) => ($i->quantity_on_hand - $i->quantity_reserved) <= 0)->count();
        $lowStockCount = $allSellerInventories->filter(fn ($i) => ($i->quantity_on_hand - $i->quantity_reserved) > 0 && ($i->quantity_on_hand - $i->quantity_reserved) <= $i->low_stock_threshold)->count();
        $inStockCount = $allSellerInventories->filter(fn ($i) => ($i->quantity_on_hand - $i->quantity_reserved) > $i->low_stock_threshold)->count();

        // Unique listed products and SKUs
        $totalProducts = Product::where('seller_id', $seller->id)->count();
        $totalSkus = $sellerVariants->count();

        // Stock changes logged in last 30 days
        $inventoryChanges30d = StockMovement::whereIn('inventory_id', $inventoryIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $metrics = [
            'total_products' => $totalProducts,
            'total_skus' => $totalSkus,
            'in_stock_skus' => $inStockCount,
            'low_stock_skus' => $lowStockCount,
            'out_of_stock_skus' => $outOfStockCount,
            'total_on_hand' => $totalOnHand,
            'total_reserved' => $totalReserved,
            'total_available' => $totalAvailable,
            'inventory_changes_30d' => $inventoryChanges30d,
        ];

        // Tab counts
        $counts = [
            'all' => $allSellerInventories->count(),
            'active' => $allSellerInventories->filter(fn($i) => $i->variant?->product?->status === ProductStatus::ACTIVE && $i->variant?->is_active)->count(),
            'out_of_stock' => $outOfStockCount,
            'low_stock' => $lowStockCount,
            'pending' => $allSellerInventories->filter(fn($i) => $i->variant?->product?->status === ProductStatus::PENDING_REVIEW)->count(),
            'blocked' => $allSellerInventories->filter(fn($i) => $i->variant?->product?->status === ProductStatus::REJECTED)->count(),
            'paused' => $allSellerInventories->filter(fn($i) => in_array($i->variant?->product?->status, [ProductStatus::INACTIVE, ProductStatus::DRAFT]) || !$i->variant?->is_active)->count(),
        ];

        $categories = Category::query()->orderBy('name')->get();
        $stores = $seller->stores()->orderBy('name')->get(['id', 'name', 'slug']);
        $locations = InventoryLocation::where('seller_id', $seller->id)->where('is_active', true)->get(['id', 'name', 'type', 'store_id']);

        return view('seller.inventory.index', [
            'tab' => $tab,
            'counts' => $counts,
            'metrics' => $metrics,
            'inventories' => $inventories,
            'categories' => $categories,
            'stores' => $stores,
            'locations' => $locations,
            'search' => $search,
            'categoryId' => $categoryId,
            'storeId' => $storeId,
            'sort' => $sort,
        ]);
    }

    /**
     * Inline quick edit for on-hand stock quantity and low-stock threshold.
     */
    public function update(Request $request, Inventory $inventory): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $inventory->location->seller_id !== $seller->id, 403, 'Unauthorized inventory record.');

        $validated = $request->validate([
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_quick_available' => ['nullable', 'boolean'],
        ]);

        $newQty = (int) $validated['quantity_on_hand'];
        $oldQty = (int) $inventory->quantity_on_hand;

        if ($newQty !== $oldQty) {
            $reason = $validated['reason'] ?? "Stock adjusted from {$oldQty} to {$newQty} by merchant";
            $this->inventoryService->adjust($inventory, $newQty, $reason, null, Auth::id());
        }

        $inventory->update([
            'low_stock_threshold' => (int) $validated['low_stock_threshold'],
            'is_quick_available' => (bool) ($validated['is_quick_available'] ?? false),
        ]);

        if ($request->wantsJson()) {
            $fresh = $inventory->fresh();
            return response()->json([
                'success' => true,
                'message' => "Stock updated for SKU: {$fresh->variant->sku}",
                'inventory' => [
                    'id' => $fresh->id,
                    'quantity_on_hand' => $fresh->quantity_on_hand,
                    'quantity_reserved' => $fresh->quantity_reserved,
                    'available_quantity' => $fresh->available_quantity,
                    'low_stock_threshold' => $fresh->low_stock_threshold,
                    'is_quick_available' => $fresh->is_quick_available,
                ],
            ]);
        }

        return back()->with('success', "Stock details updated for SKU: {$inventory->variant->sku}");
    }

    /**
     * Bulk stock update (add stock or set fixed stock).
     */
    public function bulkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403, 'Unauthorized seller profile.');

        $validated = $request->validate([
            'inventory_ids' => ['required', 'array', 'min:1'],
            'inventory_ids.*' => ['required', 'integer'],
            'action_type' => ['required', 'in:add_stock,set_stock'],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $sellerLocationIds = InventoryLocation::where('seller_id', $seller->id)->pluck('id');
        $inventories = Inventory::whereIn('id', $validated['inventory_ids'])
            ->whereIn('location_id', $sellerLocationIds)
            ->with('variant')
            ->get();

        $actionType = $validated['action_type'];
        $qty = (int) $validated['quantity'];
        $reason = $validated['reason'] ?? ($actionType === 'add_stock' ? "Bulk inward replenishment (+{$qty} units)" : "Bulk stock level adjustment (Set to {$qty} units)");
        $userId = Auth::id();
        $updatedCount = 0;

        DB::transaction(function () use ($inventories, $actionType, $qty, $reason, $userId, &$updatedCount) {
            foreach ($inventories as $inv) {
                if ($actionType === 'add_stock') {
                    if ($qty > 0) {
                        $this->inventoryService->restock($inv, $qty, $reason, null, $userId);
                        $updatedCount++;
                    }
                } else {
                    $this->inventoryService->adjust($inv, $qty, $reason, null, $userId);
                    $updatedCount++;
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully updated stock for {$updatedCount} items.",
                'updated_count' => $updatedCount,
            ]);
        }

        return back()->with('success', "Bulk inventory update applied to {$updatedCount} items.");
    }

    /**
     * Retrieve stock movements history for an inventory item.
     */
    public function movements(Inventory $inventory): JsonResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $inventory->location->seller_id !== $seller->id, 403, 'Unauthorized inventory record.');

        $movements = $inventory->movements()
            ->with('creator:id,name')
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type->value,
                'type_label' => $m->type->label(),
                'quantity' => $m->quantity,
                'note' => $m->note,
                'user' => $m->creator?->name ?? 'System',
                'created_at' => $m->created_at->format('d M Y, h:i A'),
            ]);

        return response()->json([
            'success' => true,
            'sku' => $inventory->variant->sku,
            'product_title' => $inventory->variant->product?->title,
            'location' => $inventory->location->name,
            'current_on_hand' => $inventory->quantity_on_hand,
            'current_available' => $inventory->available_quantity,
            'current_reserved' => $inventory->quantity_reserved,
            'movements' => $movements,
        ]);
    }

    /**
     * Inter-location stock transfer.
     */
    public function transfer(Request $request): RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'from_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
            'to_location_id' => ['required', 'integer', 'exists:inventory_locations,id', 'different:from_location_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $fromLoc = InventoryLocation::where('seller_id', $seller->id)->findOrFail($validated['from_location_id']);
        $toLoc = InventoryLocation::where('seller_id', $seller->id)->findOrFail($validated['to_location_id']);

        $variant = ProductVariant::whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->findOrFail($validated['variant_id']);

        try {
            $this->inventoryService->transfer(
                $variant->id,
                $fromLoc,
                $toLoc,
                (int) $validated['quantity'],
                $validated['note'] ?? 'Internal stock balance re-route'
            );
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return back()->with('warning', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            return back()->with('warning', 'Unable to complete transfer: ' . $e->getMessage())->withInput();
        }

        return back()->with('success', "Transferred {$validated['quantity']} units of {$variant->sku} from {$fromLoc->name} to {$toLoc->name}.");
    }

    /**
     * Bulk stock update via CSV import with parsing, validation, and error report.
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->withErrors(['csv_file' => 'Unable to read the uploaded CSV file.']);
        }

        // Header check
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV file is empty.']);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $skuCol = array_search('sku', $header, true);
        $qtyCol = array_search('quantity_on_hand', $header, true);
        $locCol = array_search('location_id', $header, true);
        $thresholdCol = array_search('low_stock_threshold', $header, true);

        if ($skuCol === false || $qtyCol === false) {
            fclose($handle);
            return back()->withErrors(['csv_file' => "CSV must contain at least 'sku' and 'quantity_on_hand' columns."]);
        }

        $defaultLocation = InventoryLocation::where('seller_id', $seller->id)->first();
        if (!$defaultLocation) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'No active inventory location found for this seller account.']);
        }

        $successCount = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) {
                continue;
            }

            $sku = strtoupper(trim((string) ($row[$skuCol] ?? '')));
            $qty = trim((string) ($row[$qtyCol] ?? ''));
            $locId = ($locCol !== false && !empty($row[$locCol])) ? (int) $row[$locCol] : $defaultLocation->id;
            $threshold = ($thresholdCol !== false && !empty($row[$thresholdCol])) ? (int) $row[$thresholdCol] : 5;

            if (empty($sku)) {
                $errors[] = "Row {$rowNum}: SKU is missing.";
                continue;
            }

            if (!is_numeric($qty) || (int) $qty < 0) {
                $errors[] = "Row {$rowNum}: Invalid quantity '{$qty}' for SKU {$sku}.";
                continue;
            }

            $variant = ProductVariant::where('sku', $sku)
                ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
                ->first();

            if (!$variant) {
                $errors[] = "Row {$rowNum}: SKU '{$sku}' not found in your catalogue.";
                continue;
            }

            $location = InventoryLocation::where('seller_id', $seller->id)->find($locId);
            if (!$location) {
                $errors[] = "Row {$rowNum}: Location ID {$locId} does not exist or does not belong to your store.";
                continue;
            }

            $inv = Inventory::firstOrCreate(
                ['variant_id' => $variant->id, 'location_id' => $location->id],
                ['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'low_stock_threshold' => $threshold]
            );

            $this->inventoryService->adjust($inv, (int) $qty, "Bulk CSV import (Row {$rowNum})");
            $inv->update(['low_stock_threshold' => $threshold]);
            $successCount++;
        }

        fclose($handle);

        $flashType = empty($errors) ? 'success' : 'warning';
        $message = "CSV Bulk Import complete: {$successCount} item(s) updated successfully.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " error(s) encountered.";
        }

        return back()->with($flashType, $message)->with('import_errors', array_slice($errors, 0, 15));
    }

    /**
     * Download sample CSV template.
     */
    public function sampleCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventory-import-sample.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['sku', 'location_id', 'quantity_on_hand', 'low_stock_threshold']);
            fputcsv($handle, ['SHIRT-BLK-M', '1', '100', '5']);
            fputcsv($handle, ['JEANS-BLU-32', '1', '50', '5']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export full inventory balances to streaming CSV with BOM.
     */
    public function export(Request $request): StreamedResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403, 'No seller profile associated.');

        $sellerLocationIds = InventoryLocation::where('seller_id', $seller->id)->pluck('id');

        $query = Inventory::query()
            ->whereIn('location_id', $sellerLocationIds)
            ->whereHas('variant.product', fn($q) => $q->where('seller_id', $seller->id))
            ->with([
                'variant.product.category',
                'location.store',
            ]);

        $tab = $request->query('tab', 'all');
        match ($tab) {
            'active' => $query->whereHas('variant.product', fn($pq) => $pq->where('status', ProductStatus::ACTIVE)),
            'out_of_stock' => $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0'),
            'low_stock' => $query->whereRaw('(quantity_on_hand - quantity_reserved) > 0')->whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold'),
            'pending' => $query->whereHas('variant.product', fn($pq) => $pq->where('status', ProductStatus::PENDING_REVIEW)),
            'blocked' => $query->whereHas('variant.product', fn($pq) => $pq->where('status', ProductStatus::REJECTED)),
            'paused' => $query->whereHas('variant.product', fn($pq) => $pq->whereIn('status', [ProductStatus::INACTIVE, ProductStatus::DRAFT])),
            default => null,
        };

        $filename = 'inventory-balances-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'Product Title',
                'SKU',
                'Variation',
                'Category',
                'Store Outlet',
                'Location Hub',
                'Current Stock (On Hand)',
                'Reserved Quantity',
                'Available Quantity',
                'Low Stock Threshold',
                'Product Status',
                'Stock Health',
            ]);

            $query->chunk(250, function ($inventories) use ($handle) {
                foreach ($inventories as $inv) {
                    $available = $inv->quantity_on_hand - $inv->quantity_reserved;
                    $status = match (true) {
                        $available <= 0 => 'Out of Stock',
                        $available <= $inv->low_stock_threshold => 'Low Stock Alert',
                        default => 'In Stock',
                    };

                    fputcsv($handle, [
                        $inv->variant?->product?->title ?? 'N/A',
                        $inv->variant?->sku ?? 'N/A',
                        $inv->variant?->name ?? 'Default',
                        $inv->variant?->product?->category?->name ?? 'General',
                        $inv->location?->store?->name ?? 'Default Store',
                        $inv->location?->name ?? 'N/A',
                        $inv->quantity_on_hand,
                        $inv->quantity_reserved,
                        max(0, $available),
                        $inv->low_stock_threshold,
                        $inv->variant?->product?->status?->label() ?? 'Active',
                        $status,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
