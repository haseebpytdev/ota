@extends('layouts.auth')

@section('title', 'Confirm password')

@section('content')
    <h2>Confirm your password</h2>
    <p class="ota-auth-help">For security, please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="ota-auth-group">
            <label class="ota-auth-label" for="password">Password</label>
            <input id="password" class="ota-auth-input" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>
        <button class="ota-auth-btn" type="submit">Confirm</button>
    </form>
@endsection
