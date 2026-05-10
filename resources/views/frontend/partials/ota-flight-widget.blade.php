@php
    $variant = $variant ?? 'hero';
    $showIntro = $show_intro ?? true;
    $flightNote = 'Fares are shown based on current airline availability and airline confirmation.';
    $bannerSubtitle = 'Compare fares, choose your cabin, and search with confidence.';
    $widgetId = 'fw-'.substr(md5((string) microtime(true).(string) random_int(1000, 999999)), 0, 8);
    $minDate = $minDate ?? now()->format('Y-m-d');
    $defaultTripType = old('trip_type', $defaultTripType ?? 'one_way');
    $defaultOrigin = old('from', $defaultOrigin ?? '');
    $defaultDestination = old('to', $defaultDestination ?? '');
    $defaultOriginDisplay = old('from_display', $defaultOrigin);
    $defaultDestinationDisplay = old('to_display', $defaultDestination);
    $defaultDepart = old('depart', $defaultDepart ?? '');
    $defaultReturnDate = old('return_date', $defaultReturnDate ?? '');
    $multiFrom = old('multi_from', []);
    $multiTo = old('multi_to', []);
    $multiDepart = old('multi_depart', []);
    if (! is_array($multiFrom)) {
        $multiFrom = [];
    }
    if (! is_array($multiTo)) {
        $multiTo = [];
    }
    if (! is_array($multiDepart)) {
        $multiDepart = [];
    }
    $multiCount = max(2, count($multiFrom), count($multiTo), count($multiDepart));
