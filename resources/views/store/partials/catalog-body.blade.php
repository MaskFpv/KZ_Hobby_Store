{{-- Isi katalog (filter + hasil). Dipakai halaman katalog dan respons fetch (header X-Catalog) agar filter tidak me-reload halaman. --}}
@php
    // URL katalog dengan satu filter diganti (null = hapus filter)
    $with = fn (array $changes) => route('store.catalog', array_filter(array_merge($filters, $changes, ['page' => null]), fn ($v) => $v !== null && $v !== ''));
    $chip = fn (string $key, ?string $value, string $label) => sprintf(
        '<a href="%s" class="chip %s" data-catalog-link>%s</a>',
        e($with([$key => $value])), ($filters[$key] ?? null) === $value ? 'active' : '', e($label)
    );
@endphp

<aside class="filters" aria-label="Filter produk" data-total="{{ $products->total() }}" data-lenis-prevent>
    <div class="filters-head">
        <b class="display">Filter</b>
        <button type="button" class="icon-btn" data-filters-toggle aria-label="Tutup filter"><i class="bi bi-x-lg"></i></button>
    </div>
    <div>
        <h3>Tipe</h3>
        <div class="chip-list">
            {!! $chip('tipe', null, 'Semua') !!}{!! $chip('tipe', 'ready_stock', 'Ready Stock') !!}{!! $chip('tipe', 'pre_order', 'Pre-Order') !!}
        </div>
    </div>
    <div>
        <h3>Kategori</h3>
        <div class="chip-list">
            {!! $chip('kategori', null, 'Semua') !!}
            @foreach ($categories as $c)
                {!! $chip('kategori', $c->slug, $c->name) !!}
            @endforeach
        </div>
    </div>
    <div>
        <h3>Brand</h3>
        <div class="chip-list">
            {!! $chip('brand', null, 'Semua') !!}
            @foreach ($brands as $b)
                {!! $chip('brand', $b->slug, $b->name) !!}
            @endforeach
        </div>
    </div>
    <div>
        <h3>Kondisi</h3>
        <div class="chip-list">
            {!! $chip('kondisi', null, 'Semua') !!}{!! $chip('kondisi', 'new', 'Baru (MIB)') !!}{!! $chip('kondisi', 'second', 'Second (BIB)') !!}
        </div>
    </div>
    <form action="{{ route('store.catalog') }}" method="GET" data-catalog-form>
        @foreach (array_diff_key($filters, ['min' => 1, 'max' => 1]) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <h3>Harga (Rp)</h3>
        <div class="form-grid cols-2" style="gap:.5rem">
            <input type="number" name="min" min="0" step="10000" value="{{ $filters['min'] ?? '' }}" class="input" placeholder="Min" aria-label="Harga minimum">
            <input type="number" name="max" min="0" step="10000" value="{{ $filters['max'] ?? '' }}" class="input" placeholder="Maks" aria-label="Harga maksimum">
        </div>
        <button class="btn btn-sm btn-ghost btn-block" style="margin-top:.6rem">Terapkan</button>
    </form>
    @if (array_filter($filters))
        <a href="{{ route('store.catalog') }}" class="link-arrow" style="color:var(--pink)" data-catalog-link><i class="bi bi-x-circle"></i> Hapus semua filter</a>
    @endif
</aside>

<div class="catalog-results">
    <div class="catalog-toolbar">
        <button type="button" class="btn btn-sm btn-ghost filter-toggle" data-filters-toggle><i class="bi bi-sliders"></i> Filter</button>
        <span class="muted mono" style="font-size:.8rem">MENAMPILKAN {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} DARI {{ $products->total() }}</span>
        <form action="{{ route('store.catalog') }}" method="GET" data-catalog-form>
            @foreach (array_diff_key($filters, ['urut' => 1]) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <select name="urut" class="select input" data-catalog-auto aria-label="Urutkan" style="padding:.55rem 1rem">
                @foreach (['terbaru' => 'Terbaru', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'nama' => 'Nama A–Z'] as $v => $l)
                    <option value="{{ $v }}" @selected(($filters['urut'] ?? 'terbaru') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-sm btn-ghost">Urutkan</button></noscript>
        </form>
    </div>

    @if ($products->isEmpty())
        <div class="empty">
            <div class="display">見つからない</div>
            <p>Produk tidak ditemukan. Coba kata kunci atau filter lain.</p>
            <a href="{{ route('store.catalog') }}" class="btn btn-ghost btn-sm" data-catalog-link>Reset filter</a>
        </div>
    @else
        <div class="product-grid">
            @foreach ($products as $p)
                <x-store.product-card :product="$p" />
            @endforeach
        </div>

        @if ($products->hasPages())
            <nav class="pager" aria-label="Halaman">
                @if ($products->onFirstPage())
                    <span class="disabled"><i class="bi bi-chevron-left"></i></span>
                @else
                    <a href="{{ $products->previousPageUrl() }}" aria-label="Sebelumnya" data-catalog-link><i class="bi bi-chevron-left"></i></a>
                @endif
                @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                    @if ($page === $products->currentPage())
                        <span class="active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" data-catalog-link>{{ $page }}</a>
                    @endif
                @endforeach
                @if ($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}" aria-label="Berikutnya" data-catalog-link><i class="bi bi-chevron-right"></i></a>
                @else
                    <span class="disabled"><i class="bi bi-chevron-right"></i></span>
                @endif
            </nav>
        @endif
    @endif
</div>
