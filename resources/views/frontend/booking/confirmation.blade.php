@extends('layouts.frontend')

@section('title', 'Booking request received')

@section('content')
    @php
        $d = $draft;
        $o = $offer;
        $cr = $criteria;
        $ref = $d['booking_reference'] ?? ($booking->booking_reference ?? null);
        $bm = $d['booking_method'] ?? 'pay_later';
        $statusLabel = isset($booking)
            ? str_replace('_', ' ', $booking->status->value)
            : 'Pending confirmation';
        $fare = $booking->fareBreakdown ?? null;
        $bmLabel = match ($bm) {
            'bank_transfer' => 'Bank transfer (offline)',
            'office' => 'Office confirmation',
            default => 'Pay later / booking request',
        };
    @endphp
    <section class="ota-confirmation-wrap ota-confirmation-page">
        <div class="ota-container ota-container-narrow">
            <div class="ota-confirm-hero-card">
                <div class="ota-confirm-success-ring" aria-hidden="true">
                    <span class="ota-confirm-success-icon"><i class="fa fa-check"></i></span>
                </div>
                <h1 class="ota-confirm-title">Booking request received</h1>
                <p class="ota-confirm-sub">Your booking request has been saved. No ticket has been issued.</p>

                <div class="ota-confirm-meta">
                    <div class="ota-confirm-ref">
                        <span class="ota-confirm-ref__label">Booking reference</span>
                        <span class="ota-confirm-ref__value">{{ $ref ?? '—' }}</span>
                        @unless ($ref)
                            <span class="ota-confirm-ref__hint">Complete the review step to generate your booking reference.</span>
                        @endunless
                    </div>
                    <span class="ota-confirm-status text-capitalize">{{ $statusLabel }}</span>
                </div>
            </div>

            <div class="ota-confirm-banner" role="status">
                <strong>Booking submitted.</strong>
                No payment was taken, no e-ticket or PNR exists, and no email has been sent.
            </div>

            <div class="ota-confirm-grid">
                <article class="ota-confirm-card">
                    <h2 class="ota-confirm-card__title"><i class="fa fa-plane"></i> Trip summary</h2>
                    @if ($o)
                        <div class="ota-confirm-trip">
                            @if(!empty($airlineLogo))
                                <p style="margin:0 0 0.5rem;"><img src="{{ $airlineLogo }}" alt="{{ $o['airline_name'] ?? 'Airline' }} logo" style="height:24px;width:auto;"></p>
                            @endif
                            <p class="ota-confirm-trip__line">
                                <strong>{{ $o['airline_name'] ?? '' }}</strong>
                                <span class="ota-confirm-trip__fn">{{ $o['carrier_code'] ?? '' }}{{ $o['flight_number'] ?? '' }}</span>
                            </p>
                            <p class="ota-confirm-trip__route">{{ $cr['origin'] }} → {{ $cr['destination'] }}
                                · {{ \Illuminate\Support\Carbon::parse($o['depart_at'] ?? '')->format('D, M j, Y') }}</p>
                            <p class="ota-confirm-trip__times">
                                {{ \Illuminate\Support\Carbon::parse($o['depart_at'] ?? '')->format('H:i') }}
                                – {{ \Illuminate\Support\Carbon::parse($o['arrive_at'] ?? '')->format('H:i') }}
                                <span class="text-muted">({{ $o['duration_h'] ?? 0 }}h {{ str_pad((string) ($o['duration_m'] ?? 0), 2, '0', STR_PAD_LEFT) }}m)</span>
                            </p>
                            <p class="ota-confirm-trip__meta text-muted small mb-0">
                                <i class="fa fa-suitcase"></i> {{ $o['baggage'] ?? '' }} · {{ $o['fare_family'] ?? '' }}
                            </p>
                        </div>
                    @else
                        <p class="ota-confirm-card__text">Flight <strong>{{ $d['flight_id'] ?? '—' }}</strong></p>
                        <p class="ota-confirm-card__muted">{{ $cr['origin'] }} → {{ $cr['destination'] }}
                            @if (!empty($cr['depart_date']))
                                · {{ \Illuminate\Support\Carbon::parse($cr['depart_date'])->format('M j, Y') }}
                            @endif
                        </p>
                        <p class="ota-confirm-card__muted small mb-0">Segment details appear when a matching provider offer is available in the current session.</p>
                    @endif
                </article>

                <article class="ota-confirm-card">
                    <h2 class="ota-confirm-card__title"><i class="fa fa-user"></i> Passenger &amp; contact</h2>
                    <p class="ota-confirm-pax-name"><strong>{{ trim(($d['title'] ?? '').' '.($d['first_name'] ?? '').' '.($d['last_name'] ?? '')) ?: '—' }}</strong></p>
                    <ul class="ota-confirm-list">
                        <li><span class="ota-confirm-list__k">Email</span> {{ $d['email'] ?? '—' }}</li>
                        <li><span class="ota-confirm-list__k">Mobile</span> {{ $d['phone'] ?? '—' }}</li>
                        @if (!empty($d['country']))
                            <li><span class="ota-confirm-list__k">Country</span> {{ $d['country'] }}</li>
                        @endif
                    </ul>
                </article>

                <article class="ota-confirm-card">
                    <h2 class="ota-confirm-card__title"><i class="fa fa-credit-card"></i> Booking method</h2>
                    <p class="ota-confirm-method mb-0">{{ $bmLabel }}</p>
                    @if ($fare)
                        <p class="ota-confirm-card__muted mt-2 mb-0">Final total snapshot: <strong>Rs {{ number_format((float) $fare->total, 0) }}</strong></p>
                    @endif
                </article>

                <article class="ota-confirm-card">
                    <h2 class="ota-confirm-card__title"><i class="fa fa-list-ul"></i> Next steps</h2>
                    <ol class="ota-confirm-steps">
                        <li>The agency reviews availability and fare for your request.</li>
                        <li>You are contacted by phone, WhatsApp, or email with options and payment details.</li>
                        <li>A ticket is issued only after payment and confirmation in a live system.</li>
                        <li>Your request is stored for the agency; start a new search anytime for another trip.</li>
                    </ol>
                </article>
            </div>

            <nav class="ota-confirm-actions" aria-label="What would you like to do next?">
                <a href="{{ route('lookup-booking.form') }}" class="btn btn-default btn-lg ota-confirm-btn-secondary">View booking</a>
                <a href="{{ route('flights.search') }}" class="btn btn-primary btn-lg ota-confirm-btn-primary">Book another flight</a>
                <a href="{{ route('home') }}" class="btn btn-default btn-lg ota-confirm-btn-secondary">Back to home</a>
                <a href="{{ route('support') }}" class="btn btn-default btn-lg ota-confirm-btn-admin">Contact support</a>
            </nav>
        </div>
    </section>
@endsection