@endphp
<section id="ota-flight-search" class="ota-search-widget-section" data-airport-widget="{{ $widgetId }}" data-min-date="{{ $minDate }}" data-trip-type="{{ $defaultTripType }}">
    <div class="ota-search-card {{ $variant === 'standalone' ? 'ota-search-card--standalone' : '' }}">
        <header class="ota-search-card-banner">
            <div class="ota-search-card-banner__decor" aria-hidden="true">
                <span class="ota-search-card-banner__decor-plane"><i class="fa fa-plane"></i></span>
            </div>
            <div class="ota-search-card-banner__inner">
                <span class="ota-search-card-banner__mark"><i class="fa fa-plane" aria-hidden="true"></i></span>
                <div class="ota-search-card-banner__text">
                    <h3 class="ota-search-card-banner__title">Search flights</h3>
                    <p class="ota-search-card-banner__subtitle">{{ $bannerSubtitle }}</p>
                </div>
            </div>
        </header>

        <div class="ota-search-card-body">
            @if ($errors->any())
                <div class="ota-alert ota-alert--danger ota-search-card-alert">
                    <strong>Please fix the following:</strong>
                    <ul class="ota-search-card-alert__list">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="ota-search-tabs" role="tablist" aria-label="Trip type">
                <button type="button" class="ota-tab {{ $defaultTripType === 'one_way' ? 'ota-tab-active' : '' }}" data-trip-tab="one_way">
                    <span class="ota-tab__icon" aria-hidden="true"><i class="fa fa-plane"></i></span>
                    <span class="ota-tab__label">One way</span>
                </button>
                <button type="button" class="ota-tab {{ $defaultTripType === 'round_trip' ? 'ota-tab-active' : '' }}" data-trip-tab="round_trip">
                    <span class="ota-tab__icon" aria-hidden="true"><i class="fa fa-refresh"></i></span>
                    <span class="ota-tab__label">Round trip</span>
                </button>
                <button type="button" class="ota-tab {{ $defaultTripType === 'multi_city' ? 'ota-tab-active' : '' }}" data-trip-tab="multi_city">
                    <span class="ota-tab__icon" aria-hidden="true"><i class="fa fa-map-marker"></i></span>
                    <span class="ota-tab__label">Multi-city</span>
                </button>
            </div>

            <form method="get" action="{{ route('flights.results') }}" class="ota-flight-form" id="{{ $widgetId }}-form" data-flight-search-form novalidate>
            <input type="hidden" name="trip_type" id="{{ $widgetId }}-trip-type" value="{{ $defaultTripType }}">
            <div data-trip-panel="one_way" style="{{ $defaultTripType !== 'one_way' && $defaultTripType !== 'round_trip' ? 'display:none;' : '' }}">
                <div class="ota-from-to-row">
                    <div class="ota-from-wrap">
                        <label class="ota-field-label" for="{{ $widgetId }}-from-display">From</label>
                        <div class="ota-input-shell">
                            <span class="ota-input-shell__icon" aria-hidden="true"><i class="fa fa-map-marker"></i></span>
                            <input class="ota-field ota-field--shell js-airport-autocomplete" id="{{ $widgetId }}-from-display" name="from_display" data-airport-display="from" data-hidden-target="{{ $widgetId }}-from" type="text" value="{{ $defaultOriginDisplay }}" autocomplete="off" placeholder="City or airport" inputmode="text">
                        </div>
                        <input type="hidden" id="{{ $widgetId }}-from" name="from" data-airport-hidden="from" value="{{ $defaultOrigin }}">
                        <div class="ota-airport-suggest" data-for="{{ $widgetId }}-from" data-airport-dropdown="from" role="listbox" aria-label="Airport suggestions"></div>
                    </div>
                    <div class="ota-swap-wrap">
                        <span class="ota-field-label ota-swap-wrap__label">Swap</span>
                        <button type="button" class="ota-swap-btn" data-swap-routes title="Swap from / to" aria-label="Swap from and to airports">
                            <i class="fa fa-arrows-h"></i>
                        </button>
                    </div>
                    <div class="ota-to-wrap">
                        <label class="ota-field-label" for="{{ $widgetId }}-to-display">To</label>
                        <div class="ota-input-shell">
                            <span class="ota-input-shell__icon" aria-hidden="true"><i class="fa fa-map-marker"></i></span>
                            <input class="ota-field ota-field--shell js-airport-autocomplete" id="{{ $widgetId }}-to-display" name="to_display" data-airport-display="to" data-hidden-target="{{ $widgetId }}-to" type="text" value="{{ $defaultDestinationDisplay }}" autocomplete="off" placeholder="City or airport" inputmode="text">
                        </div>
                        <input type="hidden" id="{{ $widgetId }}-to" name="to" data-airport-hidden="to" value="{{ $defaultDestination }}">
                        <div class="ota-airport-suggest" data-for="{{ $widgetId }}-to" data-airport-dropdown="to" role="listbox" aria-label="Airport suggestions"></div>
                    </div>
                </div>

                <div class="ota-search-dates {{ $defaultTripType === 'round_trip' ? 'ota-search-dates--round' : '' }}" data-search-dates>
                    <div class="ota-search-dates__field">
                        <label class="ota-field-label" for="{{ $widgetId }}-depart">Departure</label>
                        <div class="ota-input-shell ota-input-shell--date">
                            <input class="ota-field ota-field--date" id="{{ $widgetId }}-depart" name="depart" type="date" value="{{ $defaultDepart }}" min="{{ $minDate }}">
                            <span class="ota-input-shell__icon ota-input-shell__icon--end" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                        </div>
                    </div>
                    <div class="ota-search-dates__field" data-round-return style="{{ $defaultTripType !== 'round_trip' ? 'display:none;' : '' }}">
                        <label class="ota-field-label" for="{{ $widgetId }}-return">Return</label>
                        <div class="ota-input-shell ota-input-shell--date">
                            <input class="ota-field ota-field--date" id="{{ $widgetId }}-return" name="return_date" type="date" value="{{ $defaultReturnDate }}" min="{{ $defaultDepart ?: $minDate }}">
                            <span class="ota-input-shell__icon ota-input-shell__icon--end" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <div data-trip-panel="multi_city" style="{{ $defaultTripType !== 'multi_city' ? 'display:none;' : '' }}">
                <p class="ota-field-hint" style="margin-bottom:10px;">Add between 2 and 6 segments. Use IATA codes (e.g. LHE) or pick from suggestions.</p>
                <div data-multi-rows>
                    @for ($m = 0; $m < $multiCount; $m++)
                        <div class="ota-multiseg-row" style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">
                            <div class="ota-from-to-row">
                                <div class="ota-from-wrap">
                                    <label class="ota-field-label">From</label>
                                    <input class="ota-field js-airport-autocomplete" id="{{ $widgetId }}-mf-{{ $m }}-from-display" name="multi_from_display[]" data-hidden-target="{{ $widgetId }}-mf-{{ $m }}-from" type="text" value="{{ $multiFrom[$m] ?? '' }}" autocomplete="off" placeholder="City or airport">
                                    <input type="hidden" id="{{ $widgetId }}-mf-{{ $m }}-from" name="multi_from[]" value="{{ $multiFrom[$m] ?? '' }}">
                                    <div class="ota-airport-suggest" data-for="{{ $widgetId }}-mf-{{ $m }}-from" role="listbox"></div>
                                </div>
                                <div class="ota-to-wrap">
                                    <label class="ota-field-label">To</label>
                                    <input class="ota-field js-airport-autocomplete" id="{{ $widgetId }}-mf-{{ $m }}-to-display" name="multi_to_display[]" data-hidden-target="{{ $widgetId }}-mf-{{ $m }}-to" type="text" value="{{ $multiTo[$m] ?? '' }}" autocomplete="off" placeholder="City or airport">
                                    <input type="hidden" id="{{ $widgetId }}-mf-{{ $m }}-to" name="multi_to[]" value="{{ $multiTo[$m] ?? '' }}">
                                    <div class="ota-airport-suggest" data-for="{{ $widgetId }}-mf-{{ $m }}-to" role="listbox"></div>
                                </div>
                                <div>
                                    <label class="ota-field-label">Date</label>
                                    <input class="ota-field" name="multi_depart[]" type="date" value="{{ $multiDepart[$m] ?? '' }}" min="{{ $minDate }}">
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                    <button type="button" class="btn btn-default btn-sm" data-multi-add>Add segment</button>
                    <button type="button" class="btn btn-default btn-sm" data-multi-remove>Remove last segment</button>
                </div>
            </div>

            <div class="ota-search-pax-grid">
                <div class="ota-select-shell">
                    <label class="ota-field-label" for="{{ $widgetId }}-cabin">Cabin</label>
                    <div class="ota-select-shell__inner">
                        <span class="ota-select-shell__icon" aria-hidden="true"><i class="fa fa-plane"></i></span>
                        <select class="ota-field ota-field--shell ota-field--select" id="{{ $widgetId }}-cabin" name="cabin">
                            <option value="economy" @selected(old('cabin', 'economy') === 'economy')>Economy</option>
                            <option value="premium_economy" @selected(old('cabin') === 'premium_economy')>Premium Economy</option>
                            <option value="business" @selected(old('cabin') === 'business')>Business</option>
                            <option value="first" @selected(old('cabin') === 'first')>First</option>
                        </select>
                        <span class="ota-select-shell__chev" aria-hidden="true"><i class="fa fa-angle-down"></i></span>
                    </div>
                </div>
                <div class="ota-select-shell">
                    <label class="ota-field-label" for="{{ $widgetId }}-adults">Adults</label>
                    <div class="ota-select-shell__inner">
                        <span class="ota-select-shell__icon" aria-hidden="true"><i class="fa fa-user"></i></span>
                        <select class="ota-field ota-field--shell ota-field--select" id="{{ $widgetId }}-adults" name="adults">
                            @for ($a = 1; $a <= 9; $a++)
                                <option value="{{ $a }}" @selected((int) old('adults', 1) === $a)>{{ $a }}</option>
                            @endfor
                        </select>
                        <span class="ota-select-shell__chev" aria-hidden="true"><i class="fa fa-angle-down"></i></span>
                    </div>
                </div>
                <div class="ota-select-shell">
                    <label class="ota-field-label" for="{{ $widgetId }}-children">Children</label>
                    <div class="ota-select-shell__inner">
                        <span class="ota-select-shell__icon" aria-hidden="true"><i class="fa fa-child"></i></span>
                        <select class="ota-field ota-field--shell ota-field--select" id="{{ $widgetId }}-children" name="children">
                            @for ($c = 0; $c <= 8; $c++)
                                <option value="{{ $c }}" @selected((int) old('children', 0) === $c)>{{ $c }}</option>
                            @endfor
                        </select>
                        <span class="ota-select-shell__chev" aria-hidden="true"><i class="fa fa-angle-down"></i></span>
                    </div>
                </div>
                <div class="ota-select-shell">
                    <label class="ota-field-label" for="{{ $widgetId }}-infants">Infants</label>
                    <div class="ota-select-shell__inner">
                        <span class="ota-select-shell__icon" aria-hidden="true"><i class="fa fa-smile-o"></i></span>
                        <select class="ota-field ota-field--shell ota-field--select" id="{{ $widgetId }}-infants" name="infants">
                            @for ($i = 0; $i <= 9; $i++)
                                <option value="{{ $i }}" @selected((int) old('infants', 0) === $i)>{{ $i }}</option>
                            @endfor
                        </select>
                        <span class="ota-select-shell__chev" aria-hidden="true"><i class="fa fa-angle-down"></i></span>
                    </div>
                </div>
            </div>

            <div class="ota-flight-info-bar" role="note">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                <span>Adults min 1 · total passengers max 9 · infants cannot exceed adults</span>
            </div>

            <button type="submit" class="btn ota-search-submit" data-flight-search-submit>
                <i class="fa fa-search" aria-hidden="true"></i> Search flights
            </button>
            @if($showIntro)
                <p class="ota-search-card-footnote">{{ $flightNote }}</p>
            @endif
        </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function () {
  var widgets = Array.prototype.slice.call(document.querySelectorAll('[data-airport-widget]'));
  if (!widgets.length) return;

  widgets.forEach(function (widget) {
    if (widget.getAttribute('data-autocomplete-initialized') === 'true') return;
    widget.setAttribute('data-autocomplete-initialized', 'true');

    var minDate = widget.getAttribute('data-min-date') || '';

    function syncTripHidden(val) {
      var el = widget.querySelector('input[name="trip_type"]');
      if (el) el.value = val;
      widget.setAttribute('data-trip-type', val);
    }

    function airportSuggestBox(input) {
      var cell = input.closest('.ota-from-wrap, .ota-to-wrap');
      if (cell) return cell.querySelector('.ota-airport-suggest');
      var row = input.closest('.ota-multiseg-row');
      return row ? row.querySelector('.ota-airport-suggest') : null;
    }

    function setTripType(mode) {
      syncTripHidden(mode);
      var owPanel = widget.querySelector('[data-trip-panel="one_way"]');
      var mcPanel = widget.querySelector('[data-trip-panel="multi_city"]');
      var rr = widget.querySelector('[data-round-return]');
      var datesRow = widget.querySelector('[data-search-dates]');
      if (datesRow) datesRow.classList.toggle('ota-search-dates--round', mode === 'round_trip');
      if (mode === 'multi_city') {
        if (owPanel) owPanel.style.display = 'none';
        if (mcPanel) mcPanel.style.display = '';
      } else {
        if (owPanel) owPanel.style.display = '';
        if (mcPanel) mcPanel.style.display = 'none';
        if (rr) rr.style.display = (mode === 'round_trip') ? '' : 'none';
      }
      widget.querySelectorAll('.ota-search-tabs .ota-tab').forEach(function (tab) {
        tab.classList.toggle('ota-tab-active', tab.getAttribute('data-trip-tab') === mode);
      });
    }

    widget.querySelectorAll('[data-trip-tab]').forEach(function (tab) {
      tab.addEventListener('click', function () {
        setTripType(tab.getAttribute('data-trip-tab'));
      });
    });

    var initial = widget.getAttribute('data-trip-type') || 'one_way';
    setTripType(initial);

    var departIn = widget.querySelector('input[name="depart"]');
    var returnIn = widget.querySelector('input[name="return_date"]');
    function bumpReturnMin() {
      if (!departIn || !returnIn) return;
      var d = departIn.value || minDate;
      returnIn.min = d;
      if (returnIn.value && returnIn.value < d) returnIn.value = d;
    }
    if (departIn) departIn.addEventListener('change', bumpReturnMin);
    bumpReturnMin();

    var multiRows = widget.querySelector('[data-multi-rows]');
    var multiAdd = widget.querySelector('[data-multi-add]');
    var multiRemove = widget.querySelector('[data-multi-remove]');
    var multiIdx = (multiRows ? multiRows.querySelectorAll('.ota-multiseg-row').length : 0) || 2;

    function bindMultiSuggestIds(row, idx) {
      var ins = row.querySelectorAll('.js-airport-autocomplete');
      ins.forEach(function (inp, i) {
        var sid = widget.getAttribute('data-airport-widget') + '-m' + idx + '-' + i;
        inp.id = sid;
        var box = airportSuggestBox(inp);
        if (box) box.setAttribute('data-for', sid);
      });
    }

    if (multiAdd && multiRows) {
      multiAdd.addEventListener('click', function () {
        var rows = multiRows.querySelectorAll('.ota-multiseg-row');
        if (rows.length >= 6) return;
        var row = document.createElement('div');
        row.className = 'ota-multiseg-row';
        row.style.marginBottom = '12px';
        row.style.paddingBottom = '12px';
        row.style.borderBottom = '1px solid #e2e8f0';
        multiIdx++;
        row.innerHTML = '<div class="ota-from-to-row">' +
          '<div class="ota-from-wrap"><label class="ota-field-label">From</label>' +
          '<input class="ota-field js-airport-autocomplete" name="multi_from_display[]" data-hidden-target="' + widget.getAttribute('data-airport-widget') + '-m' + multiIdx + '-0-hidden" type="text" autocomplete="off" placeholder="City or airport">' +
          '<input type="hidden" id="' + widget.getAttribute('data-airport-widget') + '-m' + multiIdx + '-0-hidden" name="multi_from[]">' +
          '<div class="ota-airport-suggest" role="listbox"></div></div>' +
          '<div class="ota-to-wrap"><label class="ota-field-label">To</label>' +
          '<input class="ota-field js-airport-autocomplete" name="multi_to_display[]" data-hidden-target="' + widget.getAttribute('data-airport-widget') + '-m' + multiIdx + '-1-hidden" type="text" autocomplete="off" placeholder="City or airport">' +
          '<input type="hidden" id="' + widget.getAttribute('data-airport-widget') + '-m' + multiIdx + '-1-hidden" name="multi_to[]">' +
          '<div class="ota-airport-suggest" role="listbox"></div></div>' +
          '<div><label class="ota-field-label">Date</label>' +
          '<input class="ota-field" name="multi_depart[]" type="date" min="' + minDate + '"></div></div>';
        multiRows.appendChild(row);
        bindMultiSuggestIds(row, multiIdx);
        wireAutocomplete(row);
      });
    }

    if (multiRemove && multiRows) {
      multiRemove.addEventListener('click', function () {
        var rows = multiRows.querySelectorAll('.ota-multiseg-row');
        if (rows.length <= 2) return;
        rows[rows.length - 1].remove();
      });
    }

    var swap = widget.querySelector('[data-swap-routes]');

    var activeBox = null;
    var activeItems = [];
    var activeIndex = -1;
    var timers = new WeakMap();
    var controllers = new WeakMap();

    function closeAll() {
      widget.querySelectorAll('.ota-airport-suggest').forEach(function (box) {
        box.innerHTML = '';
        box.style.display = 'none';
      });
      activeBox = null;
      activeItems = [];
      activeIndex = -1;
    }

    function abortInputRequest(input) {
      var c = controllers.get(input);
      if (c) c.abort();
      controllers.delete(input);
    }

    function renderSuggestions(input, items) {
      var box = airportSuggestBox(input);
      if (!box) return;
      activeBox = box;
      box.innerHTML = '';
      if (!items.length) {
        box.style.display = 'none';
        return;
      }

      items.slice(0, 10).forEach(function (item, index) {
        var code = (item.iata || item.iata_code || '').toUpperCase();
        if (!code) return;
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'ota-airport-item';
        row.setAttribute('role', 'option');
        row.setAttribute('data-airport-option', '1');
        row.setAttribute('data-iata', code);
        row.setAttribute('data-index', String(index));
        row.setAttribute('data-code', code);
        row.setAttribute('aria-selected', 'false');
        row.innerHTML =
          '<span class="ota-airport-item-code">' + code + '</span>' +
          '<span class="ota-airport-item-main">' + (item.label || ((item.city || '') + ' (' + code + ')')) + '</span>' +
          '<span class="ota-airport-item-sub">' + (item.description || item.name || '') + '</span>';
        row.addEventListener('pointerdown', function (event) {
          event.preventDefault();
          var hiddenTarget = document.getElementById(input.getAttribute('data-hidden-target'));
          input.value = (item.label || code);
          input.setAttribute('data-selected-iata', code);
          if (hiddenTarget) hiddenTarget.value = code;
          closeAll();
        });
        box.appendChild(row);
      });

      activeItems = Array.prototype.slice.call(box.querySelectorAll('.ota-airport-item'));
      activeIndex = -1;
      box.style.display = activeItems.length ? 'block' : 'none';
    }

    function fetchSuggestions(input) {
      var query = (input.value || '').trim();
      if (query.length < 2) {
        abortInputRequest(input);
        closeAll();
        return;
      }

      abortInputRequest(input);
      var controller = new AbortController();
      controllers.set(input, controller);

      fetch('/airports/search?q=' + encodeURIComponent(query) + '&limit=10', {
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        signal: controller.signal
      })
        .then(function (r) { return r.ok ? r.json() : []; })
        .then(function (items) {
          if (document.activeElement !== input) return;
          renderSuggestions(input, Array.isArray(items) ? items : []);
        })
        .catch(function (error) {
          if (error && error.name === 'AbortError') return;
          closeAll();
        });
    }

    function moveHighlight(delta) {
      if (!activeItems.length) return;
      activeIndex += delta;
      if (activeIndex < 0) activeIndex = activeItems.length - 1;
      if (activeIndex >= activeItems.length) activeIndex = 0;
      activeItems.forEach(function (item) {
        item.classList.remove('is-active');
        item.setAttribute('aria-selected', 'false');
      });
      activeItems[activeIndex].classList.add('is-active');
      activeItems[activeIndex].setAttribute('aria-selected', 'true');
      if (typeof activeItems[activeIndex].scrollIntoView === 'function') {
        activeItems[activeIndex].scrollIntoView({ block: 'nearest' });
      }
    }

    function wireAutocomplete(root) {
      var scope = root || widget;
      var localInputs = Array.prototype.slice.call(scope.querySelectorAll('.js-airport-autocomplete'));
      localInputs.forEach(function (input) {
        if (input.getAttribute('data-ac-bound') === '1') return;
        input.setAttribute('data-ac-bound', '1');

        input.addEventListener('input', function () {
          var hiddenTarget = document.getElementById(input.getAttribute('data-hidden-target'));
          var selected = input.getAttribute('data-selected-iata');
          if (selected && input.value.indexOf(selected) === -1) {
            input.removeAttribute('data-selected-iata');
            if (hiddenTarget) hiddenTarget.value = '';
          }
          var t = timers.get(input);
          if (t) window.clearTimeout(t);
          var newT = window.setTimeout(function () { fetchSuggestions(input); }, 180);
          timers.set(input, newT);
        });

        input.addEventListener('focus', function () {
          if ((input.value || '').trim().length >= 2) fetchSuggestions(input);
        });

        input.addEventListener('blur', function () {
          window.setTimeout(closeAll, 120);
          var raw = (input.value || '').trim();
          if (raw === '') {
            var hidden = document.getElementById(input.getAttribute('data-hidden-target'));
            if (hidden) hidden.value = '';
            input.removeAttribute('data-selected-iata');
          }
        });

        input.addEventListener('keydown', function (event) {
          if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (!activeItems.length) {
              fetchSuggestions(input);
              return;
            }
            moveHighlight(1);
          } else if (event.key === 'ArrowUp') {
            if (!activeItems.length) return;
            event.preventDefault();
            moveHighlight(-1);
          } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            activeItems[activeIndex].click();
          } else if (event.key === 'Escape') {
            closeAll();
          }
        });
      });
    }

    wireAutocomplete(widget);

    if (swap) {
      swap.addEventListener('click', function () {
        var fromDisplay = widget.querySelector('input[name="from_display"]');
        var toDisplay = widget.querySelector('input[name="to_display"]');
        var fromHidden = widget.querySelector('input[name="from"]');
        var toHidden = widget.querySelector('input[name="to"]');
        if (fromDisplay && toDisplay) {
          var tDisplay = fromDisplay.value;
          fromDisplay.value = toDisplay.value;
          toDisplay.value = tDisplay;
        }
        if (fromHidden && toHidden) {
          var tHidden = fromHidden.value;
          fromHidden.value = toHidden.value;
          toHidden.value = tHidden;
        }
      });
    }

    var form = widget.querySelector('form');
    if (form) {
      form.addEventListener('submit', function (event) {
        var tripType = (widget.querySelector('input[name="trip_type"]') || {}).value || 'one_way';
        if (tripType !== 'multi_city') {
          var fromDisplay = widget.querySelector('input[name="from_display"]');
          var toDisplay = widget.querySelector('input[name="to_display"]');
          var fromHidden = widget.querySelector('input[name="from"]');
          var toHidden = widget.querySelector('input[name="to"]');
          if (fromDisplay && toDisplay && fromHidden && toHidden) {
            if (fromDisplay.value.trim() !== '' && fromHidden.value.trim() === '') {
              event.preventDefault();
              alert('Please select a valid origin airport from the dropdown.');
              fromDisplay.focus();
              return;
            }
            if (toDisplay.value.trim() !== '' && toHidden.value.trim() === '') {
              event.preventDefault();
              alert('Please select a valid destination airport from the dropdown.');
              toDisplay.focus();
              return;
            }
            if (fromHidden.value.trim() !== '' && fromHidden.value.trim() === toHidden.value.trim()) {
              event.preventDefault();
              alert('Origin and destination cannot be the same.');
              toDisplay.focus();
            }
          }
        }
      });
    }

    document.addEventListener('click', function (event) {
      if (!widget.contains(event.target)) closeAll();
    });
  });
})();
</script>
@endpush
