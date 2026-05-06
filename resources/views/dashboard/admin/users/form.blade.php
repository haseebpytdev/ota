@php
    $u = $userModel;
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" value="{{ old('name', $u->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" value="{{ old('email', $u->email) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Account type</label>
        <select class="form-select" name="account_type" required>
            @foreach($accountTypeOptions as $opt)
                <option value="{{ $opt }}" @selected(old('account_type', $u->account_type?->value) === $opt)>{{ str_replace('_', ' ', $opt) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select class="form-select" name="status" required>
            @foreach(['active','invited','suspended','inactive'] as $st)
                <option value="{{ $st }}" @selected(old('status', $u->status?->value ?? 'active') === $st)>{{ $st }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone" value="{{ old('phone', $u->meta['phone'] ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Department</label>
        <input class="form-control" name="department" value="{{ old('department', $u->staffProfile?->department) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Role title</label>
        <input class="form-control" name="role_title" value="{{ old('role_title', $u->staffProfile?->job_title) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Permission group</label>
        <input class="form-control" name="permission_group" value="{{ old('permission_group', $u->meta['permission_group'] ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Agency name</label>
        <input class="form-control" name="agency_name" value="{{ old('agency_name', $u->agentProfile?->meta['agency_name'] ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">City</label>
        <input class="form-control" name="city" value="{{ old('city', $u->agentProfile?->meta['city'] ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Commission %</label>
        <input class="form-control" name="commission_percent" type="number" step="0.01" value="{{ old('commission_percent', $u->agentProfile?->commission_percent) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Agent code</label>
        <input class="form-control" name="agent_code" value="{{ old('agent_code', $u->agentProfile?->code) }}">
    </div>
    @if(! $isEdit)
        <div class="col-12">
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="send_invite" value="1" @checked(old('send_invite'))>
                <span class="form-check-label">Send invite/reset link after creating user</span>
            </label>
        </div>
    @endif
</div>
