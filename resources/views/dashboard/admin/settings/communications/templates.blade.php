@extends('layouts.dashboard')

@section('title', 'Message Templates')

@section('page-header')
    <h1 class="page-title">Message Templates</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Variables</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($events as $event)
                        @foreach($channels as $channel)
                            @php
                                $item = $templates->first(fn ($row) => $row->event === $event->value && $row->channel === $channel->value);
                            @endphp
                            <tr>
                                <td>{{ $event->value }}</td>
                                <td>{{ $channel->value }}</td>
                                <td>{{ $item?->is_enabled === false ? 'Disabled' : 'Enabled' }}</td>
                                <td>{{ implode(', ', $item?->variables ?? ['agency_name', 'booking_reference', 'passenger_name']) }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.settings.communications.templates.edit', ['event' => $event->value, 'channel' => $channel->value]) }}">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
