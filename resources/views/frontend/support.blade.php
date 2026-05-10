@extends('layouts.frontend')

@php
    $brand = config('ota-brand', []);
    $client = config('ota-client', []);
    $brandName = $client['agency_name'] ?? ($brand['product_name'] ?? config('app.name'));
    $supportEmail = $client['support_email'] ?? ($brand['support_email'] ?? '');
    $supportPhone = $client['support_phone'] ?? ($brand['support_phone'] ?? '');
    $supportWhatsapp = $client['support_whatsapp'] ?? ($brand['support_whatsapp'] ?? '');
@endphp

@section('title', 'Support & contact - '.$brandName)

@section('content')
    <section class="ota-section ota-form-page ota-support-page" aria-labelledby="ota-support-heading">
        <div class="ota-container">
            <header class="ota-section-head ota-support-hero">
                <p class="ota-section-kicker">Support &amp; contact</p>
                <h1 id="ota-support-heading" class="ota-section-title ota-support-page-title">Help and Support Center</h1>
                <p class="ota-section-desc ota-support-hero-desc">Find answers quickly, send a support request or a general message, and get guided assistance for flights, payments, travel documents, and partnerships.</p>
            </header>

            <div class="row ota-support-main-row align-items-start g-4">
                <div class="col-12 col-lg-6 ota-support-col-info">
                    <div class="ota-support-panel">
                        <h2 class="ota-support-panel-title">Help categories</h2>
                        <ul class="ota-support-list">
                            <li>Booking status</li>
                            <li>Payment proof</li>
                            <li>E-ticket support</li>
                            <li>Cancellations</li>
                        </ul>
                    </div>
                    <div class="ota-support-panel">
                        <h2 class="ota-support-panel-title">FAQs</h2>
                        <div class="ota-support-faq">
                            <p class="ota-support-faq-item"><strong>How fast do you respond?</strong><br>Most requests are answered within 2-6 business hours.</p>
                            <p class="ota-support-faq-item"><strong>Can I get help on WhatsApp?</strong><br>Yes, use WhatsApp for urgent travel updates.</p>
                            <p class="ota-support-faq-item"><strong>Can I edit a booking after submission?</strong><br>Yes, contact support with booking reference and requested changes.</p>
                        </div>
                    </div>
                    <div class="ota-support-panel">
                        <h2 class="ota-support-panel-title">Office &amp; channels</h2>
                        <p class="ota-support-panel-lead">{{ $brandName }} helps customers and partners with flight booking, support operations, and travel servicing.</p>
                        <ul class="ota-support-list ota-support-list--channels">
                            @if($supportPhone !== '')
                                <li><span class="ota-support-channel-label">Phone:</span> {{ $supportPhone }}</li>
                            @endif
                            @if($supportWhatsapp !== '')
                                <li><span class="ota-support-channel-label">WhatsApp:</span> <a href="https://wa.me/{{ $supportWhatsapp }}" target="_blank" rel="noopener">Chat on WhatsApp</a></li>
                            @endif
                            @if($supportEmail !== '')
                                <li><span class="ota-support-channel-label">Email:</span> <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></li>
                            @endif
                            @php $officeCity = $client['office_city'] ?? ''; @endphp
                            @if($officeCity !== '')
                                <li><span class="ota-support-channel-label">City:</span> {{ $officeCity }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6 ota-support-col-forms">
                    <div class="ota-form-card ota-support-form-card" data-support-premium-form>
                        <h3 class="ota-support-form-card-title">Support request</h3>
                        <p class="ota-support-form-card-desc">Share your issue and our team will respond shortly.</p>
                        <form class="ota-form-grid" action="#" method="post">
                            <div class="ota-field">
                                <label class="ota-label" for="support-ref">Booking reference (optional)</label>
                                <input id="support-ref" class="ota-input" type="text" placeholder="e.g. ATR-123456" autocomplete="off">
                            </div>
                            <div class="ota-field">
                                <label class="ota-label" for="support-email">Email</label>
                                <input id="support-email" class="ota-input" type="email" placeholder="you@example.com" autocomplete="email">
                            </div>
                            <div class="ota-field">
                                <label class="ota-label" for="support-category">Issue type</label>
                                <select id="support-category" class="ota-select">
                                    <option>Booking status</option>
                                    <option>Payment proof</option>
                                    <option>Travel document</option>
                                    <option>Cancellation / refund</option>
                                </select>
                            </div>
                            <div class="ota-field">
                                <label class="ota-label" for="support-message">Message</label>
                                <textarea id="support-message" class="ota-textarea" rows="4" placeholder="Describe your support request"></textarea>
                            </div>
                            <button type="button" class="ota-btn-primary ota-btn-primary--block ota-support-submit">Submit support request</button>
                        </form>
                        <div class="ota-support-form-links">
                            @if($supportWhatsapp !== '')
                                <p class="ota-support-form-links-line">Fast channel: <a href="https://wa.me/{{ $supportWhatsapp }}" target="_blank" rel="noopener">WhatsApp support</a></p>
                            @endif
                            <p class="ota-support-form-links-line">Need status fast? <a href="{{ route('booking.lookup') }}">Manage booking</a>.</p>
                        </div>
                    </div>
                    <div class="ota-form-card ota-support-form-card" data-contact-premium-form>
                        <h3 class="ota-support-form-card-title">Send a message</h3>
                        <p class="ota-support-form-card-desc">General inquiries, partnerships, or corporate travel. We typically reply within one business day.</p>
                        <form class="ota-form-grid" action="#" method="post">
                            <div class="ota-field">
                                <label class="ota-label" for="contact-name">Your name</label>
                                <input id="contact-name" class="ota-input" type="text" placeholder="Full name" autocomplete="name">
                            </div>
                            <div class="ota-field">
                                <label class="ota-label" for="contact-email">Email</label>
                                <input id="contact-email" class="ota-input" type="email" placeholder="you@example.com" autocomplete="email">
                            </div>
                            <div class="ota-field">
                                <label class="ota-label" for="contact-message">Message</label>
                                <textarea id="contact-message" class="ota-textarea" rows="4" placeholder="How can we help?"></textarea>
                            </div>
                            <button type="button" class="ota-btn-primary ota-btn-primary--block ota-support-submit">Send message</button>
                        </form>
                        <p class="ota-visually-hidden">Email Us</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
