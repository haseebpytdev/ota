@extends('layouts.dashboard')

@section('title', 'Create Booking Request')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Agent Portal</div>
            <h1 class="page-title">Create booking request</h1>
        </div>
    </div>
@endsection

@section('content')
    @if (!empty($validationAlert))
        <div class="alert alert-warning">
            {{ $validationAlert }}
            @if (is_array($validationResult ?? null) && ($validationResult['price_changed'] ?? false))
                <div class="small mt-1">
                    Old: Rs {{ number_format((float) ($validationResult['old_total'] ?? 0), 0) }}
                    · New: Rs {{ number_format((float) ($validationResult['new_total'] ?? 0), 0) }}
                </div>
            @endif
        </div>
    @endif
    <form method="POST" action="{{ route('agent.bookings.store') }}">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Trip & mock flight selection</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">From</label>
                                <input type="text" name="from" class="form-control" value="{{ old('from', $criteria['origin'] ?? 'LHE') }}" maxlength="8" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">To</label>
                                <input type="text" name="to" class="form-control" value="{{ old('to', $criteria['destination'] ?? 'DXB') }}" maxlength="8" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Depart</label>
                                <input type="date" name="depart" class="form-control" value="{{ old('depart', $criteria['depart_date'] ?? now()->addDays(14)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Select mock flight</label>
                                <select class="form-select @error('flight_id') is-invalid @enderror" name="flight_id" required>
                                    <option value="">Choose one...</option>
                                    @foreach ($offers as $offer)
                                        <option value="{{ $offer['id'] }}" @selected(old('flight_id', $selectedFlightId ?? '') === $offer['id'])>
                                            {{ $offer['airline_name'] ?? 'Mock Airline' }} {{ $offer['carrier_code'] ?? '' }}{{ $offer['flight_number'] ?? '' }}
                                            — {{ $offer['origin'] ?? '' }} to {{ $offer['destination'] ?? '' }}
                                            — Rs {{ number_format((float) ($offer['total'] ?? 0), 0) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('flight_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Passenger details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', 'Mr') }}" maxlength="16">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">First name</label>
                                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" maxlength="120" required>
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Last name</label>
                                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" maxlength="120" required>
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of birth</label>
                                <input type="date" name="dob" class="form-control @error('dob') is-invalid @enderror" value="{{ old('dob') }}">
                                @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control @error('nationality') is-invalid @enderror" value="{{ old('nationality') }}" maxlength="8">
                                @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Contact</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" maxlength="64" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country') }}" maxlength="120">
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Optional agent note</label>
                                <textarea name="agent_note" class="form-control @error('agent_note') is-invalid @enderror" rows="3" maxlength="2000">{{ old('agent_note') }}</textarea>
                                @error('agent_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Submit booking request</button>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Summary</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $selected = collect($offers)->firstWhere('id', old('flight_id', $selectedFlightId ?? ''));
                        @endphp
                        <p class="mb-2 text-secondary">Selected sample fare</p>
                        <div class="h2 mb-3">Rs {{ number_format((float) ($selected['total'] ?? 0), 0) }}</div>
                        <div class="small text-secondary mb-2">Commission rules will apply after admin setup.</div>
                        <div class="small text-secondary">No payment is captured in the agent portal request flow.</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
