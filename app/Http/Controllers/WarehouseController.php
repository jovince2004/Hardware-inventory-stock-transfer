<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;

class WarehouseController extends Controller
{
    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        return redirect()->route('items.create')->with('success', 'Warehouse created successfully.');
    }
}
