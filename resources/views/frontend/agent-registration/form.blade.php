@extends('layouts.auth')

@section('title', 'Agent application')
@section('auth_card_class', 'auth-card--agent-premium')

@section('content')
    <header class="ota-form-header ota-form-header--compact">
        <h2 class="ota-form-title">Agent signup application</h2>
        <p class="ota-form-subtitle ota-auth-help" style="margin:0;">Submit your agency application and our partnerships team will contact you after review.</p>
    </header>
    <div class="ota-alert ota-alert--info">
        Agent applications are reviewed by Asif Travels. After approval, you will receive an activation email.
    </div>

    <div class="ota-agent-wizard" aria-hidden="true">
        <span class="ota-agent-wizard__step is-active">1. Agency</span>
        <span class="ota-agent-wizard__step">2. Contact</span>
        <span class="ota-agent-wizard__step">3. Verification</span>
        <span class="ota-agent-wizard__step">4. Services</span>
        <span class="ota-agent-wizard__step">5. Agreement</span>
    </div>

    <form method="POST" action="{{ route('agent.register.store') }}" class="ota-form-grid" data-agent-registration-premium data-agent-registration-form>
        @csrf

        <section class="ota-form-section-card" data-agent-section="business">
            <h3 class="ota-form-section-title"><span class="ota-form-section-num">1</span> Agency details</h3>
            <div class="ota-form-grid-2">
                <div class="ota-field">
                    <label class="ota-label" for="company_name">Agency name</label>
                    <input id="company_name" class="ota-input" name="company_name" value="{{ old('company_name') }}" required>
                    @error('company_name')<div class="ota-error">{{ $message }}</div>@enderror
                </div>
                <div class="ota-field">
                    <label class="ota-label" for="city">City</label>
                    <input id="city" class="ota-input" name="city" value="{{ old('city') }}" required>
                    @error('city')<div class="ota-error">{{ $message }}</div>@enderror
                </div>
                <div class="ota-field ota-form-grid-span-2">
                    <label class="ota-label" for="business_type">Business type</label>
                    <input id="business_type" class="ota-input" name="business_type" value="{{ old('business_type', 'Travel Agency') }}" required>
                    @error('business_type')<div class="ota-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="ota-form-section-card" data-agent-section="personal">
            <h3 class="ota-form-section-title"><span class="ota-form-section-num">2</span> Contact person</h3>
            <div class="ota-form-grid-2">
                <div class="ota-field">
                    <label class="ota-label" for="first_name">Contact person</label>
                    <input id="first_name" class="ota-input" name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="ota-error">{{ $message }}</div>@enderror
                </div>
                <div class="ota-field">
                    <label class="ota-label" for="mobile">Phone</label>
                    <input id="mobile" class="ota-input" name="mobile" value="{{ old('mobile') }}" required>
                    @error('mobile')<div class="ota-error">{{ $message }}</div>@enderror
                </div>
                <div class="ota-field ota-form-grid-span-2">
                    <label class="ota-label" for="email">Email</label>
                    <input id="email" class="ota-input" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="ota-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="ota-form-section-card" data-agent-section="verification">
            <h3 class="ota-form-section-title"><span class="ota-form-section-num">3</span> Verification</h3>
            <p class="ota-help">Our team may request business verification documents after you submit this application.</p>
        </section>

        <section class="ota-form-section-card" data-agent-section="expected-volume">
            <h3 class="ota-form-section-title"><span class="ota-form-section-num">4</span> Services &amp; volume</h3>
            <p class="ota-help">Share expected booking volume, routes, or partnership goals in the message below.</p>
            <div class="ota-field">
                <label class="ota-label" for="notes">Message</label>
                <textarea id="notes" class="ota-textarea" name="notes" rows="4">{{ old('notes') }}</textarea>
            </div>
        </section>

        <section class="ota-form-section-card" data-agent-section="agreement">
            <h3 class="ota-form-section-title"><span class="ota-form-section-num">5</span> Agreement</h3>
            <div class="ota-field">
                <label class="ota-help"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I confirm submitted information is accurate.</label>
                @error('terms')<div class="ota-error">{{ $message }}</div>@enderror
            </div>
        </section>

        <input type="hidden" name="last_name" value="{{ old('last_name', 'Applicant') }}">
        <input type="hidden" name="country" value="{{ old('country', 'Pakistan') }}">
        <input type="hidden" name="office_address" value="{{ old('office_address', 'To be shared during onboarding') }}">

        <div class="ota-form-actions">
            <button class="ota-btn-primary ota-btn-primary--block" type="submit" data-agent-registration-submit>Submit Agent Application</button>
        </div>
    </form>

    <script>
        (function () {
            var form = document.querySelector('[data-agent-registration-form]');
            if (!form) return;

            form.addEventListener('submit', function () {
                var submit = form.querySelector('[data-agent-registration-submit]');
                if (!submit || submit.disabled) return;

                submit.disabled = true;
                submit.setAttribute('aria-disabled', 'true');
                submit.textContent = 'Submitting application...';
            });
        })();
    </script>
@endsection
