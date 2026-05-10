<?php

use App\Http\Controllers\Agent\AgentBookingController;
use App\Http\Controllers\Agent\AgentCommissionController;
use App\Http\Controllers\Agent\BookingCancellationController;
use App\Http\Controllers\Agent\BookingPaymentProofController;
use App\Http\Controllers\Agent\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->name('agent.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/bookings', [AgentBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [AgentBookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [AgentBookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [AgentBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/payment-proof', [BookingPaymentProofController::class, 'store'])->middleware('throttle:payment-proof-submit')->name('bookings.payment-proof');
    Route::post('/bookings/{booking}/cancellations', [BookingCancellationController::class, 'store'])->name('bookings.cancellations.store');
    Route::get('/commissions', [AgentCommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/statements/{statement}', [AgentCommissionController::class, 'showStatement'])->name('commissions.statements.show');
});
