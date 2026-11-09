@php
    $editing = isset($permission);
    $system = $system ?? false;
    $checked = collect(old('roles', $editing ? $permission->roles->pluck('id')->all() : []))->map(fn ($v) => (int) $v);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nama Permission <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $editing ? $permission->name : '') }}" maxlength="64" placeholder="contoh: export laporan"
            {{ $system ? 'disabled' : 'required' }}>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($system)
            <small class="text-muted">Permission sistem: nama dikunci, hanya role pemiliknya yang bisa diatur.</small>
        @else
            <small class="text-muted">Permission baru perlu dicek di kode (policy / <code>@@can</code>) agar berfungsi.</small>
        @endif
    </div>

    <div class="col-12">
        <label class="form-label d-block">Diberikan ke Role</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($roles as $role)
                @php $isAdmin = in_array($role->name, App\Http\Controllers\RoleController::LOCKED, true); @endphp
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="role{{ $role->id }}" name="roles[]" value="{{ $role->id }}"
                        @checked($isAdmin || $checked->contains($role->id)) @disabled($isAdmin)>
                    <label class="form-check-label" for="role{{ $role->id }}">
                        {{ $role->name }} @if ($isAdmin)<small class="text-muted">(selalu)</small>@endif
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>
