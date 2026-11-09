@php $editing = isset($brand) @endphp

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label">Nama Brand <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $editing ? $brand->name : '') }}" maxlength="255"
            placeholder="Contoh: Bandai, Good Smile Company" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label for="country" class="form-label">Negara Asal</label>
        <input type="text" name="country" id="country" class="form-control @error('country') is-invalid @enderror"
            value="{{ old('country', $editing ? $brand->country : '') }}" maxlength="100" placeholder="Jepang">
        @error('country')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-8">
        <label for="description" class="form-label">Deskripsi</label>
        <textarea name="description" id="description" rows="4" maxlength="1000"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Keterangan singkat brand">{{ old('description', $editing ? $brand->description : '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label for="logo" class="form-label">Logo <small class="text-muted">(jpg/png/webp, maks 2 MB)</small></label>
        <div class="mb-2">
            <img id="logo-preview" src="{{ $editing ? $brand->logo_url : '' }}" alt="Preview logo"
                class="border rounded {{ $editing && $brand->logo ? '' : 'd-none' }}"
                style="width: 120px; height: 120px; object-fit: contain;">
        </div>
        <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/webp"
            class="form-control @error('logo') is-invalid @enderror"
            onchange="previewImage(this, 'logo-preview')">
        @error('logo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($editing && $brand->logo)
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                <label class="form-check-label" for="remove_logo">Hapus logo</label>
            </div>
        @endif
    </div>
</div>

@include('components.partials.image-preview-script')
