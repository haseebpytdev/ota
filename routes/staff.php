<?php

use App\Http\Controllers\BookingDocumentController;
use App\Http\Controllers\BookingTicketingController;
use App\Http\Controllers\Staff\BookingCancellationController;
use App\Http\Controllers\Staff\BookingController;
use App\Http\Controllers\Staff\BookingPaymentController;
use App\Http\Controllers\Staff\BookingRefundController;
use App\Http\Controllers\Staff\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')->name('staff.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
    Route::post('/bookings/{booking}/notes', [BookingController::class, 'storeNote'])->name('bookings.notes');
    Route::post('/bookings/{booking}/supplier-booking', [BookingController::class, 'createSupplierBooking'])->name('bookings.supplier-booking');
    Route::post('/bookings/{booking}/manual-pnr', [BookingController::class, 'markManualPnr'])->name('bookings.manual-pnr');
    Route::post('/bookings/{booking}/payments', [BookingPaymentController::class, 'store'])->name('bookings.payments.store');
    Route::patch('/bookings/payments/{bookingPayment}/verify', [BookingPaymentController::class, 'verify'])->name('bookings.payments.verify');
    Route::patch('/bookings/payments/{bookingPayment}/reject', [BookingPaymentController::class, 'reject'])->name('bookings.payments.reject');
    Route::post('/bookings/{booking}/cancellations', [BookingCancellationController::class, 'store'])->name('bookings.cancellations.store');
    Route::patch('/bookings/cancellations/{cancellationRequest}/approve', [BookingCancellationController::class, 'approve'])->name('bookings.cancellations.approve');
    Route::patch('/bookings/cancellations/{cancellationRequest}/reject', [BookingCancellationController::class, 'reject'])->name('bookings.cancellations.reject');
    Route::patch('/bookings/cancellations/{cancellationRequest}/process', [BookingCancellationController::class, 'process'])->name('bookings.cancellations.process');
    Route::post('/bookings/{booking}/refunds', [BookingRefundController::class, 'store'])->name('bookings.refunds.store');
    Route::patch('/bookings/refunds/{bookingRefund}/approve', [BookingRefundController::class, 'approve'])->name('bookings.refunds.approve');
    Route::patch('/bookings/refunds/{bookingRefund}/mark-paid', [BookingRefundController::class, 'markPaid'])->name('bookings.refunds.mark-paid');
    Route::patch('/bookings/refunds/{bookingRefund}/reject', [BookingRefundController::class, 'reject'])->name('bookings.refunds.reject');
    Route::post('/bookings/{booking}/issue-ticket', [BookingTicketingController::class, 'issue'])->name('bookings.issue-ticket');
    Route::post('/bookings/{booking}/documents/confirmation', [BookingDocumentController::class, 'bookingConfirmation'])->name('bookings.documents.confirmation');
    Route::post('/bookings/{booking}/documents/invoice', [BookingDocumentController::class, 'invoice'])->name('bookings.documents.invoice');
    Route::post('/bookings/{booking}/documents/ticket-itinerary', [BookingDocumentController::class, 'ticketItinerary'])->name('bookings.documents.ticket-itinerary');
    Route::post('/bookings/{booking}/documents/refund-note', [BookingDocumentController::class, 'refundNote'])->name('bookings.documents.refund-note');
    Route::post('/bookings/{booking}/documents/cancellation-confirmation', [BookingDocumentController::class, 'cancellationConfirmation'])->name('bookings.documents.cancellation-confirmation');
    Route::post('/bookings/payments/{bookingPayment}/documents/receipt', [BookingDocumentController::class, 'paymentReceipt'])->name('bookings.payments.documents.receipt');
    Route::get('/bookings/documents/{bookingDocument}/download', [BookingDocumentController::class, 'download'])->name('bookings.documents.download');
});
