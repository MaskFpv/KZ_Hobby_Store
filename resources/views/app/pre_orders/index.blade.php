@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <x-flash-errors />

        {{-- Ringkasan status --}}
        <div class="row g-3 mb-3">
            @foreach (App\Models\PreOrder::STATUSES as $value => $label)
                <div class="col-6 col-lg">
                    <a href="{{ route('pre-orders.index', ['status' => $value]) }}" class="text-decoration-none">
                        <div class="bg-light rounded p-3 h-100 {{ ($filters['status'] ?? '') === $value ? 'border border-primary' : '' }}">
                            <div class="small text-muted">{{ $label }}</div>
                            <div class="fs-4 fw-bold text-dark">{{ $summary[$value] ?? 0 }}</div>
                        </div>
                    </a>
                </div>
            @endforeach
            <div class="col-12 col-lg">
                <div class="bg-light rounded p-3 h-100">
                    <div class="small text-muted">Sisa tagihan PO berjalan</div>
                    <div class="fs-5 fw-bold text-danger">Rp {{ number_format($outstanding, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
                    <h4 class="card-title m-0">Manajemen Pre-Order</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('pre-orders-exportPdf', $filters) }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('pre-orders-export', $filters) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export XLS
                        </a>
                        @can('create', App\Models\PreOrder::class)
                            <a href="{{ route('pre-orders.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Buat Pre-Order
                            </a>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('pre-orders.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach (App\Models\PreOrder::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1">Produk</label>
                        <select name="product_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($products as $id => $name)
                                <option value="{{ $id }}" @selected(($filters['product_id'] ?? '') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('pre-orders.index') }}" class="btn btn-sm btn-light">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle" id="myTable">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Sisa</th>
                                <th>Estimasi Tiba</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preOrders as $po)
                                <tr>
                                    <td>
                                        <a href="{{ route('pre-orders.show', $po) }}" class="fw-semibold text-decoration-none">{{ $po->code }}</a>
                                        <div class="small text-muted">{{ $po->order_date->format('d-m-Y') }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $po->customer->nama }}</div>
                                        <small class="text-muted">{{ $po->customer->kode_member }} · {{ $po->customer->telepon ?? '-' }}</small>
                                    </td>
                                    <td>{{ $po->product->name }}</td>
                                    <td class="text-center">{{ $po->quantity }}</td>
                                    <td class="text-end" data-order="{{ (float) $po->total_price }}">Rp {{ number_format($po->total_price, 0, ',', '.') }}</td>
                                    <td class="text-end" data-order="{{ $po->remaining_amount }}">
                                        @if ($po->remaining_amount > 0 && $po->isOpen())
                                            <span class="text-danger">Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td data-order="{{ $po->estimated_arrival?->format('Y-m-d') }}">
                                        {{ $po->estimated_arrival?->format('d-m-Y') ?? '-' }}
                                        @if ($po->status === 'waiting' && $po->estimated_arrival?->isPast())
                                            <span class="badge bg-danger">Lewat</span>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $po->status_badge }}">{{ $po->status_label }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('pre-orders.show', $po) }}" class="btn btn-sm {{ $po->status === 'arrived' ? 'btn-success' : 'btn-light' }}">
                                            {{ $po->status === 'arrived' ? 'Pelunasan' : 'Detail' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
