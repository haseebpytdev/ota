<?php

use App\Http\Controllers\Customer\BookingCancellationController;
use App\Http\Controllers\Customer\CustomerBookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->name('customer.')->group(function (): void {
    Route::get('/', [CustomerBookingController::class, 'dashboard'])->name('dashboard');
    Route::get('/bookings', [CustomerBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [CustomerBookingController::class, 'show'])->name('bookings.show');
    Route::get('/documents/{bookingDocument}/download', [CustomerBookingController::class, 'downloadDocument'])->name('documents.download');
    Route::post('/bookings/{booking}/payment-proof', [CustomerBookingController::class, 'submitPaymentProof'])->middleware('throttle:payment-proof-submit')->name('bookings.payment-proof');
    Route::post('/bookings/{booking}/cancellations', [BookingCancellationController::class, 'store'])->name('bookings.cancellations.store');
});
