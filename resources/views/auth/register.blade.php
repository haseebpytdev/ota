@extends('layouts.auth')

@section('title', 'Register')
@section('auth_card_class', 'auth-card--register-premium')

@section('content')
    <header class="ota-form-header ota-form-header--compact">
        <h2 class="ota-form-title">Sign up | {{ config('ota-client.agency_name', 'Asif Travels') }}</h2>
        <p class="ota-form-subtitle ota-auth-help" style="margin:0;">Create a customer account to search, book, and manage travel requests.</p>
    </header>
    <p class="ota-visually-hidden">Book flights, track your booking requests, submit payment proof, and access travel documents from one place.</p>
    @if ($errors->has('social'))
        <div class="ota-alert ota-alert--danger">{{ $errors->first('social') }}</div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="ota-form-grid" data-register-premium-form data-ajax-validation-endpoint="{{ route('register.customer.validate-field') }}">
        @csrf
        <div class="ota-alert ota-alert--danger" data-global-error hidden></div>
        <div class="ota-form-grid-2">
            <div class="ota-field">
                <label class="ota-label" for="first_name">First name</label>
                <input id="first_name" class="ota-input" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name">
                <div class="ota-error field-error" data-error-for="first_name">@error('first_name'){{ $message }}@enderror</div>
            </div>

            <div class="ota-field">
                <label class="ota-label" for="last_name">Last name</label>
                <input id="last_name" class="ota-input" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
                <div class="ota-error field-error" data-error-for="last_name">@error('last_name'){{ $message }}@enderror</div>
            </div>

            <div class="ota-field">
                <label class="ota-label" for="email">Email</label>
                <input id="email" class="ota-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                <div class="ota-error field-error" data-error-for="email">@error('email'){{ $message }}@enderror</div>
            </div>

            <div class="ota-field">
                <label class="ota-label" for="mobile">Mobile number</label>
                <input id="mobile" class="ota-input" type="text" name="mobile" value="{{ old('mobile') }}" required autocomplete="tel">
                <div class="ota-error field-error" data-error-for="mobile">@error('mobile'){{ $message }}@enderror</div>
            </div>

            <div class="ota-field">
                <label class="ota-label" for="password">Password</label>
                <input id="password" class="ota-input" type="password" name="password" required autocomplete="new-password">
                <div class="ota-error field-error" data-error-for="password">@error('password'){{ $message }}@enderror</div>
            </div>

            <div class="ota-field">
                <label class="ota-label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" class="ota-input" type="password" name="password_confirmation" required autocomplete="new-password">
                <div class="ota-error field-error" data-error-for="password_confirmation">@error('password_confirmation'){{ $message }}@enderror</div>
            </div>
        </div>

        <div class="ota-field">
            <label class="ota-label" for="security_answer">Security check: {{ $securityQuestion ?? session('register_security_question', 'What is 1 + 1?') }}</label>
            <input id="security_answer" class="ota-input" type="text" name="security_answer" value="{{ old('security_answer', old('security_check')) }}" required>
            <div class="ota-error field-error" data-error-for="security_answer">@error('security_answer'){{ $message }}@elseif($errors->has('security_check')){{ $errors->first('security_check') }}@enderror</div>
        </div>

        <div class="ota-field">
            <label class="ota-help"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I agree to Asif Travels terms and privacy policy.</label>
            <div class="ota-error field-error" data-error-for="terms">@error('terms'){{ $message }}@enderror</div>
        </div>

        <div class="ota-register-submit-wrap">
            <button class="ota-btn-primary" type="submit">Register</button>
        </div>
    </form>

    @include('auth.partials.social-oauth-buttons', ['verb' => 'Sign up'])

    <div class="ota-auth-links-group" style="margin-top:var(--space-6);padding-top:var(--space-4);border-top:1px solid var(--color-border);">
        <p class="ota-auth-links-heading">Already registered?</p>
        <div class="ota-auth-links">
            <a href="{{ route('login') }}">Log in</a>
            <a href="{{ route('home') }}">Back to home</a>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/public-form-validation.js') }}?v=1"></script>
    <script>
        (function () {
            var form = document.querySelector('[data-register-premium-form]');
            if (!form || !window.PublicFormValidation) return;
            var csrf = document.querySelector('meta[name="csrf-token"]');
            var endpoint = form.getAttribute('data-ajax-validation-endpoint') || '';
            var validator = new window.PublicFormValidation(form, {
                endpoint: endpoint,
                csrf: csrf ? csrf.getAttribute('content') : '',
                requiredFields: ['first_name', 'last_name', 'email', 'mobile', 'password', 'password_confirmation', 'security_answer'],
            });
            validator.install();
        })();
    </script>
@endpush
