@props(['article'])

{{-- Sampul artikel; tanpa foto: gradasi neon + kanji kategori. --}}
@if ($article->cover_url)
    <img src="{{ $article->cover_url }}" alt="{{ $article->title }}" loading="lazy" {{ $attributes }}>
@else
    <div class="a-art a-art-{{ $article->category }}" role="img" aria-label="{{ $article->title }}" {{ $attributes }}>
        <span>{{ App\Models\Article::KANJI[$article->category] ?? '記' }}</span>
        <small>{{ strtoupper($article->category_label) }}</small>
    </div>
@endif
