{{-- Isi halaman artikel (filter + daftar). Ditukar lewat fetch saat kategori dipilih. --}}
@php
    $with = fn (array $changes) => route('store.articles', array_filter(array_merge($filters, $changes, ['page' => null]), fn ($v) => $v !== null && $v !== ''));
    $featured = $articles->onFirstPage() && empty(array_filter($filters)) ? $articles->first() : null;
@endphp

<div class="articles-wrap" data-total="{{ $articles->total() }}">
    <div class="chip-list a-filters" role="group" aria-label="Kategori artikel">
        <a href="{{ $with(['kategori' => null]) }}" class="chip {{ empty($filters['kategori']) ? 'active' : '' }}" data-catalog-link>Semua</a>
        @foreach (App\Models\Article::CATEGORIES as $key => $label)
            <a href="{{ $with(['kategori' => $key]) }}" class="chip {{ ($filters['kategori'] ?? '') === $key ? 'active' : '' }}" data-catalog-link>
                <span class="mono" style="opacity:.6">{{ App\Models\Article::KANJI[$key] }}</span> {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($articles->isEmpty())
        <div class="empty">
            <div class="display">記事なし</div>
            <p>Belum ada artikel di sini. Coba kategori lain.</p>
        </div>
    @else
        @if ($featured)
            @include('store.partials.article-card', ['a' => $featured, 'featured' => true])
        @endif
        <div class="a-grid">
            @foreach ($articles as $a)
                @continue($featured && $a->is($featured))
                @include('store.partials.article-card', ['a' => $a])
            @endforeach
        </div>

        @if ($articles->hasPages())
            <nav class="pager" aria-label="Halaman">
                @foreach ($articles->getUrlRange(1, $articles->lastPage()) as $page => $url)
                    @if ($page === $articles->currentPage())
                        <span class="active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" data-catalog-link>{{ $page }}</a>
                    @endif
                @endforeach
            </nav>
        @endif
    @endif
</div>
