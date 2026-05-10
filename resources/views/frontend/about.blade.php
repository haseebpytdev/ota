@extends('layouts.frontend')

@php
    $brand = config('ota-brand', []);
    $client = config('ota-client', []);
    $brandName = $client['agency_name'] ?? ($brand['product_name'] ?? config('app.name'));
    $footerAbout = $client['footer_text'] ?? ($brand['company_note'] ?? '');
    $officeCity = $client['office_city'] ?? '';
    $defaultStory = 'We help travellers and partners book flights with clear pricing, responsive support, and careful handling of documents and itinerary changes. Whether you are planning a single trip or managing bookings for others, we aim to make every step straightforward—from search and fare clarity to post-booking assistance.';
@endphp

@section('title', 'About us - '.$brandName)

@section('content')
    <section class="ota-section ota-form-page ota-about-page" data-about-premium aria-labelledby="ota-about-heading">
        <div class="ota-container">
            <header class="ota-section-head ota-about-hero">
                <p class="ota-section-kicker">About us</p>
                <h1 id="ota-about-heading" class="ota-section-title ota-about-page-title">About {{ $brandName }}</h1>
                <p class="ota-section-desc ota-about-hero-desc">
                    {{ $footerAbout !== '' ? $footerAbout : $defaultStory }}
                    Need booking help, ticket changes, or direct contact with our team? Visit our <a href="{{ route('support') }}">support &amp; contact</a> center anytime.
                </p>
            </header>

            <div class="ota-about-panel ota-about-panel--lead">
                <h2 class="ota-about-panel-title">Our story</h2>
                <p>{{ $brandName }} was built around a simple idea: flight booking should feel guided, not confusing. We combine search tools that respect your time with human support when schedules shift, documents need attention, or airlines update policies. {{ $officeCity !== '' ? 'From our base in '.$officeCity.', we serve travellers across routes our customers fly most often—always with an emphasis on clarity and follow-through.' : 'We focus on the routes our customers fly most often—always with an emphasis on clarity and follow-through.' }}</p>
                <p>Behind every booking is a coordinated workflow: fare checks, passenger details, payments and proofs, ticket issuance, and updates until you travel. We invest in that workflow so you spend less time chasing status and more time planning your trip.</p>
            </div>

            <div class="row align-items-start g-4 ota-about-columns">
                <div class="col-12 col-lg-6 d-flex flex-column gap-3">
                    <div class="ota-about-panel">
                        <h2 class="ota-about-panel-title">Who we are</h2>
                        <p>{{ $brandName }} is a travel booking partner for leisure flyers, business travellers, and agencies that want dependable fulfilment. Our team understands airline rules, fare conditions, and the operational realities of ticketing—so we can explain trade-offs in plain language before you commit.</p>
                        <p>We work as an extension of your travel planning: responsive on messaging channels when timing matters, structured when documentation and payments need to be right the first time.</p>
                    </div>
                    <div class="ota-about-panel">
                        <h2 class="ota-about-panel-title">What we do</h2>
                        <p>Our core services cover the full booking lifecycle—search and itinerary selection, fare review, passenger capture, payment coordination, ticket issuance, and post-ticket servicing aligned with airline policies.</p>
                        <ul class="ota-support-list">
                            <li>Flight search across preferred routes and cabins</li>
                            <li>Booking creation with transparent fare and fee context</li>
                            <li>E-tickets, receipts, and travel document coordination</li>
                            <li>Changes and cancellations handled according to carrier rules</li>
                            <li>Ongoing support until departure when plans evolve</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6 d-flex flex-column gap-3">
                    <div class="ota-about-panel">
                        <h2 class="ota-about-panel-title">How we work</h2>
                        <ul class="ota-support-list">
                            <li><strong>Clarity first</strong> — we spell out what is included, what may change, and what timelines to expect.</li>
                            <li><strong>Responsive channels</strong> — fast paths for urgent updates alongside structured email follow-up.</li>
                            <li><strong>Disciplined processes</strong> — passenger names, contacts, and payments captured consistently to reduce airline rejects.</li>
                            <li><strong>Partner-ready</strong> — structured onboarding for agents and corporates who need repeatable fulfilment.</li>
                        </ul>
                    </div>
                    <div class="ota-about-panel">
                        <h2 class="ota-about-panel-title">Travel with confidence</h2>
                        <p>International travel can mean visa considerations, name corrections, connection risk, and baggage rules that vary by airline. We help you understand what applies to your ticket and coordinate updates when airlines allow them—so you are never guessing alone at the last minute.</p>
                        <p>When disruptions happen, we prioritize communication: what the airline has confirmed, what options exist, and what we recommend based on your itinerary and fare rules.</p>
                    </div>
                </div>
            </div>

            <div class="ota-about-panel ota-about-panel--emphasis">
                <h2 class="ota-about-panel-title">Why travellers choose {{ $brandName }}</h2>
                <p>People return to us because the experience feels steady—fewer surprises on fares, clearer expectations on timelines, and staff who understand that travel plans change. We combine technology with accessible support so you are not stuck between a chatbot and an unanswered inbox.</p>
                <p>For businesses and agencies, we offer a scalable relationship: predictable processes for bookings, invoicing context where needed, and escalation paths when passenger situations get complex.</p>
                <p>Whether you book once a year or every month, we apply the same standards—accurate details, timely ticketing, and accountability through to departure.</p>
            </div>

            <div class="row align-items-start g-4">
                <div class="col-12 col-md-6">
                    <div class="ota-about-panel h-100">
                        <h2 class="ota-about-panel-title">Partners &amp; agents</h2>
                        <p>If you represent travellers—corporate mobility, retail agency, or independent advisor—we provide onboarding, policy-aligned workflows, and access aligned with how your organization sells.</p>
                        <p>Applications are reviewed before activation so every partner understands pricing posture, documentation expectations, and service boundaries. Learn more on our <a href="{{ route('agent.register') }}">Agent Network</a> page.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="ota-about-panel h-100">
                        <h2 class="ota-about-panel-title">Talk to our team</h2>
                        <p>For partnerships, corporate travel programs, or urgent booking assistance, reach us through the <a href="{{ route('support') }}">support &amp; contact</a> page—office channels, WhatsApp where available, and forms for structured requests.</p>
                        <p class="ota-about-footnote">Flight availability and fares are subject to airline confirmation at the time of booking.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
