@extends('layouts.frontend')

@section('title', 'Checkout')

@section('content')
    @php
        $o = $offer;
        $cr = $criteria;
        $wa = $client['support_whatsapp'] ?? '';
        $waUrl = $wa !== '' ? 'https://wa.me/'.$wa : '#';
    @endphp
    <div class="ota-book-wrap ota-checkout-page">
        <div class="ota-container ota-container-narrow">
            <div class="ota-checkout-page-head ota-checkout-page-head--flush">
                <h1 class="ota-checkout-page-title">Checkout</h1>
                <p class="ota-checkout-page-lead">Complete passenger and contact details. You can continue as a guest.</p>
            </div>
            @if (!empty($validationAlert))
                <div class="alert alert-warning">
                    {{ $validationAlert }}
                    @if (is_array($validationResult ?? null) && ($validationResult['price_changed'] ?? false))
                        <div class="small" style="margin-top:4px;">
                            Old: Rs {{ number_format((float) ($validationResult['old_total'] ?? 0), 0) }}
                            · New: Rs {{ number_format((float) ($validationResult['new_total'] ?? 0), 0) }}
                        </div>
                    @endif
                </div>
            @endif

            <div class="ota-checkout-grid">
                <div class="ota-checkout-main">
                    <div class="ota-checkout-card">
                        <div class="ota-checkout-card__head">
                            <span class="ota-checkout-card__icon" aria-hidden="true"><i class="fa fa-user"></i></span>
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h2 class="ota-checkout-card__title mb-0">Guest checkout</h2>
                                    <span class="ota-flow-badge">Guest flow</span>
                                </div>
                                <p class="ota-checkout-card__text mb-0">Complete this booking without creating an account. You can create a customer account anytime to manage future trips and documents.</p>
                            </div>
                        </div>
                    </div>

                    <div class="ota-checkout-accounts">
                        <div class="ota-checkout-account-card">
                            <h3 class="ota-checkout-account-card__title">Already have an account?</h3>
                            <p class="ota-checkout-account-card__text">Sign in to use saved travellers and corporate profiles.</p>
                            <button type="button" class="ota-btn-account" disabled>Sign in</button>
                        </div>
                        <div class="ota-checkout-account-card">
                            <h3 class="ota-checkout-account-card__title">New customer?</h3>
                            <p class="ota-checkout-account-card__text">Create an account to track bookings and offers.</p>
                            <button type="button" class="ota-btn-account" disabled>Create account</button>
                        </div>
                    </div>

                    <form method="post" action="{{ route('booking.passengers') }}" class="ota-checkout-form">
                        @csrf
                        <input type="hidden" name="flight_id" value="{{ old('flight_id', $flightId) }}">
                        <input type="hidden" name="offer_id" value="{{ old('offer_id', $draft['offer_id'] ?? $flightId) }}">
                        <input type="hidden" name="search_id" value="{{ old('search_id', $draft['search_id'] ?? '') }}">
                        <input type="hidden" name="from" value="{{ old('from', $draft['search_from'] ?? ($criteria['origin'] ?? '')) }}">
                        <input type="hidden" name="to" value="{{ old('to', $draft['search_to'] ?? ($criteria['destination'] ?? '')) }}">
                        <input type="hidden" name="depart" value="{{ old('depart', $draft['search_depart'] ?? ($criteria['depart_date'] ?? '')) }}">
                        <input type="hidden" name="trip_type" value="{{ old('trip_type', $draft['trip_type'] ?? ($criteria['trip_type'] ?? 'one_way')) }}">
                        <input type="hidden" name="return_date" value="{{ old('return_date', $draft['return_date'] ?? ($criteria['return_date'] ?? '')) }}">
                        <input type="hidden" name="cabin" value="{{ old('cabin', $draft['cabin'] ?? ($criteria['cabin'] ?? 'economy')) }}">
                        <input type="hidden" name="adults" value="{{ old('adults', $draft['adults'] ?? ($criteria['adults'] ?? 1)) }}">
                        <input type="hidden" name="children" value="{{ old('children', $draft['children'] ?? ($criteria['children'] ?? 0)) }}">
                        <input type="hidden" name="infants" value="{{ old('infants', $draft['infants'] ?? ($criteria['infants'] ?? 0)) }}">

                        <div class="ota-checkout-card">
                            <h2 class="ota-checkout-section-title">Passenger details</h2>
                            <p class="ota-checkout-section-hint">Primary traveller for this itinerary.</p>
                            <div class="row">
                                <div class="col-sm-2">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-title">Title</label>
                                        <select class="form-control ota-input" id="checkout-title" name="title">
                                            @foreach (['Mr', 'Ms', 'Mrs', 'Mx'] as $t)
                                                <option value="{{ $t }}" @selected(old('title', $draft['title'] ?? 'Mr') === $t)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-fn">First name</label>
                                        <input class="form-control ota-input" id="checkout-fn" type="text" name="first_name" value="{{ old('first_name', $draft['first_name'] ?? '') }}" required autocomplete="given-name">
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-ln">Last name</label>
                                        <input class="form-control ota-input" id="checkout-ln" type="text" name="last_name" value="{{ old('last_name', $draft['last_name'] ?? '') }}" required autocomplete="family-name">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-dob">Date of birth</label>
                                        <input class="form-control ota-input" id="checkout-dob" type="date" name="dob" value="{{ old('dob', $draft['dob'] ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-nat">Nationality</label>
                                        <input class="form-control ota-input" id="checkout-nat" type="text" name="nationality" placeholder="Pakistan" value="{{ old('nationality', $draft['nationality'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ota-checkout-card">
                            <h2 class="ota-checkout-section-title">Contact details</h2>
                            <p class="ota-checkout-section-hint">Itinerary and updates will be sent here.</p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-email">Email</label>
                                        <input class="form-control ota-input" id="checkout-email" type="email" name="email" value="{{ old('email', $draft['email'] ?? '') }}" required autocomplete="email">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-phone">Mobile</label>
                                        <input class="form-control ota-input" id="checkout-phone" type="tel" name="phone" placeholder="+92 300 1234567" value="{{ old('phone', $draft['phone'] ?? '') }}" required autocomplete="tel">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-country">Country / region</label>
                                        <input class="form-control ota-input" id="checkout-country" type="text" name="country" placeholder="Pakistan" value="{{ old('country', $draft['country'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="ota-btn-primary-lg btn btn-lg btn-block">Continue to review</button>
                    </form>
                </div>

                <aside class="ota-checkout-aside" aria-label="Trip summary">
                    @if ($o)
                        <div class="ota-checkout-card ota-checkout-card--accent ota-checkout-sticky-summary">
                            <h2 class="ota-checkout-aside-title">Selected flight</h2>
                            <p class="ota-checkout-summary-route"><strong>{{ $cr['origin'] }}</strong> → <strong>{{ $cr['destination'] }}</strong></p>
                            @if(!empty($airlineLogo))
                                <p style="margin:0 0 0.5rem;"><img src="{{ $airlineLogo }}" alt="{{ $o['airline_name'] ?? 'Airline' }} logo" style="height:24px;width:auto;"></p>
                            @endif
                            <p class="ota-checkout-summary-airline">{{ $o['airline_name'] ?? '' }} · {{ $o['carrier_code'] ?? '' }}{{ $o['flight_number'] ?? '' }}</p>
                            <div class="ota-checkout-flight-compact__times mt-2 mb-2">
                                <span>{{ \Illuminate\Support\Carbon::parse($o['depart_at'] ?? '')->format('H:i') }}</span>
                                <span class="ota-checkout-flight-compact__dur">{{ $o['duration_h'] ?? 0 }}h {{ str_pad((string) ($o['duration_m'] ?? 0), 2, '0', STR_PAD_LEFT) }}m</span>
                                <span>{{ \Illuminate\Support\Carbon::parse($o['arrive_at'] ?? '')->format('H:i') }}</span>
                            </div>
                            <p class="ota-checkout-flight-compact__route mb-2">{{ \Illuminate\Support\Carbon::parse($o['depart_at'] ?? '')->format('D, M j, Y') }}</p>
                            <p class="ota-checkout-flight-compact__meta mb-3">
                                <i class="fa fa-suitcase"></i> {{ $o['baggage'] ?? '' }}
                                @if (!empty($o['refundable']))
                                    <span class="label label-success ota-refund-pill">Refundable</span>
                                @else
                                    <span class="label label-default ota-refund-pill">Non-refundable</span>
                                @endif
                            </p>
                            <div class="ota-checkout-fare-est">
                                <span class="ota-checkout-fare-est__label">Fare estimate</span>
                                <span class="ota-checkout-fare-est__total">Rs {{ number_format((float) ($o['total'] ?? 0), 0) }}</span>
                                <span class="ota-checkout-fare-est__sub">Estimated total in PKR.</span>
                            </div>
                            <p class="ota-checkout-pay-note"><i class="fa fa-info-circle"></i> No payment is captured at this stage.</p>
                        </div>
                    @else
                        <div class="ota-checkout-card ota-checkout-card--muted">
                            <h2 class="ota-checkout-aside-title">No flight selected</h2>
                            <p class="ota-checkout-card__text">Choose a flight from results to see route, times, and fare here.</p>
                            <a href="{{ route('flights.search') }}" class="btn btn-default btn-block">Browse flights</a>
                        </div>
                    @endif

                    <div class="ota-checkout-card ota-checkout-wa">
                        <h2 class="ota-checkout-aside-title">Questions?</h2>
                        <p class="ota-checkout-card__text">Reach {{ $client['agency_name'] ?? 'our team' }} on WhatsApp for help with this itinerary.</p>
                        @if ($waUrl !== '#')
                            <a href="{{ $waUrl }}" class="ota-btn-wa btn btn-block" target="_blank" rel="noopener noreferrer">
                                <i class="fa fa-whatsapp"></i> Chat on WhatsApp
                            </a>
                            <p class="ota-checkout-wa-phone">{{ $client['support_phone'] ?? '' }}</p>
                        @else
                            <p class="text-muted small mb-0">Support number not configured.</p>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
