<form method="POST" action="{{ $action }}" class="card">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $rule->name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Rule type</label>
                <select name="rule_type" class="form-select" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('rule_type', $rule->rule_type?->value) === $type->value)>{{ str_replace('_', ' ', $type->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $rule->status?->value ?? 'active') === $status->value)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Value</label>
                <input type="number" step="0.0001" min="0" name="value" class="form-control" value="{{ old('value', $rule->value) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Value type</label>
                <select name="value_type" class="form-select" required>
                    @foreach ($valueTypes as $valueType)
                        <option value="{{ $valueType->value }}" @selected(old('value_type', $rule->value_type?->value ?? 'percentage') === $valueType->value)>{{ ucfirst($valueType->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <input type="number" min="1" name="priority" class="form-control" value="{{ old('priority', $rule->priority ?: 100) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Starts at</label>
                <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', $rule->starts_at?->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ends at</label>
                <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at', $rule->ends_at?->format('Y-m-d')) }}">
            </div>
            <div class="col-md-9">
                <label class="form-label">Applies to (JSON)</label>
                <input type="text" name="applies_to" class="form-control" value="{{ old('applies_to', $rule->applies_to ? json_encode($rule->applies_to) : '') }}" placeholder='{"route":"LHE-DXB"}'>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="meta_notes" class="form-control" rows="3">{{ old('meta_notes', $rule->meta['notes'] ?? '') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('admin.markups') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save rule</button>
    </div>
</form>
