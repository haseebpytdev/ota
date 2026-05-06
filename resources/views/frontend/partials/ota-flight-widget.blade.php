@php
    $variant = $variant ?? 'hero';
    $showIntro = $show_intro ?? true;
    $flightNote = 'Fares are shown based on current airline availability and airline confirmation.';
    $widgetId = 'fw-'.substr(md5((string) microtime(true).(string) random_int(1000, 999999)), 0, 8);
    $minDate = $minDate ?? now()->format('Y-m-d');
    $defaultTripType = old('trip_type', $defaultTripType ?? 'one_way');
    $defaultOrigin = old('from', $defaultOrigin ?? '');
    $defaultDestination = old('to', $defaultDestination ?? '');
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
        <div class="ota-search-card-head">
            <h3 class="ota-search-card-title">Search flights</h3>
            @if($showIntro)
                <p class="ota-search-note">{{ $flightNote }}</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom:12px;">
                <strong>Please fix the following:</strong>
                <ul style="margin:8px 0 0 18px;">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="ota-search-tabs" role="tablist">
            <button type="button" class="ota-tab {{ $defaultTripType === 'one_way' ? 'ota-tab-active' : '' }}" data-trip-tab="one_way">One way</button>
            <button type="button" class="ota-tab {{ $defaultTripType === 'round_trip' ? 'ota-tab-active' : '' }}" data-trip-tab="round_trip">Round trip</button>
            <button type="button" class="ota-tab {{ $defaultTripType === 'multi_city' ? 'ota-tab-active' : '' }}" data-trip-tab="multi_city">Multi-city</button>
        </div>

        <form method="get" action="{{ route('flights.results') }}" class="ota-flight-form" id="{{ $widgetId }}-form" novalidate>
            <input type="hidden" name="trip_type" id="{{ $widgetId }}-trip-type" value="{{ $defaultTripType }}">
            <div data-trip-panel="one_way" style="{{ $defaultTripType !== 'one_way' && $defaultTripType !== 'round_trip' ? 'display:none;' : '' }}">
                <div class="ota-from-to-row">
                    <div class="ota-from-wrap">
                        <label class="ota-field-label" for="{{ $widgetId }}-from">From</label>
                        <input class="ota-field js-airport-autocomplete" id="{{ $widgetId }}-from" name="from" type="text" value="{{ $defaultOrigin }}" autocomplete="off" placeholder="City or airport" inputmode="text">
                        <div class="ota-airport-suggest" data-for="{{ $widgetId }}-from" role="listbox" aria-label="Airport suggestions"></div>
                    </div>
                    <div class="ota-swap-wrap">
                        <span class="ota-field-label" style="visibility:hidden;">Swap</span>
                        <button type="button" class="ota-swap-btn" data-swap-routes title="Swap from / to" aria-label="Swap from and to airports">
                            <i class="fa fa-exchange"></i>
                        </button>
                    </div>
                    <div class="ota-to-wrap">
                        <label class="ota-field-label" for="{{ $widgetId }}-to">To</label>
                        <input class="ota-field js-airport-autocomplete" id="{{ $widgetId }}-to" name="to" type="text" value="{{ $defaultDestination }}" autocomplete="off" placeholder="City or airport" inputmode="text">
                        <div class="ota-airport-suggest" data-for="{{ $widgetId }}-to" role="listbox" aria-label="Airport suggestions"></div>
                    </div>
                </div>

                <div class="ota-search-grid2">
                    <div>
                        <label class="ota-field-label" for="{{ $widgetId }}-depart">Departure</label>
                        <input class="ota-field" id="{{ $widgetId }}-depart" name="depart" type="date" value="{{ $defaultDepart }}" min="{{ $minDate }}" placeholder="Select date">
                    </div>
                    <div data-round-return style="{{ $defaultTripType !== 'round_trip' ? 'display:none;' : '' }}">
                        <label class="ota-field-label" for="{{ $widgetId }}-return">Return</label>
                        <input class="ota-field" id="{{ $widgetId }}-return" name="return_date" type="date" value="{{ $defaultReturnDate }}" min="{{ $defaultDepart ?: $minDate }}" placeholder="Select return date">
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
                                    <input class="ota-field js-airport-autocomplete" id="{{ $widgetId }}-mf-{{ $m }}-from" name="multi_from[]" type="text" value="{{ $multiFrom[$m] ?? '' }}" autocomplete="off" placeholder="City or airport">
                                    <div class="ota-airport-suggest" data-for="{{ $widgetId }}-mf-{{ $m }}-from" role="listbox"></div>
                                </div>
                                <div class="ota-to-wrap">
                                    <label class="ota-field-label">To</label>
                                    <input class="ota-field js-airport-autocomplete" id="{{ $widgetId }}-mf-{{ $m }}-to" name="multi_to[]" type="text" value="{{ $multiTo[$m] ?? '' }}" autocomplete="off" placeholder="City or airport">
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

            <div class="ota-search-grid2" style="margin-top:4px;">
                <div>
                    <label class="ota-field-label" for="{{ $widgetId }}-cabin">Cabin</label>
                    <select class="ota-field" id="{{ $widgetId }}-cabin" name="cabin">
                        <option value="economy" @selected(old('cabin', 'economy') === 'economy')>Economy</option>
                        <option value="premium_economy" @selected(old('cabin') === 'premium_economy')>Premium Economy</option>
                        <option value="business" @selected(old('cabin') === 'business')>Business</option>
                        <option value="first" @selected(old('cabin') === 'first')>First</option>
                    </select>
                </div>
                <div>
                    <label class="ota-field-label" for="{{ $widgetId }}-adults">Adults</label>
                    <select class="ota-field" id="{{ $widgetId }}-adults" name="adults">
                        @for ($a = 1; $a <= 9; $a++)
                            <option value="{{ $a }}" @selected((int) old('adults', 1) === $a)>{{ $a }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="ota-field-label" for="{{ $widgetId }}-children">Children</label>
                    <select class="ota-field" id="{{ $widgetId }}-children" name="children">
                        @for ($c = 0; $c <= 8; $c++)
                            <option value="{{ $c }}" @selected((int) old('children', 0) === $c)>{{ $c }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="ota-field-label" for="{{ $widgetId }}-infants">Infants</label>
                    <select class="ota-field" id="{{ $widgetId }}-infants" name="infants">
                        @for ($i = 0; $i <= 9; $i++)
                            <option value="{{ $i }}" @selected((int) old('infants', 0) === $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <p class="ota-field-hint">Adults min 1 · total passengers max 9 · infants cannot exceed adults (validated on search).</p>

            <button type="submit" class="btn ota-search-submit">
                <i class="fa fa-search"></i> Search flights
            </button>
        </form>
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

    function setTripType(mode) {
      syncTripHidden(mode);
      var owPanel = widget.querySelector('[data-trip-panel="one_way"]');
      var mcPanel = widget.querySelector('[data-trip-panel="multi_city"]');
      var rr = widget.querySelector('[data-round-return]');
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
        var box = inp.parentNode.querySelector('.ota-airport-suggest');
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
          '<input class="ota-field js-airport-autocomplete" name="multi_from[]" type="text" autocomplete="off" placeholder="City or airport">' +
          '<div class="ota-airport-suggest" role="listbox"></div></div>' +
          '<div class="ota-to-wrap"><label class="ota-field-label">To</label>' +
          '<input class="ota-field js-airport-autocomplete" name="multi_to[]" type="text" autocomplete="off" placeholder="City or airport">' +
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
      var box = input.parentNode.querySelector('.ota-airport-suggest');
      if (!box) return;
      activeBox = box;
      box.innerHTML = '';
      if (!items.length) {
        box.style.display = 'none';
        return;
      }

      items.slice(0, 10).forEach(function (item, index) {
        var code = (item.iata_code || '').toUpperCase();
        if (!code) return;
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'ota-airport-item';
        row.setAttribute('role', 'option');
        row.setAttribute('data-index', String(index));
        row.setAttribute('data-code', code);
        row.setAttribute('aria-selected', 'false');
        row.innerHTML =
          '<span class="ota-airport-item-code">' + code + '</span>' +
          '<span class="ota-airport-item-main">' + (item.city || '') + ' - ' + (item.country || '') + '</span>' +
          '<span class="ota-airport-item-sub">' + (item.name || '') + '</span>';
        row.addEventListener('click', function () {
          input.value = code;
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

      fetch('/airports/search?q=' + encodeURIComponent(query), {
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
          var t = timers.get(input);
          if (t) window.clearTimeout(t);
          var newT = window.setTimeout(function () { fetchSuggestions(input); }, 240);
          timers.set(input, newT);
        });

        input.addEventListener('focus', function () {
          if ((input.value || '').trim().length >= 2) fetchSuggestions(input);
        });

        input.addEventListener('blur', function () {
          window.setTimeout(closeAll, 120);
          var raw = (input.value || '').trim();
          if (/^[a-z0-9]{2,4}$/i.test(raw)) input.value = raw.toUpperCase();
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
        var oneWayInputs = widget.querySelectorAll('[data-trip-panel="one_way"] .js-airport-autocomplete');
        if (oneWayInputs.length >= 2) {
          var t = oneWayInputs[0].value;
          oneWayInputs[0].value = oneWayInputs[1].value;
          oneWayInputs[1].value = t;
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
