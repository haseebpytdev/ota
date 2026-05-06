<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Booking;
use App\Services\Booking\BookingDraftService;
use App\Services\Booking\BookingService;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\Suppliers\OfferValidationService;
use App\Support\PublicBooking;
use App\Support\Security\SensitiveDataRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        protected BookingDraftService $bookingDraft,
        protected FlightSearchService $flightSearch,
        protected BookingService $bookingService,
        protected OfferValidationService $offerValidationService,
    ) {}

    public function passengers(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'flight_id' => ['required', 'string', 'max:64'],
                'title' => ['nullable', 'string', 'max:16'],
                'first_name' => ['required', 'string', 'max:120'],
                'last_name' => ['required', 'string', 'max:120'],
                'dob' => ['nullable', 'date'],
                'nationality' => ['nullable', 'string', 'max:8'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:64'],
                'country' => ['nullable', 'string', 'max:120'],
            ]);

            $merge = [
                'flight_id' => $validated['flight_id'],
                'search_from' => $request->string('from')->toString(),
                'search_to' => $request->string('to')->toString(),
                'search_depart' => $request->string('depart')->toString(),
            ];
            $this->bookingDraft->merge($merge);

            $criteria = [
                'origin' => $merge['search_from'] !== '' ? $merge['search_from'] : 'LHE',
                'destination' => $merge['search_to'] !== '' ? $merge['search_to'] : 'DXB',
                'depart_date' => $merge['search_depart'] !== '' ? $merge['search_depart'] : now()->addDays(14)->format('Y-m-d'),
            ];

            $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
            if ($agency === null) {
                return redirect()->route('flights.search')->withErrors(['flight_id' => __('Booking is temporarily unavailable.')]);
            }

            $offers = $this->flightSearch->search($criteria, $agency, 'public_guest');
            $offer = collect($offers)->firstWhere('id', $validated['flight_id']);

            if ($offer === null) {
                return redirect()->route('flights.search')->withErrors(['flight_id' => __('Selected flight is no longer available.')]);
            }

            $validation = $this->offerValidationService->validateSelectedOffer($agency, $offer, $criteria + [
                'source_channel' => 'public_guest',
            ]);
            if ($validation->status === 'price_changed') {
                return redirect()->route('booking.passengers', [
                    'flight_id' => $validated['flight_id'],
                    'from' => $criteria['origin'],
                    'to' => $criteria['destination'],
                    'depart' => $criteria['depart_date'],
                ])->with('validation_alert', 'Fare changed during validation. Please review the updated fare before continuing.')
                    ->with('validation_result', $validation->toArray());
            }
            if (! $validation->is_valid || $validation->validated_offer === null) {
                return redirect()->route('flights.results', [
                    'from' => $criteria['origin'],
                    'to' => $criteria['destination'],
                    'depart' => $criteria['depart_date'],
                ])->withErrors(['flight_id' => __('Selected fare is no longer available. Please choose another option.')]);
            }

            $normalizedValidated = $validation->validated_offer->toArray();
            $pricing = $validation->meta['pricing_snapshot'] ?? [];
            $offer = $this->presentValidatedOffer($normalizedValidated, $pricing);

            $routeStr = $criteria['origin'].' → '.$criteria['destination'];
            $airlineStr = ($offer['airline_name'] ?? '').' ('.($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')).')';
            $travelDate = Carbon::parse($offer['depart_at'] ?? $criteria['depart_date'])->toDateString();
            $booking = DB::transaction(function () use ($agency, $validated, $offer, $pricing, $criteria, $routeStr, $airlineStr, $travelDate, $validation, $normalizedValidated): Booking {
                $booking = $this->bookingService->createDraftBooking($agency);
                $booking->forceFill([
                    'supplier' => $offer['supplier_provider'] ?? 'mock',
                    'route' => $routeStr,
                    'airline' => $airlineStr,
                    'travel_date' => $travelDate,
                    'payment_status' => 'unpaid',
                    'source_channel' => 'public_guest',
                    'meta' => [
                        'flight_offer_snapshot' => SensitiveDataRedactor::redact($offer),
                        'normalized_offer_snapshot' => SensitiveDataRedactor::redact($offer),
                        'supplier_provider' => $offer['supplier_provider'] ?? 'mock',
                        'supplier_connection_id' => $offer['supplier_connection_id'] ?? null,
                        'search_criteria' => $criteria,
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

                return $booking->fresh();
            });

            $request->session()->put(PublicBooking::SESSION_BOOKING_ID, $booking->id);
            $this->bookingDraft->clear();

            return redirect()->route('booking.review');
        }

        $flightId = $request->string('flight_id')->toString();
        if ($flightId !== '') {
            $this->bookingDraft->merge([
                'flight_id' => $flightId,
                'search_from' => $request->string('from')->toString(),
                'search_to' => $request->string('to')->toString(),
                'search_depart' => $request->string('depart')->toString(),
            ]);
        }

        $draft = $this->bookingDraft->current();
        $effectiveFlightId = $flightId !== '' ? $flightId : ($draft['flight_id'] ?? '');

        $criteria = [
            'origin' => $draft['search_from'] ?? 'LHE',
            'destination' => $draft['search_to'] ?? 'DXB',
            'depart_date' => $draft['search_depart'] ?? now()->addDays(14)->format('Y-m-d'),
        ];

        $offer = null;
        $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
        if ($effectiveFlightId !== '') {
            $offers = $agency !== null
                ? $this->flightSearch->search($criteria, $agency, 'public_guest')
                : [];
            $offer = collect($offers)->firstWhere('id', $effectiveFlightId);
        }

        return view('frontend.booking.passenger-details', [
            'draft' => $draft,
            'flightId' => $effectiveFlightId,
            'offer' => $offer,
            'criteria' => $criteria,
            'client' => config('demo-client', []),
            'validationResult' => session('validation_result'),
            'validationAlert' => session('validation_alert'),
        ]);
    }

    public function review(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'booking_method' => ['required', 'string', 'in:pay_later,bank_transfer,office'],
            ]);

            $bookingId = $request->session()->get(PublicBooking::SESSION_BOOKING_ID);
            if ($bookingId === null) {
                return redirect()->route('flights.search');
            }

            $booking = Booking::query()->find($bookingId);
            if ($booking === null) {
                $request->session()->forget(PublicBooking::SESSION_BOOKING_ID);

                return redirect()->route('flights.search');
            }

            $meta = $booking->meta ?? [];
            $meta['booking_method'] = $validated['booking_method'];
            $booking->forceFill(['meta' => $meta])->save();
            $booking->refresh();

            $this->bookingService->submitBookingRequest($booking);

            return redirect()->route('booking.confirmation');
        }

        $bookingId = $request->session()->get(PublicBooking::SESSION_BOOKING_ID);
        if ($bookingId === null) {
            return redirect()->route('flights.search');
        }

        $booking = Booking::query()
            ->with(['passengers', 'contact', 'fareBreakdown'])
            ->find($bookingId);

        if ($booking === null) {
            $request->session()->forget(PublicBooking::SESSION_BOOKING_ID);

            return redirect()->route('flights.search');
        }

        $meta = $booking->meta ?? [];
        $offer = $meta['flight_offer_snapshot'] ?? null;
        $criteria = $meta['search_criteria'] ?? [
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => $booking->travel_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
        ];

        if ($offer === null) {
            return redirect()->route('flights.search');
        }

        $pax = $booking->passengers->first();
        $contact = $booking->contact;
        $draft = [
            'flight_id' => $offer['id'] ?? '',
            'title' => $pax?->title,
            'first_name' => $pax?->first_name,
            'last_name' => $pax?->last_name,
            'email' => $contact?->email,
            'phone' => $contact?->phone,
            'country' => $contact?->country,
            'search_from' => $criteria['origin'] ?? '',
            'search_to' => $criteria['destination'] ?? '',
            'search_depart' => $criteria['depart_date'] ?? '',
        ];

        return view('frontend.booking.review', [
            'draft' => $draft,
            'offer' => $offer,
            'criteria' => $criteria,
            'booking' => $booking,
        ]);
    }

    public function confirmation(Request $request): View|RedirectResponse
    {
        $bookingId = $request->session()->get(PublicBooking::SESSION_BOOKING_ID);
        if ($bookingId === null) {
            return redirect()->route('flights.search');
        }

        $booking = Booking::query()
            ->with(['passengers', 'contact', 'fareBreakdown'])
            ->find($bookingId);

        if ($booking === null) {
            $request->session()->forget(PublicBooking::SESSION_BOOKING_ID);

            return redirect()->route('flights.search');
        }

        $meta = $booking->meta ?? [];
        $offer = $meta['flight_offer_snapshot'] ?? null;
        $criteria = $meta['search_criteria'] ?? [
            'origin' => 'LHE',
            'destination' => 'DXB',
            'depart_date' => $booking->travel_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
        ];

        $pax = $booking->passengers->first();
        $contact = $booking->contact;
        $draft = [
            'flight_id' => $offer['id'] ?? '',
            'booking_reference' => $booking->booking_reference,
            'booking_method' => $meta['booking_method'] ?? 'pay_later',
            'title' => $pax?->title,
            'first_name' => $pax?->first_name,
            'last_name' => $pax?->last_name,
            'email' => $contact?->email,
            'phone' => $contact?->phone,
            'country' => $contact?->country,
            'search_from' => $criteria['origin'] ?? '',
            'search_to' => $criteria['destination'] ?? '',
            'search_depart' => $criteria['depart_date'] ?? '',
        ];

        return view('frontend.booking.confirmation', [
            'draft' => $draft,
            'offer' => $offer,
            'criteria' => $criteria,
            'booking' => $booking,
        ]);
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
