@extends('layouts.app')

@section('title', 'Kategori Artikel')

@section('content')
    <x-flash-errors />

    @can('create', App\Models\ArticleCategory::class)
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-plus-circle"></i> Tambah kategori</h2>
                <form method="POST" action="{{ route('article-categories.store') }}">
                    @csrf
                    @include('app.article-categories.form-fields')
                    <div class="mt-3 text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Kategori</button></div>
                </form>
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 m-0">Kategori Artikel</h2>
                    <small class="text-muted">Tampil sebagai filter di halaman Artikel website.</small>
                </div>
                <a href="{{ route('articles.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Artikel</a>
            </div>
            <div class="cat-grid">
                @foreach ($categories as $c)
                    <div class="cat-item" style="--cat: {{ $c->color }}">
                        <span class="cat-kanji">{{ $c->kanji_or_default }}</span>
                        <div class="flex-fill" style="min-width:0">
                            <div class="fw-bold text-truncate">{{ $c->name }}</div>
                            <small class="text-muted">{{ $c->articles_count }} artikel · /artikel?kategori={{ $c->slug }}</small>
                        </div>
                        <div class="d-flex gap-1">
                            @can('update', $c)
                                <a href="{{ route('article-categories.edit', $c) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $c)
                                <form method="POST" action="{{ route('article-categories.destroy', $c) }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-light text-danger btn-delete" data-confirm="Kategori &quot;{{ $c->name }}&quot; akan dihapus." title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
