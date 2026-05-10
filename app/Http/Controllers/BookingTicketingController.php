<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\Suppliers\TicketingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingTicketingController extends Controller
{
    public function __construct(
        protected TicketingService $ticketingService,
    ) {}

    public function issue(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('issueTicket', $booking);

        $result = $this->ticketingService->issueTickets($booking, $request->user());
        if (! $result->success) {
            return back()->withErrors([
                'ticketing' => $result->error_message ?: ($result->warnings[0] ?? 'Ticket issuance failed.'),
            ]);
        }

        return back()->with('status', 'tickets-issued');
    }
}
