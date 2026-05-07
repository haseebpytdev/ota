@extends('layouts.frontend')

@section('title', 'Customer Support - Asif Travels')

@section('content')
    <section class="ota-section">
        <div class="ota-container ota-container-narrow">
            <header class="ota-section-head">
                <p class="ota-section-kicker">Support</p>
                <h1 class="ota-section-title">Customer Support</h1>
                <p class="ota-section-desc">Need help with booking requests, payment proof, travel documents, or itinerary updates? Our team is here to assist you.</p>
            </header>

            <div class="row" style="margin-top:8px;">
                <div class="col-md-6">
                    <div class="ota-checkout-card">
                        <h3 class="ota-checkout-section-title">Customer support</h3>
                        <p class="ota-checkout-section-hint">Support for existing bookings, travel changes, and payment/document guidance.</p>
                        <ul style="padding-left:18px;color:#475569;">
                            <li>Existing booking help</li>
                            <li>Payment proof and document support</li>
                            <li>Travel update and itinerary assistance</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ota-checkout-card">
                        <h3 class="ota-checkout-section-title">Quick actions</h3>
                        <p class="ota-checkout-section-hint">Choose your next step.</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="{{ route('lookup-booking.form') }}" class="ota-btn" style="border:1px solid #cbd5e1;background:#fff;color:#0f172a;">Lookup Booking</a>
                            <a href="{{ route('flights.search') }}" class="ota-btn ota-btn-primary">Book Flights</a>
                            <a href="{{ route('contact') }}" class="ota-btn" style="border:1px solid #cbd5e1;background:#fff;color:#0f172a;">Contact form &amp; channels</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
