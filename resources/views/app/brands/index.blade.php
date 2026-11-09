@extends('layouts.app')

@section('content')
    <div class="container">
        <x-flash-errors />

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
                    <h4 class="card-title m-0">Brand / Pabrikan</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('brands-exportPdf') }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('brands-export') }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export XLS
                        </a>
                        @can('create', App\Models\Brand::class)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-file-earmark-plus"></i> Import XLS
                            </button>
                            <a href="{{ route('brands.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Tambah Brand
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle" id="myTable">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Logo</th>
                                <th>Nama Brand</th>
                                <th>Negara Asal</th>
                                <th class="text-center">Jumlah Produk</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($brands as $brand)
                                <tr>
                                    <td><x-partials.thumbnail :src="$brand->logo_url" size="45" /></td>
                                    <td>
                                        <div class="fw-semibold">{{ $brand->name }}</div>
                                        @if ($brand->description)
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($brand->description, 60) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $brand->country ?? '-' }}</td>
                                    <td class="text-center">{{ $brand->products_count }}</td>
                                    <td class="text-center" style="width: 134px;">
                                        <div role="group" class="btn-group">
                                            @can('update', $brand)
                                                <a href="{{ route('brands.edit', $brand) }}" class="btn btn-light" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $brand)
                                                <form action="{{ route('brands.destroy', $brand) }}" method="POST">
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

    <x-import-modal :action="route('brands-import')" template="import/import-brand.xlsx"
        columns="nama_brand, negara_asal, deskripsi" />
@endsection
