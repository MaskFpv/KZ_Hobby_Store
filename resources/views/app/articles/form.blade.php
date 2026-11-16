{{-- Form artikel (tulis & edit). Editor: TinyMCE 7 (GPL), HTML dibersihkan server sebelum disimpan. --}}
@php $editing = $article->exists; @endphp

<form method="POST" action="{{ $editing ? route('articles.update', $article) : route('articles.store') }}" enctype="multipart/form-data" class="article-form" data-article-form>
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card p-3 p-md-4">
                <input type="text" name="title" value="{{ old('title', $article->title) }}" maxlength="160"
                    class="form-control form-control-lg article-title-input @error('title') is-invalid @enderror" placeholder="Judul artikel..." aria-label="Judul artikel">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <textarea name="excerpt" rows="2" maxlength="300" class="form-control mt-3 @error('excerpt') is-invalid @enderror"
                    placeholder="Ringkasan singkat (tampil di kartu & awal artikel, opsional)">{{ old('excerpt', $article->excerpt) }}</textarea>
                @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="mt-3">
                    <textarea name="body" id="body" class="form-control @error('body') is-invalid @enderror" rows="18">{{ old('body', $article->body) }}</textarea>
                    @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <small class="text-muted d-block mt-2">
                        Tips: pakai <b>Judul Bagian</b> (H2) untuk tiap bagian — otomatis jadi daftar isi di website. Gambar bisa di-drag langsung ke editor (maks 3 MB).
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card p-3 p-md-4 article-side">
                <label class="form-label">Status</label>
                <div class="status-pills mb-3">
                    @foreach (App\Models\Article::STATUSES as $value => $label)
                        <label class="status-pill">
                            <input type="radio" name="status" value="{{ $value }}" @checked(old('status', $article->status) === $value)>
                            <span><i class="bi {{ $value === 'published' ? 'bi-broadcast' : 'bi-pencil-square' }}"></i> {{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <label for="published_at" class="form-label">Waktu terbit <small class="text-muted">(kosong = sekarang)</small></label>
                <input type="datetime-local" id="published_at" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}"
                    class="form-control mb-3 @error('published_at') is-invalid @enderror">

                <label for="article_category_id" class="form-label d-flex justify-content-between">
                    Kategori
                    @can('create', App\Models\ArticleCategory::class)
                        <a href="{{ route('article-categories.index') }}" target="_blank" class="small fw-normal">+ kelola kategori</a>
                    @endcan
                </label>
                <select id="article_category_id" name="article_category_id" class="form-select mb-3 @error('article_category_id') is-invalid @enderror">
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('article_category_id', $article->article_category_id) == $c->id)>{{ $c->kanji_or_default }} · {{ $c->name }}</option>
                    @endforeach
                </select>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_pinned" name="is_pinned" value="1" @checked(old('is_pinned', $article->is_pinned))>
                    <label class="form-check-label" for="is_pinned"><i class="bi bi-pin-angle"></i> Sorot di halaman Artikel <small class="text-muted d-block">Hanya satu artikel yang disorot; tanpa sorotan = artikel terbaru.</small></label>
                </div>

                <label for="cover" class="form-label">Sampul <small class="text-muted">(16:9, maks 3 MB)</small></label>
                <div class="cover-preview mb-2">
                    @if ($article->cover_url)
                        <img src="{{ $article->cover_url }}" alt="Sampul" id="cover-preview">
                    @else
                        <img alt="" id="cover-preview" class="d-none">
                        <div class="text-muted small text-center py-4" id="cover-empty"><i class="bi bi-image fs-3 d-block"></i>Tanpa sampul: memakai gradasi kategori</div>
                    @endif
                </div>
                <input type="file" id="cover" name="cover" accept="image/png,image/jpeg,image/webp" class="form-control @error('cover') is-invalid @enderror">
                @error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($article->cover)
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="remove_cover">
                        <label class="form-check-label" for="remove_cover">Hapus sampul</label>
                    </div>
                @endif

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                    @if ($editing && $article->isPublic())
                        <a href="{{ route('store.article', $article->slug) }}" target="_blank" class="btn btn-light"><i class="bi bi-box-arrow-up-right"></i> Lihat di website</a>
                    @endif
                    <a href="{{ route('articles.index') }}" class="btn btn-light">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js"></script>
    <script>
        (function () {
            const dark = document.documentElement.getAttribute('data-theme') === 'dark';
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            tinymce.init({
                selector: '#body',
                license_key: 'gpl',
                height: 620,
                menubar: false,
                branding: false,
                promotion: false,
                skin: dark ? 'oxide-dark' : 'oxide',
                content_css: dark ? 'dark' : 'default',
                plugins: 'autolink link image media table lists advlist code fullscreen preview wordcount quickbars charmap emoticons',
                toolbar: 'undo redo | blocks | bold italic underline strikethrough forecolor | alignleft aligncenter alignright | bullist numlist blockquote hr | link image media table | emoticons charmap | removeformat code preview fullscreen',
                block_formats: 'Paragraf=p; Judul Bagian=h2; Sub Judul=h3; Sub-sub Judul=h4; Kode=pre',
                quickbars_selection_toolbar: 'bold italic | h2 h3 | blockquote link',
                quickbars_insert_toolbar: false,
                image_caption: true,
                image_dimensions: false,
                link_default_target: '_blank',
                automatic_uploads: true,
                relative_urls: false,
                remove_script_host: false,
                // Unggah gambar ke server (storage/app/public/articles/inline)
                images_upload_handler: (blobInfo) => {
                    const form = new FormData();
                    form.append('file', blobInfo.blob(), blobInfo.filename());
                    return fetch(@json(route('articles.upload-image')), { method: 'POST', body: form, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                        .then((r) => r.ok ? r.json() : r.json().then((e) => Promise.reject(e.message || 'Gagal mengunggah gambar')))
                        .then((json) => json.location);
                },
                content_style: 'body { font-family: "M PLUS Rounded 1c", system-ui, sans-serif; font-size: 16px; line-height: 1.8; max-width: 760px; margin: 1rem auto; } img { max-width: 100%; height: auto; border-radius: 12px; } blockquote { border-left: 3px solid #ff4f8b; margin-left: 0; padding-left: 1rem; font-style: italic; }',
            });

            // Pratinjau sampul
            document.getElementById('cover').addEventListener('change', function () {
                if (!this.files[0]) return;
                const img = document.getElementById('cover-preview');
                img.src = URL.createObjectURL(this.files[0]);
                img.classList.remove('d-none');
                document.getElementById('cover-empty')?.remove();
            });
        })();
    </script>
@endpush
