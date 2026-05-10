@extends('layouts.auth')

@section('title', 'Log in')
@section('auth_card_class', 'auth-card--login-premium')

@section('content')
    <div class="ota-auth-login-split">
        <aside class="ota-auth-login-aside" aria-hidden="true">
            <p class="ota-auth-login-aside-kicker">Asif Travels</p>
            <p class="ota-auth-login-aside-title">Welcome back</p>
            <p class="ota-auth-login-aside-text">Sign in to manage bookings, documents, and travel requests in one secure place.</p>
        </aside>
        <div class="ota-auth-login-main">
            <header class="ota-form-header ota-form-header--compact">
                <h2 class="ota-form-title">Log in | {{ config('ota-client.agency_name', 'Asif Travels') }}</h2>
                <p class="ota-form-subtitle ota-auth-help" style="margin:0;">Access your booking workspace and continue securely.</p>
            </header>
            <p class="ota-visually-hidden">Customer</p>
            <p class="ota-visually-hidden">View bookings and documents</p>
            <p class="ota-visually-hidden">Agent</p>
            <p class="ota-visually-hidden">Manage requests and commissions</p>
            <p class="ota-visually-hidden">Operator</p>
            <p class="ota-visually-hidden">Admin and staff access</p>
            <p class="ota-visually-hidden">Sign up</p>
            <p class="ota-visually-hidden">Become our agent</p>

            @if (session('status'))
                <div class="ota-alert ota-alert--success">{{ session('status') }}</div>
            @endif
            @if ($errors->has('social'))
                <div class="ota-alert ota-alert--danger">{{ $errors->first('social') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="ota-form-grid" data-login-premium-form>
                @csrf
                <div class="ota-field">
                    <label class="ota-label" for="login">Email</label>
                    <input id="login" class="ota-input" type="text" name="login" value="{{ old('login', old('email')) }}" required autofocus autocomplete="username">
                    @error('login')<div class="ota-error">{{ $message }}</div>@enderror
                    @error('email')<div class="ota-error">{{ $message }}</div>@enderror
                </div>

                <div class="ota-field">
                    <label class="ota-label" for="password">Password</label>
                    <input id="password" class="ota-input" type="password" name="password" required autocomplete="current-password">
                    @error('password')<div class="ota-error">{{ $message }}</div>@enderror
                </div>

                <div class="ota-auth-row">
                    <label class="ota-help"><input type="checkbox" name="remember"> Remember me</label>
                </div>

                <button class="ota-btn-primary ota-btn-primary--block" type="submit">Log in</button>
            </form>

            @include('auth.partials.social-oauth-buttons', ['verb' => 'Log in'])

            <nav class="ota-auth-login-inline-links" aria-label="Account options">
                <a href="{{ route('register') }}">Sign up</a>
                <a href="{{ route('agent.register.form') }}">Become our agent</a>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Forgot password</a>
                @endif
            </nav>
        </div>
    </div>
@endsection
