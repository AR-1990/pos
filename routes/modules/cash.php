<?php

// Loaded from routes/web.php inside the auth middleware group.
// Cash register shifts (open/close, Z-report) + customer credit (udhaar ledger).

use App\Http\Controllers\CustomerLedgerController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

// ---- Shifts ----
Route::middleware('permission:shifts,view')->group(function () {
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/current', [ShiftController::class, 'current'])->name('shifts.current');
    Route::get('/shifts/{id}', [ShiftController::class, 'show'])->whereNumber('id')->name('shifts.show');
});
Route::middleware('permission:shifts,add')->group(function () {
    Route::post('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/{id}/close', [ShiftController::class, 'close'])->whereNumber('id')->name('shifts.close');
});

// ---- Customer credit ----
Route::middleware('permission:customer_credit,view')->group(function () {
    Route::get('/customer-ledger', [CustomerLedgerController::class, 'index'])->name('customer-ledger.index');
    Route::get('/customer-ledger/{customer}', [CustomerLedgerController::class, 'show'])->whereNumber('customer')->name('customer-ledger.show');
    Route::get('/customer-payments/{id}/receipt', [CustomerLedgerController::class, 'receipt'])->whereNumber('id')->name('customer-payments.receipt');
});
Route::post('/customer-ledger/{customer}/payments', [CustomerLedgerController::class, 'storePayment'])
    ->whereNumber('customer')
    ->middleware('permission:customer_credit,add')
    ->name('customer-payments.store');
