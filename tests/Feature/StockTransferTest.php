<?php

use App\Models\Item;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $item = Item::where('item_code', 'HW-1002')->firstOrFail();

        $this->post('/transfers', [
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'transfer_date' => '2026-11-03',
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

        $this->from('/transfers/create')
            ->post('/transfers', [
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'transfer_date' => '2026-11-03',
                'transfer_reason' => 'Relocation',
                'approved_by_staff' => 'Ako Eh',
                'items' => [
                    ['item_code' => 'HW-2003', 'quantity' => 5],
                ],
            ])
            ->assertSessionHasErrors(['items']);
    }
}
