<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ALL business logic for stock transfers lives here:
 * stock validation, atomic stock movement, sanitizing, transactions.
 * If a transfer misbehaves, look in this file.
 */
class StockTransferService
{
    /* ----------------------------- READ ----------------------------- */

    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return StockTransfer::with(['source', 'target', 'details.item'])   // eager loading
            ->searchData($search)
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Dependency data for the "create transfer" form. */
    public function createFormData(): array
    {
        return [
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'items'      => Item::orderBy('item_code')->get(['id', 'item_code', 'item_description']),
        ];
    }

    /* ---------------------------- CREATE ---------------------------- */

    /**
     * $data = [
     *   'source_warehouse_id' => 1, 'target_warehouse_id' => 2,
     *   'transfer_date' => '2026-10-27', 'transfer_reason' => 'Restock',
     *   'approved_by_staff' => 'J. Cruz',
     *   'items' => [ ['item_code' => 'hm-001', 'quantity' => 5], ... ],
     * ]
     */
    public function create(array $data): StockTransfer
    {
        $sourceId = (int) $data['source_warehouse_id'];
        $targetId = (int) $data['target_warehouse_id'];

        if ($sourceId === $targetId) {
            throw ValidationException::withMessages([
                'target_warehouse_id' => 'Source and target warehouse must be different.',
            ]);
        }

        // ONE atomic unit: any exception below rolls back everything.
        return DB::transaction(function () use ($data, $sourceId, $targetId) {

            // Lock the SOURCE warehouse row: concurrent transfers out of the same
            // warehouse now run one at a time, so stock can't be double-spent.
            Warehouse::whereKey($sourceId)->lockForUpdate()->first();

            $transfer = StockTransfer::create([
                'source_warehouse_id' => $sourceId,
                'target_warehouse_id' => $targetId,
                'transfer_date'       => $data['transfer_date'],
                'transfer_reason'     => $this->sanitizeText($data['transfer_reason']),
                'approved_by_staff'   => $this->sanitizeText($data['approved_by_staff']),
                'transfer_status'     => 'pending',
            ]);

            foreach ($data['items'] as $line) {
                $code = $this->sanitizeCode($line['item_code']);   // rule 1: uppercase
                $qty  = (int) $line['quantity'];
                $item = Item::where('item_code', $code)->firstOrFail();

                // rule 2: validate source stock BEFORE moving anything
                $available = $this->availableStock($item->id, $sourceId);

                if ($available < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$code}: available {$available}, requested {$qty}.",
                    ]);
                }

                // rule 3: one detail row = stock leaves source AND arrives at target.
                // Stock is derived (received - sent), so both sides change together
                // the moment this transfer is marked completed, inside this transaction.
                $transfer->details()->create([
                    'item_id'              => $item->id,
                    'quantity_transferred' => $qty,
                ]);
            }

            $transfer->update(['transfer_status' => 'completed']);

            return $transfer->load(['source', 'target', 'details.item']);
        });
    }

    /* ---------------------------- UPDATE ---------------------------- */

    /** Only descriptive fields; stock already moved, so nothing else may change. */
    public function update(StockTransfer $transfer, array $data): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            $clean = [];

            if (isset($data['transfer_date'])) {
                $clean['transfer_date'] = $data['transfer_date'];
            }
            if (isset($data['transfer_reason'])) {
                $clean['transfer_reason'] = $this->sanitizeText($data['transfer_reason']);
            }
            if (isset($data['approved_by_staff'])) {
                $clean['approved_by_staff'] = $this->sanitizeText($data['approved_by_staff']);
            }

            $transfer->update($clean);

            return $transfer->load(['source', 'target', 'details.item']);
        });
    }

    /* ------------------------ STOCK CALCULATION ------------------------ */

    /** Stock of one item in one warehouse = completed IN - completed OUT. */
    private function availableStock(int $itemId, int $warehouseId): int
    {
        $base = fn () => DB::table('transfer_details as d')
            ->join('stock_transfers as t', 't.id', '=', 'd.stock_transfer_id')
            ->where('t.transfer_status', 'completed')
            ->where('d.item_id', $itemId);

        $in  = (int) $base()->where('t.target_warehouse_id', $warehouseId)->sum('d.quantity_transferred');
        $out = (int) $base()->where('t.source_warehouse_id', $warehouseId)->sum('d.quantity_transferred');

        return $in - $out;
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
