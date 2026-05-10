@extends('layouts.auth')

@section('title', 'Set new password')

@section('content')
    <h2>Set a new password</h2>
    <p class="ota-auth-help">Create a new password to continue to your account.</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="ota-auth-group">
            <label class="ota-auth-label" for="email">Email</label>
            <input id="email" class="ota-auth-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')<div class="ota-auth-error">{{ $message }}</div>@enderror
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
        <button class="ota-auth-btn" type="submit">Reset password</button>
    </form>
@endsection
