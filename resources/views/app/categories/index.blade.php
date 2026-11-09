@extends('layouts.app')

@section('content')
    <div class="container">
        <x-flash-errors />

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
                    <h4 class="card-title m-0">Kategori Produk</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('categoires-exportPdf') }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('categories-export') }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export XLS
                        </a>
                        @can('create', App\Models\Category::class)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-file-earmark-plus"></i> Import XLS
                            </button>
                            <a href="{{ route('categories.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Tambah Kategori
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle" id="myTable">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Jumlah Produk</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td class="fw-semibold">
                                        @if (Route::has('products.index'))
                                            <a href="{{ route('products.index', ['category_id' => $category->id]) }}" class="text-decoration-none">{{ $category->name }}</a>
                                        @else
                                            {{ $category->name }}
                                        @endif
                                    </td>
                                    <td>{{ $category->description ? \Illuminate\Support\Str::limit($category->description, 60) : '-' }}</td>
                                    <td class="text-center">{{ $category->products_count }}</td>
                                    <td class="text-center" style="width: 134px;">
                                        <div role="group" class="btn-group">
                                            @can('update', $category)
                                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-light" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $category)
                                                <form action="{{ route('categories.destroy', $category) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="btn btn-light text-danger btn-delete" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-import-modal :action="route('categories-import')" template="import/import-kategori.xlsx"
        columns="nama_kategori, deskripsi" />
@endsection
