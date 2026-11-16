@props(['article'])

{{-- Sampul artikel; tanpa foto: gradasi warna kategori + kanji kategori. --}}
@if ($article->cover_url)
    <img src="{{ $article->cover_url }}" alt="{{ $article->title }}" loading="lazy" {{ $attributes }}>
@else
    <div class="a-art" style="--g1: {{ $article->category_color }}" role="img" aria-label="{{ $article->title }}" {{ $attributes }}>
        <span>{{ $article->category_kanji }}</span>
        <small>{{ strtoupper($article->category_label) }}</small>
    </div>
@endif
