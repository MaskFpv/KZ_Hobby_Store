{{-- Kartu artikel. Param: $a (Article). Warna aksen mengikuti kategori. --}}
<article class="a-card" style="--cat: {{ $a->category_color }}" data-tilt data-reveal>
    <div class="a-media">
        <x-store.article-cover :article="$a" />
        <span class="cat-chip is-solid">{{ $a->category_kanji }} {{ $a->category_label }}</span>
    </div>
    <div class="a-body">
        <div class="a-meta mono">
            <span>{{ $a->published_at->translatedFormat('d M Y') }}</span>
            <span>·</span>
            <span>{{ $a->reading_minutes }} mnt baca</span>
        </div>
        <h3 class="a-title">{{ $a->title }}</h3>
        <p class="muted a-summary">{{ $a->summary }}</p>
        <span class="link-arrow">Baca artikel <i class="bi bi-arrow-right"></i></span>
    </div>
    <a href="{{ route('store.article', $a->slug) }}" class="stretched" aria-label="Baca {{ $a->title }}"></a>
</article>
