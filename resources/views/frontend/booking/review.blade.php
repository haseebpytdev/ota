@extends('layouts.frontend')

@section('title', 'Review booking')

@section('content')
    @php
        use App\Support\Travel\TravelDocumentFormatter;
        $d = $draft;
        $o = $offer;
        $cr = $criteria;
        $meta = $booking->meta ?? [];
        $allPassengers = $booking->passengers->sortBy('passenger_index')->values();
        $paxSummary = $leadPassenger ?? null;
        $passengerCounts = [
            'adults' => $allPassengers->where('passenger_type', 'adult')->count(),
            'children' => $allPassengers->where('passenger_type', 'child')->count(),
            'infants' => $allPassengers->where('passenger_type', 'infant')->count(),
            'total' => $allPassengers->count(),
        ];
        $fare = $booking->fareBreakdown;
        $protectionMode = (string) ($meta['protection_mode'] ?? '');
        $holdRef = (string) ($meta['supplier_hold_reference'] ?? '');
        $lockExpiresAt = (string) ($meta['checkout_lock_expires_at'] ?? '');
        $totalFromDb = (float) ($fare?->total ?? 0);
        $baseFromDb = (float) ($fare?->base_fare ?? 0);
        $taxesFromDb = (float) ($fare?->taxes ?? 0);
        $markupFromDb = (float) ($fare?->markup ?? 0);
        $feesFromDb = (float) ($fare?->fees ?? 0);
        $supplierPassengerPricing = is_array(data_get($o, 'fare_breakdown.passenger_pricing'))
            ? data_get($o, 'fare_breakdown.passenger_pricing')
            : [];
        $passengerPricingAvailable = (bool) (data_get($o, 'fare_breakdown.passenger_pricing_available') ?? (! empty($supplierPassengerPricing)));
        $holdUntil = (string) ($meta['payment_required_by'] ?? $meta['price_guarantee_expires_at'] ?? $meta['offer_expires_at'] ?? '');
        $fareRecheckedAt = (string) ($meta['fare_rechecked_at'] ?? '');
        $requiresPriceConfirm = (bool) ($meta['requires_price_change_confirmation'] ?? false);
        $priceChangeOld = (float) ($meta['price_change_old_total'] ?? 0);
        $priceChangeNew = (float) ($meta['price_change_new_total'] ?? 0);
        $groupedPassengerPricing = [
            'adult' => ['count' => 0, 'base' => 0.0, 'tax' => 0.0, 'total' => 0.0],
            'child' => ['count' => 0, 'base' => 0.0, 'tax' => 0.0, 'total' => 0.0],
            'infant' => ['count' => 0, 'base' => 0.0, 'tax' => 0.0, 'total' => 0.0],
        ];
        foreach ($supplierPassengerPricing as $pp) {
            $type = strtolower((string) ($pp['passenger_type'] ?? 'adult'));
            if ($type === 'children') {
                $type = 'child';
            } elseif ($type === 'adults') {
                $type = 'adult';
            } elseif ($type === 'infants') {
                $type = 'infant';
            }
            if (! isset($groupedPassengerPricing[$type])) {
                $type = 'adult';
            }
            $groupedPassengerPricing[$type]['count']++;
            $groupedPassengerPricing[$type]['base'] += (float) ($pp['base_amount'] ?? 0);
            $groupedPassengerPricing[$type]['tax'] += (float) ($pp['tax_amount'] ?? 0);
            $groupedPassengerPricing[$type]['total'] += (float) ($pp['total_amount'] ?? 0);
        }
    @endphp
    <div class="ota-rev-wrap ota-checkout-page">
        <div class="ota-container ota-container-narrow">
            <div class="ota-checkout-page-head ota-checkout-page-head--flush">
                <p class="ota-checkout-page-kicker">Step 4 of 5</p>
                <h1 class="ota-checkout-page-title">Review your booking</h1>
                <p class="ota-checkout-page-lead">Review locked fare/hold status. Final confirmation is Step 5.</p>
            </div>
            @if (!empty($meta['validation_warnings']) && is_array($meta['validation_warnings']))
                <div class="alert alert-warning">
                    @foreach ($meta['validation_warnings'] as $warning)
                        <div>{{ $warning }}</div>
                    @endforeach
                </div>
            @endif
            @if ($protectionMode !== '')
                <div class="alert alert-info">
                    @if ($protectionMode === 'hold_price_guaranteed')
                        Fare protection: hold + price guaranteed.
                    @elseif ($protectionMode === 'hold_no_price_guarantee')
                        Hold may exist but fare is not guaranteed; final recheck is applied before confirmation.
                    @else
                        Instant payment required by supplier; fare is rechecked before confirmation.
                    @endif
                    @if ($holdRef !== '')
                        <div class="small mt-1">Supplier hold/order reference: {{ $holdRef }}</div>
                    @endif
                    @if ($lockExpiresAt !== '')
                        <div class="small mt-1">Checkout lock expires at: {{ \Illuminate\Support\Carbon::parse($lockExpiresAt)->format('Y-m-d H:i') }}</div>
                    @endif
                </div>
            @endif
            @if (($passengerCounts['total'] ?? 0) >= 9 && isset($meta['supplier_hold_success']) && ! $meta['supplier_hold_success'])
                <div class="alert alert-warning">
                    We could not temporarily hold this fare with the airline. You can still submit a booking request, and our team will confirm availability manually.
                </div>
            @endif
            <div class="alert alert-secondary">
                <strong>Fare status:</strong>
                @if ($requiresPriceConfirm && $priceChangeOld > 0 && $priceChangeNew > 0)
                    Fare changed — review required.
                @elseif ($holdUntil !== '')
                    Held until {{ \Illuminate\Support\Carbon::parse($holdUntil)->format('h:i A') }}.
                @elseif ($fareRecheckedAt !== '')
                    Fare rechecked at {{ \Illuminate\Support\Carbon::parse($fareRecheckedAt)->format('h:i A') }}.
                @else
                    Fare awaiting final recheck.
                @endif
                @if ($recheckRequired ?? false)
                    <div class="mt-2">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('booking.passengers', [
                            'flight_id' => (string) ($meta['original_offer_id'] ?? ''),
                            'offer_id' => (string) ($meta['original_offer_id'] ?? ''),
                            'search_id' => (string) data_get($meta, 'search_criteria.search_id', ''),
                            'from' => (string) data_get($meta, 'search_criteria.origin', ''),
                            'to' => (string) data_get($meta, 'search_criteria.destination', ''),
                            'depart' => (string) data_get($meta, 'search_criteria.depart_date', ''),
                        ]) }}">Recheck fare</a>
                    </div>
                @endif
            </div>

            <div class="ota-checkout-grid">
                <div class="ota-checkout-main">
                    <div class="ota-checkout-card">
                        <h2 class="ota-checkout-section-title">Flight summary</h2>
                        <div class="ota-review-flight">
                            <div class="ota-review-flight__brand">
                                @if(!empty($airlineLogo))
                                    <div class="ota-airline-logo ota-airline-logo--img"><img src="{{ $airlineLogo }}" alt="{{ $o['airline_name'] ?? 'Airline' }} logo"></div>
                                @else
                                    <div class="ota-airline-logo">{{ $o['airline_code'] ?? 'XX' }}</div>
                                @endif
                                <div>
                                    <div class="ota-airline-name">{{ $o['airline_name'] ?? '' }}</div>
                                    <div class="ota-flight-no">{{ $o['carrier_code'] ?? '' }}{{ $o['flight_number'] ?? '' }}</div>
                                </div>
                            </div>
                            <div class="ota-review-flight__route">{{ $cr['origin'] }} → {{ $cr['destination'] }} · {{ \Illuminate\Support\Carbon::parse($o['depart_at'] ?? '')->format('D, M j, Y') }}</div>
                            <div class="row ota-time-row">
                                <div class="col-xs-4">
                                    <div class="ota-time-lg">{{ \Illuminate\Support\Carbon::parse($o['depart_at'] ?? '')->format('H:i') }}</div>
                                    <div class="ota-time-sub">{{ $cr['origin'] }}</div>
                                </div>
                                <div class="col-xs-4 text-center">
                                    <div class="ota-dur-line">{{ $o['duration_h'] ?? 0 }}h {{ str_pad((string) ($o['duration_m'] ?? 0), 2, '0', STR_PAD_LEFT) }}m</div>
                                    <div class="ota-dur-bar"></div>
                                    <span class="label label-default ota-direct-pill">Direct</span>
                                </div>
                                <div class="col-xs-4 text-right">
                                    <div class="ota-time-lg">{{ \Illuminate\Support\Carbon::parse($o['arrive_at'] ?? '')->format('H:i') }}</div>
                                    <div class="ota-time-sub">{{ $cr['destination'] }}</div>
                                </div>
                            </div>
                            <div class="ota-result-tags">
                                <span><i class="fa fa-suitcase"></i> {{ $o['baggage'] ?? '' }}</span>
                                <span class="text-capitalize">{{ str_replace('_', ' ', $o['cabin'] ?? '') }}</span>
                                <span>{{ $o['fare_family'] ?? '' }}</span>
                                @if (!empty($o['refundable']))
                                    <span class="label label-success">Refundable</span>
                                @else
                                    <span class="label label-warning">Non-refundable</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="ota-checkout-card">
                        <h2 class="ota-checkout-section-title">Passenger &amp; contact</h2>
                        <p class="small text-muted mb-3">
                            {{ $passengerCounts['total'] }} passengers:
                            {{ $passengerCounts['adults'] }} adults, {{ $passengerCounts['children'] }} children, {{ $passengerCounts['infants'] }} infants
                        </p>
                        <div class="ota-review-pax">
                            @foreach ($allPassengers as $idx => $passenger)
                                <div class="mb-3 pb-2 border-bottom border-secondary-subtle">
                                    <p class="ota-review-pax__name mb-1">
                                        <strong>
                                            Passenger {{ $idx + 1 }} — {{ ucfirst((string) $passenger->passenger_type) }}
                                            @if($passenger->is_lead_passenger)
                                                <span class="label label-info">Lead passenger</span>
                                            @endif
                                        </strong>
                                    </p>
                                    <p class="mb-1">{{ trim(($passenger->title ?? '').' '.($passenger->first_name ?? '').' '.($passenger->last_name ?? '')) }}</p>
                                    <ul class="ota-review-pax__list ota-review-pax__list--docs mb-2">
                                        @if ($passenger->date_of_birth)
                                            <li><i class="fa fa-calendar"></i> DOB {{ $passenger->date_of_birth->format('j M Y') }}</li>
                                        @endif
                                        @if ($passenger->nationality)
                                            <li><i class="fa fa-flag-o"></i> Nationality {{ strtoupper($passenger->nationality) }}</li>
                                        @endif
                                        @if ($passenger->gender)
                                            <li><i class="fa fa-user"></i> Gender {{ $passenger->gender }}</li>
                                        @endif
                                    </ul>
                                    @if ($passenger->passport_number || $passenger->national_id_number)
                                        <p class="small text-muted mb-0">
                                            @if ($passenger->document_type === 'national_id' && $passenger->national_id_number)
                                                National ID: {{ TravelDocumentFormatter::maskPassport($passenger->national_id_number) }}
                                            @elseif ($passenger->passport_number)
                                                Passport: {{ TravelDocumentFormatter::maskPassport($passenger->passport_number) }}
                                                @if ($passenger->passport_issuing_country)
                                                    · {{ strtoupper($passenger->passport_issuing_country) }}
                                                @endif
                                                @if ($passenger->passport_expiry_date)
                                                    · expires {{ $passenger->passport_expiry_date->format('j M Y') }}
                                                @endif
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                            <ul class="ota-review-pax__list">
                                <li><i class="fa fa-envelope-o"></i> {{ $d['email'] ?? '—' }}</li>
                                <li><i class="fa fa-phone"></i> {{ $d['phone'] ?? '—' }}</li>
                                @if (!empty($d['country']))
                                    <li><i class="fa fa-map-marker"></i> {{ $d['country'] }}</li>
                                @else
                                    <li class="text-muted"><i class="fa fa-map-marker"></i> Country not provided</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <aside class="ota-checkout-aside ota-review-aside-stack" aria-label="Fare and confirmation">
                    <div class="ota-checkout-card ota-checkout-card--accent">
                        <h2 class="ota-checkout-section-title">Fare breakdown</h2>
                        <dl class="ota-fare-dl">
                            @if ($passengerPricingAvailable && ! empty($supplierPassengerPricing))
                                <div class="ota-fare-dl__row"><dt><strong>Adult x {{ $groupedPassengerPricing['adult']['count'] }}</strong></dt><dd></dd></div>
                                <div class="ota-fare-dl__row"><dt>Base fare</dt><dd>Rs {{ number_format($groupedPassengerPricing['adult']['base'], 0) }}</dd></div>
                                <div class="ota-fare-dl__row"><dt>Taxes/fees</dt><dd>Rs {{ number_format($groupedPassengerPricing['adult']['tax'], 0) }}</dd></div>
                                <div class="ota-fare-dl__row"><dt>Total</dt><dd>
                                    Rs {{ number_format($groupedPassengerPricing['adult']['count'] > 0 ? ($groupedPassengerPricing['adult']['total'] / $groupedPassengerPricing['adult']['count']) : 0, 0) }} each
                                    / Rs {{ number_format($groupedPassengerPricing['adult']['total'], 0) }} subtotal
                                </dd></div>

                                <div class="ota-fare-dl__row"><dt><strong>Child x {{ $groupedPassengerPricing['child']['count'] }}</strong></dt><dd></dd></div>
                                <div class="ota-fare-dl__row"><dt>Base fare</dt><dd>Rs {{ number_format($groupedPassengerPricing['child']['base'], 0) }}</dd></div>
                                <div class="ota-fare-dl__row"><dt>Taxes/fees</dt><dd>Rs {{ number_format($groupedPassengerPricing['child']['tax'], 0) }}</dd></div>
                                <div class="ota-fare-dl__row"><dt>Total</dt><dd>Rs {{ number_format($groupedPassengerPricing['child']['total'], 0) }}</dd></div>

                                <div class="ota-fare-dl__row"><dt><strong>Infant x {{ $groupedPassengerPricing['infant']['count'] }}</strong></dt><dd></dd></div>
                                <div class="ota-fare-dl__row"><dt>Base fare</dt><dd>Rs {{ number_format($groupedPassengerPricing['infant']['base'], 0) }}</dd></div>
                                <div class="ota-fare-dl__row"><dt>Taxes/fees</dt><dd>Rs {{ number_format($groupedPassengerPricing['infant']['tax'], 0) }}</dd></div>
                                <div class="ota-fare-dl__row"><dt>Total</dt><dd>Rs {{ number_format($groupedPassengerPricing['infant']['total'], 0) }}</dd></div>
                                <div class="ota-fare-dl__row">
                                    <dt>Markup/service fee</dt>
                                    <dd>Rs {{ number_format(($markupFromDb > 0 ? $markupFromDb : (float) ($o['markup'] ?? 0)) + ($feesFromDb > 0 ? $feesFromDb : (float) ($o['service_fee'] ?? 0)), 0) }}</dd>
                                </div>
                                <div class="ota-fare-dl__row ota-fare-dl__row--total">
                                    <dt>Total payable</dt>
                                    <dd>Rs {{ number_format($totalFromDb > 0 ? $totalFromDb : (float) ($o['total'] ?? 0), 0) }}</dd>
                                </div>
                            @else
                                <div class="ota-fare-dl__row"><dt><strong>Passenger mix</strong></dt><dd>{{ $passengerCounts['adults'] }} adults, {{ $passengerCounts['children'] }} child, {{ $passengerCounts['infants'] }} infant</dd></div>
                                <div class="ota-fare-dl__row ota-fare-dl__row--total"><dt>Airline confirmed total</dt><dd>Rs {{ number_format($totalFromDb > 0 ? $totalFromDb : (float) ($o['total'] ?? 0), 0) }}</dd></div>
                            @endif
                        </dl>
                        <div class="mt-2">
                            @if (! $passengerPricingAvailable || empty($supplierPassengerPricing))
                                <p class="small text-muted mb-0">
                                    Fare is priced by the airline for all travellers together. Individual passenger fare breakdown is not available for this offer.
                                </p>
                            @endif
                        </div>
                        <p class="small text-muted mb-0 mt-2">Final fare shown in PKR. If fare is not guaranteed, we recheck again at confirmation.</p>
                    </div>

                    <form method="post" action="{{ route('booking.review') }}" class="ota-checkout-form">
                        @csrf
                        <div class="ota-checkout-card">
                            <h2 class="ota-checkout-section-title">Confirmation method</h2>
                            <p class="ota-checkout-section-hint">Choose how your agency will finalize this booking request.</p>

                            <label class="ota-method-card">
                                <input type="radio" name="booking_method" value="pay_later" class="ota-method-card__input" checked>
                                <span class="ota-method-card__body">
                                    <span class="ota-method-card__title">Pay later / booking request</span>
                                    <span class="ota-method-card__hint">Hold the itinerary; confirm and pay later.</span>
                                </span>
                            </label>
                            <label class="ota-method-card">
                                <input type="radio" name="booking_method" value="bank_transfer" class="ota-method-card__input">
                                <span class="ota-method-card__body">
                                    <span class="ota-method-card__title">Bank transfer (offline)</span>
                                    <span class="ota-method-card__hint">Pay via bank transfer using instructions from your consultant.</span>
                                </span>
                            </label>
                            <label class="ota-method-card">
                                <input type="radio" name="booking_method" value="office" class="ota-method-card__input">
                                <span class="ota-method-card__body">
                                    <span class="ota-method-card__title">Office confirmation</span>
                                    <span class="ota-method-card__hint">Complete ticketing with your travel consultant in-office or by phone.</span>
                                </span>
                            </label>
                        </div>

                        <div class="ota-review-total-hero" aria-live="polite">
                            <span class="ota-review-total-hero__label">Amount due (PKR)</span>
                            <span class="ota-review-total-hero__value">Rs {{ number_format($totalFromDb > 0 ? $totalFromDb : (float) ($o['total'] ?? 0), 0) }}</span>
                        </div>
                        @if ($requiresPriceConfirm)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="confirm_updated_fare" id="confirm-updated-fare" value="1">
                                <label class="form-check-label small" for="confirm-updated-fare">
                                    Fare changed from Rs {{ number_format($priceChangeOld, 0) }} to Rs {{ number_format($priceChangeNew, 0) }}. I have reviewed and accept the updated fare.
                                </label>
                            </div>
                            @error('confirm_updated_fare')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @endif
                        <button type="submit" class="ota-btn-primary-lg btn btn-lg btn-block">Continue to Step 5 (Confirm/payment)</button>
                        <p class="ota-checkout-disclaimer">Step 5 confirms booking/payment path. Held orders can be completed or moved to manual payment flow.</p>
                    </form>
                </aside>
            </div>
        </div>
    </div>
@endsection
