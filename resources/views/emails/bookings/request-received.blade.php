<x-mail::message>
# Booking Request Received

Hello {{ $booking->contact?->meta['name'] ?? 'Customer' }},

@php
    $brandName = $booking->agency->agencySetting?->display_name ?: $booking->agency->name;
    $supportEmail = $booking->agency->agencySetting?->support_email ?: null;
    $supportPhone = $booking->agency->agencySetting?->support_phone ?: null;
@endphp
Your booking request has been received by {{ $brandName }}.

- Booking reference: **{{ $booking->booking_reference ?? 'Pending allocation' }}**
- Route: **{{ $booking->route ?? 'N/A' }}**
- Primary passenger: **{{ optional($booking->passengers->first())->first_name }} {{ optional($booking->passengers->first())->last_name }}**
- Status: **{{ str_replace('_', ' ', $booking->status->value) }}**

Our team will keep you updated on the next steps.

Thanks,<br>
{{ $brandName }}

@if ($supportEmail || $supportPhone)
Support: {{ $supportEmail ?? 'N/A' }}{{ $supportPhone ? ' · '.$supportPhone : '' }}
@endif
</x-mail::message>
