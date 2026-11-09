@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 m-0">Riwayat Transaksi</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('transaction-list-exportPdf', $filters) }}" class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                    <a href="{{ route('list-export', $filters) }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Export XLS
                    </a>
                    @can('create', App\Models\Transaction::class)
                        <a href="{{ route('transaction.index') }}" class="btn btn-primary"><i class="bi bi-calculator"></i> Buka Kasir</a>
                    @endcan
                </div>
            </div>

            <form method="GET" action="{{ route('transaction.data') }}" class="row g-2 align-items-end mb-2">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Dari tanggal</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Pembayaran</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (App\Models\Transaction::PAYMENT_METHODS as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['payment_method'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('transaction.data') }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>

            <p class="small text-muted mb-0">
                {{ $transactions->count() }} transaksi &middot; total
                <b>Rp {{ number_format($transactions->sum('total_price'), 0, ',', '.') }}</b>
            </p>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="myTable">
                    <thead>
                        <tr>
                            <th>No Faktur</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th>Pembayaran</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $trx)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $trx->id }}</span>
                                    @if ($trx->preOrder)
                                        <span class="badge bg-info text-dark" title="Pelunasan {{ $trx->preOrder->code }}">PO</span>
                                    @endif
                                </td>
                                <td data-order="{{ $trx->date->format('Y-m-d') }}">{{ $trx->date->format('d-m-Y') }}</td>
                                <td>{{ $trx->customer->nama ?? 'Pelanggan Umum' }}</td>
                                <td>{{ $trx->user->name ?? '-' }}</td>
                                <td>{{ $trx->payment_method_label }}</td>
                                <td class="text-end" data-order="{{ (float) $trx->total_price }}">
                                    Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                    @if ($trx->discount > 0)
                                        <div class="small text-danger">diskon Rp {{ number_format($trx->discount, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('transaction.invoice', $trx->id) }}" class="btn btn-sm btn-light" title="Lihat nota">
                                        <i class="bi bi-receipt"></i>
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
