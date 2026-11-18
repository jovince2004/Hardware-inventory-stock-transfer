<?php

use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupplierWarehouseTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_dashboard_supports_create_update_and_safe_delete(): void
    {
        $this->get('/suppliers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('suppliers/index')->has('suppliers.data', 0));

        $this->post('/suppliers', [
            'name' => 'Acme Supply',
            'return_to' => 'suppliers.index',
        ])->assertRedirect('/suppliers');

        $supplier = Supplier::where('name', 'Acme Supply')->firstOrFail();

        $this->put("/suppliers/{$supplier->id}", ['name' => 'Acme Industrial'])
            ->assertRedirect('/suppliers');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Acme Industrial']);

        $this->delete("/suppliers/{$supplier->id}")
            ->assertRedirect('/suppliers')
            ->assertSessionHas('success', 'Supplier deleted successfully.');
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_with_items_cannot_be_deleted(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Supply']);
        Item::create([
            'item_code' => 'HW-SUPPLIER-1',
            'item_description' => 'Wireless keyboard',
            'supplier_id' => $supplier->id,
        ]);

        $this->delete("/suppliers/{$supplier->id}")
            ->assertRedirect('/suppliers')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
        $this->get('/suppliers')
            ->assertInertia(fn (Assert $page) => $page->where(
                'flash.error',
                'This supplier cannot be deleted because it is assigned to items.',
            ));
    }

    public function test_warehouse_dashboard_supports_create_update_and_safe_delete(): void
    {
        $this->get('/warehouses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('warehouses/index')->has('warehouses.data', 0));

        $this->post('/warehouses', [
            'name' => '  Main Warehouse  ',
            'location' => '  Building A  ',
            'return_to' => 'warehouses.index',
        ])->assertRedirect('/warehouses');

        $warehouse = Warehouse::where('name', 'Main Warehouse')->firstOrFail();

        $this->put("/warehouses/{$warehouse->id}", [
            'name' => '  North Warehouse  ',
            'location' => '  Building B  ',
        ])->assertRedirect('/warehouses');
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'North Warehouse',
            'location' => 'Building B',
        ]);

        $this->delete("/warehouses/{$warehouse->id}")
            ->assertRedirect('/warehouses')
            ->assertSessionHas('success', 'Warehouse deleted successfully.');
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    public function test_warehouse_name_and_location_are_trimmed(): void
    {
        $this->post('/warehouses', [
            'name' => '   ',
            'location' => '  ',
            'return_to' => 'warehouses.index',
        ])->assertSessionHasErrors('name');

        $this->post('/warehouses', [
            'name' => '  Warehouse  ',
            'location' => '  Building C  ',
            'return_to' => 'warehouses.index',
        ])->assertRedirect('/warehouses');

        $warehouse = Warehouse::where('name', 'Warehouse')->firstOrFail();

        $this->put("/warehouses/{$warehouse->id}", [
            'name' => '  Warehouse North  ',
            'location' => '   ',
        ])->assertRedirect('/warehouses');

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Warehouse North',
            'location' => null,
        ]);
    }

    public function test_warehouse_name_and_location_reject_random_input_on_create_and_update(): void
    {
        $this->post('/warehouses', [
            'name' => ' dhgauwerhb234 ',
            'location' => ' dsjbasgbasdvnjbefg ',
            'return_to' => 'warehouses.index',
        ])->assertSessionHasErrors(['name', 'location']);

        $this->assertDatabaseCount('warehouses', 0);

        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'location' => 'Building A']);

        $this->put("/warehouses/{$warehouse->id}", [
            'name' => ' dhgauwerhb234 ',
            'location' => ' dsjbasgbasdvnjbefg ',
        ])->assertSessionHasErrors(['name', 'location']);

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Main Warehouse',
            'location' => 'Building A',
        ]);
    }

    public function test_warehouse_with_transfer_history_cannot_be_deleted(): void
    {
        $warehouse = Warehouse::create(['name' => 'Main Warehouse']);
        StockTransfer::create([
            'source_warehouse_id' => null,
            'target_warehouse_id' => $warehouse->id,
            'transfer_date' => '2026-11-18',
            'transfer_reason' => 'Opening stock',
            'approved_by_staff' => 'System',
            'transfer_status' => 'completed',
        ]);

        $this->delete("/warehouses/{$warehouse->id}")
            ->assertRedirect('/warehouses')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
        $this->get('/warehouses')
            ->assertInertia(fn (Assert $page) => $page->where(
                'flash.error',
                'This warehouse cannot be deleted because it has stock transfer history.',
            ));
    }
}
