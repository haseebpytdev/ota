@extends('layouts.dashboard')

@section('title', 'Branding Settings')

@section('page-header')
    <h1 class="page-title">Settings / Branding</h1>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="post" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" class="card">
        @csrf
        @method('PATCH')
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Display name</label><input class="form-control" name="display_name" value="{{ old('display_name', $settings->display_name) }}"></div>
                <div class="col-md-4"><label class="form-label">Legal name</label><input class="form-control" name="legal_name" value="{{ old('legal_name', $settings->legal_name) }}"></div>
                <div class="col-md-4"><label class="form-label">Tagline</label><input class="form-control" name="tagline" value="{{ old('tagline', $settings->tagline) }}"></div>
                <div class="col-md-4"><label class="form-label">Support phone</label><input class="form-control" name="support_phone" value="{{ old('support_phone', $settings->support_phone) }}"></div>
                <div class="col-md-4"><label class="form-label">Support WhatsApp</label><input class="form-control" name="support_whatsapp" value="{{ old('support_whatsapp', $settings->support_whatsapp) }}"></div>
                <div class="col-md-4"><label class="form-label">Support email</label><input class="form-control" name="support_email" value="{{ old('support_email', $settings->support_email) }}"></div>
                <div class="col-md-4"><label class="form-label">Primary color</label><input class="form-control" name="primary_color" value="{{ old('primary_color', $settings->primary_color) }}" placeholder="#1d4ed8"></div>
                <div class="col-md-4"><label class="form-label">Secondary color</label><input class="form-control" name="secondary_color" value="{{ old('secondary_color', $settings->secondary_color) }}" placeholder="#0ea5e9"></div>
                <div class="col-md-4"><label class="form-label">Accent color</label><input class="form-control" name="accent_color" value="{{ old('accent_color', $settings->accent_color) }}" placeholder="#f59e0b"></div>
                <div class="col-md-6"><label class="form-label">Header CTA label</label><input class="form-control" name="header_cta_label" value="{{ old('header_cta_label', $settings->header_cta_label) }}"></div>
                <div class="col-md-6"><label class="form-label">Header CTA URL</label><input class="form-control" name="header_cta_url" value="{{ old('header_cta_url', $settings->header_cta_url) }}"></div>
                <div class="col-12"><label class="form-label">Footer about</label><textarea class="form-control" rows="3" name="footer_about">{{ old('footer_about', $settings->footer_about) }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Footer copyright</label><input class="form-control" name="footer_copyright" value="{{ old('footer_copyright', $settings->footer_copyright) }}"></div>
                <div class="col-md-6"><label class="form-label">Website URL</label><input class="form-control" name="website_url" value="{{ old('website_url', $settings->website_url) }}"></div>
                <div class="col-md-3"><label class="form-label">Logo</label><input class="form-control" type="file" name="logo"></div>
                <div class="col-md-3"><label class="form-label">Favicon</label><input class="form-control" type="file" name="favicon"></div>
                <div class="col-md-3"><label class="form-label">Hero image</label><input class="form-control" type="file" name="hero_image"></div>
                <div class="col-md-3"><label class="form-label">Footer logo</label><input class="form-control" type="file" name="footer_logo"></div>
            </div>
            <hr>
            <div class="small text-secondary mb-2">Social links</div>
            @php $social = $settings->social_links ?? []; @endphp
            <div class="row g-2">
                <div class="col-md-3"><input class="form-control" name="social_links[facebook]" placeholder="Facebook URL" value="{{ old('social_links.facebook', $social['facebook'] ?? '') }}"></div>
                <div class="col-md-3"><input class="form-control" name="social_links[instagram]" placeholder="Instagram URL" value="{{ old('social_links.instagram', $social['instagram'] ?? '') }}"></div>
                <div class="col-md-3"><input class="form-control" name="social_links[linkedin]" placeholder="LinkedIn URL" value="{{ old('social_links.linkedin', $social['linkedin'] ?? '') }}"></div>
                <div class="col-md-3"><input class="form-control" name="social_links[twitter]" placeholder="Twitter URL" value="{{ old('social_links.twitter', $social['twitter'] ?? '') }}"></div>
            </div>
            <div class="mt-3 d-flex justify-content-end"><button class="btn btn-primary">Save branding</button></div>
        </div>
    </form>
@endsection
