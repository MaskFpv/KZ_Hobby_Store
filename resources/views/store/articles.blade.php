@extends('layouts.store')

@section('title', 'Artikel')
@section('description', 'Kabar toko, berita anime, dan tips merawat koleksi dari ' . config('store.name') . '.')

@section('content')
    <section class="section" style="padding-bottom:20px">
        <div class="jp-outline" aria-hidden="true" style="position:absolute;right:-2%;top:0;font-size:clamp(8rem,24vw,20rem)" data-parallax="-0.2">記事</div>
        <div class="wrap" data-catalog>
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>ARTIKEL</span></nav>
            <div class="section-head" style="margin-bottom:1.6rem">
                <div>
                    <span class="eyebrow">記事 · JOURNAL · <span data-catalog-total>{{ $articles->total() }}</span> artikel</span>
                    <h1 class="section-title" style="font-size:clamp(2.4rem,6vw,4.6rem);max-width:820px" data-split>Kabar & cerita para kolektor.</h1>
                </div>
                <form action="{{ route('store.articles') }}" method="GET" style="flex:1 1 280px;max-width:380px" role="search" data-catalog-form>
                    <div style="position:relative">
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Cari artikel..." aria-label="Cari artikel" style="padding-left:2.8rem">
                        <i class="bi bi-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                    </div>
                </form>
            </div>

            <div data-catalog-body aria-live="polite">
                @include('store.partials.articles-body')
            </div>
        </div>
    </section>
@endsection
