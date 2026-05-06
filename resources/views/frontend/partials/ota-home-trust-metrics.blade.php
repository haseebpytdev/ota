@php
    $tm = $trustMetrics ?? [];
    $section = $trustMetricsContent ?? [];
    $metrics = is_array($section['metrics'] ?? null) ? $section['metrics'] : [];
@endphp
<section class="ota-metrics-band" id="metrics" aria-label="Trust metrics">
    <div class="ota-container">
        <div class="metric-grid">
            <article class="metric-card">
                <span class="metric-card-icon" aria-hidden="true"><i class="fa fa-check-circle"></i></span>
                <div class="metric-card-value">{{ (string) ($metrics[0]['value'] ?? '24/7') }}</div>
                <div class="metric-card-label">{{ (string) ($metrics[0]['label'] ?? '24/7 travel support') }}</div>
            </article>
            <article class="metric-card">
                <span class="metric-card-icon" aria-hidden="true"><i class="fa fa-users"></i></span>
                <div class="metric-card-value">{{ (string) ($metrics[1]['value'] ?? 'Clear') }}</div>
                <div class="metric-card-label">{{ (string) ($metrics[1]['label'] ?? 'Transparent booking process') }}</div>
            </article>
            <article class="metric-card">
                <span class="metric-card-icon" aria-hidden="true"><i class="fa fa-line-chart"></i></span>
                <div class="metric-card-value">{{ (string) ($metrics[2]['value'] ?? 'Flexible') }}</div>
                <div class="metric-card-label">{{ (string) ($metrics[2]['label'] ?? 'Flexible travel assistance') }}</div>
            </article>
            <article class="metric-card">
                <span class="metric-card-icon" aria-hidden="true"><i class="fa fa-plug"></i></span>
                <div class="metric-card-value">{{ (string) ($metrics[3]['value'] ?? 'Trusted') }}</div>
                <div class="metric-card-label">{{ (string) ($metrics[3]['label'] ?? 'Trusted fare review') }}</div>
            </article>
        </div>
    </div>
</section>
