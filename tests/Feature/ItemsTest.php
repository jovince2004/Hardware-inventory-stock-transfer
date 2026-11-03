<?php

use App\Models\Item;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_be_created_from_the_item_form(): void
    {
        $this->post('/suppliers', ['name' => 'Acme Supply'])
            ->assertRedirect('/items/create');

        $this->assertDatabaseHas('suppliers', ['name' => 'Acme Supply']);
    }

    public function test_warehouse_can_be_created_from_the_item_form(): void
    {
        $this->post('/warehouses', [
            'name' => 'North Warehouse',
            'location' => 'Building A',
        ])->assertRedirect('/items/create');

        $warehouse = Warehouse::where('name', 'North Warehouse')->firstOrFail();
        $supplier = Supplier::create(['name' => 'Acme Supply']);

        $this->get('/items/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('item/create')
                ->where('warehouses.0.id', $warehouse->id)
                ->where('warehouses.0.name', 'North Warehouse'));

        $this->post('/items', [
            'item_code' => 'hw-warehouse-test',
            'item_description' => 'Test warehouse item',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $warehouse->id, 'quantity' => 3],
            ],
        ])->assertRedirect('/items');

        $item = Item::where('item_code', 'HW-WAREHOUSE-TEST')->firstOrFail();
        $this->assertDatabaseHas('transfer_details', [
            'item_id' => $item->id,
            'quantity_transferred' => 3,
        ]);
        $this->assertDatabaseHas('warehouses', [
            'name' => 'North Warehouse',
            'location' => 'Building A',
        ]);
    }

    public function test_item_dashboard_displays_inventory_totals(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse']);

        $this->post('/items', [
            'item_code' => 'hw-1001',
            'item_description' => 'Wireless keyboard',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $warehouse->id, 'quantity' => 8],
            ],
        ])->assertRedirect('/items');

        $this->get('/items')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('item/index')
                ->has('items.data', 1)
                ->where('summary.items', 1)
                ->where('summary.suppliers', 1)
                ->where('summary.warehouses', 1)
                ->where('summary.stockUnits', 8)
                ->where('items.data.0.item_code', 'HW-1001')
                ->where('items.data.0.stock.0.quantity', 8));
    }

    public function test_item_can_be_created_with_opening_stock(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse']);

        $this->post('/items', [
            'item_code' => 'hw-2002',
            'item_description' => 'USB-C adapter',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $warehouse->id, 'quantity' => 4],
            ],
        ])->assertRedirect('/items');

        $item = Item::where('item_code', 'HW-2002')->firstOrFail();

        $this->assertDatabaseHas('transfer_details', [
            'item_id' => $item->id,
            'quantity_transferred' => 4,
        ]);

        $this->get('/items/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('item/create')
                ->has('suppliers', 1)
                ->has('warehouses', 1));
    }
}
