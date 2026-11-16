{{-- Field kategori artikel. Param: $category --}}
<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="name">Nama kategori</label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" maxlength="60" class="form-control @error('name') is-invalid @enderror" placeholder="mis. Action Figure" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-4 col-md-2">
        <label class="form-label" for="kanji">Kanji <small class="text-muted">(hiasan)</small></label>
        <input id="kanji" name="kanji" value="{{ old('kanji', $category->kanji) }}" maxlength="4" class="form-control text-center fs-5" placeholder="像">
    </div>
    <div class="col-4 col-md-2">
        <label class="form-label" for="color">Warna</label>
        <input id="color" type="color" name="color" value="{{ old('color', $category->color) }}" class="form-control form-control-color w-100 @error('color') is-invalid @enderror">
    </div>
    <div class="col-12 col-md-3 d-flex align-items-end">
        <span class="cat-preview w-100" data-cat-preview style="--cat: {{ old('color', $category->color) }}">
            <b data-cat-kanji>{{ old('kanji', $category->kanji) ?: '記' }}</b><span data-cat-name>{{ old('name', $category->name) ?: 'Pratinjau' }}</span>
        </span>
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Deskripsi <small class="text-muted">(opsional, tampil di halaman Artikel saat kategori dipilih)</small></label>
        <input id="description" name="description" value="{{ old('description', $category->description) }}" maxlength="200" class="form-control">
    </div>
</div>

@push('scripts')
    <script>
        // Pratinjau chip kategori langsung saat nama/kanji/warna diubah
        (function () {
            const p = document.querySelector('[data-cat-preview]');
            const sync = () => {
                p.style.setProperty('--cat', document.getElementById('color').value);
                p.querySelector('[data-cat-kanji]').textContent = document.getElementById('kanji').value || '記';
                p.querySelector('[data-cat-name]').textContent = document.getElementById('name').value || 'Pratinjau';
            };
            ['name', 'kanji', 'color'].forEach((id) => document.getElementById(id).addEventListener('input', sync));
        })();
    </script>
@endpush
