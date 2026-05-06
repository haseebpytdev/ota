@extends('layouts.frontend')

@section('title', 'Contact Asif Travels')

@section('content')
    <section class="ota-section">
        <div class="ota-container ota-container-narrow">
            <header class="ota-section-head ota-section-head--compact">
                <p class="ota-section-kicker">Contact</p>
                <h1 class="ota-section-title">Contact Asif Travels</h1>
                <p class="ota-section-desc">Reach our team for flight booking assistance, travel support, agent partnerships, and corporate travel requests.</p>
            </header>

            <div class="row">
                <div class="col-sm-6">
                    <div class="ota-checkout-card">
                        <h3 class="ota-checkout-section-title">General contact</h3>
                        <p class="ota-checkout-section-hint">For business and customer inquiries.</p>
                        <ul style="padding-left:18px;color:#475569;">
                            <li>Email support for bookings and inquiries</li>
                            <li>WhatsApp support for quick travel assistance</li>
                            <li>Agent partnership and corporate travel guidance</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="ota-checkout-card">
                        <h3 class="ota-checkout-section-title">Quick actions</h3>
                        <p class="ota-checkout-section-hint">Choose your preferred contact channel.</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="mailto:support@haseebasif.com" class="ota-btn ota-btn-primary">Email Us</a>
                            <a href="https://wa.me/923007654321" target="_blank" rel="noopener" class="ota-btn" style="border:1px solid #cbd5e1;background:#fff;color:#0f172a;">WhatsApp</a>
                            <a href="{{ route('agent.register') }}" class="ota-btn" style="border:1px solid #cbd5e1;background:#fff;color:#0f172a;">Agent Registration</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
