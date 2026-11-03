<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\WarehouseController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('items.index');
    })->name('dashboard');
});

Route::resource('items', ItemController::class)
    ->only(['index', 'create', 'store', 'update']);

Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');

Route::resource('transfers', StockTransferController::class)
    ->parameters(['transfers' => 'transfer'])
    ->only(['index', 'create', 'store', 'update']);



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
