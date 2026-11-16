@extends('layouts.app')

@section('content')
    <x-flash-errors />

    <div class="row g-3 mb-3">
        @foreach ([['Terbit', $summary['published'], 'bi-broadcast', 'tone-sakura'], ['Draf', $summary['draft'], 'bi-pencil-square', 'tone-lavender'], ['Total dibaca', number_format($summary['views'], 0, ',', '.'), 'bi-eye', 'tone-sky']] as [$label, $value, $icon, $tone])
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon {{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                    <div><p class="stat-label">{{ $label }}</p><p class="stat-value">{{ $value }}</p></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 m-0">Artikel Website</h2>
                    <small class="text-muted">Info toko, anime, hobi & event. Tampil di menu <b>Artikel</b> website.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('store.articles') }}" target="_blank" class="btn btn-light"><i class="bi bi-box-arrow-up-right"></i> Lihat di website</a>
                    @can('create', App\Models\Article::class)
                        <a href="{{ route('articles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tulis Artikel</a>
                    @endcan
                </div>
            </div>

            <form method="GET" action="{{ route('articles.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari judul</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Kategori</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (App\Models\Article::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (App\Models\Article::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('articles.index') }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>

            <div class="article-list">
                @forelse ($articles as $a)
                    <div class="article-row">
                        <div class="article-thumb"><x-store.article-cover :article="$a" /></div>
                        <div class="flex-fill" style="min-width:0">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                <span class="badge {{ $a->status === 'published' ? ($a->isPublic() ? 'bg-success' : 'bg-info text-dark') : 'bg-light text-dark' }}">
                                    {{ $a->status === 'published' && ! $a->isPublic() ? 'Terjadwal' : App\Models\Article::STATUSES[$a->status] }}
                                </span>
                                <span class="badge bg-light text-dark">{{ $a->category_label }}</span>
                            </div>
                            <a href="{{ route('articles.edit', $a) }}" class="fw-bold text-decoration-none d-block text-truncate">{{ $a->title }}</a>
                            <small class="text-muted">
                                {{ $a->author->name ?? '-' }} · {{ $a->published_at?->translatedFormat('d M Y H:i') ?? 'belum terbit' }} · {{ $a->reading_minutes }} mnt · {{ number_format($a->views, 0, ',', '.') }}x dibaca
                            </small>
                        </div>
                        <div class="d-flex gap-1 flex-none">
                            @if ($a->isPublic())
                                <a href="{{ route('store.article', $a->slug) }}" target="_blank" class="btn btn-sm btn-light" title="Lihat"><i class="bi bi-eye"></i></a>
                            @endif
                            @can('update', $a)
                                <a href="{{ route('articles.edit', $a) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $a)
                                <form method="POST" action="{{ route('articles.destroy', $a) }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-light text-danger btn-delete" data-confirm="Artikel &quot;{{ $a->title }}&quot; akan dihapus permanen." title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="empty-state"><i class="bi bi-journal-richtext"></i>Belum ada artikel. Mulai tulis yang pertama!</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
