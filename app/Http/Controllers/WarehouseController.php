<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService
    ) {}

    public function index(): Response
    {
        return Inertia::render('warehouses/index', [
            'warehouses' => $this->warehouseService->paginate(),
        ]);
    }


    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $returnTo = $data['return_to'] ?? 'items.create';
        unset($data['return_to']);

        $this->warehouseService->create($data);

        return redirect()->route($returnTo)->with('success', 'Warehouse created successfully.');
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->warehouseService->update($warehouse, $request->validated());

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if (! $this->warehouseService->delete($warehouse)) {
            return redirect()->route('warehouses.index')
                ->with('error', 'This warehouse cannot be deleted because it has stock transfer history.');
        }

        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
}
