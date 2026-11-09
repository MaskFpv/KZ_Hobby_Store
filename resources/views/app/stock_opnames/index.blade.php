@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <x-flash-errors />

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
                    <h4 class="card-title m-0">Riwayat Stok Opname</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('stock-opnames-exportPdf', $filters) }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('stock-opnames-export', $filters) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export XLS
                        </a>
                        @can('create', App\Models\StockOpname::class)
                            <a href="{{ route('stock-opnames.create') }}" class="btn btn-primary">
                                <i class="bi bi-clipboard-check"></i> Mulai Opname
                            </a>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('stock-opnames.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Dari tanggal</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Sampai</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="only_diff" value="1" id="only_diff" @checked(!empty($filters['only_diff']))>
                            <label class="form-check-label small" for="only_diff">Hanya yang ada selisih</label>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('stock-opnames.index') }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle" id="myTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th class="text-center">Sistem</th>
                                <th class="text-center">Fisik</th>
                                <th class="text-center">Selisih</th>
                                <th>Catatan</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($opnames as $o)
                                <tr>
                                    <td data-order="{{ $o->opname_date->format('Y-m-d') }}">{{ $o->opname_date->format('d-m-Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $o->product->name }}</div>
                                        <small class="text-muted">{{ $o->product->sku }}</small>
                                    </td>
                                    <td class="text-center">{{ $o->system_stock }}</td>
                                    <td class="text-center">{{ $o->physical_stock }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $o->difference == 0 ? 'bg-light text-dark border' : ($o->difference > 0 ? 'bg-success' : 'bg-danger') }}">
                                            {{ $o->difference > 0 ? '+' : '' }}{{ $o->difference }}
                                        </span>
                                    </td>
                                    <td>{{ $o->notes ?? '-' }}</td>
                                    <td>{{ $o->user->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
