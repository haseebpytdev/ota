@extends('layouts.frontend')

@section('title', 'Agent signup - Asif Travels')

@section('content')
    <section class="ota-section ota-routes-section">
        <div class="ota-container">
            <header class="ota-section-head">
                <p class="ota-section-kicker">Agent partnership</p>
                <h1 class="ota-section-title">Join the Asif Travels Agent Network</h1>
                <p class="ota-section-desc">Apply for partner access to submit booking requests, track commissions, and manage customer travel.</p>
            </header>
            <div class="ota-hero-actions" style="justify-content:center;margin-top:10px;">
                <a href="{{ route('agent.register.form') }}" class="ota-btn ota-btn-primary">Apply as Agent</a>
                <a href="{{ url('/agent') }}" class="ota-btn" style="border:1px solid #cbd5e1;background:#fff;color:#0f172a;">Already registered? Login</a>
            </div>
            <div class="row" style="margin-top:18px;">
                <div class="col-md-7">
                    <div class="ota-checkout-card">
                        <h3 class="ota-checkout-section-title">How it works</h3>
                        <ol style="padding-left:18px;color:#475569;">
                            <li>Submit application</li>
                            <li>Admin review</li>
                            <li>Receive activation link</li>
                            <li>Start booking</li>
                        </ol>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="ota-checkout-card">
                        <h3 class="ota-checkout-section-title">Agent benefits</h3>
                        <ul style="padding-left:18px;color:#475569;">
                            <li>Agent dashboard</li>
                            <li>Booking request tools</li>
                            <li>Commission tracking</li>
                            <li>Dedicated support</li>
                        </ul>
                    </div>
                    <div class="ota-checkout-card">
                        <h3 class="ota-checkout-section-title">FAQ</h3>
                        <p class="ota-checkout-section-hint"><strong>Who can apply?</strong><br>Licensed agencies, consultants, and travel businesses handling customer bookings.</p>
                        <p class="ota-checkout-section-hint"><strong>Is approval instant?</strong><br>No, every application is reviewed before access is granted.</p>
                        <p class="ota-checkout-section-hint"><strong>Are there registration fees?</strong><br>No registration fee is charged for application submission.</p>
                        <p class="ota-checkout-section-hint"><strong>When do I get dashboard access?</strong><br>Only after approval and account activation email setup.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
