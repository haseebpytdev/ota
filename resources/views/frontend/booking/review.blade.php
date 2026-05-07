@extends('layouts.frontend')

@section('title', 'Review booking')

@section('content')
    @php
        use App\Support\Travel\TravelDocumentFormatter;
        $d = $draft;
        $o = $offer;
        $cr = $criteria;
        $meta = $booking->meta ?? [];
        $paxSummary = $leadPassenger ?? null;
        $fare = $booking->fareBreakdown;
        $totalFromDb = (float) ($fare?->total ?? 0);
        $baseFromDb = (float) ($fare?->base_fare ?? 0);
        $taxesFromDb = (float) ($fare?->taxes ?? 0);
        $markupFromDb = (float) ($fare?->markup ?? 0);
        $feesFromDb = (float) ($fare?->fees ?? 0);
    @endphp
    <div class="ota-rev-wrap ota-checkout-page">
        <div class="ota-container ota-container-narrow">
            <div class="ota-checkout-page-head ota-checkout-page-head--flush">
                <p class="ota-checkout-page-kicker">Step 3 of 3</p>
                <h1 class="ota-checkout-page-title">Review your booking</h1>
                <p class="ota-checkout-page-lead">Check flight, traveller, and fare before you confirm your booking request.</p>
            </div>
            @if (!empty($meta['validation_warnings']) && is_array($meta['validation_warnings']))
                <div class="alert alert-warning">
                    @foreach ($meta['validation_warnings'] as $warning)
                        <div>{{ $warning }}</div>
                    @endforeach
                </div>
            @endif

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
                        <div class="ota-review-pax">
                            <p class="ota-review-pax__name"><strong>{{ $d['title'] ?? '' }} {{ $d['first_name'] ?? '' }} {{ $d['last_name'] ?? '' }}</strong></p>
                            @if ($paxSummary)
                                <ul class="ota-review-pax__list ota-review-pax__list--docs mb-2">
                                    @if ($paxSummary->date_of_birth)
                                        <li><i class="fa fa-calendar"></i> DOB {{ $paxSummary->date_of_birth->format('j M Y') }}</li>
                                    @endif
                                    @if ($paxSummary->nationality)
                                        <li><i class="fa fa-flag-o"></i> Nationality {{ strtoupper($paxSummary->nationality) }}</li>
                                    @endif
                                    @if ($paxSummary->gender)
                                        <li><i class="fa fa-user"></i> Gender {{ $paxSummary->gender }}</li>
                                    @endif
                                </ul>
                                @if ($paxSummary->passport_number || $paxSummary->national_id_number)
                                    <p class="small text-muted mb-2">
                                        @if ($paxSummary->document_type === 'national_id' && $paxSummary->national_id_number)
                                            National ID: {{ TravelDocumentFormatter::maskPassport($paxSummary->national_id_number) }}
                                        @elseif ($paxSummary->passport_number)
                                            Passport: {{ TravelDocumentFormatter::maskPassport($paxSummary->passport_number) }}
                                            @if ($paxSummary->passport_issuing_country)
                                                · {{ strtoupper($paxSummary->passport_issuing_country) }}
                                            @endif
                                            @if ($paxSummary->passport_expiry_date)
                                                · expires {{ $paxSummary->passport_expiry_date->format('j M Y') }}
                                            @endif
                                        @endif
                                    </p>
                                @endif
                            @endif
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
                            <div class="ota-fare-dl__row">
                                <dt>Base fare</dt>
                                <dd>Rs {{ number_format($baseFromDb > 0 ? $baseFromDb : (float) ($o['base_fare'] ?? 0), 0) }}</dd>
                            </div>
                            <div class="ota-fare-dl__row">
                                <dt>Taxes &amp; surcharges</dt>
                                <dd>Rs {{ number_format($taxesFromDb > 0 ? $taxesFromDb : (float) ($o['taxes'] ?? 0), 0) }}</dd>
                            </div>
                            <div class="ota-fare-dl__row">
                                <dt>Markup</dt>
                                <dd>Rs {{ number_format($markupFromDb > 0 ? $markupFromDb : (float) ($o['markup'] ?? 0), 0) }}</dd>
                            </div>
                            <div class="ota-fare-dl__row">
                                <dt>Service fee</dt>
                                <dd>Rs {{ number_format($feesFromDb > 0 ? $feesFromDb : (float) ($o['service_fee'] ?? 0), 0) }}</dd>
                            </div>
                            <div class="ota-fare-dl__row ota-fare-dl__row--total">
                                <dt>Total</dt>
                                <dd>Rs {{ number_format($totalFromDb > 0 ? $totalFromDb : (float) ($o['total'] ?? 0), 0) }}</dd>
                            </div>
                        </dl>
                        <p class="small text-muted mb-0 mt-2">Final fare shown in PKR. Fare availability is subject to airline confirmation.</p>
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
                        <button type="submit" class="ota-btn-primary-lg btn btn-lg btn-block">Request booking</button>
                        <p class="ota-checkout-disclaimer">Booking request is submitted after review. No payment is captured here. Our team will contact you for confirmation or payment if required. No ticket is issued yet.</p>
                    </form>
                </aside>
            </div>
        </div>
    </div>
@endsection
