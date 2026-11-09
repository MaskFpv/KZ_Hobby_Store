@php $editing = isset($category) @endphp

<div class="row align-items-center g-3">
    <div class="col-md-12">
        <label for="name" class="form-label">Nama Kategori</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $editing ? $category->name : '') }}" maxlength="255"
            placeholder="Contoh: Action Figure, Gunpla, Manga" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-12">
        <label for="description" class="form-label">Deskripsi <small class="text-muted">(opsional)</small></label>
        <textarea name="description" id="description" rows="3" maxlength="1000"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Keterangan singkat kategori">{{ old('description', $editing ? $category->description : '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
