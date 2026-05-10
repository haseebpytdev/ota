<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingPaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDocument;
use App\Services\Payments\BookingPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerBookingController extends Controller
{
    public function __construct(
        protected BookingPaymentService $paymentService,
    ) {}

    public function dashboard(Request $request): View
    {
        $customerId = (int) $request->user()->id;
        $bookings = Booking::query()->where('customer_id', $customerId);

        return view('dashboard.customer.dashboard', [
            'kpis' => [
                'total' => (clone $bookings)->count(),
                'pending' => (clone $bookings)->where('status', 'pending')->count(),
                'payment_pending' => (clone $bookings)->where('status', 'payment_pending')->count(),
                'ticketed' => (clone $bookings)->where('status', 'ticketed')->count(),
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->where('customer_id', $request->user()->id)
            ->with(['contact'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('dashboard.customer.bookings.index', ['bookings' => $bookings]);
    }

    public function show(Request $request, Booking $booking): View
    {
        Gate::authorize('view', $booking);
        $this->ensureCustomerOwnsBooking($request, $booking);

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
            'isGuestView' => false,
        ]);
    }

    public function downloadDocument(Request $request, BookingDocument $bookingDocument): BinaryFileResponse
    {
        Gate::authorize('view', $bookingDocument);
        if ($bookingDocument->booking?->customer_id !== $request->user()->id) {
            abort(403);
        }

        if ($bookingDocument->file_path === null || ! Storage::disk('local')->exists($bookingDocument->file_path)) {
            abort(404);
        }

        return response()->download(Storage::disk('local')->path($bookingDocument->file_path), basename((string) $bookingDocument->file_path));
    }

    public function submitPaymentProof(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('submitPaymentProof', $booking);
        $this->ensureCustomerOwnsBooking($request, $booking);

        $validated = $request->validate([
            'method' => ['required', Rule::enum(BookingPaymentMethod::class)],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->paymentService->submitPaymentProof($booking, $request->user(), $validated);

        return back()->with('status', 'payment-proof-submitted');
    }

    protected function ensureCustomerOwnsBooking(Request $request, Booking $booking): void
    {
        if ($booking->customer_id !== $request->user()->id) {
            abort(403);
        }
    }
}
