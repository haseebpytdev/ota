@php
    $whyTitle = (string) ($whyChooseUsContent['title'] ?? 'Travel booking made simple with Asif Travels');
    $whySubtitle = (string) ($whyChooseUsContent['subtitle'] ?? 'Search fares, submit booking requests, and get support from a team that understands your travel needs.');
    $bullets = is_array(($whyChooseUsContent['bullets'] ?? null)) ? $whyChooseUsContent['bullets'] : [];
@endphp
<section class="ota-section ota-why-section" id="why">
    <div class="ota-container">
        <header class="ota-section-head">
            <p class="ota-section-kicker">Why book with us</p>
            <h2 class="ota-section-title">{{ $whyTitle }}</h2>
            <p class="ota-section-desc">{{ $whySubtitle }}</p>
        </header>
        <div class="ota-why-grid">
            <div class="ota-why-item">
                <span class="ota-why-item__icon" aria-hidden="true"><i class="fa fa-shield"></i></span>
                <div>
                    <h4>{{ (string) ($bullets[0]['title'] ?? 'Reliable booking support') }}</h4>
                    <p>{{ (string) ($bullets[0]['text'] ?? 'Get help from search to confirmation with clear guidance for fares, payments, and ticketing.') }}</p>
                </div>
            </div>
            <div class="ota-why-item">
                <span class="ota-why-item__icon" aria-hidden="true"><i class="fa fa-tags"></i></span>
                <div>
                    <h4>{{ (string) ($bullets[1]['title'] ?? 'Clear fare details') }}</h4>
                    <p>{{ (string) ($bullets[1]['text'] ?? 'Review routes, baggage, refund rules, and PKR pricing before sending a booking request.') }}</p>
                </div>
            </div>
            <div class="ota-why-item">
                <span class="ota-why-item__icon" aria-hidden="true"><i class="fa fa-bolt"></i></span>
                <div>
                    <h4>{{ (string) ($bullets[2]['title'] ?? 'Fast booking updates') }}</h4>
                    <p>{{ (string) ($bullets[2]['text'] ?? 'Submit passenger details, track your request, and receive updates as your booking moves forward.') }}</p>
                </div>
            </div>
            <div class="ota-why-item">
                <span class="ota-why-item__icon" aria-hidden="true"><i class="fa fa-users"></i></span>
                <div>
                    <h4>{{ (string) ($bullets[3]['title'] ?? 'Built for travelers and agents') }}</h4>
                    <p>{{ (string) ($bullets[3]['text'] ?? 'Direct customers and partner agents can manage bookings through dedicated, secure portals.') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
