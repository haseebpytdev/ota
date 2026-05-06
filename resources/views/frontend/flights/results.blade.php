@extends('layouts.frontend')

@section('title', 'Flight results')

@section('content')
    <div class="ota-results-pro">
        <div class="ota-results-pro-head">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8">
                        <h1 class="ota-results-pro-title">Available flights</h1>
                        <p class="ota-results-pro-sub">
                            <strong>{{ $criteria['origin'] }}</strong> → <strong>{{ $criteria['destination'] }}</strong>
                            · {{ \Illuminate\Support\Carbon::parse($criteria['depart_date'])->format('l, M j, Y') }}
                            <span class="label label-primary" style="margin-left:8px;">Fare options</span>
                        </p>
                    </div>
                    <div class="col-sm-4 text-right hidden-xs">
                        <a href="{{ route('flights.search') }}" class="btn btn-default">Edit search</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container ota-results-pro-body">
            <div class="row">
                <aside class="col-md-3 ota-results-filters">
                    <div class="ota-filter-card">
                        <h4>Filters</h4>
                        <div class="form-group">
                            <label class="control-label">Stops</label>
                            <select class="form-control" disabled>
                                <option>Any</option>
                                <option>Non-stop</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Airlines</label>
                            <select class="form-control" disabled>
                                <option>All carriers</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Departure</label>
                            <input type="time" class="form-control" disabled value="06:00">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Refundable only</label>
                            <div class="checkbox disabled"><label><input type="checkbox" disabled> Yes</label></div>
                        </div>
                        <button type="button" class="btn btn-primary btn-block" disabled>Apply filters</button>
                        <p class="small text-muted" style="margin-top:8px;">Refine options by stop, time, and carrier preferences.</p>
                    </div>
                </aside>
                <div class="col-md-9">
                    @if (!empty($warnings ?? []))
                        <div class="alert alert-warning">
                            <strong>Some suppliers are unavailable:</strong>
                            <ul style="margin:8px 0 0 18px;">
                                @foreach ($warnings as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @forelse ($offers as $offer)
                        <article class="ota-result-pro-card">
                            <div class="row">
                                <div class="col-sm-2 text-center">
                                    <div class="ota-airline-logo">{{ $offer['airline_code'] ?? 'XX' }}</div>
                                    <div class="ota-airline-name">{{ $offer['airline_name'] ?? '' }}</div>
                                    <div class="ota-flight-no">{{ $offer['carrier_code'] ?? '' }}{{ $offer['flight_number'] ?? '' }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="row ota-time-row">
                                        <div class="col-xs-4">
                                            <div class="ota-time-lg">{{ \Illuminate\Support\Carbon::parse($offer['depart_at'])->format('H:i') }}</div>
                                            <div class="ota-time-sub">{{ $criteria['origin'] }}</div>
                                        </div>
                                        <div class="col-xs-4 text-center">
                                            <div class="ota-dur-line">{{ $offer['duration_h'] }}h {{ str_pad((string) $offer['duration_m'], 2, '0', STR_PAD_LEFT) }}m</div>
                                            <div class="ota-dur-bar"></div>
                                            <span class="label label-default" style="font-size:10px;">Direct</span>
                                        </div>
                                        <div class="col-xs-4 text-right">
                                            <div class="ota-time-lg">{{ \Illuminate\Support\Carbon::parse($offer['arrive_at'])->format('H:i') }}</div>
                                            <div class="ota-time-sub">{{ $criteria['destination'] }}</div>
                                        </div>
                                    </div>
                                    <div class="ota-result-tags">
                                        <span><i class="fa fa-suitcase"></i> {{ $offer['baggage'] ?? '' }}</span>
                                        <span class="text-capitalize">{{ str_replace('_', ' ', $offer['cabin'] ?? '') }}</span>
                                        <span>{{ $offer['fare_family'] ?? '' }}</span>
                                    </div>
                                </div>
                                <div class="col-sm-4 text-right">
                                    @if (!empty($offer['refundable']))
                                        <span class="label label-success">Refundable</span>
                                    @else
                                        <span class="label label-warning">Non-refundable</span>
                                    @endif
                                    @if (($offer['seats_left'] ?? null) !== null)
                                        <div style="margin-top:6px;"><span class="label label-info">{{ $offer['seats_left'] }} seats left</span></div>
                                    @endif
                                    <div class="ota-price-stack">
                                        <div class="ota-price-lg">Rs {{ number_format($offer['total'], 0) }}</div>
                                        <div class="ota-price-sub">Base Rs {{ number_format((float) ($offer['base_fare'] ?? 0), 0) }} + taxes &amp; fees</div>
                                        <div class="ota-pay-later">Continue to review and submit booking request</div>
                                    </div>
                                    <a class="btn btn-primary btn-block ota-select-primary"
                                       href="{{ route('booking.passengers', array_merge(['flight_id' => $offer['id']], request()->only(['from', 'to', 'depart']))) }}">
                                        Book flight
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p>No flights found for this search. Try nearby dates or update your route.</p>
                    @endforelse
                    <p style="margin-top: 16px;"><a href="{{ route('flights.search') }}">← Back to search</a> · <a href="{{ route('home') }}">Home</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
