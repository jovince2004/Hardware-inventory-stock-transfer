<?php

use App\Models\Item;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_be_created_from_the_item_form(): void
    {
        $this->post('/suppliers', ['name' => ' <b>Acme Supply</b> '])
            ->assertRedirect('/items/create');

        $this->assertDatabaseHas('suppliers', ['name' => 'Acme Supply']);
    }

    public function test_supplier_name_rejects_unstructured_input(): void
    {
        $this->post('/suppliers', ['name' => ' dhgauwerhb234 '])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_item_description_rejects_unstructured_input_on_create_and_update(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);

        foreach ([
            ' dsjbasgbasdvnjbefg ',
            ' yehbaefg3eh ',
            ' seoajr;klbmateh ',
            ' ODFGIAFJPRFMARFH ',
        ] as $description) {
            $this->post('/items', [
                'item_description' => $description,
                'supplier_id' => $supplier->id,
            ])->assertSessionHasErrors('item_description');
        }

        $this->assertDatabaseMissing('items', ['item_description' => 'dsjbasgbasdvnjbefg']);
        $this->assertDatabaseCount('items', 0);

        $item = Item::create([
            'item_code' => 'HW-TEST-1',
            'item_description' => 'Wireless keyboard',
            'supplier_id' => $supplier->id,
        ]);

        $this->put("/items/{$item->id}", [
            'item_description' => ' dsjbasgbasdvnjbefg ',
        ])->assertSessionHasErrors('item_description');

        $this->assertSame('Wireless keyboard', $item->fresh()->item_description);
    }

    public function test_item_description_is_trimmed_and_sanitized_when_created(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);

        $this->post('/items', [
            'item_description' => '   ',
            'supplier_id' => $supplier->id,
        ])->assertSessionHasErrors('item_description');

        $this->post('/items', [
            'item_description' => '  <b>Wireless keyboard</b>  ',
            'supplier_id' => $supplier->id,
        ])->assertRedirect('/items');

        $item = Item::where('item_description', 'Wireless keyboard')->firstOrFail();

        $this->put("/items/{$item->id}", [
            'item_description' => '  <i> USB-C keyboard </i>  ',
        ])->assertRedirect('/items');

        $this->assertSame('USB-C keyboard', $item->fresh()->item_description);

        $this->put("/items/{$item->id}", [
            'item_description' => '   ',
        ])->assertSessionHasErrors('item_description');

        $this->assertSame('USB-C keyboard', $item->fresh()->item_description);
    }

    public function test_warehouse_can_be_created_from_the_item_form(): void
    {
        $this->post('/warehouses', [
            'name' => ' <b>North Warehouse</b> ',
            'location' => ' <i>Building A</i> ',
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

        $item = Item::where('item_description', 'Test warehouse item')->firstOrFail();
        $this->assertDatabaseHas('transfer_details', [
            'item_id' => $item->id,
            'quantity_transferred' => 3,
        ]);
        $this->assertMatchesRegularExpression('/^HW-\d{6,}$/', $item->item_code);
        $this->assertDatabaseHas('warehouses', [
            'name' => 'North Warehouse',
            'location' => 'Building A',
        ]);
    }

    public function test_dashboard_displays_inventory_totals_and_item_list_displays_items(): void
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

        $item = Item::where('item_description', 'Wireless keyboard')->firstOrFail();
        $this->assertMatchesRegularExpression('/^HW-\d{6,}$/', $item->item_code);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('summary.items', 1)
                ->where('summary.suppliers', 1)
                ->where('summary.warehouses', 1)
                ->where('summary.stockUnits', 8));

        $this->get('/items')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('item/index')
                ->has('items.data', 1)
                ->where('items.data.0.item_code', $item->item_code)
                ->where('items.data.0.stock.0.quantity', 8));
    }

    public function test_item_codes_are_generated_and_unique_even_if_a_code_is_submitted(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        Item::create([
            'item_code' => 'HW-000002',
            'item_description' => 'Existing item',
            'supplier_id' => $supplier->id,
        ]);

        $this->post('/items', [
            'item_code' => 'CLIENT-PROVIDED-CODE',
            'item_description' => 'Generated-code item',
            'supplier_id' => $supplier->id,
        ])->assertRedirect('/items');

        $item = Item::where('item_description', 'Generated-code item')->firstOrFail();

        $this->assertSame('HW-000003', $item->item_code);
        $this->assertDatabaseMissing('items', ['item_code' => 'CLIENT-PROVIDED-CODE']);
    }

    public function test_items_can_be_viewed_and_edited(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $item = Item::create([
            'item_code' => 'HW-3003',
            'item_description' => 'USB hub',
            'supplier_id' => $supplier->id,
        ]);

        $this->get("/items/{$item->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('item/show')
                ->where('item.item_code', 'HW-3003')
                ->where('item.supplier.name', 'Acme Supply'));

        $this->get("/items/{$item->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('item/edit')
                ->where('item.id', $item->id)
                ->has('suppliers', 1));

        $this->put("/items/{$item->id}", [
            'item_code' => 'hw-3004',
            'item_description' => 'USB-C hub',
            'supplier_id' => $supplier->id,
        ])->assertRedirect('/items');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'item_code' => 'HW-3004',
            'item_description' => 'USB-C hub',
        ]);
    }

    public function test_items_can_be_archived_without_deleting_their_history(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $item = Item::create([
            'item_code' => 'HW-4004',
            'item_description' => 'Docking station',
            'supplier_id' => $supplier->id,
        ]);

        $this->delete("/items/{$item->id}")
            ->assertRedirect('/items');

        $this->assertNotNull($item->fresh()->archived_at);
        $this->assertDatabaseHas('items', ['id' => $item->id]);

        $this->get('/items')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('item/index')
                ->has('items.data', 0));

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('summary.items', 0));

        $this->get("/items/{$item->id}")->assertNotFound();
        $this->put("/items/{$item->id}", [
            'item_description' => 'Updated description',
        ])->assertNotFound();
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

        $item = Item::where('item_description', 'USB-C adapter')->firstOrFail();
        $this->assertMatchesRegularExpression('/^HW-\d{6,}$/', $item->item_code);

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

    public function test_item_opening_stock_must_be_between_one_and_five_hundred_per_warehouse(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse']);
        $secondWarehouse = Warehouse::create(['name' => 'Overflow Warehouse']);

        foreach ([0, -1, 501] as $quantity) {
            $itemCode = 'HW-INVALID-'.$quantity;

            $this->post('/items', [
                'item_code' => $itemCode,
                'item_description' => 'Invalid opening stock item',
                'supplier_id' => $supplier->id,
                'stocks' => [
                    ['warehouse_id' => $warehouse->id, 'quantity' => $quantity],
                ],
            ])->assertSessionHasErrors('stocks.0.quantity');

            $this->assertDatabaseMissing('items', ['item_description' => 'Invalid opening stock item']);
        }

        $this->post('/items', [
            'item_code' => 'HW-MAX-STOCK',
            'item_description' => 'Maximum opening stock item',
            'supplier_id' => $supplier->id,
            'stocks' => [
                ['warehouse_id' => $warehouse->id, 'quantity' => 1],
                ['warehouse_id' => $secondWarehouse->id, 'quantity' => 500],
            ],
        ])->assertRedirect('/items');

        $item = Item::where('item_description', 'Maximum opening stock item')->firstOrFail();
        $this->assertDatabaseHas('transfer_details', ['item_id' => $item->id, 'quantity_transferred' => 1]);
        $this->assertDatabaseHas('transfer_details', ['item_id' => $item->id, 'quantity_transferred' => 500]);
    }
}
