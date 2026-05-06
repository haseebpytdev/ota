@extends('layouts.dashboard')

@section('title', 'Communication Settings')

@section('page-header')
    <h1 class="page-title">Communication Settings</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.settings.communications.update') }}" class="row g-3">
                @csrf
                @method('PATCH')

                <div class="col-md-6">
                    <h3 class="h5">Email settings</h3>
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="email_enabled" value="1" {{ $settings->email_enabled ? 'checked' : '' }}>
                        <span class="form-check-label">Enable email notifications</span>
                    </label>
                </div>

                <div class="col-md-6">
                    <h3 class="h5">SMTP settings</h3>
                    <label class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="smtp_enabled" value="1" {{ $settings->smtp_enabled ? 'checked' : '' }}>
                        <span class="form-check-label">Use custom SMTP</span>
                    </label>
                    <input class="form-control mb-2" name="smtp_host" placeholder="SMTP Host" value="{{ old('smtp_host', $settings->smtp_host) }}">
                    <input class="form-control mb-2" name="smtp_port" placeholder="SMTP Port" value="{{ old('smtp_port', $settings->smtp_port) }}">
                    <input class="form-control mb-2" name="smtp_username" placeholder="SMTP Username" value="{{ old('smtp_username', $settings->smtp_username) }}">
                    <input type="password" class="form-control" name="smtp_password" placeholder="SMTP Password (leave blank to keep existing)">
                    @if($settings->maskedSmtpPassword())
                        <div class="text-secondary small mt-1">Saved password: {{ $settings->maskedSmtpPassword() }}</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <h3 class="h5">Sender identity</h3>
                    <input class="form-control mb-2" name="mail_from_name" placeholder="From name" value="{{ old('mail_from_name', $settings->mail_from_name) }}">
                    <input class="form-control mb-2" name="mail_from_email" placeholder="From email" value="{{ old('mail_from_email', $settings->mail_from_email) }}">
                    <input class="form-control" name="reply_to_email" placeholder="Reply-to email" value="{{ old('reply_to_email', $settings->reply_to_email) }}">
                </div>

                <div class="col-md-6">
                    <h3 class="h5">WhatsApp readiness</h3>
                    <label class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="whatsapp_enabled" value="1" {{ $settings->whatsapp_enabled ? 'checked' : '' }}>
                        <span class="form-check-label">Enable WhatsApp rules (no sending yet)</span>
                    </label>
                    <input class="form-control mb-2" name="whatsapp_provider" placeholder="Provider: meta_cloud_api|twilio|custom" value="{{ old('whatsapp_provider', $settings->whatsapp_provider) }}">
                    <input class="form-control mb-2" name="whatsapp_phone_number_id" placeholder="Phone Number ID" value="{{ old('whatsapp_phone_number_id', $settings->whatsapp_phone_number_id) }}">
                    <input class="form-control mb-2" name="whatsapp_business_account_id" placeholder="Business Account ID" value="{{ old('whatsapp_business_account_id', $settings->whatsapp_business_account_id) }}">
                    <input type="password" class="form-control mb-2" name="whatsapp_access_token" placeholder="Access token (leave blank to keep existing)">
                    @if($settings->maskedWhatsappToken())
                        <div class="text-secondary small">Saved token: {{ $settings->maskedWhatsappToken() }}</div>
                    @endif
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Save settings</button>
                </div>
            </form>
        </div>
    </div>
@endsection
