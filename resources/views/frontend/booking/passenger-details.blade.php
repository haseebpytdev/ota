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
    <div class="ota-book-wrap ota-checkout-page">
        <div class="ota-container ota-container-narrow">
            <div class="ota-checkout-page-head ota-checkout-page-head--flush">
                <p class="ota-checkout-stepper">Step 1 of 3 <span>Passenger details → Review booking → Request received</span></p>
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

                    <form method="post" action="{{ route('booking.passengers') }}" class="ota-checkout-form" id="ota-checkout-passengers-form">
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

                        <div class="ota-checkout-card ota-checkout-card--section">
                            <p class="ota-checkout-section-kicker">Section 1</p>
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
                                        @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-fn">First name</label>
                                        <input class="form-control ota-input @error('first_name') is-invalid @enderror" id="checkout-fn" type="text" name="first_name" value="{{ old('first_name', $draft['first_name'] ?? '') }}" required autocomplete="given-name">
                                        @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-ln">Last name</label>
                                        <input class="form-control ota-input @error('last_name') is-invalid @enderror" id="checkout-ln" type="text" name="last_name" value="{{ old('last_name', $draft['last_name'] ?? '') }}" required autocomplete="family-name">
                                        @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-dob">Date of birth</label>
                                        <input class="form-control ota-input @error('dob') is-invalid @enderror" id="checkout-dob" type="date" name="dob" value="{{ old('dob', $draft['dob'] ?? '') }}" required>
                                        @error('dob')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-nat">Nationality (ISO code)</label>
                                        <input class="form-control ota-input @error('nationality') is-invalid @enderror" id="checkout-nat" type="text" name="nationality" placeholder="PK" maxlength="2" value="{{ old('nationality', $draft['nationality'] ?? '') }}" required autocomplete="country">
                                        @error('nationality')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-gender">Gender</label>
                                        <select class="form-control ota-input @error('gender') is-invalid @enderror" id="checkout-gender" name="gender" required>
                                            <option value="" disabled @selected(old('gender', $draft['gender'] ?? '') === '')>Select</option>
                                            @foreach (['M' => 'Male', 'F' => 'Female', 'X' => 'Unspecified'] as $gv => $gl)
                                                <option value="{{ $gv }}" @selected(old('gender', $draft['gender'] ?? '') === $gv)>{{ $gl }}</option>
                                            @endforeach
                                        </select>
                                        @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ota-checkout-card ota-checkout-card--section">
                            <p class="ota-checkout-section-kicker">Section 2</p>
                            <h2 class="ota-checkout-section-title">Travel document details</h2>
                            <p class="ota-checkout-section-hint">
                                @if (!empty($isInternationalRoute))
                                    Passport details are required for international bookings.
                                @else
                                    Passport is optional for domestic itineraries. Add CNIC or national ID if applicable.
                                @endif
                            </p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-doc-type">Document type</label>
                                        <select class="form-control ota-input" id="checkout-doc-type" name="document_type">
                                            <option value="passport" @selected(old('document_type', $draft['document_type'] ?? 'passport') === 'passport')>Passport</option>
                                            <option value="national_id" @selected(old('document_type', $draft['document_type'] ?? '') === 'national_id')>National ID</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-nid">CNIC / National ID</label>
                                        <input class="form-control ota-input @error('national_id_number') is-invalid @enderror" id="checkout-nid" type="text" name="national_id_number" value="{{ old('national_id_number', $draft['national_id_number'] ?? '') }}" autocomplete="off">
                                        @error('national_id_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-passport">Passport number</label>
                                        <input class="form-control ota-input @error('passport_number') is-invalid @enderror" id="checkout-passport" type="text" name="passport_number" value="{{ old('passport_number', $draft['passport_number'] ?? '') }}" autocomplete="off">
                                        @error('passport_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-issue-cc">Issuing country (ISO)</label>
                                        <input class="form-control ota-input @error('passport_issuing_country') is-invalid @enderror" id="checkout-issue-cc" type="text" name="passport_issuing_country" maxlength="2" placeholder="PK" value="{{ old('passport_issuing_country', $draft['passport_issuing_country'] ?? '') }}">
                                        @error('passport_issuing_country')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-pass-exp">Passport expiry</label>
                                        <input class="form-control ota-input @error('passport_expiry_date') is-invalid @enderror" id="checkout-pass-exp" type="date" name="passport_expiry_date" value="{{ old('passport_expiry_date', $draft['passport_expiry_date'] ?? '') }}">
                                        @error('passport_expiry_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-pass-issue">Passport issue date</label>
                                        <input class="form-control ota-input @error('passport_issue_date') is-invalid @enderror" id="checkout-pass-issue" type="date" name="passport_issue_date" value="{{ old('passport_issue_date', $draft['passport_issue_date'] ?? '') }}">
                                        @error('passport_issue_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-cor">Country of residence</label>
                                        <input class="form-control ota-input" id="checkout-cor" type="text" name="country_of_residence" value="{{ old('country_of_residence', $draft['country_of_residence'] ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-pob">Place of birth</label>
                                        <input class="form-control ota-input" id="checkout-pob" type="text" name="place_of_birth" value="{{ old('place_of_birth', $draft['place_of_birth'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ota-checkout-card ota-checkout-card--section">
                            <p class="ota-checkout-section-kicker">Section 3</p>
                            <h2 class="ota-checkout-section-title">Contact details</h2>
                            <p class="ota-checkout-section-hint">Itinerary and updates will be sent here.</p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-email">Email</label>
                                        <input class="form-control ota-input @error('email') is-invalid @enderror" id="checkout-email" type="email" name="email" value="{{ old('email', $draft['email'] ?? '') }}" required autocomplete="email">
                                        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="ota-form-group">
                                        <label class="ota-label" for="checkout-phone">Mobile</label>
                                        <input class="form-control ota-input @error('phone') is-invalid @enderror" id="checkout-phone" type="tel" name="phone" placeholder="+92 300 1234567" value="{{ old('phone', $draft['phone'] ?? '') }}" required autocomplete="tel">
                                        @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

                            @if (empty($hideInlineAccount))
                                <input type="hidden" name="create_account" value="0">
                                <div class="ota-form-group mt-3">
                                    <label class="ota-checkbox-label d-flex align-items-start gap-2 mb-0">
                                        <input type="checkbox" name="create_account" value="1" id="checkout-create-account" @checked(old('create_account'))>
                                        <span>Create an account with this booking (uses email above and traveller name)</span>
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
                                <script>
                                    (function () {
                                        var cb = document.getElementById('checkout-create-account');
                                        var box = document.getElementById('checkout-inline-account-fields');
                                        if (!cb || !box) return;
                                        function sync() { box.classList.toggle('d-none', !cb.checked); }
                                        cb.addEventListener('change', sync);
                                        sync();
                                    })();
                                </script>
                            @endif
                        </div>

                        <p class="ota-checkout-review-hint text-muted small">You will review fare and passenger details before submitting.</p>
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
@endsection
