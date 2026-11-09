@php $editing = isset($customer) @endphp

<div class="row g-3">
    @if ($editing)
        <div class="col-md-4">
            <label class="form-label">Kode Member</label>
            <input type="text" class="form-control" value="{{ $customer->kode_member }}" disabled>
        </div>
    @endif
    <div class="{{ $editing ? 'col-md-8' : 'col-12' }}">
        <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror"
            value="{{ old('nama', $editing ? $customer->nama : '') }}" maxlength="255"
            placeholder="Nama pelanggan / kolektor" required>
        @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="telepon" class="form-label">Telepon / WhatsApp</label>
        <input type="text" name="telepon" id="telepon" class="form-control @error('telepon') is-invalid @enderror"
            value="{{ old('telepon', $editing ? $customer->telepon : '') }}" maxlength="30" placeholder="08xxxxxxxxxx">
        @error('telepon')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $editing ? $customer->email : '') }}" maxlength="255" placeholder="nama@email.com">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="alamat" class="form-label">Alamat</label>
        <textarea name="alamat" id="alamat" rows="3" maxlength="500"
            class="form-control @error('alamat') is-invalid @enderror"
            placeholder="Alamat (opsional, untuk pengiriman PO)">{{ old('alamat', $editing ? $customer->alamat : '') }}</textarea>
        @error('alamat')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
@if (!$editing)
    <p class="small text-muted mt-2 mb-0">Kode member dibuat otomatis setelah disimpan.</p>
@endif
