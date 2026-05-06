<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\BookingPaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDocument;
use App\Services\Customer\GuestBookingAccessService;
use App\Services\Payments\BookingPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuestBookingLookupController extends Controller
{
    public function __construct(
        protected GuestBookingAccessService $guestAccessService,
        protected BookingPaymentService $paymentService,
    ) {}

    public function showLookupForm(): View
    {
        return view('frontend.booking.lookup');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_reference' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return back()->withErrors(['lookup' => 'Please provide email or phone for secure verification.']);
        }

        $booking = $this->guestAccessService->findBookingForLookup(
            $validated['booking_reference'],
            $validated['email'] ?? null,
            $validated['phone'] ?? null
        );

        if ($booking === null) {
            return back()->withErrors(['lookup' => 'Booking not found for provided reference and contact details.']);
        }

        $token = $this->guestAccessService->createTokenForBooking($booking, $validated['email'] ?? null, $validated['phone'] ?? null);

        return redirect()->route('guest.bookings.show', ['booking' => $booking, 'token' => $token]);
    }

    public function showGuestBooking(Request $request, Booking $booking, string $token): View
    {
        if (! $this->guestAccessService->validateToken($booking, $token)) {
            abort(403);
        }

        $booking->load([
            'passengers',
            'contact',
            'fareBreakdown',
            'statusLogs',
            'payments',
            'tickets',
            'documents',
            'communicationLogs',
            'cancellationRequests.requester',
        ]);

        return view('dashboard.customer.bookings.show', [
            'booking' => $booking,
            'isGuestView' => true,
            'guestToken' => $token,
        ]);
    }

    public function downloadGuestDocument(Request $request, BookingDocument $bookingDocument): BinaryFileResponse
    {
        $token = (string) $request->query('token', '');
        $booking = $bookingDocument->booking;
        if ($booking === null || $token === '' || ! $this->guestAccessService->validateToken($booking, $token)) {
            abort(403);
        }

        if ($bookingDocument->file_path === null || ! Storage::disk('local')->exists($bookingDocument->file_path)) {
            abort(404);
        }

        return response()->download(Storage::disk('local')->path($bookingDocument->file_path), basename((string) $bookingDocument->file_path));
    }

    public function submitGuestPaymentProof(Request $request, Booking $booking, string $token): RedirectResponse
    {
        if (! $this->guestAccessService->validateToken($booking, $token)) {
            abort(403);
        }

        $validated = $request->validate([
            'method' => ['required', Rule::enum(BookingPaymentMethod::class)],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->paymentService->submitPaymentProof($booking, null, $validated);

        return back()->with('status', 'payment-proof-submitted');
    }
}
