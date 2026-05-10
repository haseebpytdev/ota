@php
    /**
     * Agents table body partial — used by both the full Agents page render
     * and the AJAX filter endpoint (admin.agents.data). Keeps row markup in
     * one place so the JS swap can never drift from the server-rendered form.
     *
     * Inputs:
     *   $agents (iterable<array>)  — filtered agent row payloads.
     *   $a      (?array)           — currently selected agent (for is-active
     *                                highlighting).
     *   $totalAgents (int)         — count for the current filter scope (matches
     *                                "of N agents" subtitle on the page).
     *   $hasFilters (bool)         — true when any user-controlled filter or
     *                                queue tab is narrowing the view; drives the
     *                                empty-state copy ("no agents match" vs
     *                                "no agents yet").
     */
    $statusBadgeFor = static fn (string $status): string => match ($status) {
        'active' => 'badge-soft-success',
        'inactive' => 'badge-soft-neutral',
        default => 'badge-soft-warning',
    };
    $hasFilters = $hasFilters ?? false;
@endphp

@if (! $agents || (is_countable($agents) && count($agents) === 0))
    <div class="agents-empty-state m-3" data-testid="ota-agents-empty">
        @if ($hasFilters || ($totalAgents ?? 0) > 0)
            <i class="ti ti-filter-off d-block mb-2 fs-2 text-muted"></i>
            <strong class="d-block mb-1">No agents match your filters</strong>
            Try a different queue or clear your filters.
        @else
            <i class="ti ti-users d-block mb-2 fs-2 text-muted"></i>
            <strong class="d-block mb-1">No agents yet</strong>
            <p class="mb-3">Agents and partner agencies will appear here after approval or manual creation.</p>
            <a href="{{ route('admin.agent-applications.index') }}"
               class="btn btn-sm btn-primary"
               data-testid="ota-agents-empty-review-applications">
                <i class="ti ti-clipboard-check me-1"></i> Review applications
            </a>
        @endif
    </div>
@else
    <table class="agents-table" data-testid="ota-agents-table">
        <thead>
            <tr>
                <th class="col-agent">Agent</th>
                <th class="col-contact">Contact</th>
                <th class="col-status">Status</th>
                <th class="col-commission">Commission</th>
                <th class="col-bookings">Bookings</th>
                <th class="col-sales">Monthly sales</th>
                <th class="col-action">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($agents as $row)
                @php
                    $isSel = $a && (((int) ($row['id'] ?? 0)) === ((int) ($a['id'] ?? 0)));
                    $previewUrl = route('admin.agents', ['preview' => $row['id']]);
                    $st = $row['status'] ?? 'inactive';
                    $stClass = $statusBadgeFor($st);
                    $bookingsCount = (int) ($row['bookings_count'] ?? 0);
                    $hasPhone = ($row['phone'] ?? '—') !== '—' && trim((string) $row['phone']) !== '';
                @endphp
                <tr class="{{ $isSel ? 'is-active' : '' }}"
                    data-agent-row
                    data-agent-id="{{ $row['id'] }}"
                    data-agent-code="{{ $row['agent_code'] }}"
                    data-preview-url="{{ $previewUrl }}"
                    data-preview-ajax-url="{{ route('admin.agents.preview', ['agent' => $row['id']]) }}"
                    tabindex="0"
                    role="button"
                    aria-label="Preview agent {{ $row['agent_code'] }}">
                    <td class="col-agent" data-label="Agent">
                        <a href="{{ $previewUrl }}" class="agent-cell-code">{{ $row['agent_code'] }}</a>
                        <span class="agent-cell-agency">{{ $row['agency_name'] }}</span>
                    </td>
                    <td class="col-contact" data-label="Contact">
                        <div class="agent-cell-name">{{ $row['contact_person'] }}</div>
                        <div class="agent-cell-contactline">
                            <span class="agent-cell-email">{{ $row['email'] }}</span>
                            @if ($hasPhone)
                                <span class="agent-cell-sep" aria-hidden="true">·</span>
                                <span class="agent-cell-phone">{{ $row['phone'] }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="col-status" data-label="Status">
                        <span class="badge {{ $stClass }}" data-testid="ota-agent-status-{{ $st }}">{{ ucfirst($st) }}</span>
                    </td>
                    <td class="col-commission col-numeric" data-label="Commission">{{ number_format((float) ($row['commission_percent'] ?? 0), 1) }}%</td>
                    <td class="col-bookings col-numeric" data-label="Bookings">{{ number_format($bookingsCount) }} {{ $bookingsCount === 1 ? 'booking' : 'bookings' }}</td>
                    <td class="col-sales col-numeric" data-label="Monthly sales">Rs {{ number_format((int) round((float) ($row['monthly_sales'] ?? 0))) }}</td>
                    <td class="col-action agent-cell-action" data-label="Action">
                        <a href="{{ $previewUrl }}"
                           class="btn btn-sm btn-outline-primary"
                           aria-label="Open agent {{ $row['agent_code'] }}">Open</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
