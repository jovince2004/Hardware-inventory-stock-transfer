<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Requests\DeleteWarehouseRequest;
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

        return redirect()->route($returnTo)->with('success', $request->successMessage());
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->warehouseService->update($warehouse, $request->validated());

        return redirect()->route('warehouses.index')->with('success', $request->successMessage());
    }

    public function destroy(DeleteWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        if (! $this->warehouseService->delete($warehouse)) {
            return redirect()->route('warehouses.index')
                ->with('error', $request->failureMessage());
        }

        return redirect()->route('warehouses.index')->with('success', $request->successMessage());
    }
}
