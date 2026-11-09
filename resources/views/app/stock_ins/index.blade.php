@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <x-flash-errors />

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
                    <div>
                        <h4 class="card-title m-0">Stok Masuk</h4>
                        @if ($pendingCount)
                            <small class="text-muted">{{ $pendingCount }} kiriman belum diterima</small>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('stock-ins-exportPdf', $filters) }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('stock-ins-export', $filters) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export XLS
                        </a>
                        @can('create', App\Models\StockIn::class)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-file-earmark-plus"></i> Import XLS
                            </button>
                            <a href="{{ route('stock-ins.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Catat Stok Masuk
                            </a>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('stock-ins.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach (App\Models\StockIn::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Tgl pesan dari</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">sampai</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('stock-ins.index') }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle" id="myTable">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Tgl Pesan</th>
                                <th>Produk</th>
                                <th>Supplier</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Total Beli</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockIns as $si)
                                <tr>
                                    <td><a href="{{ route('stock-ins.show', $si) }}" class="text-decoration-none">{{ $si->code }}</a></td>
                                    <td data-order="{{ $si->ordered_at->format('Y-m-d') }}">{{ $si->ordered_at->format('d-m-Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $si->product->name }}</div>
                                        <small class="text-muted">{{ $si->product->sku }}</small>
                                    </td>
                                    <td>{{ $si->supplier ?? '-' }}</td>
                                    <td class="text-center">{{ $si->quantity }}</td>
                                    <td class="text-end">Rp {{ number_format($si->purchase_price * $si->quantity, 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $si->status_badge }}">{{ $si->status_label }}</span></td>
                                    <td class="text-center" style="width: 190px;">
                                        <div class="d-flex justify-content-center gap-1">
                                            @can('update', $si)
                                                @if ($si->status === 'ordered')
                                                    <form method="POST" action="{{ route('stock-ins.status', $si) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="shipped">
                                                        <button class="btn btn-sm btn-outline-info" title="Tandai dikirim"><i class="bi bi-truck"></i></button>
                                                    </form>
                                                @endif
                                                @if (in_array($si->status, ['ordered', 'shipped']))
                                                    <a href="{{ route('stock-ins.show', $si) }}#terima" class="btn btn-sm btn-success" title="Terima barang">
                                                        <i class="bi bi-box-arrow-in-down"></i> Terima
                                                    </a>
                                                @endif
                                            @endcan
                                            <a href="{{ route('stock-ins.show', $si) }}" class="btn btn-sm btn-light" title="Detail"><i class="bi bi-eye"></i></a>
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

    <x-import-modal :action="route('stock-ins-import')" template="import/import-stok-masuk.xlsx"
        columns="sku, jumlah, harga_beli, supplier, status (dipesan/dikirim/diterima), tanggal_pesan, catatan" />
@endsection
