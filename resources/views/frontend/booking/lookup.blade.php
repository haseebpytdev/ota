@extends('layouts.frontend')

@section('title', 'Lookup your booking')

@section('content')
    <section class="ota-section ota-form-page ota-lookup-page" aria-labelledby="ota-lookup-heading">
        <div class="ota-container">
            <header class="ota-section-head ota-lookup-hero">
                <p class="ota-section-kicker">Manage booking</p>
                <h1 id="ota-lookup-heading" class="ota-section-title ota-lookup-page-title">Lookup your booking</h1>
                <p class="ota-section-desc ota-lookup-hero-desc">
                    Access your booking request, documents, payment status, and travel updates securely. We verify your details before showing sensitive information.
                </p>
            </header>

            <div class="row align-items-start g-4 ota-lookup-main-row">
                <div class="col-12 col-lg-5 d-flex flex-column gap-3">
                    <div class="ota-about-panel">
                        <h2 class="ota-about-panel-title">How lookup works</h2>
                        <p class="ota-lookup-info-lead">Enter the booking reference from your confirmation together with the email address or phone number used when you booked. If everything matches our records, we send you a secure link to view your trip.</p>
                        <p class="ota-lookup-info-text">Your reference usually looks like a short code or alphanumeric ID from your confirmation email or receipt.</p>
                    </div>
                    <div class="ota-about-panel">
                        <h2 class="ota-about-panel-title">What you&apos;ll need</h2>
                        <ul class="ota-support-list">
                            <li><strong>Booking reference</strong> — from your confirmation</li>
                            <li><strong>Email or phone</strong> — at least one must match what we have on file</li>
                        </ul>
                        <p class="ota-lookup-tip">Tip: use the same email or phone you entered during checkout or passenger details.</p>
                    </div>
                    <div class="ota-about-panel">
                        <h2 class="ota-about-panel-title">Privacy</h2>
                        <p class="ota-lookup-info-text ota-lookup-info-text--flush">For privacy, access links are only sent when your details match the booking. We never display full itineraries or documents without this check.</p>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="ota-form-card ota-lookup-form-card" data-lookup-premium-form>
                        <h2 class="ota-support-form-card-title">Enter your details</h2>
                        <p class="ota-support-form-card-desc">Enter your booking reference and either the email or phone number used for the booking.</p>

                        <form method="post" action="{{ route('lookup-booking.submit') }}" class="ota-form-grid">
                            @csrf

                            @if ($errors->has('lookup'))
                                <div class="ota-alert ota-alert--danger" role="alert">{{ $errors->first('lookup') }}</div>
                            @endif

                            <div class="ota-field">
                                <label class="ota-label" for="booking_reference">Booking reference</label>
                                <input
                                    id="booking_reference"
                                    class="ota-input"
                                    name="booking_reference"
                                    value="{{ old('booking_reference') }}"
                                    autocomplete="off"
                                    required
                                >
                                @error('booking_reference')
                                    <div class="ota-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="ota-field">
                                <label class="ota-label" for="lookup_email">Email address</label>
                                <input
                                    id="lookup_email"
                                    class="ota-input"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                >
                                @error('email')
                                    <div class="ota-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="ota-field">
                                <label class="ota-label" for="lookup_phone">Phone number</label>
                                <input
                                    id="lookup_phone"
                                    class="ota-input"
                                    name="phone"
                                    type="tel"
                                    value="{{ old('phone') }}"
                                    autocomplete="tel"
                                >
                                @error('phone')
                                    <div class="ota-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <p class="ota-security-note">For privacy, access links are only sent when your details match the booking.</p>

                            <button class="ota-btn-primary ota-btn-primary--block ota-lookup-submit" type="submit">Lookup booking</button>
                        </form>
                    </div>

                    <nav class="ota-auth-links ota-lookup-footer-links" aria-label="Booking help">
                        <a href="{{ route('support') }}">Need help? Contact support</a>
                        <a href="{{ route('flights.search') }}">Back to flights</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>
@endsection
