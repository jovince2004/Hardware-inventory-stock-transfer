<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService
    ) {}

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $this->warehouseService->create($request->validated());

        return redirect()->route('items.create')->with('success', 'Warehouse created successfully.');
    }
}
