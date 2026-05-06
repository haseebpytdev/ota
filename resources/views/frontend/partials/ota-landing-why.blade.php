@php
    $whyTitle = (string) ($whyChooseUsContent['title'] ?? 'Why choose Asif Travels');
    $whySubtitle = (string) ($whyChooseUsContent['subtitle'] ?? 'A practical OTA experience focused on travelers, support, and reliable booking service.');
    $bullets = is_array(($whyChooseUsContent['bullets'] ?? null)) ? $whyChooseUsContent['bullets'] : [];
@endphp
<section class="ota-section ota-why-section" id="why">
    <div class="ota-container">
        <header class="ota-section-head">
            <p class="ota-section-kicker">Why this OTA</p>
            <h2 class="ota-section-title">{{ $whyTitle }}</h2>
            <p class="ota-section-desc">{{ $whySubtitle }}</p>
        </header>
        <div class="ota-why-grid">
            <div class="ota-why-item">
                <i class="fa fa-paint-brush" aria-hidden="true"></i>
                <div>
                    <h4>{{ (string) ($bullets[0]['title'] ?? 'Trusted booking support') }}</h4>
                    <p>{{ (string) ($bullets[0]['text'] ?? 'Our team helps you from search to confirmation, including payment and ticketing guidance.') }}</p>
                </div>
            </div>
            <div class="ota-why-item">
                <i class="fa fa-users" aria-hidden="true"></i>
                <div>
                    <h4>{{ (string) ($bullets[1]['title'] ?? 'Transparent fares') }}</h4>
                    <p>{{ (string) ($bullets[1]['text'] ?? 'Review route details, pricing, and baggage information before you confirm a booking request.') }}</p>
                </div>
            </div>
            <div class="ota-why-item">
                <i class="fa fa-rocket" aria-hidden="true"></i>
                <div>
                    <h4>{{ (string) ($bullets[2]['title'] ?? 'Fast confirmation workflow') }}</h4>
                    <p>{{ (string) ($bullets[2]['text'] ?? 'Submit passenger details, review fare breakdown, and get booking reference updates quickly.') }}</p>
                </div>
            </div>
            <div class="ota-why-item">
                <i class="fa fa-line-chart" aria-hidden="true"></i>
                <div>
                    <h4>{{ (string) ($bullets[3]['title'] ?? 'Corporate and agent support') }}</h4>
                    <p>{{ (string) ($bullets[3]['text'] ?? 'We support both direct travelers and partner agencies with clear operational channels.') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
