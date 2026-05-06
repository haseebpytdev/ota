@extends('layouts.auth')

@section('title', 'Create customer account')

@section('content')
    <h2>Create your Asif Travels account</h2>
    <p class="ota-auth-help">Book flights, track requests, submit payments, and download your travel documents.</p>
    <div class="ota-auth-alert" style="background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a;">
        <strong>Customer benefits:</strong> fast booking flow, document access, and real-time request visibility.
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="ota-auth-group">
            <label class="ota-auth-label" for="first_name">First name</label>
            <input id="first_name" class="ota-auth-input" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name">
            @error('first_name')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="last_name">Last name</label>
            <input id="last_name" class="ota-auth-input" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
            @error('last_name')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="email">Email</label>
            <input id="email" class="ota-auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            @error('email')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="mobile">Mobile number</label>
            <input id="mobile" class="ota-auth-input" type="text" name="mobile" value="{{ old('mobile') }}" required autocomplete="tel">
            @error('mobile')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="password">Password</label>
            <input id="password" class="ota-auth-input" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" class="ota-auth-input" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="security_check">Security check: What is 2 + 3?</label>
            <input id="security_check" class="ota-auth-input" type="text" name="security_check" value="{{ old('security_check') }}" required>
            @error('security_check')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="ota-auth-group">
            <label><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I agree to Asif Travels terms and privacy policy.</label>
            @error('terms')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>

        <button class="ota-auth-btn" type="submit">Create customer account</button>
    </form>

    <p style="margin-top:16px;color:#64748b;">Already registered? <a class="ota-auth-link" href="{{ route('login') }}">Log in</a></p>
    <p style="margin-top:8px;color:#64748b;">Need help? <a class="ota-auth-link" href="{{ route('home') }}#support">Contact support</a></p>
@endsection
