@php
    $flightNote = 'Provider fares are shown based on current readiness and availability.';
@endphp
<section id="ota-flight-search" class="ota-search-widget-section">
    <div class="ota-search-card">
        <div class="ota-search-card-head">
            <h3 class="ota-search-card-title">Search flights</h3>
            <p class="ota-search-note">{{ $flightNote }}</p>
        </div>

        <div class="ota-search-tabs" role="tablist">
            <span class="ota-tab ota-tab-active" aria-current="true">One way</span>
            <span class="ota-tab ota-tab-muted" title="Coming soon">Round trip</span>
            <span class="ota-tab ota-tab-muted" title="Coming soon">Multi-city</span>
        </div>

        <form method="get" action="{{ route('flights.results') }}" class="ota-flight-form">
            <div class="ota-from-to-row">
                <div class="ota-from-wrap">
                    <label class="ota-field-label" for="fw-from">From</label>
                    <input class="ota-field" id="fw-from" name="from" type="text" placeholder="LHE" value="{{ strtoupper($defaultOrigin) }}" maxlength="3" required autocomplete="off">
                </div>
                <div class="ota-swap-wrap">
                    <span class="ota-field-label" style="visibility:hidden;">Swap</span>
                    <button type="button" class="ota-swap-btn" id="ota-swap-routes" title="Swap from / to" aria-label="Swap from and to airports">
                        <i class="fa fa-exchange"></i>
                    </button>
                </div>
                <div class="ota-to-wrap">
                    <label class="ota-field-label" for="fw-to">To</label>
                    <input class="ota-field" id="fw-to" name="to" type="text" placeholder="DXB" value="{{ strtoupper($defaultDestination) }}" maxlength="3" required autocomplete="off">
                </div>
            </div>

            <div class="ota-search-grid2">
                <div>
                    <label class="ota-field-label" for="fw-depart">Departure</label>
                    <input class="ota-field" id="fw-depart" name="depart" type="date" value="{{ $defaultDepart }}" required>
                </div>
                <div>
                    <label class="ota-field-label" for="fw-cabin">Cabin</label>
                    <select class="ota-field" id="fw-cabin" disabled style="cursor:not-allowed;opacity:0.75;">
                        <option>Economy</option>
                        <option disabled>Premium (soon)</option>
                        <option disabled>Business (soon)</option>
                    </select>
                </div>
                <div>
                    <label class="ota-field-label" for="fw-adults">Passengers</label>
                    <select class="ota-field" id="fw-adults" name="adults" disabled style="cursor:not-allowed;opacity:0.75;">
                        <option value="1" selected>1 adult</option>
                    </select>
                    <p class="ota-field-hint">Search currently starts with one adult; additional traveler options expand per provider capabilities.</p>
                </div>
            </div>

            <button type="submit" class="btn ota-search-submit">
                <i class="fa fa-search"></i> Search flights
            </button>
        </form>
    </div>
</section>

@push('scripts')
<script>
(function () {
  var swap = document.getElementById('ota-swap-routes');
  if (!swap) return;
  swap.addEventListener('click', function () {
    var a = document.getElementById('fw-from');
    var b = document.getElementById('fw-to');
    if (!a || !b) return;
    var t = a.value;
    a.value = b.value;
    b.value = t;
  });
})();
</script>
@endpush
