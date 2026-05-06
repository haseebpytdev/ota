@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h2>Sign in to Asif Travels</h2>
    <p class="ota-auth-help">Sign in to access your account and continue your booking workflow.</p>

    <div class="ota-auth-role-grid" aria-label="Account guidance">
        <div class="ota-auth-role-chip"><strong>Customer</strong><span>View bookings and documents</span></div>
        <div class="ota-auth-role-chip"><strong>Agent</strong><span>Manage requests and commissions</span></div>
        <div class="ota-auth-role-chip"><strong>Operator</strong><span>Admin and staff access</span></div>
    </div>

    @if (session('status'))
        <div class="ota-auth-alert">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="ota-auth-group">
            <label class="ota-auth-label" for="login">Username or Email</label>
            <input id="login" class="ota-auth-input" type="text" name="login" value="{{ old('login', old('email')) }}" required autofocus autocomplete="username">
            @error('login')<div class="ota-auth-error">{{ $message }}</div>@enderror
            @error('email')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="password">Password</label>
            <input id="password" class="ota-auth-input" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-row">
            <label><input type="checkbox" name="remember"> Remember me</label>
            @if (Route::has('password.request'))
                <a class="ota-auth-link" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <button class="ota-auth-btn" type="submit">Log in</button>
    </form>

    <div style="margin-top:16px;color:#64748b;display:flex;flex-wrap:wrap;gap:10px 18px;">
        <a class="ota-auth-link" href="{{ route('register') }}">Customer Signup</a>
        <a class="ota-auth-link" href="{{ route('agent.register') }}">Agent Registration</a>
        <a class="ota-auth-link" href="{{ route('password.request') }}">Forgot password</a>
        <a class="ota-auth-link" href="{{ route('home') }}">Back to home</a>
    </div>
@endsection
