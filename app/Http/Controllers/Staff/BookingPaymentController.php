<?php

namespace App\Http\Controllers\Staff;

use App\Enums\BookingPaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Services\Payments\BookingPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BookingPaymentController extends Controller
{
    public function __construct(
        protected BookingPaymentService $paymentService,
    ) {}

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('recordPayment', $booking);
        $validated = $request->validate([
            'method' => ['required', Rule::enum(BookingPaymentMethod::class)],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->paymentService->recordManualPayment($booking, $request->user(), $validated);

        return back()->with('status', 'payment-recorded');
    }

    public function verify(Request $request, BookingPayment $bookingPayment): RedirectResponse
    {
        Gate::authorize('verifyPayment', $bookingPayment->booking);
        $this->paymentService->verifyPayment($bookingPayment, $request->user());

        return back()->with('status', 'payment-verified');
    }

    public function reject(Request $request, BookingPayment $bookingPayment): RedirectResponse
    {
        Gate::authorize('rejectPayment', $bookingPayment->booking);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $this->paymentService->rejectPayment($bookingPayment, $request->user(), $validated['reason']);

        return back()->with('status', 'payment-rejected');
    }
}
