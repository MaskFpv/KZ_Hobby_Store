@extends('layouts.app')

@section('content')
    <x-flash-errors />

    {{-- Ringkasan status (klik untuk filter) --}}
    <div class="row g-3 mb-3">
        @foreach (['pending_payment', 'paid', 'processing', 'ready_pickup', 'completed'] as $status)
            <div class="col-6 col-lg">
                <a href="{{ route('online-orders.index', ['status' => $status]) }}" class="text-decoration-none">
                    <div class="stat-card {{ ($filters['status'] ?? '') === $status ? 'border border-primary' : '' }}">
                        <div>
                            <p class="stat-label">{{ App\Models\Order::STATUSES[$status] }}</p>
                            <p class="stat-value">{{ $summary[$status] ?? 0 }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 m-0">Pesanan Online</h2>
                    <small class="text-muted">Pesanan ready stock dari website, diambil di toko.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('online-orders-exportPdf', $filters) }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
                    <a href="{{ route('online-orders-export', $filters) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Export XLS</a>
                </div>
            </div>

            <form method="GET" action="{{ route('online-orders.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Cari kode / nama / telepon</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="ORD-... (kode persis langsung dibuka)" autofocus>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (App\Models\Order::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Dari</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('online-orders.index') }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="myTable">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pemesan</th>
                            <th class="text-center">Item</th>
                            <th class="text-end">Total</th>
                            <th>Bayar</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('online-orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->code }}</a>
                                    <div class="small text-muted">{{ $order->created_at->format('d-m-Y H:i') }}</div>
                                </td>
                                <td>
                                    {{ $order->name }}
                                    <div class="small text-muted">{{ $order->customer->kode_member ?? 'Tamu' }} · {{ $order->phone }}</div>
                                </td>
                                <td class="text-center">{{ $order->items_count }}</td>
                                <td class="text-end" data-order="{{ (float) $order->total }}">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                <td>{{ $order->payment_method_label }}</td>
                                <td><span class="badge {{ $order->status_badge }}">{{ $order->status_label }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('online-orders.show', $order) }}" class="btn btn-sm {{ in_array($order->status, ['paid', 'processing', 'ready_pickup']) ? 'btn-primary' : 'btn-light' }}">
                                        {{ match ($order->status) { 'paid' => 'Proses', 'processing' => 'Siapkan', 'ready_pickup' => 'Serahkan', default => 'Detail' } }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
