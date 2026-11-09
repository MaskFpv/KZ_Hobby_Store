@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <x-flash-errors />

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
                    <h4 class="card-title m-0">Katalog Produk</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('products-exportPdf', $filters) }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('products-export', $filters) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export XLS
                        </a>
                        @can('create', App\Models\Product::class)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-file-earmark-plus"></i> Import XLS
                            </button>
                            <a href="{{ route('products.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Tambah Produk
                            </a>
                        @endcan
                    </div>
                </div>

                {{-- Filter (ikut terbawa ke export) --}}
                <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kategori</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" @selected(($filters['category_id'] ?? '') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Brand</label>
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($brands as $id => $name)
                                <option value="{{ $id }}" @selected(($filters['brand_id'] ?? '') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Tipe</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach (App\Models\Product::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kondisi</label>
                        <select name="condition" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach (App\Models\Product::CONDITIONS as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['condition'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Stok</label>
                        <select name="stock_status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="low" @selected(($filters['stock_status'] ?? '') === 'low')>Menipis (≤ {{ App\Models\Product::LOW_STOCK_THRESHOLD }})</option>
                            <option value="out" @selected(($filters['stock_status'] ?? '') === 'out')>Habis</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-light" title="Reset">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle" id="myTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Foto</th>
                                <th>Produk</th>
                                <th>Kategori / Brand</th>
                                <th>Kondisi</th>
                                <th>Tipe</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td><x-partials.thumbnail :src="$product->image_url" size="45" /></td>
                                    <td>
                                        <a href="{{ route('products.show', $product) }}" class="fw-semibold text-decoration-none">{{ $product->name }}</a>
                                        <div class="small text-muted">{{ $product->sku }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $product->category->name }}</div>
                                        <small class="text-muted">{{ $product->brand->name }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->condition === 'new' ? 'bg-success' : 'bg-warning text-dark' }}">
                                            {{ $product->condition === 'new' ? 'Baru (MIB)' : 'Second (BIB)' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->type === 'pre_order' ? 'bg-info text-dark' : 'bg-secondary' }}">
                                            {{ $product->type_label }}
                                        </span>
                                    </td>
                                    <td class="text-end" data-order="{{ (float) $product->selling_price }}">
                                        Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center" data-order="{{ $product->stock }}">
                                        @if ($product->stock == 0)
                                            <span class="badge bg-danger">Habis</span>
                                        @elseif ($product->stock <= App\Models\Product::LOW_STOCK_THRESHOLD)
                                            <span class="badge bg-warning text-dark">{{ $product->stock }}</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ $product->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center" style="width: 150px;">
                                        <div role="group" class="btn-group">
                                            <a href="{{ route('products.show', $product) }}" class="btn btn-light" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @can('update', $product)
                                                <a href="{{ route('products.edit', $product) }}" class="btn btn-light" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $product)
                                                <form action="{{ route('products.destroy', $product) }}" method="POST">
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

    <x-import-modal :action="route('products-import')" template="import/import-produk.xlsx"
        columns="sku, nama_produk, kategori, brand, harga_modal, harga_jual, stok, kondisi, tipe, deskripsi" />
@endsection
