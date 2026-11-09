@php $editing = isset($product) @endphp

<div class="row g-3">
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-md-8">
                <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $editing ? $product->name : '') }}" maxlength="255"
                    placeholder="Contoh: Nendoroid Frieren" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="sku" class="form-label">SKU</label>
                <input type="text" name="sku" id="sku" class="form-control text-uppercase @error('sku') is-invalid @enderror"
                    value="{{ old('sku', $editing ? $product->sku : '') }}" maxlength="64"
                    placeholder="Kosongkan = otomatis">
                @error('sku')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}" @selected(old('category_id', $editing ? $product->category_id : '') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="brand_id" class="form-label">Brand / Pabrikan <span class="text-danger">*</span></label>
                <select name="brand_id" id="brand_id" class="form-select @error('brand_id') is-invalid @enderror" required>
                    <option value="">Pilih brand</option>
                    @foreach ($brands as $id => $name)
                        <option value="{{ $id }}" @selected(old('brand_id', $editing ? $product->brand_id : '') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('brand_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="purchase_price" class="form-label">Harga Modal (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="purchase_price" id="purchase_price" min="0" step="1"
                    class="form-control @error('purchase_price') is-invalid @enderror"
                    value="{{ old('purchase_price', $editing ? (int) $product->purchase_price : '') }}" required>
                @error('purchase_price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="selling_price" class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="selling_price" id="selling_price" min="0" step="1"
                    class="form-control @error('selling_price') is-invalid @enderror"
                    value="{{ old('selling_price', $editing ? (int) $product->selling_price : '') }}" required>
                @error('selling_price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small id="margin-info" class="form-text"></small>
            </div>
            <div class="col-md-4">
                @if ($editing)
                    <label class="form-label">Stok Saat Ini</label>
                    <input type="text" class="form-control" value="{{ $product->stock }}" disabled>
                    <small class="form-text text-muted">Ubah lewat Stok Masuk / Stok Opname.</small>
                @else
                    <label for="stock" class="form-label">Stok Awal <span class="text-danger">*</span></label>
                    <input type="number" name="stock" id="stock" min="0" step="1"
                        class="form-control @error('stock') is-invalid @enderror"
                        value="{{ old('stock', 0) }}" required>
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label d-block">Kondisi <span class="text-danger">*</span></label>
                @foreach (App\Models\Product::CONDITIONS as $value => $label)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="condition" id="condition_{{ $value }}"
                            value="{{ $value }}" @checked(old('condition', $editing ? $product->condition : 'new') === $value)>
                        <label class="form-check-label" for="condition_{{ $value }}">{{ $label }}</label>
                    </div>
                @endforeach
                @error('condition')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Tipe <span class="text-danger">*</span></label>
                @foreach (App\Models\Product::TYPES as $value => $label)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="type" id="type_{{ $value }}"
                            value="{{ $value }}" @checked(old('type', $editing ? $product->type : 'ready_stock') === $value)>
                        <label class="form-check-label" for="type_{{ $value }}">{{ $label }}</label>
                    </div>
                @endforeach
                @error('type')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted d-block">Pre-Order tidak muncul di Kasir; dijual lewat Manajemen PO.</small>
            </div>

            <div class="col-12">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea name="description" id="description" rows="4" maxlength="2000"
                    class="form-control @error('description') is-invalid @enderror"
                    placeholder="Skala, seri, tanggal rilis, kelengkapan box, dll.">{{ old('description', $editing ? $product->description : '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <label for="image" class="form-label">Foto Produk <small class="text-muted">(jpg/png/webp, maks 2 MB)</small></label>
        <div class="mb-2 text-center border rounded p-2 bg-light">
            <img id="image-preview" src="{{ $editing ? $product->image_url : '' }}" alt="Preview foto"
                class="{{ $editing && $product->image ? '' : 'd-none' }}"
                style="max-width: 100%; height: 220px; object-fit: contain;">
            @if (!($editing && $product->image))
                <div class="text-muted py-5" id="image-placeholder"><i class="bi bi-image fs-1 d-block"></i>Belum ada foto</div>
            @endif
        </div>
        <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp"
            class="form-control @error('image') is-invalid @enderror"
            onchange="previewImage(this, 'image-preview'); document.getElementById('image-placeholder')?.remove();">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($editing && $product->image)
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                <label class="form-check-label" for="remove_image">Hapus foto</label>
            </div>
        @endif
    </div>
</div>

@include('components.partials.image-preview-script')

@push('scripts')
    <style>
        #sku::placeholder { text-transform: none; }
    </style>
    <script>
        // Tampilkan margin & peringatan bila harga jual < modal
        (function() {
            const buy = document.getElementById('purchase_price');
            const sell = document.getElementById('selling_price');
            const info = document.getElementById('margin-info');
            function update() {
                const b = parseFloat(buy.value) || 0, s = parseFloat(sell.value) || 0;
                if (!buy.value || !sell.value) { info.textContent = ''; return; }
                const m = s - b;
                const pct = b > 0 ? ' (' + (m / b * 100).toFixed(1) + '%)' : '';
                info.textContent = 'Margin: Rp ' + m.toLocaleString('id-ID') + pct;
                info.className = 'form-text ' + (m < 0 ? 'text-danger' : 'text-success');
            }
            buy.addEventListener('input', update);
            sell.addEventListener('input', update);
            update();
        })();
    </script>
@endpush
