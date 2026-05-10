<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\Suppliers\OfferValidationService;
use App\Support\Security\SensitiveDataRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AgentBookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected FlightSearchService $flightSearch,
        protected OfferValidationService $offerValidationService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Booking::class);

        $agent = $this->resolveCurrentAgent();

        $query = Booking::query()
            ->where('agent_id', $agent->id)
            ->with(['passengers', 'contact', 'fareBreakdown'])
            ->orderByDesc('created_at');

        $bookings = (clone $query)->paginate(20);

        $kpis = [
            'my_bookings' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'ticketed' => (clone $query)->where('status', 'ticketed')->count(),
            'monthly_sales' => (float) (clone $query)
                ->leftJoin('booking_fare_breakdowns as fare', 'fare.booking_id', '=', 'bookings.id')
                ->whereBetween('bookings.created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('fare.total'),
        ];

        return view('dashboard.agent.bookings.index', [
            'bookings' => $bookings,
            'kpis' => $kpis,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Booking::class);
        $agent = $this->resolveCurrentAgent();

        $criteria = [
            'origin' => strtoupper($request->string('from')->toString() ?: 'LHE'),
            'destination' => strtoupper($request->string('to')->toString() ?: 'DXB'),
            'depart_date' => $request->string('depart')->toString() ?: now()->addDays(14)->format('Y-m-d'),
        ];

        $offers = $this->flightSearch->search($criteria, $request->user()->currentAgency, 'agent_portal', $agent->id);

        return view('dashboard.agent.bookings.create', [
            'criteria' => $criteria,
            'offers' => $offers,
            'selectedFlightId' => $request->string('flight_id')->toString(),
            'validationAlert' => session('validation_alert'),
            'validationResult' => session('validation_result'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Booking::class);

        $validated = $request->validate([
            'from' => ['required', 'string', 'max:8'],
            'to' => ['required', 'string', 'max:8'],
            'depart' => ['required', 'date'],
            'flight_id' => ['required', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:16'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'dob' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:8'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:120'],
            'agent_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $agent = $this->resolveCurrentAgent();
        $agency = $request->user()->currentAgency;

        $criteria = [
            'origin' => strtoupper($validated['from']),
            'destination' => strtoupper($validated['to']),
            'depart_date' => Carbon::parse($validated['depart'])->toDateString(),
        ];

        $offers = $this->flightSearch->search($criteria, $agency, 'agent_portal', $agent->id);
        $offer = collect($offers)->firstWhere('id', $validated['flight_id']);

        if ($offer === null) {
            return back()->withErrors(['flight_id' => 'Selected flight is no longer available.'])->withInput();
        }

        $validation = $this->offerValidationService->validateSelectedOffer($agency, $offer, $criteria + [
            'source_channel' => 'agent_portal',
            'agent_id' => $agent->id,
        ]);
        if ($validation->status === 'price_changed') {
            return redirect()->route('agent.bookings.create', [
                'flight_id' => $validated['flight_id'],
                'from' => $criteria['origin'],
                'to' => $criteria['destination'],
                'depart' => $criteria['depart_date'],
            ])->with('validation_alert', 'Fare changed during validation. Please review the updated fare before continuing.')
                ->with('validation_result', $validation->toArray());
        }
        if (! $validation->is_valid || $validation->validated_offer === null) {
            return back()->withErrors(['flight_id' => 'Selected fare is no longer available. Please choose another option.'])->withInput();
        }

        $normalizedValidated = $validation->validated_offer->toArray();
        $pricing = $validation->meta['pricing_snapshot'] ?? [];
        $offer = $this->presentValidatedOffer($normalizedValidated, $pricing);

        $booking = DB::transaction(function () use ($agency, $agent, $validated, $criteria, $offer, $validation, $normalizedValidated): Booking {
            $booking = $this->bookingService->createDraftBooking($agency, null, $agent);
            $pricing = $offer['pricing_components'] ?? [];

            $booking->forceFill([
                'agent_id' => $agent->id,
                'supplier' => $offer['supplier_provider'] ?? 'duffel',
                'source_channel' => 'agent_portal',
                'payment_status' => 'unpaid',
                'route' => $criteria['origin'].' → '.$criteria['destination'],
                'airline' => ($offer['airline_name'] ?? 'Airline').' ('.($offer['airline_code'] ?? ($offer['carrier_code'] ?? 'XX')).')',
                'travel_date' => Carbon::parse($offer['depart_at'] ?? $criteria['depart_date'])->toDateString(),
                'notes' => ($validated['agent_note'] ?? null) ?: null,
                'meta' => [
                    'flight_offer_snapshot' => SensitiveDataRedactor::redact($offer),
                    'normalized_offer_snapshot' => SensitiveDataRedactor::redact($offer),
                    'supplier_provider' => $offer['supplier_provider'] ?? 'duffel',
                    'supplier_connection_id' => $offer['supplier_connection_id'] ?? null,
                    'search_criteria' => $criteria,
                    'origin_channel' => 'agent_portal',
                    'pricing_snapshot' => SensitiveDataRedactor::redact($pricing),
                    'applied_rules' => SensitiveDataRedactor::redact($pricing['applied_rules'] ?? []),
                    'offer_validation_status' => $validation->status,
                    'validated_at' => now()->toIso8601String(),
                    'validated_offer_snapshot' => SensitiveDataRedactor::redact($normalizedValidated),
                    'validation_warnings' => SensitiveDataRedactor::redact($validation->warnings),
                ],
            ])->save();

            $this->bookingService->attachPassenger($booking, [
                'passenger_index' => 0,
                'title' => $validated['title'] ?? null,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'date_of_birth' => $validated['dob'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'meta' => null,
            ]);

            $this->bookingService->attachContact($booking, [
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'country' => $validated['country'] ?? null,
                'address_line' => null,
                'meta' => null,
            ]);

            $this->bookingService->attachFareBreakdown($booking, [
                'base_fare' => $pricing['base_fare'],
                'taxes' => $pricing['taxes'],
                'fees' => $pricing['service_fee'],
                'markup' => $pricing['admin_markup'] + $pricing['route_markup'] + $pricing['airline_markup'] + $pricing['agent_markup_or_commission'],
                'discount' => 0,
                'total' => $pricing['final_total'],
                'currency' => $offer['currency'] ?? 'PKR',
                'breakdown' => [
                    ['label' => 'Base fare', 'amount' => $pricing['base_fare']],
                    ['label' => 'Taxes & surcharges', 'amount' => $pricing['taxes']],
                    ['label' => 'Admin markup', 'amount' => $pricing['admin_markup']],
                    ['label' => 'Route markup', 'amount' => $pricing['route_markup']],
                    ['label' => 'Airline markup', 'amount' => $pricing['airline_markup']],
                    ['label' => 'Channel/agent markup', 'amount' => $pricing['agent_markup_or_commission']],
                    ['label' => 'Service fee', 'amount' => $pricing['service_fee']],
                ],
            ]);

            return $this->bookingService->submitBookingRequest($booking, auth()->user());
        });

        return redirect()
            ->route('agent.bookings.show', $booking)
            ->with('status', 'booking-request-created');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);
        $this->resolveCurrentAgent();

        $booking->load([
            'agent.user',
            'passengers',
            'contact',
            'fareBreakdown',
            'statusLogs.user',
            'payments',
            'cancellationRequests.requester',
        ]);

        return view('dashboard.agent.bookings.show', [
            'booking' => $booking,
        ]);
    }

    protected function resolveCurrentAgent()
    {
        $agent = auth()->user()?->agent();
        if ($agent === null) {
            abort(403, 'Agent profile is not configured for this agency.');
        }

        return $agent;
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>
     */
    protected function presentValidatedOffer(array $offer, array $pricing): array
    {
        $durationMinutes = (int) ($offer['duration_minutes'] ?? 0);
        $fare = $offer['fare_breakdown'] ?? [];
        $baggageSummary = is_array($offer['baggage'] ?? null)
            ? (string) (($offer['baggage']['summary'] ?? '') ?: ($offer['baggage']['checked'] ?? ''))
            : (string) ($offer['baggage'] ?? '');

        return array_merge($offer, [
            'id' => $offer['offer_id'] ?? null,
            'depart_at' => $offer['departure_at'] ?? null,
            'arrive_at' => $offer['arrival_at'] ?? null,
            'carrier_code' => $offer['airline_code'] ?? 'XX',
            'duration_h' => intdiv($durationMinutes, 60),
            'duration_m' => $durationMinutes % 60,
            'baggage' => $baggageSummary,
            'base_fare' => (float) ($fare['base_fare'] ?? 0),
            'currency' => (string) ($fare['currency'] ?? 'PKR'),
            'taxes' => (float) ($pricing['taxes'] ?? 0),
            'markup' => (float) ($pricing['admin_markup'] ?? 0)
                + (float) ($pricing['route_markup'] ?? 0)
                + (float) ($pricing['airline_markup'] ?? 0)
                + (float) ($pricing['agent_markup_or_commission'] ?? 0),
            'service_fee' => (float) ($pricing['service_fee'] ?? 0),
            'total' => (float) ($pricing['final_total'] ?? 0),
            'pricing_components' => $pricing,
        ]);
    }
}
