<?php

namespace App\Services;

use App\Exceptions\StockTransferException;
use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * ALL business logic for stock transfers lives here:
 * stock validation, atomic stock movement, sanitizing, transactions.
 * If a transfer misbehaves, look in this file.
 */
class StockTransferService
{
    public function __construct(
        private readonly WarehouseService $warehouseService
    ) {}

    /* ----------------------------- READ ----------------------------- */

    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return StockTransfer::with([
                'source:id,name',
                'target:id,name',
                'details.item:id,item_code,item_description',
            ])
            ->searchData($search)
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Dependency data for the "create transfer" form. */
    public function createFormData(): array
    {
        return [
            'warehouses' => $this->warehouseService->listForSelect(),
            'items'      => Item::orderBy('item_code')->get(['id', 'item_code', 'item_description']),
        ];
    }

    /* ---------------------------- CREATE ---------------------------- */

    /**
     * $data = [
     *   'source_warehouse_id' => 1, 'target_warehouse_id' => 2,
     *   'transfer_date' => '2026-10-27', 'transfer_reason' => 'Restock',
     *   'items' => [ ['item_code' => 'hm-001', 'quantity' => 5], ... ],
     * ]
     * The authenticated approver is passed separately so client input cannot override it.
     */
    public function create(array $data, string $approvedBy): StockTransfer
    {
        $sourceId = (int) $data['source_warehouse_id'];
        $targetId = (int) $data['target_warehouse_id'];

        if ($sourceId === $targetId) {
            throw new StockTransferException(StockTransferException::SAME_WAREHOUSE);
        }

        // ONE atomic unit: any exception below rolls back everything.
        return DB::transaction(function () use ($data, $sourceId, $targetId, $approvedBy) {

            // Lock the SOURCE warehouse row: concurrent transfers out of the same
            // warehouse now run one at a time, so stock can't be double-spent.
            Warehouse::whereKey($sourceId)->lockForUpdate()->firstOrFail();

            $lines = [];
            $requestedByCode = [];
            if (empty($data['items'])) {
                throw new StockTransferException(StockTransferException::ITEMS_REQUIRED);
            }

            foreach ($data['items'] as $line) {
                $code = $this->sanitizeCode($line['item_code']);
                $quantity = (int) $line['quantity'];
                if ($quantity < 1) {
                    throw new StockTransferException(
                        StockTransferException::INVALID_QUANTITY,
                        ['code' => $code]
                    );
                }

                $requestedByCode[$code] = ($requestedByCode[$code] ?? 0) + $quantity;
                $lines[] = ['item_code' => $code, 'quantity' => $quantity];
            }

            $itemsByCode = Item::whereIn('item_code', array_keys($requestedByCode))
                ->get(['id', 'item_code'])
                ->keyBy('item_code');

            foreach ($requestedByCode as $code => $quantity) {
                if (! $itemsByCode->has($code)) {
                    throw new StockTransferException(
                        StockTransferException::ITEM_NOT_FOUND,
                        ['code' => $code]
                    );
                }
            }

            $itemIds = $itemsByCode->pluck('id')->all();
            $availableStock = $this->availableStockForItems($itemIds, $sourceId);

            $transfer = StockTransfer::create([
                'source_warehouse_id' => $sourceId,
                'target_warehouse_id' => $targetId,
                'transfer_date'       => $data['transfer_date'],
                'transfer_reason'     => $this->sanitizeText($data['transfer_reason']),
                'approved_by_staff'   => $this->sanitizeText($approvedBy),
                'transfer_status'     => 'pending',
            ]);

            foreach ($requestedByCode as $code => $quantity) {
                $item = $itemsByCode->get($code);
                $available = (int) ($availableStock[$item->id] ?? 0);

                if ($available < $quantity) {
                    throw new StockTransferException(
                        StockTransferException::INSUFFICIENT_STOCK,
                        ['code' => $code, 'available' => $available, 'requested' => $quantity]
                    );
                }
            }

            foreach ($lines as $line) {
                $item = $itemsByCode->get($line['item_code']);
                $transfer->details()->create([
                    'item_id'              => $item->id,
                    'quantity_transferred' => $line['quantity'],
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

            $transfer->update($clean);

            return $transfer->load(['source', 'target', 'details.item']);
        });
    }

    /* ------------------------ STOCK CALCULATION ------------------------ */

    /** Stock per item in one warehouse = completed IN - completed OUT. */
    private function availableStockForItems(array $itemIds, int $warehouseId): array
    {
        return DB::table('transfer_details as d')
            ->join('stock_transfers as t', 't.id', '=', 'd.stock_transfer_id')
            ->where('t.transfer_status', 'completed')
            ->whereIn('d.item_id', $itemIds)
            ->where(function ($query) use ($warehouseId) {
                $query->where('t.target_warehouse_id', $warehouseId)
                    ->orWhere('t.source_warehouse_id', $warehouseId);
            })
            ->groupBy('d.item_id')
            ->select('d.item_id')
            ->selectRaw(
                'SUM(CASE WHEN t.target_warehouse_id = ? THEN d.quantity_transferred ELSE 0 END)
                - SUM(CASE WHEN t.source_warehouse_id = ? THEN d.quantity_transferred ELSE 0 END) as quantity',
                [$warehouseId, $warehouseId]
            )
            ->pluck('quantity', 'd.item_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
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
