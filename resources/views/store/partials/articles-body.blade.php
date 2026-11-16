{{-- Isi halaman artikel (filter + sorotan + daftar). Ditukar lewat fetch saat kategori dipilih. --}}
@php
    $with = fn (array $changes) => route('store.articles', array_filter(array_merge($filters, $changes, ['page' => null]), fn ($v) => $v !== null && $v !== ''));
    $totalAll = $categories->sum('articles_count');
@endphp

<div class="articles-wrap" data-total="{{ $articles->total() }}">
    {{-- Kategori dinamis (dikelola di panel) beserta jumlah artikelnya --}}
    <div class="chip-list a-filters" role="group" aria-label="Kategori artikel">
        <a href="{{ $with(['kategori' => null]) }}" class="cat-chip {{ $current ? '' : 'active' }}" style="--cat: #ff2e88" data-catalog-link>
            記 Semua <small>{{ $totalAll }}</small>
        </a>
        @foreach ($categories as $c)
            @continue($c->articles_count === 0 && ! $current?->is($c))
            <a href="{{ $with(['kategori' => $c->slug]) }}" class="cat-chip {{ $current?->is($c) ? 'active' : '' }}" style="--cat: {{ $c->color }}" data-catalog-link>
                {{ $c->kanji_or_default }} {{ $c->name }} <small>{{ $c->articles_count }}</small>
            </a>
        @endforeach
    </div>

    @if ($current)
        <div class="cat-banner" style="--cat: {{ $current->color }}" data-reveal>
            <span class="cat-banner-kanji" aria-hidden="true">{{ $current->kanji_or_default }}</span>
            <div>
                <span class="eyebrow">KATEGORI</span>
                <h2 class="display">{{ $current->name }}</h2>
                <p class="muted">{{ $current->description ?: $articles->total() . ' artikel tentang ' . strtolower($current->name) . '.' }}</p>
            </div>
        </div>
    @endif

    @if ($spotlight)
        {{-- Sorotan: artikel yang di-pin (atau terbaru) + 3 artikel terpopuler --}}
        <section class="a-bento" aria-label="Sorotan artikel">
            <article class="a-hero" style="--cat: {{ $spotlight->category_color }}" data-reveal>
                <div class="a-hero-media"><x-store.article-cover :article="$spotlight" /></div>
                <div class="a-hero-body">
                    <div class="a-hero-top">
                        <span class="cat-chip is-solid">{{ $spotlight->category_kanji }} {{ $spotlight->category_label }}</span>
                        <span class="a-hero-badge mono"><i class="bi bi-stars"></i> {{ $spotlight->is_pinned ? 'SOROTAN' : 'TERBARU' }}</span>
                    </div>
                    <h2 class="a-hero-title">{{ $spotlight->title }}</h2>
                    <p class="a-hero-summary">{{ $spotlight->summary }}</p>
                    <div class="a-hero-meta mono">
                        <span><i class="bi bi-person-circle"></i> {{ $spotlight->author->name ?? config('store.name') }}</span>
                        <span><i class="bi bi-calendar3"></i> {{ $spotlight->published_at->translatedFormat('d M Y') }}</span>
                        <span><i class="bi bi-clock"></i> {{ $spotlight->reading_minutes }} mnt</span>
                    </div>
                    <span class="btn btn-sm a-hero-cta">Baca sekarang <i class="bi bi-arrow-right"></i></span>
                </div>
                <a href="{{ route('store.article', $spotlight->slug) }}" class="stretched" aria-label="Baca {{ $spotlight->title }}"></a>
            </article>

            @if ($popular->isNotEmpty())
                <aside class="a-popular" data-reveal>
                    <h3 class="mono"><i class="bi bi-fire"></i> TERPOPULER · 人気</h3>
                    <ol>
                        @foreach ($popular as $p)
                            <li style="--cat: {{ $p->category_color }}">
                                <a href="{{ route('store.article', $p->slug) }}">
                                    <span class="a-rank mono">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="a-pop-thumb"><x-store.article-cover :article="$p" /></span>
                                    <span class="a-pop-text">
                                        <small class="a-pop-cat">{{ $p->category_label }}</small>
                                        <b>{{ $p->title }}</b>
                                        <small class="muted mono"><i class="bi bi-eye"></i> {{ number_format($p->views, 0, ',', '.') }} · {{ $p->reading_minutes }} mnt</small>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </aside>
            @endif
        </section>
    @endif

    @if ($articles->isEmpty())
        <div class="empty">
            <div class="display">記事なし</div>
            <p>Belum ada artikel di sini. Coba kategori lain.</p>
        </div>
    @else
        <div class="a-grid">
            @foreach ($articles as $a)
                @continue($spotlight && $a->is($spotlight))
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
