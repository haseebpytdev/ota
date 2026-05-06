@extends('layouts.auth')

@section('title', 'Application submitted')

@section('content')
    <h2>Application submitted</h2>
    <p class="ota-auth-help">Our team will review your details and contact you after verification.</p>
    <div class="ota-auth-alert" style="background:#ecfeff;border-color:#a5f3fc;color:#155e75;">
        You will receive login access only after approval.
    </div>
    <p class="ota-auth-help" style="margin-bottom:8px;">Need help with your application? Contact support@haseebasif.com.</p>
    <a class="ota-auth-link" href="{{ route('home') }}">Back to homepage</a>
@endsection
