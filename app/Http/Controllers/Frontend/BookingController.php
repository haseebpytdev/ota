<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreBookingPassengersRequest;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\User;
use App\Services\Booking\BookingDraftService;
use App\Services\Booking\BookingService;
use App\Services\Booking\InternationalRouteDetector;
use App\Services\FlightSearch\FlightDeparturePolicy;
use App\Services\FlightSearch\FlightSearchResultStore;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\Suppliers\OfferValidationService;
use App\Services\TravelData\AirlineBrandingService;
use App\Support\PublicBooking;
use App\Support\Security\SensitiveDataRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        protected BookingDraftService $bookingDraft,
        protected FlightSearchService $flightSearch,
        protected FlightSearchResultStore $searchStore,
        protected BookingService $bookingService,
        protected OfferValidationService $offerValidationService,
        protected AirlineBrandingService $airlineBranding,
        protected FlightDeparturePolicy $departurePolicy,
    ) {}

    public function passengers(StoreBookingPassengersRequest $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $validated = $request->validated();

            $selectedOfferId = trim((string) ($validated['offer_id'] ?? $validated['flight_id'] ?? ''));
            if ($selectedOfferId === '') {
                return redirect()->route('flights.search')->withErrors(['flight_id' => __('Selected flight is required.')]);
            }

            $merge = [
                'flight_id' => $selectedOfferId,
                'offer_id' => $selectedOfferId,
                'search_id' => trim((string) ($validated['search_id'] ?? '')),
                'search_from' => $request->string('from')->toString(),
                'search_to' => $request->string('to')->toString(),
                'search_depart' => $request->string('depart')->toString(),
                'trip_type' => $request->string('trip_type', 'one_way')->toString(),
                'return_date' => $request->string('return_date')->toString(),
                'cabin' => $request->string('cabin', 'economy')->toString(),
                'adults' => max(1, (int) $request->input('adults', 1)),
                'children' => max(0, (int) $request->input('children', 0)),
                'infants' => max(0, (int) $request->input('infants', 0)),
            ];
            $this->bookingDraft->merge($merge);

            $criteria = [
                'origin' => $merge['search_from'] ?? '',
                'destination' => $merge['search_to'] ?? '',
                'depart_date' => $merge['search_depart'] ?? '',
                'trip_type' => $merge['trip_type'] ?? 'one_way',
                'return_date' => $merge['return_date'] ?? null,
                'cabin' => $merge['cabin'] ?? 'economy',
                'adults' => (int) ($merge['adults'] ?? 1),
                'children' => (int) ($merge['children'] ?? 0),
                'infants' => (int) ($merge['infants'] ?? 0),
                'segments' => $merge['segments'] ?? null,
                'source_channel' => 'public_guest',
            ];

            $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
            if ($agency === null) {
                return redirect()->route('flights.search')->withErrors(['flight_id' => __('Booking is temporarily unavailable.')]);
            }

            $offer = null;
            $searchId = $merge['search_id'];
            if ($searchId !== '') {
                $offer = $this->searchStore->findOffer($searchId, $selectedOfferId);
            }
            // Offer may be missing from the cached slice (MAX_STORED_OFFERS); fall back to a fresh search before treating the session as expired.
            if ($offer === null) {
                $offers = $this->flightSearch->search($criteria, $agency, 'public_guest');
                $offer = collect($offers)->firstWhere('id', $selectedOfferId);
            }

            if ($offer === null) {
                return redirect()->route('flights.search')->withErrors(['flight_id' => __('Selected flight is no longer available.')]);
            }

            $leadCriteria = $criteria;
            if (($merge['search_id'] ?? '') !== '') {
                $payload = $this->searchStore->get((string) $merge['search_id']);
                if (is_array($payload['criteria'] ?? null)) {
                    $leadCriteria = $payload['criteria'];
                }
            }
            if (! $this->departurePolicy->offerMeetsLeadTimeForBooking($offer, $leadCriteria)) {
                return redirect()->route('flights.search')
                    ->withErrors(['flight_id' => __(FlightDeparturePolicy::SAME_DAY_LEAD_MESSAGE)]);
            }

            $resultsQuery = [
                'from' => $criteria['origin'],
                'to' => $criteria['destination'],
                'depart' => $criteria['depart_date'],
                'trip_type' => $criteria['trip_type'] ?? 'one_way',
                'cabin' => $criteria['cabin'] ?? 'economy',
                'adults' => $criteria['adults'] ?? 1,
                'children' => $criteria['children'] ?? 0,
                'infants' => $criteria['infants'] ?? 0,
            ];
            if (($resultsQuery['trip_type'] ?? '') === 'round_trip') {
                $rd = trim((string) ($criteria['return_date'] ?? ''));
                if ($rd !== '') {
                    $resultsQuery['return_date'] = $rd;
                }
            }

            if (($offer['conversion_status'] ?? 'same_currency') === 'conversion_missing') {
                return redirect()->route('flights.results', $resultsQuery)
                    ->withErrors(['flight_id' => __('This fare requires currency review before booking.')]);
            }

            $validation = $this->offerValidationService->validateSelectedOffer($agency, $offer, $criteria + [
                'source_channel' => 'public_guest',
            ]);
            if ($validation->status === 'price_changed') {
                return redirect()->route('booking.passengers', [
                    'flight_id' => $selectedOfferId,
                    'offer_id' => $selectedOfferId,
                    'search_id' => $searchId,
                    'from' => $criteria['origin'],
                    'to' => $criteria['destination'],
                    'depart' => $criteria['depart_date'],
                ])->with('validation_alert', 'Fare changed during validation. Please review the updated fare before continuing.')
                    ->with('validation_result', $validation->toArray());
            }
            if (! $validation->is_valid || $validation->validated_offer === null) {
                return redirect()->route('flights.results', $resultsQuery)
                    ->withErrors(['flight_id' => __('Selected fare is no longer available. Please choose another option.')]);
            }

            $normalizedValidated = $validation->validated_offer->toArray();
            $pricing = $validation->meta['pricing_snapshot'] ?? [];
            $offer = $this->presentValidatedOffer($normalizedValidated, $pricing);

            $routeStr = $criteria['origin'].' → '.$criteria['destination'];
            $airlineStr = ($offer['airline_name'] ?? '').' ('.($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')).')';
            $travelDate = Carbon::parse($offer['depart_at'] ?? $criteria['depart_date'])->toDateString();
            $booking = DB::transaction(function () use ($agency, $validated, $offer, $pricing, $criteria, $routeStr, $airlineStr, $travelDate, $validation, $normalizedValidated, $request): Booking {
                if (! Auth::check() && ($validated['create_account'] ?? false)) {
                    $user = User::query()->create([
                        'name' => trim($validated['first_name'].' '.$validated['last_name']),
                        'email' => $validated['email'],
                        'password' => Hash::make((string) $validated['password']),
                        'account_type' => AccountType::Customer,
                        'status' => UserAccountStatus::Active,
                        'current_agency_id' => $agency->id,
                        'meta' => [
                            'first_name' => $validated['first_name'],
                            'last_name' => $validated['last_name'],
                            'phone' => $validated['phone'],
                            'registered_via' => 'checkout_inline',
                        ],
                    ]);
                    Auth::login($user);
                    $request->session()->regenerate();
                }

                $actor = Auth::user();
                $customer = ($actor instanceof User && $actor->isCustomer()) ? $actor : null;

                $booking = $this->bookingService->createDraftBooking($agency, $customer);
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
                    'nationality' => isset($validated['nationality']) ? strtoupper((string) $validated['nationality']) : null,
                    'gender' => $validated['gender'] ?? null,
                    'passport_number' => isset($validated['passport_number']) && trim((string) $validated['passport_number']) !== ''
                        ? trim((string) $validated['passport_number'])
                        : null,
                    'passport_issuing_country' => isset($validated['passport_issuing_country'])
                        ? strtoupper((string) $validated['passport_issuing_country'])
                        : null,
                    'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
                    'passport_issue_date' => $validated['passport_issue_date'] ?? null,
                    'document_type' => $validated['document_type'] ?? 'passport',
                    'national_id_number' => isset($validated['national_id_number']) && trim((string) $validated['national_id_number']) !== ''
                        ? trim((string) $validated['national_id_number'])
                        : null,
                    'country_of_residence' => $validated['country_of_residence'] ?? null,
                    'place_of_birth' => $validated['place_of_birth'] ?? null,
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
        $offerId = $request->string('offer_id')->toString();
        $searchId = $request->string('search_id')->toString();
        if ($flightId !== '' || $offerId !== '' || $searchId !== '') {
            $this->bookingDraft->merge([
                'flight_id' => $flightId !== '' ? $flightId : $offerId,
                'offer_id' => $offerId !== '' ? $offerId : $flightId,
                'search_id' => $searchId,
                'search_from' => $request->string('from')->toString(),
                'search_to' => $request->string('to')->toString(),
                'search_depart' => $request->string('depart')->toString(),
                'trip_type' => $request->string('trip_type', 'one_way')->toString(),
                'return_date' => $request->string('return_date')->toString(),
                'cabin' => $request->string('cabin', 'economy')->toString(),
                'adults' => max(1, (int) $request->input('adults', 1)),
                'children' => max(0, (int) $request->input('children', 0)),
                'infants' => max(0, (int) $request->input('infants', 0)),
            ]);
        }

        $draft = $this->bookingDraft->current();
        $effectiveFlightId = $flightId !== '' ? $flightId : (($draft['offer_id'] ?? '') !== '' ? $draft['offer_id'] : ($draft['flight_id'] ?? ''));

        $criteria = [
            'origin' => $draft['search_from'] ?? '',
            'destination' => $draft['search_to'] ?? '',
            'depart_date' => $draft['search_depart'] ?? '',
            'trip_type' => $draft['trip_type'] ?? 'one_way',
            'return_date' => $draft['return_date'] ?? null,
            'cabin' => $draft['cabin'] ?? 'economy',
            'adults' => (int) ($draft['adults'] ?? 1),
            'children' => (int) ($draft['children'] ?? 0),
            'infants' => (int) ($draft['infants'] ?? 0),
        ];

        $offer = null;
        $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
        if ($effectiveFlightId !== '') {
            $offer = null;
            if (is_string($draft['search_id'] ?? null) && ($draft['search_id'] ?? '') !== '') {
                $offer = $this->searchStore->findOffer((string) $draft['search_id'], $effectiveFlightId);
            }
            if ($offer === null) {
                $offers = $agency !== null && $criteria['origin'] !== '' && $criteria['destination'] !== '' && $criteria['depart_date'] !== ''
                    ? $this->flightSearch->search($criteria, $agency, 'public_guest')
                    : [];
                $offer = collect($offers)->firstWhere('id', $effectiveFlightId);
            }
        }

        if ($offer !== null && is_string($draft['search_id'] ?? null) && ($draft['search_id'] ?? '') !== '') {
            $payload = $this->searchStore->get((string) $draft['search_id']);
            $leadCriteria = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : $criteria;
            if (! $this->departurePolicy->offerMeetsLeadTimeForBooking($offer, $leadCriteria)) {
                return redirect()->route('flights.search')
                    ->withErrors(['flight_id' => __(FlightDeparturePolicy::SAME_DAY_LEAD_MESSAGE)]);
            }
        }

        return view('frontend.booking.passenger-details', [
            'draft' => $draft,
            'flightId' => $effectiveFlightId,
            'offer' => $offer,
            'criteria' => $criteria,
            'client' => config('ota-client', []),
            'validationResult' => session('validation_result'),
            'validationAlert' => session('validation_alert'),
            'airlineLogo' => $offer !== null
                ? $this->airlineBranding->getLogoForCode((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')))
                : null,
            'hideInlineAccount' => Auth::check(),
            'isInternationalRoute' => app(InternationalRouteDetector::class)
                ->isInternational((string) ($draft['search_from'] ?? ''), (string) ($draft['search_to'] ?? '')),
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
            'dob' => $pax?->date_of_birth?->format('Y-m-d'),
            'gender' => $pax?->gender,
            'nationality' => $pax?->nationality,
            'email' => $contact?->email,
            'phone' => $contact?->phone,
            'country' => $contact?->country,
            'search_from' => $criteria['origin'] ?? '',
            'search_to' => $criteria['destination'] ?? '',
            'search_depart' => $criteria['depart_date'] ?? '',
        ];

        return view('frontend.booking.review', [
            'draft' => $draft,
            'leadPassenger' => $pax,
            'offer' => $offer,
            'criteria' => $criteria,
            'booking' => $booking,
            'airlineLogo' => $this->airlineBranding->getLogoForCode((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? ''))),
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
            'airlineLogo' => $offer !== null
                ? $this->airlineBranding->getLogoForCode((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')))
                : null,
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
