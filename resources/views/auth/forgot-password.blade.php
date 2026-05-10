@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
    <h2>Reset your password</h2>
    <p class="ota-auth-help">Enter your email to receive a secure reset link.</p>

    @if (session('status'))
        <div class="ota-auth-alert">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="ota-auth-group">
            <label class="ota-auth-label" for="email">Email</label>
            <input id="email" class="ota-auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>
        <button class="ota-auth-btn" type="submit">Send reset link</button>
    </form>
@endsection
