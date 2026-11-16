<?php

use App\Models\Item;
use App\Models\Supplier;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_can_be_created_from_opening_stock(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $source = Warehouse::create(['name' => 'Silay Branch', 'location' => 'Silay']);
        $target = Warehouse::create(['name' => 'Istad Branch', 'location' => 'Istad']);

        $this->post('/items', [
            'item_code' => 'hw-1002',
            'item_description' => 'Alloy pipe',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $source->id, 'quantity' => 12],
            ],
        ])->assertRedirect('/items');

        $item = Item::where('item_description', 'Alloy pipe')->firstOrFail();

        $this->post('/transfers', [
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'transfer_date' => Carbon::today()->toDateString(),
            'transfer_reason' => 'Relocation',
            'approved_by_staff' => 'Ako Eh',
            'items' => [
                ['item_code' => $item->item_code, 'quantity' => 5],
            ],
        ])->assertRedirect('/transfers');

        $this->assertDatabaseHas('stock_transfers', [
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'transfer_status' => 'completed',
        ]);
    }

    public function test_transfer_rejects_when_source_stock_is_insufficient(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $source = Warehouse::create(['name' => 'Silay Branch', 'location' => 'Silay']);
        $target = Warehouse::create(['name' => 'Istad Branch', 'location' => 'Istad']);

        $this->post('/items', [
            'item_code' => 'hw-2003',
            'item_description' => 'Steel beam',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $source->id, 'quantity' => 3],
            ],
        ])->assertRedirect('/items');

        $item = Item::where('item_description', 'Steel beam')->firstOrFail();

        $this->from('/transfers/create')
            ->post('/transfers', [
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'transfer_date' => Carbon::today()->toDateString(),
                'transfer_reason' => 'Relocation',
                'approved_by_staff' => 'Ako Eh',
                'items' => [
                    ['item_code' => $item->item_code, 'quantity' => 5],
                ],
            ])
            ->assertSessionHasErrors(['items']);
    }

    public function test_transfer_reports_each_item_code_that_does_not_exist(): void
    {
        $source = Warehouse::create(['name' => 'Silay Branch']);
        $target = Warehouse::create(['name' => 'Istad Branch']);

        $this->from('/transfers/create')
            ->post('/transfers', [
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'transfer_date' => Carbon::today()->toDateString(),
                'transfer_reason' => 'Relocation',
                'approved_by_staff' => 'Ako Eh',
                'items' => [
                    ['item_code' => 'MISSING-1', 'quantity' => 1],
                    ['item_code' => 'MISSING-2', 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors(['items.0.item_code', 'items.1.item_code']);

        $this->assertDatabaseCount('stock_transfers', 0);
    }

    public function test_transfer_accepts_a_past_transfer_date(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $source = Warehouse::create(['name' => 'Silay Branch', 'location' => 'Silay']);
        $target = Warehouse::create(['name' => 'Istad Branch', 'location' => 'Istad']);

        $this->post('/items', [
            'item_code' => 'HW-PAST-DATE',
            'item_description' => 'Test item',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $source->id, 'quantity' => 5],
            ],
        ])->assertRedirect('/items');

        $item = Item::where('item_description', 'Test item')->firstOrFail();
        $pastDate = Carbon::yesterday()->toDateString();
        $this->post('/transfers', [
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'transfer_date' => $pastDate,
                'transfer_reason' => 'Relocation',
                'approved_by_staff' => 'Ako Eh',
                'items' => [
                    ['item_code' => $item->item_code, 'quantity' => 1],
                ],
            ])
            ->assertRedirect('/transfers');

        $this->assertTrue(StockTransfer::whereDate('transfer_date', $pastDate)->exists());
    }

    public function test_transfer_rejects_a_future_transfer_date(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $source = Warehouse::create(['name' => 'Silay Branch', 'location' => 'Silay']);
        $target = Warehouse::create(['name' => 'Istad Branch', 'location' => 'Istad']);

        $this->post('/items', [
            'item_code' => 'HW-FUTURE-DATE',
            'item_description' => 'Test item',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $source->id, 'quantity' => 5],
            ],
        ])->assertRedirect('/items');

        $item = Item::where('item_description', 'Test item')->firstOrFail();
        $transferCount = StockTransfer::count();
        $this->from('/transfers/create')
            ->post('/transfers', [
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'transfer_date' => Carbon::tomorrow()->toDateString(),
            'transfer_reason' => 'Relocation',
            'approved_by_staff' => 'Ako Eh',
            'items' => [
                ['item_code' => $item->item_code, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors([
            'transfer_date' => 'Transfer date cannot be in the future.',
        ]);

        $this->assertSame($transferCount, StockTransfer::count());
    }
}
