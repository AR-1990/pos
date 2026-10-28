<?php

// Loaded from routes/web.php inside the auth middleware group.

use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;

// Stock levels + movement ledger
Route::middleware('permission:stock_ledger,view')->group(function () {
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');
});

// Shared product lookup for the transfer / adjustment forms (returns available qty at a store)
Route::get('/stock/product-search', [StockController::class, 'productSearch'])->name('stock.product-search');

// Barcode labels
Route::middleware('permission:products,view')->group(function () {
    Route::get('/products/labels', [StockController::class, 'labels'])->name('products.labels');
    Route::get('/products/labels/search', [StockController::class, 'labelSearch'])->name('products.labels.search');
});

// Stock transfers
Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index')->middleware('permission:stock_transfers,view');
Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create')->middleware('permission:stock_transfers,add');
Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store')->middleware('permission:stock_transfers,add');
Route::get('/stock-transfers/{id}', [StockTransferController::class, 'show'])->name('stock-transfers.show')->middleware('permission:stock_transfers,view')->whereNumber('id');

// Stock adjustments
Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index')->middleware('permission:stock_adjustments,view');
Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create')->middleware('permission:stock_adjustments,add');
Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store')->middleware('permission:stock_adjustments,add');
Route::get('/stock-adjustments/{id}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show')->middleware('permission:stock_adjustments,view')->whereNumber('id');
