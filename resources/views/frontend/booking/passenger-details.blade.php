@extends('layouts.frontend')

@section('title', 'Checkout')

@section('content')
    @php
        $o = $offer;
        $cr = $criteria;
        $wa = $client['support_whatsapp'] ?? '';
        $waUrl = $wa !== '' ? 'https://wa.me/'.$wa : '#';
        $checkoutReturnParams = [
            'flight_id' => (string) ($flightId ?? ''),
            'offer_id' => (string) (($draft['offer_id'] ?? '') !== '' ? $draft['offer_id'] : ($flightId ?? '')),
            'search_id' => (string) ($draft['search_id'] ?? ''),
            'from' => (string) ($draft['search_from'] ?? ($criteria['origin'] ?? '')),
            'to' => (string) ($draft['search_to'] ?? ($criteria['destination'] ?? '')),
            'depart' => (string) ($draft['search_depart'] ?? ($criteria['depart_date'] ?? '')),
            'trip_type' => (string) ($draft['trip_type'] ?? ($criteria['trip_type'] ?? 'one_way')),
            'return_date' => (string) ($draft['return_date'] ?? ($criteria['return_date'] ?? '')),
            'cabin' => (string) ($draft['cabin'] ?? ($criteria['cabin'] ?? 'economy')),
            'adults' => (int) ($draft['adults'] ?? ($criteria['adults'] ?? 1)),
            'children' => (int) ($draft['children'] ?? ($criteria['children'] ?? 0)),
            'infants' => (int) ($draft['infants'] ?? ($criteria['infants'] ?? 0)),
        ];
        $checkoutReturnPath = '/booking/passengers?'.http_build_query($checkoutReturnParams);
        $departureCarbon = null;
        $arrivalCarbon = null;
        $arrivalDayNote = null;
        $protection = is_array($checkoutProtection ?? null) ? $checkoutProtection : [];
        $protectionMode = (string) ($protection['protection_mode'] ?? '');
        $lockExpiresAt = (string) ($protection['checkout_lock_expires_at'] ?? '');
        if ($o) {
            try {
                $departureCarbon = ! empty($o['depart_at']) ? \Illuminate\Support\Carbon::parse($o['depart_at']) : null;
                $arrivalCarbon = ! empty($o['arrive_at']) ? \Illuminate\Support\Carbon::parse($o['arrive_at']) : null;
                if ($departureCarbon && $arrivalCarbon) {
                    $calDays = $departureCarbon->copy()->startOfDay()->diffInDays($arrivalCarbon->copy()->startOfDay());
                    if ($calDays > 0) {
                        $arrivalDayNote = $calDays === 1 ? '+1 day' : '+'.$calDays.' days';
                    }
                }
            } catch (\Throwable) {
                $departureCarbon = $arrivalCarbon = null;
            }
        }
    @endphp
    <div class="ota-book-wrap ota-checkout-page" data-checkout-page>
        <div class="ota-container ota-container-wide">
            <div class="ota-checkout-page-head ota-checkout-page-head--flush">
                <p class="ota-checkout-stepper">Step 2 of 5 <span>Secure fare → Passenger basics → Documents → Review → Confirm/payment</span></p>
                <h1 class="ota-checkout-page-title">Checkout</h1>
                <p class="ota-checkout-page-lead">Step 2: enter passenger basics quickly, then we attempt supplier hold where supported.</p>
            </div>
            @if ($protectionMode !== '')
                <div class="alert alert-info" data-fare-hold-status role="status">
                    @if (!empty($protection['provider_unstable_test_mode']))
                        <strong>Test environment:</strong> airline fare could not be re-confirmed via live validation yet. Checkout continues using cached search pricing; automated supplier booking will require staff confirmation.
                    @elseif ($protectionMode === 'hold_price_guaranteed')
                        Fare hold eligible with price guarantee.
                    @elseif ($protectionMode === 'hold_no_price_guarantee')
                        This offer may support hold, but fare can still change before confirmation.
                    @else
                        This fare cannot be held before payment. We will recheck before confirmation.
                    @endif
                    @if ($lockExpiresAt !== '')
                        <div class="small mt-1">Checkout lock expires at: {{ \Illuminate\Support\Carbon::parse($lockExpiresAt)->format('Y-m-d H:i') }}</div>
                    @endif
                    <div class="small mt-1">
                        Total fare locked/checked for:
                        {{ $passengerCountSummary['adults'] }} adult{{ $passengerCountSummary['adults'] === 1 ? '' : 's' }}
                        · {{ $passengerCountSummary['children'] }} child{{ $passengerCountSummary['children'] === 1 ? '' : 'ren' }}
                        · {{ $passengerCountSummary['infants'] }} infant{{ $passengerCountSummary['infants'] === 1 ? '' : 's' }}
                    </div>
                </div>
            @endif
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
                    <div class="ota-checkout-card ota-checkout-card--choice">
                        <h2 class="ota-checkout-section-title mb-2">How would you like to continue?</h2>
                        <p class="ota-checkout-section-hint mb-3">Guest checkout is selected by default. Sign in to attach this booking to your customer profile, or create an account using the same email and traveller details below.</p>
                        @if (!empty($hideInlineAccount))
                            <div class="alert alert-info py-2 px-3 mb-0">
                                You are signed in as <strong>{{ auth()->user()->name }}</strong>. This booking will be linked to your account.
                            </div>
                        @else
                            <ul class="ota-checkout-choices list-unstyled mb-0">
                                <li class="ota-checkout-choices__item">
                                    <span class="ota-checkout-choices__badge"><i class="fa fa-check-circle text-success"></i></span>
                                    <div>
                                        <strong>Continue as guest</strong>
                                        <p class="small text-muted mb-0">Submit a booking request without an account. No payment is taken here.</p>
                                    </div>
                                </li>
                                <li class="ota-checkout-choices__item">
                                    <span class="ota-checkout-choices__badge"><i class="fa fa-sign-in"></i></span>
                                    <div>
                                        <strong>Sign in</strong>
                                        <p class="small text-muted mb-2">Return here after login with your flight selection preserved.</p>
                                        <a href="{{ route('login', ['redirect' => $checkoutReturnPath, 'checkout_return' => $checkoutReturnPath]) }}" class="ota-btn-account ota-btn-account--secondary">Sign in to continue</a>
                                        <div class="auth-social-stack auth-social-stack--stacked mt-2">
                                            <a class="public-btn public-btn-secondary auth-social-btn" href="{{ route('social.redirect', ['provider' => 'google', 'redirect' => $checkoutReturnPath, 'checkout_return' => $checkoutReturnPath]) }}">Quick signup with Google</a>
                                            <a class="public-btn public-btn-secondary auth-social-btn" href="{{ route('social.redirect', ['provider' => 'facebook', 'redirect' => $checkoutReturnPath, 'checkout_return' => $checkoutReturnPath]) }}">Quick signup with Facebook</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="ota-checkout-choices__item">
                                    <span class="ota-checkout-choices__badge"><i class="fa fa-user-plus"></i></span>
                                    <div>
                                        <strong>Create an account with this booking</strong>
                                        <p class="small text-muted mb-2">Use the contact email and traveller name below. Choose a password when you tick the box under Contact details.</p>
                                    </div>
                                </li>
                            </ul>
                        @endif
                    </div>

                    <form method="post" action="{{ route('booking.passengers') }}" class="ota-checkout-form" id="ota-checkout-passengers-form" data-checkout-passenger-form>
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
                        <input type="hidden" name="adults" value="{{ old('adults', $passengerCountSummary['adults']) }}">
                        <input type="hidden" name="children" value="{{ old('children', $passengerCountSummary['children']) }}">
                        <input type="hidden" name="infants" value="{{ old('infants', $passengerCountSummary['infants']) }}">
                        <input type="hidden" name="total_passengers" value="{{ old('total_passengers', $passengerCountSummary['total']) }}">

                        <div class="ota-checkout-card ota-checkout-card--section">
                            <p class="ota-checkout-section-kicker">Passenger progress</p>
                            <h2 class="ota-checkout-section-title">
                                {{ $passengerCountSummary['total'] }} passengers:
                                {{ $passengerCountSummary['adults'] }} adult{{ $passengerCountSummary['adults'] === 1 ? '' : 's' }},
                                {{ $passengerCountSummary['children'] }} child{{ $passengerCountSummary['children'] === 1 ? '' : 'ren' }},
                                {{ $passengerCountSummary['infants'] }} infant{{ $passengerCountSummary['infants'] === 1 ? '' : 's' }}
                            </h2>
                            <p class="ota-checkout-section-hint">Each traveller needs their own details. Infant lead passenger is not allowed.</p>
                            @error('passengers')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                            @error('lead_passenger_index')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                            @error('total_passengers')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                            @error('infants')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        </div>

                        @php
                            $oldPassengers = old('passengers', []);
                            $adultIndexes = collect($expectedPassengers)->filter(fn ($p) => $p['type'] === 'adult')->pluck('index')->values();
                            $leadPassengerIndex = (int) old('lead_passenger_index', $adultIndexes->first() ?? 0);
                            $titles = ['Mr', 'Ms', 'Mrs', 'Mx'];
                            $genders = ['M' => 'Male', 'F' => 'Female', 'X' => 'Unspecified'];
                        @endphp

                        @foreach ($expectedPassengers as $pos => $pax)
                            @php
                                $i = $pax['index'];
                                $pp = $oldPassengers[$i] ?? [];
                                $type = $pax['type'];
                                $isLead = $leadPassengerIndex === $i;
                                $isAdult = $type === 'adult';
                            @endphp
                            <details class="ota-checkout-card ota-checkout-card--section ota-passenger-card" {{ $pos === 0 || $errors->any() ? 'open' : '' }}>
                                <summary class="ota-passenger-card__summary">
                                    <span class="ota-passenger-card__title">
                                        Passenger {{ $pos + 1 }} of {{ $passengerCountSummary['total'] }} — {{ ucfirst($type) }}
                                        @if($isLead)<span class="label label-info ms-1">Lead passenger</span>@endif
                                    </span>
                                </summary>
                                <div class="ota-passenger-card__body">
                                    <input type="hidden" name="passengers[{{ $i }}][passenger_type]" value="{{ $type }}">
                                    <p class="small text-muted mb-2"><strong>Step 2 — Passenger basics</strong></p>
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Title</label>
                                                <select class="form-control ota-input" name="passengers[{{ $i }}][title]">
                                                    @foreach ($titles as $t)
                                                        <option value="{{ $t }}" @selected(($pp['title'] ?? 'Mr') === $t)>{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                                @error("passengers.$i.title")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-5">
                                            <div class="ota-form-group">
                                                <label class="ota-label">First name</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][first_name]" value="{{ $pp['first_name'] ?? '' }}" required>
                                                @error("passengers.$i.first_name")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Last name</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][last_name]" value="{{ $pp['last_name'] ?? '' }}" required>
                                                @error("passengers.$i.last_name")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <p class="small text-muted mt-2 mb-2"><strong>Step 3 — Passport / documents</strong></p>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Date of birth</label>
                                                <input class="form-control ota-input" type="date" name="passengers[{{ $i }}][date_of_birth]" value="{{ $pp['date_of_birth'] ?? '' }}" required>
                                                @error("passengers.$i.date_of_birth")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Gender</label>
                                                <select class="form-control ota-input" name="passengers[{{ $i }}][gender]" required>
                                                    <option value="" disabled @selected(($pp['gender'] ?? '') === '')>Select</option>
                                                    @foreach ($genders as $gv => $gl)
                                                        <option value="{{ $gv }}" @selected(($pp['gender'] ?? '') === $gv)>{{ $gl }}</option>
                                                    @endforeach
                                                </select>
                                                @error("passengers.$i.gender")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Nationality (ISO)</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][nationality]" maxlength="2" placeholder="PK" value="{{ $pp['nationality'] ?? '' }}" required>
                                                @error("passengers.$i.nationality")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Document type</label>
                                                <select class="form-control ota-input" name="passengers[{{ $i }}][document_type]">
                                                    <option value="passport" @selected(($pp['document_type'] ?? 'passport') === 'passport')>Passport</option>
                                                    <option value="national_id" @selected(($pp['document_type'] ?? '') === 'national_id')>National ID</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">National ID</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][national_id_number]" value="{{ $pp['national_id_number'] ?? '' }}">
                                                @error("passengers.$i.national_id_number")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Passport number</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][passport_number]" value="{{ $pp['passport_number'] ?? '' }}">
                                                @error("passengers.$i.passport_number")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Passport issuing country</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][passport_issuing_country]" maxlength="2" placeholder="PK" value="{{ $pp['passport_issuing_country'] ?? '' }}">
                                                @error("passengers.$i.passport_issuing_country")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Passport expiry date</label>
                                                <input class="form-control ota-input" type="date" name="passengers[{{ $i }}][passport_expiry_date]" value="{{ $pp['passport_expiry_date'] ?? '' }}">
                                                @error("passengers.$i.passport_expiry_date")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Passport issue date</label>
                                                <input class="form-control ota-input" type="date" name="passengers[{{ $i }}][passport_issue_date]" value="{{ $pp['passport_issue_date'] ?? '' }}">
                                                @error("passengers.$i.passport_issue_date")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Country of residence</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][country_of_residence]" value="{{ $pp['country_of_residence'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="ota-form-group">
                                                <label class="ota-label">Place of birth</label>
                                                <input class="form-control ota-input" type="text" name="passengers[{{ $i }}][place_of_birth]" value="{{ $pp['place_of_birth'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    @if($isAdult && $adultIndexes->count() > 1)
                                        <div class="ota-form-group mt-2">
                                            <label class="ota-checkbox-label d-flex align-items-center gap-2 mb-0">
                                                <input type="radio" name="lead_passenger_index" value="{{ $i }}" @checked($isLead)>
                                                <span>Set as lead passenger</span>
                                            </label>
                                        </div>
                                    @elseif($isLead)
                                        <input type="hidden" name="lead_passenger_index" value="{{ $i }}">
                                    @endif
                                </div>
                            </details>
                        @endforeach

                        <div class="ota-checkout-card ota-checkout-card--section">
                            <p class="ota-checkout-section-kicker">Booking contact</p>
                            <h2 class="ota-checkout-section-title">Contact and booking owner</h2>
                            <p class="ota-checkout-section-hint">This person receives all booking updates. Passenger accounts are not created.</p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-contact-name">Contact name</label>
                                        <input class="form-control ota-input @error('contact_name') is-invalid @enderror" id="checkout-contact-name" type="text" name="contact_name" value="{{ old('contact_name') }}">
                                        @error('contact_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-email">Email</label>
                                        <input class="form-control ota-input @error('email') is-invalid @enderror" id="checkout-email" type="email" name="email" value="{{ old('email', $draft['email'] ?? '') }}" required autocomplete="email">
                                        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-phone">Mobile</label>
                                        <input class="form-control ota-input @error('phone') is-invalid @enderror" id="checkout-phone" type="tel" name="phone" placeholder="+92 300 1234567" value="{{ old('phone', $draft['phone'] ?? '') }}" required autocomplete="tel">
                                        @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-country">Country / region</label>
                                        <input class="form-control ota-input" id="checkout-country" type="text" name="country" placeholder="Pakistan" value="{{ old('country', $draft['country'] ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            @if (empty($hideInlineAccount))
                                <input type="hidden" name="create_account" value="0">
                                <div class="ota-form-group mt-3">
                                    <label class="ota-checkbox-label d-flex align-items-start gap-2 mb-0">
                                        <input type="checkbox" name="create_account" value="1" id="checkout-create-account" @checked(old('create_account'))>
                                        <span>Create an account to manage this booking. Passenger accounts are not created.</span>
                                    </label>
                                </div>
                                <div id="checkout-inline-account-fields" class="mt-3 {{ old('create_account') ? '' : 'd-none' }}">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="ota-form-group">
                                                <label class="ota-label" for="checkout-password">Password</label>
                                                <input class="form-control ota-input @error('password') is-invalid @enderror" id="checkout-password" type="password" name="password" autocomplete="new-password">
                                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="ota-form-group">
                                                <label class="ota-label" for="checkout-password-confirm">Confirm password</label>
                                                <input class="form-control ota-input" id="checkout-password-confirm" type="password" name="password_confirmation" autocomplete="new-password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <p class="ota-checkout-review-hint text-muted small">Next: Step 4 review. We show fare held details (if any) or recheck warning before confirmation.</p>
                        <div class="ota-checkout-submit-bar">
                            <button type="submit" class="ota-btn-primary-lg btn btn-lg btn-block">Continue to review</button>
                        </div>
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
                                <span>{{ $departureCarbon ? $departureCarbon->format('H:i') : '' }}</span>
                                <span class="ota-checkout-flight-compact__dur">{{ $o['duration_h'] ?? 0 }}h {{ str_pad((string) ($o['duration_m'] ?? 0), 2, '0', STR_PAD_LEFT) }}m</span>
                                <span>{{ $arrivalCarbon ? $arrivalCarbon->format('H:i') : '' }}</span>
                            </div>
                            <p class="ota-checkout-flight-compact__route mb-2">
                                {{ $departureCarbon ? $departureCarbon->format('D, j M') : '' }}
                                @if ($departureCarbon && $arrivalCarbon)
                                    <span class="text-muted"> · </span>{{ $arrivalCarbon->format('D, j M') }}
                                    @if ($arrivalDayNote)
                                        <span class="label label-default ota-arr-offset">{{ $arrivalDayNote }}</span>
                                    @endif
                                @endif
                            </p>
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

                    <div class="ota-checkout-card ota-checkout-wa ota-checkout-wa--subtle">
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
    <script>
        (function () {
            var cb = document.getElementById('checkout-create-account');
            var box = document.getElementById('checkout-inline-account-fields');
            if (cb && box) {
                var sync = function () { box.classList.toggle('d-none', !cb.checked); };
                cb.addEventListener('change', sync);
                sync();
            }
        })();
    </script>
@endsection
