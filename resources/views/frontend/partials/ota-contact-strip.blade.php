@php
    $b = config('ota-brand', []);
    $wa = preg_replace('/\D/', '', (string) ($b['support_whatsapp'] ?? '')) ?: '923001234567';
    $mail = $b['support_email'] ?? 'support@haseebasif.com';
@endphp
<section class="ota-contact-strip">
    <span>Need travel assistance?</span>
    <a href="mailto:{{ $mail }}?subject=Asif%20Travels%20Support">Email support</a>
    <span aria-hidden="true">·</span>
    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">WhatsApp</a>
    <span aria-hidden="true">·</span>
    <a href="{{ route('login') }}">Customer login</a>
</section>
