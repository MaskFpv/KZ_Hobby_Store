@php $editing = isset($user); @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $editing ? $user->name : '') }}" maxlength="255" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email (untuk login) <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $editing ? $user->email : '') }}" maxlength="255" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">Password {!! $editing ? '' : '<span class="text-danger">*</span>' !!}</label>
        <input type="password" name="password" id="password" autocomplete="new-password"
            class="form-control @error('password') is-invalid @enderror" {{ $editing ? '' : 'required' }}
            placeholder="{{ $editing ? 'Kosongkan bila tidak diganti' : 'Minimal 8 karakter' }}">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Ulangi Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="form-control">
    </div>

    <div class="col-12">
        <label class="form-label d-block">Role <span class="text-danger">*</span></label>
        @php $current = old('role', $editing ? $user->getRoleNames()->first() : 'kasir'); @endphp
        <div class="d-flex flex-wrap gap-3">
            @foreach ($roles as $role)
                <label class="border rounded-3 p-3 d-flex gap-2 align-items-start" style="min-width: 220px; cursor: pointer;">
                    <input class="form-check-input mt-1" type="radio" name="role" value="{{ $role }}" @checked($current === $role)>
                    <span>
                        <span class="fw-bold d-block">{{ App\Support\Roles::label($role) }}</span>
                        <small class="text-muted">{{ App\Support\Roles::description($role) }}</small>
                    </span>
                </label>
            @endforeach
        </div>
        @error('role')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>
