<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::resource('items', ItemController::class)
    ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('warehouses', WarehouseController::class)->only(['index', 'store', 'update', 'destroy']);

Route::middleware(['auth'])->group(function () {
    Route::resource('transfers', StockTransferController::class)
        ->parameters(['transfers' => 'transfer'])
        ->only(['index', 'create', 'store', 'update']);
});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
