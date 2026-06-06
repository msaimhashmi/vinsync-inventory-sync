<?php

use App\Http\Controllers\InventoryController;
use App\Services\VehicleSyncService;
use Illuminate\Support\Facades\Route;

// Root redirect to inventory
Route::get('/', fn () => redirect()->route('inventory.index'));

// Inventory listing
Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
