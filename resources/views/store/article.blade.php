@extends('layouts.store')

@section('title', $article->title)
@section('description', $article->summary)

@section('content')
    <article class="article-page" data-article>
        <header class="wrap article-hero">
            <nav class="crumbs" aria-label="Breadcrumb">
                <a href="{{ route('store.home') }}">BERANDA</a> / <a href="{{ route('store.articles') }}">ARTIKEL</a> /
                @if ($article->category)
                    <a href="{{ route('store.articles', ['kategori' => $article->category->slug]) }}">{{ strtoupper($article->category_label) }}</a>
                @endif
            </nav>
            <span class="cat-chip is-solid" style="--cat: {{ $article->category_color }}">{{ $article->category_kanji }} {{ $article->category_label }}</span>
            <h1 class="display article-title" data-split>{{ $article->title }}</h1>
            <div class="article-meta mono" data-reveal>
                <span><i class="bi bi-person-circle"></i> {{ $article->author->name ?? config('store.name') }}</span>
                <span><i class="bi bi-calendar3"></i> {{ $article->published_at->translatedFormat('d F Y') }}</span>
                <span><i class="bi bi-clock"></i> {{ $article->reading_minutes }} menit baca</span>
                <span><i class="bi bi-eye"></i> {{ number_format($article->views, 0, ',', '.') }}x dibaca</span>
            </div>
            <div class="article-cover" data-reveal>
                <x-store.article-cover :article="$article" data-parallax="0.08" />
            </div>
        </header>

        <div class="wrap article-layout">
            <aside class="article-aside" aria-label="Daftar isi & bagikan">
                <nav class="toc" data-toc hidden>
                    <h4 class="mono">DAFTAR ISI</h4>
                    <ol></ol>
                </nav>
                <div class="share">
                    <h4 class="mono">BAGIKAN</h4>
                    <div class="share-row">
                        <a class="icon-btn" href="https://wa.me/?text={{ urlencode($article->title . ' ' . url()->current()) }}" target="_blank" rel="noopener" aria-label="Bagikan ke WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <a class="icon-btn" href="https://x.com/intent/post?text={{ urlencode($article->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="Bagikan ke X"><i class="bi bi-twitter-x"></i></a>
                        <button type="button" class="icon-btn" data-copy="{{ url()->current() }}" aria-label="Salin tautan"><i class="bi bi-link-45deg"></i></button>
                    </div>
                </div>
            </aside>

            {{-- Isi sudah dibersihkan HTMLPurifier saat disimpan (App\Support\HtmlSanitizer) --}}
            <div class="article-body" data-article-body data-reveal>
                @if ($article->excerpt)
                    <p class="article-lead">{{ $article->excerpt }}</p>
                @endif
                {!! $article->body !!}
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="section">
                <div class="wrap">
                    <div class="section-head">
                        <div>
                            <span class="eyebrow">関連記事 · Baca juga</span>
                            <h2 class="section-title" data-split>Artikel lainnya</h2>
                        </div>
                        <a href="{{ route('store.articles') }}" class="link-arrow">Semua artikel <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="a-grid">
                        @foreach ($related as $a)
                            @include('store.partials.article-card', ['a' => $a])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </article>
@endsection
