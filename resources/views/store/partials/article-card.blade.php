{{-- Kartu artikel. Param: $a (Article), opsional $featured (kartu besar). --}}
<article class="a-card {{ ($featured ?? false) ? 'is-featured' : '' }}" data-tilt data-reveal>
    <div class="a-media"><x-store.article-cover :article="$a" /></div>
    <div class="a-body">
        <div class="a-meta mono">
            <span class="tag tag-pink">{{ strtoupper($a->category_label) }}</span>
            <span>{{ $a->published_at->translatedFormat('d M Y') }} · {{ $a->reading_minutes }} mnt baca</span>
        </div>
        <h3 class="a-title">{{ $a->title }}</h3>
        <p class="muted a-summary">{{ $a->summary }}</p>
        <span class="link-arrow">Baca artikel <i class="bi bi-arrow-right"></i></span>
    </div>
    <a href="{{ route('store.article', $a->slug) }}" class="stretched" aria-label="Baca {{ $a->title }}"></a>
</article>
