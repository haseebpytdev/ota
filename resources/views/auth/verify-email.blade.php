@extends('layouts.auth')

@section('title', 'Verify email')

@section('content')
    <h2>Verify your email</h2>
    <p class="ota-auth-help">Please verify your email address to secure your account and continue.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="ota-auth-alert">A new verification link has been sent to your email address.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" style="margin-bottom:12px;">
        @csrf
        <button class="ota-auth-btn" type="submit">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="ota-auth-link" style="background:none;border:none;padding:0;" type="submit">Log out</button>
    </form>
@endsection
