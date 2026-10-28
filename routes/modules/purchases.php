<?php

// Loaded from routes/web.php inside the auth middleware group.

use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\VendorPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index')->middleware('permission:purchases,view');
Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create')->middleware('permission:purchases,add');
Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store')->middleware('permission:purchases,add');
Route::get('/purchases/products/search', [PurchaseController::class, 'searchProducts'])->name('purchases.products.search')->middleware('permission:purchases,add');
Route::get('/purchases/{id}', [PurchaseController::class, 'show'])->name('purchases.show')->whereNumber('id')->middleware('permission:purchases,view');

Route::get('/vendor-payments', [VendorPaymentController::class, 'index'])->name('vendor-payments.index')->middleware('permission:vendor_payments,view');
Route::post('/vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store')->middleware('permission:vendor_payments,add');
