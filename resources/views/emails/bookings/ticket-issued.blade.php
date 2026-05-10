<x-mail::message>
# Ticket Issued

Hello {{ $booking->contact?->meta['name'] ?? 'Customer' }},
@php
    $brandName = $booking->agency->agencySetting?->display_name ?: $booking->agency->name;
    $supportPhone = $booking->agency->agencySetting?->support_phone ?: null;
@endphp

Your booking has been ticketed.

- Booking reference: **{{ $booking->booking_reference ?? 'N/A' }}**
- Route: **{{ $booking->route ?? 'N/A' }}**
- PNR: **{{ $booking->pnr ?? 'N/A' }}**
- Ticket count: **{{ $booking->tickets->count() }}**

@if($booking->tickets->isNotEmpty())
Ticket numbers:
@foreach($booking->tickets as $ticket)
- {{ $ticket->ticket_number ?? 'N/A' }} ({{ $ticket->meta['passenger_name'] ?? 'Passenger' }})
@endforeach
@endif

Thanks,<br>
{{ $brandName }}
@if($supportPhone)
Support: {{ $supportPhone }}
@endif
</x-mail::message>
