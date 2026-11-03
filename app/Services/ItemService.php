<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * ALL business logic for items lives here:
 * reading, sanitizing, transactions, stock display.
 * If item behavior breaks, look in this file.
 */
class ItemService
{
    /* ----------------------------- READ ----------------------------- */

    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $page = Item::with('supplier')                 // eager loading
            ->searchData($search)
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString();

        // attach calculated stock-per-warehouse to each item on this page
        $stock = $this->stockMap($page->getCollection()->pluck('id')->all());
        $page->getCollection()->each(
            fn (Item $item) => $item->setAttribute('stock', $stock[$item->id] ?? [])
        );

        return $page;
    }

    /** Dependency data for the "create item" form. */
    public function createFormData(): array
    {
        return [
            'suppliers'  => Supplier::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
        ];
    }

    /** Summary values for the inventory dashboard. */
    public function dashboardSummary(): array
    {
        return [
            'items'      => Item::count(),
            'suppliers'  => Supplier::count(),
            'warehouses' => Warehouse::count(),
            'stockUnits' => DB::table('transfer_details as d')
                ->join('stock_transfers as t', 't.id', '=', 'd.stock_transfer_id')
                ->where('t.transfer_status', 'completed')
                ->whereNull('t.source_warehouse_id')
                ->sum('d.quantity_transferred'),
        ];
    }

    /* ---------------------------- CREATE ---------------------------- */

    public function create(array $data): Item
    {
        return DB::transaction(function () use ($data) {
            $item = Item::create([
                'item_code'        => $this->sanitizeCode($data['item_code']),
                'item_description' => $this->sanitizeText($data['item_description']),
                'supplier_id'      => (int) $data['supplier_id'],
            ]);

            // Opening stock is recorded as a completed "receipt" (source = NULL)
            // so it lives in the same transfer ledger as every other movement.
            foreach ($data['stocks'] ?? [] as $stock) {
                $qty = (int) $stock['quantity'];
                if ($qty < 1) {
                    continue;
                }

                $receipt = StockTransfer::create([
                    'source_warehouse_id' => null,
                    'target_warehouse_id' => (int) $stock['warehouse_id'],
                    'transfer_date'       => now()->toDateString(),
                    'transfer_reason'     => 'Opening stock',
                    'approved_by_staff'   => 'System',
                    'transfer_status'     => 'completed',
                ]);

                $receipt->details()->create([
                    'item_id'              => $item->id,
                    'quantity_transferred' => $qty,
                ]);
            }

            return $this->withStock($item->load('supplier'));
        });
    }

    /* ---------------------------- UPDATE ---------------------------- */

    public function update(Item $item, array $data): Item
    {
        return DB::transaction(function () use ($item, $data) {
            $clean = [];

            if (isset($data['item_code'])) {
                $clean['item_code'] = $this->sanitizeCode($data['item_code']);
            }
            if (isset($data['item_description'])) {
                $clean['item_description'] = $this->sanitizeText($data['item_description']);
            }
            if (isset($data['supplier_id'])) {
                $clean['supplier_id'] = (int) $data['supplier_id'];
            }

            $item->update($clean);

            return $this->withStock($item->load('supplier'));
        });
    }

    /* ------------------------ STOCK (display) ------------------------ */

    private function withStock(Item $item): Item
    {
        $item->setAttribute('stock', $this->stockMap([$item->id])[$item->id] ?? []);

        return $item;
    }

    /**
     * Stock = everything received in a warehouse - everything sent out of it
     * (completed transfers only). Returns [item_id => [ {warehouse_id, warehouse, quantity}, ... ]].
     */
    private function stockMap(array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        $base = fn () => DB::table('transfer_details as d')
            ->join('stock_transfers as t', 't.id', '=', 'd.stock_transfer_id')
            ->where('t.transfer_status', 'completed')
            ->whereIn('d.item_id', $itemIds);

        $incoming = $base()
            ->groupBy('d.item_id', 't.target_warehouse_id')
            ->get(['d.item_id', 't.target_warehouse_id as warehouse_id', DB::raw('SUM(d.quantity_transferred) as qty')]);

        $outgoing = $base()
            ->whereNotNull('t.source_warehouse_id')
            ->groupBy('d.item_id', 't.source_warehouse_id')
            ->get(['d.item_id', 't.source_warehouse_id as warehouse_id', DB::raw('SUM(d.quantity_transferred) as qty')]);

        $totals = [];
        foreach ($incoming as $row) {
            $totals[$row->item_id][$row->warehouse_id] = ($totals[$row->item_id][$row->warehouse_id] ?? 0) + (int) $row->qty;
        }
        foreach ($outgoing as $row) {
            $totals[$row->item_id][$row->warehouse_id] = ($totals[$row->item_id][$row->warehouse_id] ?? 0) - (int) $row->qty;
        }

        $names = Warehouse::pluck('name', 'id');
        $map = [];
        foreach ($totals as $itemId => $byWarehouse) {
            foreach ($byWarehouse as $warehouseId => $qty) {
                $map[$itemId][] = [
                    'warehouse_id' => $warehouseId,
                    'warehouse'    => $names[$warehouseId] ?? null,
                    'quantity'     => $qty,
                ];
            }
        }

        return $map;
    }

    /* -------------------------- SANITIZERS -------------------------- */

    private function sanitizeCode(string $code): string
    {
        return strtoupper(trim(strip_tags($code)));
    }

    private function sanitizeText(string $text): string
    {
        return trim(strip_tags($text));
    }
}
