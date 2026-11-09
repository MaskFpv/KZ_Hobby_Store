@php
    $editing = isset($role);
    $locked = $locked ?? false;
    $checked = collect(old('permissions', $editing ? $role->permissions->pluck('id')->all() : []))->map(fn ($v) => (int) $v);
    $actionLabels = ['list' => 'Lihat daftar', 'view' => 'Lihat detail', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus'];
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nama Role <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $editing ? $role->name : '') }}" maxlength="32" placeholder="contoh: kasir-senior"
            {{ $locked ? 'disabled' : 'required' }}>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($locked)
            <small class="text-muted">Role sistem: nama dikunci dan selalu memegang semua permission.</small>
        @endif
    </div>
</div>

@unless ($locked)
    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h3 class="h6 m-0">Hak Akses</h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('.perm-check').forEach(c => c.checked = true)">Pilih semua</button>
            <button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('.perm-check').forEach(c => c.checked = false)">Kosongkan</button>
        </div>
    </div>
    <div class="row g-3">
        @foreach ($permissionGroups as $group => $permissions)
            <div class="col-md-6 col-xl-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-bold mb-2">{{ $group }}</div>
                    @foreach ($permissions as $permission)
                        @php $action = explode(' ', $permission->name, 2)[0]; @endphp
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input perm-check" id="perm{{ $permission->id }}"
                                name="permissions[]" value="{{ $permission->id }}" @checked($checked->contains($permission->id))>
                            <label class="form-check-label" for="perm{{ $permission->id }}">
                                {{ $actionLabels[$action] ?? $permission->name }}
                                <small class="text-muted">({{ $permission->name }})</small>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endunless
