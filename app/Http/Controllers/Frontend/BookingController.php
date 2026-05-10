<?php

namespace App\Http\Controllers\Frontend;

use App\Data\NormalizedFlightOfferData;
use App\Data\OfferValidationResultData;
use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreBookingPassengersRequest;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingHoldSession;
use App\Models\User;
use App\Services\Booking\BookingDraftService;
use App\Services\Booking\BookingService;
use App\Services\Booking\InternationalRouteDetector;
use App\Services\Bookings\FareHoldService;
use App\Services\FlightSearch\FlightDeparturePolicy;
use App\Services\FlightSearch\FlightSearchResultStore;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\Suppliers\OfferValidationService;
use App\Services\Suppliers\SupplierBookingService;
use App\Services\TravelData\AirlineBrandingService;
use App\Support\ProviderUnstableTestMode;
use App\Support\PublicBooking;
use App\Support\Security\SensitiveDataRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingController extends Controller
{
    /** Session flag: next passengers GET after a stale-offer recovery redirect must not run recovery again (prevents redirect loops). */
    public const SESSION_BOOKING_AFTER_STALE_RECOVERY = 'ota_booking_after_stale_recovery';

    public function __construct(
        protected BookingDraftService $bookingDraft,
        protected FlightSearchService $flightSearch,
        protected FlightSearchResultStore $searchStore,
        protected BookingService $bookingService,
        protected FareHoldService $fareHoldService,
        protected OfferValidationService $offerValidationService,
        protected SupplierBookingService $supplierBookingService,
        protected AirlineBrandingService $airlineBranding,
        protected FlightDeparturePolicy $departurePolicy,
    ) {}

    public function passengers(StoreBookingPassengersRequest $request): View|RedirectResponse
    {
        $this->logBookingRouteEntry($request);

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
                return $this->redirectToBookingPassengers([
                    'flight_id' => $selectedOfferId,
                    'offer_id' => $selectedOfferId,
                    'search_id' => $searchId,
                    'from' => $criteria['origin'],
                    'to' => $criteria['destination'],
                    'depart' => $criteria['depart_date'],
                    'trip_type' => (string) ($criteria['trip_type'] ?? 'one_way'),
                    'return_date' => (string) ($criteria['return_date'] ?? ''),
                    'cabin' => (string) ($criteria['cabin'] ?? 'economy'),
                    'adults' => (int) ($criteria['adults'] ?? 1),
                    'children' => (int) ($criteria['children'] ?? 0),
                    'infants' => (int) ($criteria['infants'] ?? 0),
                ], 'Fare changed during validation. Please review the updated fare before continuing.')
                    ->with('validation_result', $validation->toArray());
            }

            $normalizedValidated = null;
            $pricing = [];
            $protection = [];
            $passengerPricing = null;
            $passengerPricingAvailable = false;
            $holdSessionId = 0;

            $postCheckoutReady = $validation->is_valid && $validation->validated_offer !== null;

            if (! $postCheckoutReady) {
                if (in_array((string) $validation->status, ['unavailable', 'expired'], true)) {
                    if ($request->session()->get(self::SESSION_BOOKING_AFTER_STALE_RECOVERY)) {
                        $request->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

                        return redirect()->route('flights.results', $resultsQuery)
                            ->withErrors(['flight_id' => __('That fare is no longer available. Please select another updated option.')]);
                    }
                    $unstablePack = $this->tryCheckoutProviderUnstableTestMode(
                        $agency,
                        $criteria,
                        $offer,
                        $selectedOfferId,
                        $searchId,
                        (string) $validation->status,
                    );
                    if ($unstablePack !== null) {
                        $validation = $unstablePack['validation'];
                        $normalizedValidated = $unstablePack['normalizedValidated'];
                        $pricing = $unstablePack['pricing'];
                        $offer = $unstablePack['offer'];
                        $protection = $unstablePack['protection'];
                        $holdSessionId = $unstablePack['hold_session']->id;
                        $normalizedFare = is_array($normalizedValidated['fare_breakdown'] ?? null) ? $normalizedValidated['fare_breakdown'] : [];
                        $passengerPricing = is_array($normalizedFare['passenger_pricing'] ?? null) ? $normalizedFare['passenger_pricing'] : null;
                        $passengerPricingAvailable = (bool) ($normalizedFare['passenger_pricing_available'] ?? (is_array($passengerPricing) && $passengerPricing !== []));
                        $this->bookingDraft->merge([
                            'checkout_protection' => $protection,
                            'hold_session_id' => $holdSessionId,
                        ]);
                        $postCheckoutReady = true;
                    }

                    if (! $postCheckoutReady) {
                        $recovered = $this->attemptStaleOfferRecovery($agency, $criteria, $offer);
                        if ($recovered !== null) {
                            $selectedOfferId = (string) ($recovered['offer']['id'] ?? $selectedOfferId);
                            $searchId = (string) $recovered['search_id'];
                            $this->bookingDraft->merge([
                                'flight_id' => $selectedOfferId,
                                'offer_id' => $selectedOfferId,
                                'search_id' => $searchId,
                            ]);
                            $offer = $recovered['offer'];
                            $validation = $this->offerValidationService->validateSelectedOffer($agency, $offer, $criteria + [
                                'source_channel' => 'public_guest',
                            ]);
                            if ($validation->is_valid && $validation->validated_offer !== null) {
                                $normalizedValidated = $validation->validated_offer->toArray();
                                $pricing = $validation->meta['pricing_snapshot'] ?? [];
                                $offer = $this->presentValidatedOffer($normalizedValidated, $pricing);
                                $normalizedFare = is_array($normalizedValidated['fare_breakdown'] ?? null) ? $normalizedValidated['fare_breakdown'] : [];
                                $passengerPricing = is_array($normalizedFare['passenger_pricing'] ?? null) ? $normalizedFare['passenger_pricing'] : null;
                                $passengerPricingAvailable = (bool) ($normalizedFare['passenger_pricing_available'] ?? (is_array($passengerPricing) && $passengerPricing !== []));
                                $protection = $this->buildCheckoutProtectionState($offer, $validation, $selectedOfferId);
                                $holdSessionId = (int) ($this->bookingDraft->current()['hold_session_id'] ?? 0);
                                session()->flash('validation_alert', 'Fare was refreshed with the latest airline availability.');
                                $postCheckoutReady = true;
                            } else {
                                return redirect()->route('flights.results', $resultsQuery)
                                    ->withErrors(['flight_id' => __('That fare is no longer available. Please select another updated option.')]);
                            }
                        } else {
                            return redirect()->route('flights.results', $resultsQuery)
                                ->withErrors(['flight_id' => __('That fare is no longer available. Please select another updated option.')]);
                        }
                    }
                } elseif ((string) $validation->status === 'provider_error') {
                    return redirect()->route('flights.results', $resultsQuery)
                        ->withErrors(['flight_id' => __('Fare validation is temporarily unavailable. Please try again.')]);
                }

                if (! $postCheckoutReady) {
                    return redirect()->route('flights.results', $resultsQuery)
                        ->withErrors(['flight_id' => __('Selected fare is no longer available. Please choose another option.')]);
                }
            }

            if ($normalizedValidated === null) {
                $normalizedValidated = $validation->validated_offer->toArray();
                $pricing = $validation->meta['pricing_snapshot'] ?? [];
                $offer = $this->presentValidatedOffer($normalizedValidated, $pricing);
                $normalizedFare = is_array($normalizedValidated['fare_breakdown'] ?? null) ? $normalizedValidated['fare_breakdown'] : [];
                $passengerPricing = is_array($normalizedFare['passenger_pricing'] ?? null) ? $normalizedFare['passenger_pricing'] : null;
                $passengerPricingAvailable = (bool) ($normalizedFare['passenger_pricing_available'] ?? (is_array($passengerPricing) && $passengerPricing !== []));
                $protection = $this->buildCheckoutProtectionState($offer, $validation, $selectedOfferId);
                $holdSessionId = (int) ($this->bookingDraft->current()['hold_session_id'] ?? 0);
            }

            $routeStr = $criteria['origin'].' → '.$criteria['destination'];
            $airlineStr = ($offer['airline_name'] ?? '').' ('.($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')).')';
            $travelDate = Carbon::parse($offer['depart_at'] ?? $criteria['depart_date'])->toDateString();
            $booking = DB::transaction(function () use ($agency, $validated, $offer, $pricing, $criteria, $routeStr, $airlineStr, $travelDate, $validation, $normalizedValidated, $request, $holdSessionId, $protection, $selectedOfferId, $passengerPricing, $passengerPricingAvailable): Booking {
                $leadIdx = (int) ($validated['lead_passenger_index'] ?? 0);
                $passengersInput = (array) ($validated['passengers'] ?? []);
                $leadPassenger = $passengersInput[$leadIdx] ?? ($passengersInput[0] ?? []);
                $leadFirstName = trim((string) ($leadPassenger['first_name'] ?? ''));
                $leadLastName = trim((string) ($leadPassenger['last_name'] ?? ''));
                if (! Auth::check() && ($validated['create_account'] ?? false)) {
                    $accountName = trim((string) ($validated['contact_name'] ?? '')) !== ''
                        ? trim((string) $validated['contact_name'])
                        : trim($leadFirstName.' '.$leadLastName);
                    $user = User::query()->create([
                        'name' => $accountName,
                        'email' => $validated['email'],
                        'password' => Hash::make((string) $validated['password']),
                        'account_type' => AccountType::Customer,
                        'status' => UserAccountStatus::Active,
                        'current_agency_id' => $agency->id,
                        'meta' => [
                            'first_name' => $leadFirstName,
                            'last_name' => $leadLastName,
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
                    'supplier' => $offer['supplier_provider'] ?? 'duffel',
                    'route' => $routeStr,
                    'airline' => $airlineStr,
                    'travel_date' => $travelDate,
                    'payment_status' => 'unpaid',
                    'source_channel' => 'public_guest',
                    'hold_session_id' => $holdSessionId > 0 ? $holdSessionId : null,
                    'supplier_hold_status' => (string) ($protection['hold_status'] ?? 'not_started'),
                    'price_guarantee_expires_at' => $protection['price_guarantee_expires_at'] ?? null,
                    'payment_required_by' => $protection['payment_required_by'] ?? null,
                    'meta' => [
                        'flight_offer_snapshot' => SensitiveDataRedactor::redact($offer),
                        'normalized_offer_snapshot' => SensitiveDataRedactor::redact($offer),
                        'supplier_provider' => $offer['supplier_provider'] ?? 'duffel',
                        'supplier_connection_id' => $offer['supplier_connection_id'] ?? null,
                        'search_criteria' => $criteria,
                        'pricing_snapshot' => SensitiveDataRedactor::redact($pricing),
                        'applied_rules' => SensitiveDataRedactor::redact($pricing['applied_rules'] ?? []),
                        'offer_validation_status' => $validation->status,
                        'defer_supplier_booking_to_manual_review' => (bool) ($protection['provider_unstable_test_mode'] ?? false),
                        'provider_unstable_test_mode' => (bool) ($protection['provider_unstable_test_mode'] ?? false),
                        'validated_at' => now()->toIso8601String(),
                        'original_offer_id' => $selectedOfferId,
                        'supplier_total' => (float) ($protection['supplier_total'] ?? 0),
                        'supplier_currency' => (string) ($protection['supplier_currency'] ?? 'PKR'),
                        'price_changed' => (bool) ($protection['price_changed'] ?? false),
                        'offer_validated_at' => (string) ($protection['offer_validated_at'] ?? now()->toIso8601String()),
                        'payment_requirements' => $protection['payment_requirements'] ?? [],
                        'protection_mode' => (string) ($protection['protection_mode'] ?? 'instant_payment_required'),
                        'requires_instant_payment' => (bool) ($protection['requires_instant_payment'] ?? true),
                        'hold_supported' => (bool) ($protection['hold_supported'] ?? false),
                        'price_guaranteed' => (bool) ($protection['price_guaranteed'] ?? false),
                        'offer_expires_at' => $protection['offer_expires_at'] ?? null,
                        'price_guarantee_expires_at' => $protection['price_guarantee_expires_at'] ?? null,
                        'checkout_lock_started_at' => now()->toIso8601String(),
                        'checkout_lock_expires_at' => $protection['checkout_lock_expires_at'] ?? now()->addMinutes(30)->toIso8601String(),
                        'validated_offer_snapshot' => SensitiveDataRedactor::redact($normalizedValidated),
                        'validation_warnings' => SensitiveDataRedactor::redact($validation->warnings),
                        'passenger_pricing' => $passengerPricing,
                        'passenger_pricing_available' => $passengerPricingAvailable,
                        'pricing_breakdown_available' => $passengerPricingAvailable,
                        'passenger_counts' => [
                            'adults' => (int) ($validated['adults'] ?? 1),
                            'children' => (int) ($validated['children'] ?? 0),
                            'infants' => (int) ($validated['infants'] ?? 0),
                            'total' => (int) (($validated['adults'] ?? 1) + ($validated['children'] ?? 0) + ($validated['infants'] ?? 0)),
                        ],
                        'lead_passenger_sequence' => $leadIdx + 1,
                        'checkout_search_id' => trim((string) ($validated['search_id'] ?? '')),
                    ],
                ])->save();

                $mappedPassengers = collect($passengersInput)->values()->map(
                    function (array $passenger, int $idx) use ($leadIdx): array {
                        return [
                            'passenger_index' => $idx + 1,
                            'passenger_type' => (string) ($passenger['passenger_type'] ?? 'adult'),
                            'is_lead_passenger' => $idx === $leadIdx,
                            'title' => $passenger['title'] ?? null,
                            'first_name' => (string) ($passenger['first_name'] ?? ''),
                            'last_name' => (string) ($passenger['last_name'] ?? ''),
                            'date_of_birth' => $passenger['date_of_birth'] ?? null,
                            'nationality' => isset($passenger['nationality']) ? strtoupper((string) $passenger['nationality']) : null,
                            'gender' => $passenger['gender'] ?? null,
                            'passport_number' => isset($passenger['passport_number']) && trim((string) $passenger['passport_number']) !== ''
                                ? trim((string) $passenger['passport_number'])
                                : null,
                            'passport_issuing_country' => isset($passenger['passport_issuing_country']) && trim((string) $passenger['passport_issuing_country']) !== ''
                                ? strtoupper((string) $passenger['passport_issuing_country'])
                                : null,
                            'passport_expiry_date' => $passenger['passport_expiry_date'] ?? null,
                            'passport_issue_date' => $passenger['passport_issue_date'] ?? null,
                            'document_type' => $passenger['document_type'] ?? 'passport',
                            'national_id_number' => isset($passenger['national_id_number']) && trim((string) $passenger['national_id_number']) !== ''
                                ? trim((string) $passenger['national_id_number'])
                                : null,
                            'country_of_residence' => $passenger['country_of_residence'] ?? null,
                            'place_of_birth' => $passenger['place_of_birth'] ?? null,
                            'meta' => [
                                'traveler_type' => (string) ($passenger['passenger_type'] ?? 'adult'),
                            ],
                        ];
                    }
                )->all();
                $this->bookingService->attachPassengers($booking, $mappedPassengers);

                $this->bookingService->attachContact($booking, [
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'country' => $validated['country'] ?? null,
                    'address_line' => null,
                    'meta' => [
                        'contact_name' => trim((string) ($validated['contact_name'] ?? '')) !== ''
                            ? trim((string) $validated['contact_name'])
                            : trim($leadFirstName.' '.$leadLastName),
                    ],
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
                        [
                            'passenger_pricing' => $passengerPricing,
                            'passenger_pricing_available' => $passengerPricingAvailable,
                            'passenger_counts' => [
                                'adults' => (int) ($validated['adults'] ?? 1),
                                'children' => (int) ($validated['children'] ?? 0),
                                'infants' => (int) ($validated['infants'] ?? 0),
                            ],
                        ],
                    ],
                ]);

                return $booking->fresh();
            });

            $activeHoldSession = null;
            if ($holdSessionId > 0) {
                BookingHoldSession::query()->whereKey($holdSessionId)->update([
                    'booking_id' => $booking->id,
                    'passenger_counts' => [
                        'adults' => (int) ($validated['adults'] ?? 1),
                        'children' => (int) ($validated['children'] ?? 0),
                        'infants' => (int) ($validated['infants'] ?? 0),
                        'total' => (int) (($validated['adults'] ?? 1) + ($validated['children'] ?? 0) + ($validated['infants'] ?? 0)),
                    ],
                    'hold_status' => in_array((string) ($protection['protection_mode'] ?? ''), ['hold_price_guaranteed', 'hold_no_price_guarantee'], true)
                        ? 'pending'
                        : 'not_supported',
                    'updated_at' => now(),
                ]);
                $activeHoldSession = BookingHoldSession::query()->find($holdSessionId);
            }

            $request->session()->put(PublicBooking::SESSION_BOOKING_ID, $booking->id);
            $this->bookingDraft->clear();

            $actor = Auth::user();
            if (! $actor instanceof User) {
                $actor = User::query()
                    ->where('current_agency_id', $booking->agency_id)
                    ->whereIn('account_type', [AccountType::AgencyAdmin, AccountType::Staff, AccountType::PlatformAdmin])
                    ->orderBy('id')
                    ->first();
            }
            if (in_array((string) (($booking->meta['protection_mode'] ?? '')), ['hold_price_guaranteed', 'hold_no_price_guarantee'], true)) {
                $holdAction = $this->fareHoldService->createHoldIfSupported(
                    $booking,
                    $actor instanceof User ? $actor : null,
                    fn (Booking $b, User $a) => $this->supplierBookingService->createSupplierBooking($b, $a, true)
                );
                $holdResult = $holdAction['result'];
                $meta = is_array($booking->meta) ? $booking->meta : [];
                $meta['supplier_hold_attempted_at'] = now()->toIso8601String();
                $meta['supplier_hold_action_status'] = (string) ($holdAction['status'] ?? 'not_supported');
                $meta['supplier_hold_success'] = (bool) ($holdResult?->success ?? false);
                $meta['supplier_hold_status'] = (string) ($holdResult?->status ?? ($holdAction['status'] ?? 'not_supported'));
                $meta['supplier_hold_reference'] = $holdResult?->supplier_reference;
                $meta['supplier_hold_pnr'] = $holdResult?->pnr;
                $meta['supplier_hold_warnings'] = $holdResult?->warnings ?? [];
                $booking->forceFill([
                    'meta' => $meta,
                    'supplier_hold_status' => $holdResult?->success ? 'held' : (($holdAction['status'] ?? '') === 'hold_pending_passenger_details' ? 'pending' : 'failed'),
                ])->save();
                if ($activeHoldSession !== null) {
                    BookingHoldSession::query()->whereKey($activeHoldSession->id)->update([
                        'supplier_order_id' => $holdResult?->supplier_reference,
                        'supplier_order_reference' => $holdResult?->pnr,
                        'hold_status' => $holdResult?->success ? 'held' : (($holdAction['status'] ?? '') === 'hold_pending_passenger_details' ? 'pending' : 'failed'),
                        'hold_order_snapshot' => [
                            'provider' => $holdResult?->provider,
                            'status' => $holdResult?->status,
                            'warnings' => $holdResult?->warnings ?? [],
                            'safe_summary' => $holdResult?->safe_summary ?? [],
                        ],
                        'last_error_safe' => $holdResult?->success ? null : ($holdResult?->error_message ?? 'Supplier hold not confirmed.'),
                        'updated_at' => now(),
                    ]);
                    if ($holdResult?->success) {
                        $this->fareHoldService->markHoldCompleted($booking, $activeHoldSession, $actor instanceof User ? $actor : null);
                    } else {
                        $this->fareHoldService->markHoldFailed($booking, $activeHoldSession, (string) ($holdResult?->error_message ?? 'Supplier hold not confirmed.'), $actor instanceof User ? $actor : null);
                    }
                }
            }

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

        if ($offer !== null && $agency !== null) {
            $holdPreparation = $this->fareHoldService->prepareCheckoutHold(
                searchId: (string) ($draft['search_id'] ?? ''),
                offerId: $effectiveFlightId,
                agency: $agency,
                user: auth()->user(),
                offer: $offer,
                criteria: $criteria + ['source_channel' => 'public_guest'],
                presentOffer: fn (array $normalized, array $pricing): array => $this->presentValidatedOffer($normalized, $pricing),
            );
            $validation = $holdPreparation['validation'];
            $checkoutReady = $validation->is_valid && $validation->validated_offer !== null;
            $mergedHoldFromUnstableTestMode = false;

            if (! $checkoutReady) {
                if (in_array((string) $validation->status, ['unavailable', 'expired'], true)) {
                    if ($request->session()->get(self::SESSION_BOOKING_AFTER_STALE_RECOVERY)) {
                        $request->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

                        return redirect()->route('flights.search')
                            ->withErrors(['flight_id' => __('That fare is no longer available. Please select another updated option.')]);
                    }
                    $unstablePack = $this->tryCheckoutProviderUnstableTestMode(
                        $agency,
                        $criteria,
                        $offer,
                        $effectiveFlightId,
                        (string) ($draft['search_id'] ?? ''),
                        (string) $validation->status,
                    );
                    if ($unstablePack !== null) {
                        $offer = $unstablePack['offer'];
                        $this->bookingDraft->merge([
                            'checkout_protection' => $unstablePack['protection'],
                            'hold_session_id' => $unstablePack['hold_session']->id,
                        ]);
                        $checkoutReady = true;
                        $mergedHoldFromUnstableTestMode = true;
                    }
                }

                if (! $checkoutReady && in_array((string) $validation->status, ['unavailable', 'expired'], true)) {
                    $recovered = $this->attemptStaleOfferRecovery($agency, $criteria, $offer);
                    if ($recovered !== null) {
                        $effectiveFlightId = (string) ($recovered['offer']['id'] ?? $effectiveFlightId);
                        $this->bookingDraft->merge([
                            'flight_id' => $effectiveFlightId,
                            'offer_id' => $effectiveFlightId,
                            'search_id' => (string) $recovered['search_id'],
                        ]);

                        $request->session()->put(self::SESSION_BOOKING_AFTER_STALE_RECOVERY, true);

                        return $this->redirectToBookingPassengers([
                            'flight_id' => $effectiveFlightId,
                            'offer_id' => $effectiveFlightId,
                            'search_id' => (string) $recovered['search_id'],
                            'from' => $criteria['origin'],
                            'to' => $criteria['destination'],
                            'depart' => $criteria['depart_date'],
                            'trip_type' => (string) ($criteria['trip_type'] ?? 'one_way'),
                            'return_date' => (string) ($criteria['return_date'] ?? ''),
                            'cabin' => (string) ($criteria['cabin'] ?? 'economy'),
                            'adults' => (int) ($criteria['adults'] ?? 1),
                            'children' => (int) ($criteria['children'] ?? 0),
                            'infants' => (int) ($criteria['infants'] ?? 0),
                            'recovery_done' => '1',
                        ], 'Fare was refreshed with the latest airline availability.');
                    }

                    $request->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

                    return redirect()->route('flights.search')
                        ->withErrors(['flight_id' => __('That fare is no longer available. Please select another updated option.')]);
                }
                if (! $checkoutReady && (string) $validation->status === 'provider_error') {
                    $request->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

                    return redirect()->route('flights.search')
                        ->withErrors(['flight_id' => __('Fare validation is temporarily unavailable. Please try again.')]);
                }

                if (! $checkoutReady) {
                    $request->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

                    return redirect()->route('flights.search')
                        ->withErrors(['flight_id' => __('Selected flight is no longer available.')]);
                }
            }

            if ($checkoutReady && ! $mergedHoldFromUnstableTestMode) {
                $pricing = $validation->meta['pricing_snapshot'] ?? [];
                $normalizedValidated = $validation->validated_offer->toArray();
                $offer = $holdPreparation['presented_offer'];
                $protection = $this->buildCheckoutProtectionState($offer, $validation, $effectiveFlightId);
                $holdSession = $holdPreparation['hold_session'];
                $this->bookingDraft->merge([
                    'checkout_protection' => $protection,
                    'hold_session_id' => $holdSession?->id,
                ]);
            }
        }

        if ($offer !== null && is_string($draft['search_id'] ?? null) && ($draft['search_id'] ?? '') !== '') {
            $payload = $this->searchStore->get((string) $draft['search_id']);
            $leadCriteria = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : $criteria;
            if (! $this->departurePolicy->offerMeetsLeadTimeForBooking($offer, $leadCriteria)) {
                $request->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

                return redirect()->route('flights.search')
                    ->withErrors(['flight_id' => __(FlightDeparturePolicy::SAME_DAY_LEAD_MESSAGE)]);
            }
        }

        $adults = (int) ($draft['adults'] ?? $criteria['adults'] ?? 1);
        $children = (int) ($draft['children'] ?? $criteria['children'] ?? 0);
        $infants = (int) ($draft['infants'] ?? $criteria['infants'] ?? 0);
        $expectedPassengers = [];
        $idx = 0;
        for ($i = 0; $i < $adults; $i++) {
            $expectedPassengers[] = ['index' => $idx++, 'type' => 'adult', 'label' => 'Adult'];
        }
        for ($i = 0; $i < $children; $i++) {
            $expectedPassengers[] = ['index' => $idx++, 'type' => 'child', 'label' => 'Child'];
        }
        for ($i = 0; $i < $infants; $i++) {
            $expectedPassengers[] = ['index' => $idx++, 'type' => 'infant', 'label' => 'Infant'];
        }

        $draft = $this->bookingDraft->current();

        $request->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

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
            'expectedPassengers' => $expectedPassengers,
            'passengerCountSummary' => [
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'total' => $adults + $children + $infants,
            ],
            'checkoutProtection' => $draft['checkout_protection'] ?? null,
        ]);
    }

    public function review(Request $request): View|RedirectResponse
    {
        $this->logBookingRouteEntry($request);

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'booking_method' => ['required', 'string', 'in:pay_later,bank_transfer,office'],
                'confirm_updated_fare' => ['nullable', 'boolean'],
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

            $meta = is_array($booking->meta) ? $booking->meta : [];
            if (($meta['requires_price_change_confirmation'] ?? false) && ! (bool) ($validated['confirm_updated_fare'] ?? false)) {
                return redirect()->route('booking.review')
                    ->withErrors(['confirm_updated_fare' => 'Fare changed. Please review updated fare and confirm before final submit.']);
            }

            $revalidated = $this->revalidateCheckoutBeforeConfirmation($booking);
            if (($revalidated['status'] ?? 'ok') === 'hold_expired') {
                return redirect()->route('booking.review')
                    ->withErrors(['flight_id' => 'Your airline hold has expired. Please recheck the fare before continuing.'])
                    ->with('recheck_required', true);
            }
            if (($revalidated['status'] ?? 'ok') === 'price_changed') {
                return redirect()->route('booking.review')
                    ->withErrors([
                        'flight_id' => sprintf(
                            'Fare changed from Rs %s to Rs %s. Please review the updated fare before continuing.',
                            number_format((float) ($revalidated['old_total'] ?? 0), 0),
                            number_format((float) ($revalidated['new_total'] ?? 0), 0)
                        ),
                    ])
                    ->with('recheck_required', true);
            }
            if (($revalidated['status'] ?? 'ok') !== 'ok') {
                return $this->redirectToBookingPassengers([
                    'flight_id' => (string) ($meta['original_offer_id'] ?? ''),
                    'offer_id' => (string) ($meta['original_offer_id'] ?? ''),
                    'search_id' => (string) ($meta['checkout_search_id'] ?? ''),
                    'from' => (string) data_get($meta, 'search_criteria.origin', ''),
                    'to' => (string) data_get($meta, 'search_criteria.destination', ''),
                    'depart' => (string) data_get($meta, 'search_criteria.depart_date', ''),
                    'trip_type' => (string) data_get($meta, 'search_criteria.trip_type', 'one_way'),
                    'return_date' => (string) data_get($meta, 'search_criteria.return_date', ''),
                    'cabin' => (string) data_get($meta, 'search_criteria.cabin', 'economy'),
                    'adults' => (int) data_get($meta, 'search_criteria.adults', 1),
                    'children' => (int) data_get($meta, 'search_criteria.children', 0),
                    'infants' => (int) data_get($meta, 'search_criteria.infants', 0),
                ])->withErrors(['flight_id' => 'This fare is no longer available. Please choose another flight.']);
            }
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

        $leadPassenger = $booking->passengers->firstWhere('is_lead_passenger', true) ?? $booking->passengers->first();
        $contact = $booking->contact;
        $draft = [
            'flight_id' => $offer['id'] ?? '',
            'title' => $leadPassenger?->title,
            'first_name' => $leadPassenger?->first_name,
            'last_name' => $leadPassenger?->last_name,
            'dob' => $leadPassenger?->date_of_birth?->format('Y-m-d'),
            'gender' => $leadPassenger?->gender,
            'nationality' => $leadPassenger?->nationality,
            'email' => $contact?->email,
            'phone' => $contact?->phone,
            'country' => $contact?->country,
            'search_from' => $criteria['origin'] ?? '',
            'search_to' => $criteria['destination'] ?? '',
            'search_depart' => $criteria['depart_date'] ?? '',
        ];

        return view('frontend.booking.review', [
            'draft' => $draft,
            'leadPassenger' => $leadPassenger,
            'offer' => $offer,
            'criteria' => $criteria,
            'booking' => $booking,
            'airlineLogo' => $this->airlineBranding->getLogoForCode((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? ''))),
            'recheckRequired' => (bool) session('recheck_required', false),
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

        $leadPassenger = $booking->passengers->firstWhere('is_lead_passenger', true) ?? $booking->passengers->first();
        $contact = $booking->contact;
        $draft = [
            'flight_id' => $offer['id'] ?? '',
            'booking_reference' => $booking->booking_reference,
            'booking_method' => $meta['booking_method'] ?? 'pay_later',
            'title' => $leadPassenger?->title,
            'first_name' => $leadPassenger?->first_name,
            'last_name' => $leadPassenger?->last_name,
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

    /**
     * @param  array<string, mixed>  $offer
     */
    protected function buildCheckoutProtectionState(array $offer, $validation, string $selectedOfferId, bool $providerUnstableTestMode = false): array
    {
        $rawPayload = is_array($offer['raw_payload'] ?? null) ? $offer['raw_payload'] : [];
        $paymentRequirements = is_array(data_get($rawPayload, 'payment_requirements'))
            ? data_get($rawPayload, 'payment_requirements')
            : [];
        $requiresInstantPayment = (bool) ($paymentRequirements['requires_instant_payment'] ?? true);
        $priceGuarantee = data_get($rawPayload, 'conditions.price_guarantee');
        $priceGuaranteed = is_array($priceGuarantee)
            ? (bool) ($priceGuarantee['enabled'] ?? $priceGuarantee['is_guaranteed'] ?? false)
            : false;
        $holdSupported = ! $requiresInstantPayment;
        $mode = $requiresInstantPayment
            ? 'instant_payment_required'
            : ($priceGuaranteed ? 'hold_price_guaranteed' : 'hold_no_price_guarantee');
        $offerExpiresAt = (string) ($offer['expires_at'] ?? '');
        $paymentRequiredBy = data_get($rawPayload, 'payment_requirements.payment_required_by');
        $holdStatus = $holdSupported ? 'pending' : 'not_supported';

        if ($providerUnstableTestMode) {
            $requiresInstantPayment = true;
            $holdSupported = false;
            $priceGuaranteed = false;
            $mode = 'instant_payment_required';
            $holdStatus = 'not_supported';
        }

        return [
            'original_offer_id' => $selectedOfferId,
            'validated_offer_snapshot' => SensitiveDataRedactor::redact($offer),
            'supplier_total' => (float) ($offer['total'] ?? 0),
            'supplier_currency' => (string) ($offer['currency'] ?? 'PKR'),
            'price_changed' => (bool) ($validation->price_changed ?? false),
            'offer_validated_at' => now()->toIso8601String(),
            'payment_requirements' => SensitiveDataRedactor::redact($paymentRequirements),
            'requires_instant_payment' => $requiresInstantPayment,
            'hold_supported' => $holdSupported,
            'price_guaranteed' => $priceGuaranteed,
            'protection_mode' => $mode,
            'offer_expires_at' => $offerExpiresAt !== '' ? $offerExpiresAt : null,
            'price_guarantee_expires_at' => is_array($priceGuarantee) ? ($priceGuarantee['expires_at'] ?? null) : null,
            'payment_required_by' => $paymentRequiredBy ?: null,
            'hold_status' => $holdStatus,
            'checkout_lock_expires_at' => $offerExpiresAt !== '' ? $offerExpiresAt : now()->addMinutes(30)->toIso8601String(),
            'provider_unstable_test_mode' => $providerUnstableTestMode,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $searchPayload
     */
    protected function searchPayloadIsFreshForUnstableTestMode(?array $searchPayload): bool
    {
        if (! is_array($searchPayload)) {
            return false;
        }
        $createdAt = $searchPayload['created_at'] ?? null;
        if (! is_string($createdAt) || trim($createdAt) === '') {
            return false;
        }
        try {
            $created = Carbon::parse($createdAt);
        } catch (\Throwable) {
            return false;
        }
        $window = (int) config('ota.provider_unstable_test_mode_window_seconds', 120);
        $window = max(1, $window);

        return ! now()->subSeconds($window)->greaterThan($created);
    }

    /**
     * Testing: allow provider-unstable fallback. Local: only when OTA_ALLOW_PROVIDER_UNSTABLE_LOCAL=true.
     * Staging/production: never.
     */
    protected function providerUnstableTestModeIsAllowed(): bool
    {
        return ProviderUnstableTestMode::isCheckoutFallbackAllowed();
    }

    /**
     * local/testing only: allow checkout using cached search pricing when single-offer validation fails.
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $cachedOffer
     * @return array{
     *     validation: OfferValidationResultData,
     *     normalizedValidated: array<string, mixed>,
     *     pricing: array<string, mixed>,
     *     offer: array<string, mixed>,
     *     protection: array<string, mixed>,
     *     hold_session: BookingHoldSession
     * }|null
     */
    protected function tryCheckoutProviderUnstableTestMode(
        Agency $agency,
        array $criteria,
        array $cachedOffer,
        string $effectiveFlightId,
        string $searchId,
        string $underlyingValidationStatus,
    ): ?array {
        if (! $this->providerUnstableTestModeIsAllowed()) {
            return null;
        }
        if ($searchId === '' || ! in_array($underlyingValidationStatus, ['unavailable', 'expired'], true)) {
            return null;
        }
        $payload = $this->searchStore->get($searchId);
        if (! $this->searchPayloadIsFreshForUnstableTestMode($payload)) {
            return null;
        }

        $validatedDto = NormalizedFlightOfferData::fromArray($cachedOffer);
        $normalizedValidated = $validatedDto->toArray();
        $pricing = $this->offerValidationService->pricingSnapshotForCachedOffer(
            $agency,
            $cachedOffer,
            $criteria + ['source_channel' => 'public_guest']
        );
        $offer = $this->presentValidatedOffer($normalizedValidated, $pricing);
        $validation = new OfferValidationResultData(
            is_valid: true,
            status: 'provider_unstable_test_mode',
            original_offer_id: $effectiveFlightId,
            validated_offer: $validatedDto,
            warnings: [
                'Duffel did not confirm this offer via single-offer retrieval; checkout uses cached search pricing in this environment.',
            ],
            meta: [
                'underlying_validation_status' => $underlyingValidationStatus,
                'reason_code' => 'provider_unstable_test_mode',
            ],
        );
        $protection = $this->buildCheckoutProtectionState($offer, $validation, $effectiveFlightId, true);
        $holdSession = $this->fareHoldService->refreshHoldSession(
            agency: $agency,
            booking: null,
            searchId: $searchId,
            offerId: $effectiveFlightId,
            normalizedOffer: $offer,
            user: auth()->user(),
            holdStatus: 'not_supported',
            safeError: null,
            existing: null,
            metaOverrides: [
                'reason_code' => 'provider_unstable_test_mode',
                'provider_unstable_test_mode' => true,
            ],
        );

        Log::info('booking.checkout.provider_unstable_test_mode', [
            'reason_code' => 'provider_unstable_test_mode',
            'offer_id' => $effectiveFlightId,
            'search_id' => $searchId,
            'underlying_validation_status' => $underlyingValidationStatus,
        ]);

        return [
            'validation' => $validation,
            'normalizedValidated' => $normalizedValidated,
            'pricing' => $pricing,
            'offer' => $offer,
            'protection' => $protection,
            'hold_session' => $holdSession,
        ];
    }

    protected function upsertCheckoutHoldSession(Request $request, Agency $agency, array $criteria, string $offerId, array $protection): ?BookingHoldSession
    {
        if ($offerId === '') {
            return null;
        }

        $draft = $this->bookingDraft->current();
        $existingId = (int) ($draft['hold_session_id'] ?? 0);
        $session = $existingId > 0 ? BookingHoldSession::query()->find($existingId) : null;
        if ($session === null) {
            $session = new BookingHoldSession;
        }

        $session->fill([
            'agency_id' => $agency->id,
            'search_id' => (string) ($draft['search_id'] ?? $request->query('search_id', '')),
            'offer_id' => $offerId,
            'supplier_provider' => (string) ($protection['validated_offer_snapshot']['supplier_provider'] ?? ''),
            'supplier_connection_id' => $protection['validated_offer_snapshot']['supplier_connection_id'] ?? null,
            'supplier_offer_id' => (string) ($protection['validated_offer_snapshot']['raw_reference'] ?? $offerId),
            'hold_status' => (string) ($protection['hold_status'] ?? 'not_started'),
            'requires_instant_payment' => (bool) ($protection['requires_instant_payment'] ?? true),
            'price_guarantee_expires_at' => $protection['price_guarantee_expires_at'] ?? null,
            'payment_required_by' => $protection['payment_required_by'] ?? null,
            'local_checkout_expires_at' => $protection['checkout_lock_expires_at'] ?? null,
            'hold_expires_at' => $protection['offer_expires_at'] ?? null,
            'validated_total_amount' => (float) ($protection['supplier_total'] ?? 0),
            'validated_total_currency' => (string) ($protection['supplier_currency'] ?? 'PKR'),
            'converted_total_pkr' => (float) ($protection['supplier_total'] ?? 0),
            'markup_snapshot' => is_array($protection['validated_offer_snapshot']['pricing_components'] ?? null)
                ? $protection['validated_offer_snapshot']['pricing_components']
                : [],
            'passenger_counts' => [
                'adults' => (int) ($criteria['adults'] ?? 1),
                'children' => (int) ($criteria['children'] ?? 0),
                'infants' => (int) ($criteria['infants'] ?? 0),
                'total' => (int) (($criteria['adults'] ?? 1) + ($criteria['children'] ?? 0) + ($criteria['infants'] ?? 0)),
            ],
            'passenger_pricing' => is_array(data_get($protection, 'validated_offer_snapshot.fare_breakdown.passenger_pricing'))
                ? data_get($protection, 'validated_offer_snapshot.fare_breakdown.passenger_pricing')
                : null,
            'passenger_pricing_available' => (bool) data_get($protection, 'validated_offer_snapshot.fare_breakdown.passenger_pricing_available', false),
            'validated_offer_snapshot' => $protection['validated_offer_snapshot'] ?? [],
            'safe_error' => null,
            'expires_at' => $protection['checkout_lock_expires_at'] ?? now()->addMinutes(15),
            'created_by_user_id' => auth()->id(),
        ]);
        $session->save();

        return $session->fresh();
    }

    /**
     * @return array{status:string, old_total?:float, new_total?:float}
     */
    protected function revalidateCheckoutBeforeConfirmation(Booking $booking): array
    {
        $meta = is_array($booking->meta) ? $booking->meta : [];
        $holdExpiry = $meta['payment_required_by'] ?? $meta['price_guarantee_expires_at'] ?? $meta['offer_expires_at'] ?? null;
        if (is_string($holdExpiry) && trim($holdExpiry) !== '') {
            try {
                if (now()->greaterThan(Carbon::parse($holdExpiry))) {
                    return ['status' => 'hold_expired'];
                }
            } catch (\Throwable) {
                // ignore parse issues and continue with revalidation
            }
        }
        $mode = (string) ($meta['protection_mode'] ?? 'instant_payment_required');
        if (! $this->fareHoldService->requiresFinalRevalidation($booking)) {
            return ['status' => 'ok'];
        }

        $agency = Agency::query()->find($booking->agency_id);
        if ($agency === null) {
            return ['status' => 'unavailable'];
        }

        $validation = $this->fareHoldService->revalidateBeforeConfirmation($booking, $agency);
        if (! $validation->is_valid || $validation->validated_offer === null) {
            if (
                $this->providerUnstableTestModeIsAllowed()
                && ($meta['provider_unstable_test_mode'] ?? false) === true
            ) {
                Log::info('booking.checkout.revalidation_skipped_provider_unstable_test_mode', [
                    'booking_id' => $booking->id,
                    'reason_code' => 'provider_unstable_test_mode',
                ]);

                return ['status' => 'ok'];
            }

            return ['status' => 'unavailable'];
        }

        $oldTotal = (float) ($meta['supplier_total'] ?? 0);
        $normalizedValidated = $validation->validated_offer->toArray();
        $pricing = $validation->meta['pricing_snapshot'] ?? [];
        $presented = $this->presentValidatedOffer($normalizedValidated, $pricing);
        $newTotal = (float) ($presented['total'] ?? 0);
        $priceChanged = (bool) ($validation->price_changed ?? false) || ($oldTotal > 0 && abs($newTotal - $oldTotal) > 0.009);
        $meta['validated_offer_snapshot'] = SensitiveDataRedactor::redact($normalizedValidated);
        $meta['flight_offer_snapshot'] = SensitiveDataRedactor::redact($presented);
        $meta['supplier_total'] = (float) ($presented['total'] ?? 0);
        $meta['supplier_currency'] = (string) ($presented['currency'] ?? 'PKR');
        $meta['price_changed'] = (bool) ($validation->price_changed ?? false);
        $meta['offer_validated_at'] = now()->toIso8601String();
        $normalizedFare = is_array($normalizedValidated['fare_breakdown'] ?? null) ? $normalizedValidated['fare_breakdown'] : [];
        $passengerPricing = is_array($normalizedFare['passenger_pricing'] ?? null) ? $normalizedFare['passenger_pricing'] : null;
        $passengerPricingAvailable = (bool) ($normalizedFare['passenger_pricing_available'] ?? (is_array($passengerPricing) && $passengerPricing !== []));
        $meta['passenger_pricing'] = $passengerPricing;
        $meta['passenger_pricing_available'] = $passengerPricingAvailable;
        $meta['pricing_breakdown_available'] = $passengerPricingAvailable;
        $meta['fare_rechecked_at'] = now()->toIso8601String();
        $meta['requires_price_change_confirmation'] = $priceChanged;
        if ($priceChanged) {
            $meta['price_change_old_total'] = $oldTotal;
            $meta['price_change_new_total'] = $newTotal;
        } else {
            unset($meta['price_change_old_total'], $meta['price_change_new_total']);
        }
        $booking->forceFill(['meta' => $meta])->save();
        $this->bookingService->attachFareBreakdown($booking, [
            'base_fare' => (float) ($pricing['base_fare'] ?? 0),
            'taxes' => (float) ($pricing['taxes'] ?? 0),
            'fees' => (float) ($pricing['service_fee'] ?? 0),
            'markup' => (float) (($pricing['admin_markup'] ?? 0) + ($pricing['route_markup'] ?? 0) + ($pricing['airline_markup'] ?? 0) + ($pricing['agent_markup_or_commission'] ?? 0)),
            'discount' => 0,
            'total' => (float) ($pricing['final_total'] ?? 0),
            'currency' => (string) ($presented['currency'] ?? 'PKR'),
            'breakdown' => [
                ['label' => 'Base fare', 'amount' => (float) ($pricing['base_fare'] ?? 0)],
                ['label' => 'Taxes & surcharges', 'amount' => (float) ($pricing['taxes'] ?? 0)],
                ['label' => 'Admin markup', 'amount' => (float) ($pricing['admin_markup'] ?? 0)],
                ['label' => 'Route markup', 'amount' => (float) ($pricing['route_markup'] ?? 0)],
                ['label' => 'Airline markup', 'amount' => (float) ($pricing['airline_markup'] ?? 0)],
                ['label' => 'Channel/agent markup', 'amount' => (float) ($pricing['agent_markup_or_commission'] ?? 0)],
                ['label' => 'Service fee', 'amount' => (float) ($pricing['service_fee'] ?? 0)],
                [
                    'passenger_pricing' => $passengerPricing,
                    'passenger_pricing_available' => $passengerPricingAvailable,
                    'passenger_counts' => is_array($meta['passenger_counts'] ?? null) ? $meta['passenger_counts'] : [],
                ],
            ],
        ]);

        if ($priceChanged) {
            return [
                'status' => 'price_changed',
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
            ];
        }

        return ['status' => 'ok'];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $selectedOffer
     * @return array{search_id: string, offer: array<string,mixed>}|null
     */
    protected function attemptStaleOfferRecovery(Agency $agency, array $criteria, array $selectedOffer): ?array
    {
        $freshResult = $this->flightSearch->searchWithMeta($criteria, $agency, 'public_guest');
        $freshOffers = array_values(array_filter(
            is_array($freshResult['offers'] ?? null) ? $freshResult['offers'] : [],
            fn (array $offer): bool => strtolower((string) ($offer['supplier_provider'] ?? '')) === 'duffel'
        ));
        if ($freshOffers === []) {
            Log::info('booking.stale_offer.recovery_failed', ['reason_code' => 'stale_offer_recovery_failed']);

            return null;
        }

        $selectedAirline = strtolower((string) ($selectedOffer['airline_code'] ?? ''));
        $selectedFlightNumber = strtolower((string) ($selectedOffer['flight_number'] ?? ''));
        $selectedCabin = strtolower((string) ($selectedOffer['cabin'] ?? ''));
        $selectedFareFamily = strtolower((string) ($selectedOffer['fare_family'] ?? ''));
        $selectedTotal = (float) ($selectedOffer['total'] ?? $selectedOffer['final_customer_price'] ?? 0);

        $best = null;
        $bestScore = -INF;
        foreach ($freshOffers as $candidate) {
            $score = 0.0;
            if (strtolower((string) ($candidate['airline_code'] ?? '')) === $selectedAirline) {
                $score += 100;
            }
            if ($selectedFlightNumber !== '' && strtolower((string) ($candidate['flight_number'] ?? '')) === $selectedFlightNumber) {
                $score += 120;
            }
            if ($selectedCabin !== '' && strtolower((string) ($candidate['cabin'] ?? '')) === $selectedCabin) {
                $score += 50;
            }
            if ($selectedFareFamily !== '' && strtolower((string) ($candidate['fare_family'] ?? '')) === $selectedFareFamily) {
                $score += 25;
            }
            $candidateTotal = (float) ($candidate['total'] ?? $candidate['final_customer_price'] ?? 0);
            if ($selectedTotal > 0) {
                $deltaPct = abs($candidateTotal - $selectedTotal) / $selectedTotal;
                $score -= ($deltaPct * 100);
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }
        if (! is_array($best)) {
            Log::info('booking.stale_offer.recovery_failed', ['reason_code' => 'stale_offer_recovery_failed']);

            return null;
        }

        $newSearchId = $this->searchStore->store(
            $criteria,
            $freshOffers,
            is_array($freshResult['warnings'] ?? null) ? $freshResult['warnings'] : []
        );
        Log::info('booking.stale_offer.recovered', [
            'reason_code' => 'stale_offer_recovered',
            'old_offer_id' => (string) ($selectedOffer['id'] ?? $selectedOffer['offer_id'] ?? ''),
            'new_offer_id' => (string) ($best['id'] ?? $best['offer_id'] ?? ''),
            'search_id' => $newSearchId,
        ]);

        return [
            'search_id' => $newSearchId,
            'offer' => $best,
        ];
    }

    protected function logBookingRouteEntry(Request $request): void
    {
        Log::info('booking_route_entry', [
            'method' => $request->method(),
            'route' => optional($request->route())->getName(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'search_id_present' => $request->filled('search_id'),
            'offer_id_present' => $request->filled('offer_id'),
            'flight_id_present' => $request->filled('flight_id'),
            'draft_id_present' => $request->filled('draft_id'),
            'hold_session_id_present' => $request->filled('hold_session_id'),
            'recovery_done_present' => $request->filled('recovery_done'),
            'user_authenticated' => Auth::check(),
            'intended_url' => $request->session()->get('url.intended'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function redirectToBookingPassengers(array $params, ?string $validationAlert = null): RedirectResponse
    {
        $routeParams = array_filter(
            $params,
            static fn (mixed $v): bool => $v !== null && $v !== ''
        );
        $targetUrl = route('booking.passengers', $routeParams, true);
        $current = request()->fullUrl();
        if (request()->isMethod('get') && $this->normalizedUrlSignature($current) === $this->normalizedUrlSignature($targetUrl)) {
            Log::warning('booking.passengers.self_redirect_blocked', [
                'url' => $current,
            ]);
            request()->session()->forget(self::SESSION_BOOKING_AFTER_STALE_RECOVERY);

            return redirect()->route('flights.search')
                ->withErrors(['flight_id' => __('Checkout could not continue with this fare. Please search again.')]);
        }

        $redirect = redirect()->route('booking.passengers', $routeParams);
        if ($validationAlert !== null) {
            $redirect->with('validation_alert', $validationAlert);
        }

        return $redirect;
    }

    protected function normalizedUrlSignature(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        ksort($query);

        return $path.'?'.http_build_query($query);
    }
}
