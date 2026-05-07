@extends('layouts.auth')

@section('title', 'Agent application')

@section('content')
    <h2>Agent signup application</h2>
    <p class="ota-auth-help">Agent applications are reviewed by Asif Travels. After approval, you will receive an activation email.</p>
    <div class="ota-agent-wizard" aria-label="Application steps">
        <span class="ota-agent-wizard__step is-active">1. Personal</span>
        <span class="ota-agent-wizard__step">2. Business</span>
        <span class="ota-agent-wizard__step">3. Verification</span>
        <span class="ota-agent-wizard__step">4. Volume</span>
        <span class="ota-agent-wizard__step">5. Agreement</span>
    </div>

    <form method="POST" action="{{ route('agent.register.store') }}">
        @csrf
        <div class="ota-auth-alert" style="background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a;">
            Agent applications are reviewed by Asif Travels. After approval, you will receive an activation email.
        </div>

        <div class="ota-auth-section-card" data-agent-section="personal">
            <h3>1. Personal details</h3>
            <div class="ota-auth-grid-2">
                <div class="ota-auth-group"><label class="ota-auth-label">First name</label><input class="ota-auth-input" name="first_name" value="{{ old('first_name') }}" required>@error('first_name')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
                <div class="ota-auth-group"><label class="ota-auth-label">Last name</label><input class="ota-auth-input" name="last_name" value="{{ old('last_name') }}" required>@error('last_name')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
                <div class="ota-auth-group"><label class="ota-auth-label">Email</label><input class="ota-auth-input" type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
                <div class="ota-auth-group"><label class="ota-auth-label">Mobile</label><input class="ota-auth-input" name="mobile" value="{{ old('mobile') }}" required>@error('mobile')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
            </div>
        </div>

        <div class="ota-auth-section-card" data-agent-section="business">
            <h3>2. Business details</h3>
            <div class="ota-auth-grid-2">
                <div class="ota-auth-group"><label class="ota-auth-label">Company name</label><input class="ota-auth-input" name="company_name" value="{{ old('company_name') }}" required>@error('company_name')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
                <div class="ota-auth-group"><label class="ota-auth-label">Business type</label><input class="ota-auth-input" name="business_type" value="{{ old('business_type') }}" required>@error('business_type')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
                <div class="ota-auth-group"><label class="ota-auth-label">City</label><input class="ota-auth-input" name="city" value="{{ old('city') }}" required>@error('city')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
                <div class="ota-auth-group"><label class="ota-auth-label">Country</label><input class="ota-auth-input" name="country" value="{{ old('country', 'Pakistan') }}" required>@error('country')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
            </div>
            <div class="ota-auth-group"><label class="ota-auth-label">Office address</label><textarea class="ota-auth-input" name="office_address" required>{{ old('office_address') }}</textarea>@error('office_address')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
            <div class="ota-auth-group"><label class="ota-auth-label">Website (optional)</label><input class="ota-auth-input" name="website" value="{{ old('website') }}">@error('website')<div class="ota-auth-error">{{ $message }}</div>@enderror</div>
        </div>

        <div class="ota-auth-section-card" data-agent-section="verification">
            <h3>3. Verification</h3>
            <div class="ota-auth-grid-2">
                <div class="ota-auth-group"><label class="ota-auth-label">CNIC (optional)</label><input class="ota-auth-input" name="cnic" value="{{ old('cnic') }}"></div>
                <div class="ota-auth-group"><label class="ota-auth-label">NTN (optional)</label><input class="ota-auth-input" name="ntn" value="{{ old('ntn') }}"></div>
                <div class="ota-auth-group"><label class="ota-auth-label">IATA number (optional)</label><input class="ota-auth-input" name="iata_number" value="{{ old('iata_number') }}"></div>
                <div class="ota-auth-group"><label class="ota-auth-label">Years in business (optional)</label><input class="ota-auth-input" type="number" min="0" name="years_in_business" value="{{ old('years_in_business') }}"></div>
            </div>
        </div>

        <div class="ota-auth-section-card" data-agent-section="expected-volume">
            <h3>4. Expected volume</h3>
            <div class="ota-auth-group"><label class="ota-auth-label">Expected monthly booking volume (optional)</label><input class="ota-auth-input" name="expected_booking_volume" value="{{ old('expected_booking_volume') }}"></div>
            <div class="ota-auth-group">
                <label class="ota-auth-label">Services interested</label>
                <label><input type="checkbox" name="services_interested[]" value="Flights"> Flights</label><br>
                <label><input type="checkbox" name="services_interested[]" value="Corporate travel"> Corporate travel</label><br>
                <label><input type="checkbox" name="services_interested[]" value="Group bookings"> Group bookings</label>
            </div>
            <div class="ota-auth-group"><label class="ota-auth-label">Notes (optional)</label><textarea class="ota-auth-input" name="notes">{{ old('notes') }}</textarea></div>
        </div>

        <div class="ota-auth-section-card" data-agent-section="agreement">
            <h3>5. Agreement</h3>
            <label><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I confirm submitted information is accurate.</label>
            @error('terms')<div class="ota-auth-error">{{ $message }}</div>@enderror
        </div>
        <button class="ota-auth-btn" type="submit">Submit Agent Application</button>
    </form>
@endsection
