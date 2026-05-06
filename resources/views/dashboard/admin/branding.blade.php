@extends('layouts.dashboard')

@section('title', 'Branding')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">White-label</div>
            <h1 class="page-title">Branding settings</h1>
            <div class="text-secondary mt-1">Client preview values from <code>config/demo-client.php</code> — all fields disabled for demo.</div>
        </div>
    </div>
@endsection

@section('content')
    @php $c = $client ?? []; @endphp
    <div class="alert alert-info mb-4">
        <i class="ti ti-info-circle me-2"></i>Production would persist tenant branding per domain and inject CSS tokens at runtime.
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Agency name</label>
                    <input type="text" class="form-control" disabled value="{{ $c['agency_name'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo text</label>
                    <input type="text" class="form-control" disabled value="{{ $c['logo_text'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Primary color</label>
                    <input type="text" class="form-control" disabled value="{{ $c['primary_color'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Domain</label>
                    <input type="text" class="form-control" disabled value="{{ $c['domain_preview'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Support phone</label>
                    <input type="text" class="form-control" disabled value="{{ $c['support_phone'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" disabled value="{{ $c['support_whatsapp'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" disabled value="{{ $c['support_email'] ?? '' }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Footer text</label>
                    <textarea class="form-control" rows="2" disabled>{{ $c['footer_text'] ?? '' }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Facebook</label>
                    <input type="url" class="form-control" disabled value="{{ $c['social_facebook'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">LinkedIn</label>
                    <input type="url" class="form-control" disabled value="{{ $c['social_linkedin'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Instagram</label>
                    <input type="url" class="form-control" disabled value="{{ $c['social_instagram'] ?? '' }}">
                </div>
            </div>
            <div class="mt-4">
                <button type="button" class="btn btn-primary btn-demo-action" disabled>Save branding @include('components.demo-only-hint')</button>
            </div>
        </div>
    </div>
@endsection
