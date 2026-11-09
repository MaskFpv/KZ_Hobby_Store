@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title m-0">
                    <a href="{{ route('customers.index') }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                    {{ $customer->nama }}
                    <span class="badge bg-primary fs-6 align-middle">{{ $customer->kode_member }}</span>
                </h4>
                @can('update', $customer)
                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
                @endcan
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="bg-light rounded p-3 h-100">
                        <div class="small text-muted">Kontak</div>
                        <div>{{ $customer->telepon ?? '-' }}</div>
                        <div>{{ $customer->email ?? '-' }}</div>
                        <div class="small text-muted mt-2">Alamat</div>
                        <div style="white-space: pre-line;">{{ $customer->alamat ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="bg-light rounded p-3 text-center">
                                <div class="small text-muted">Jumlah Transaksi</div>
                                <div class="fs-4 fw-bold">{{ $stats['count'] }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="bg-light rounded p-3 text-center">
                                <div class="small text-muted">Total Belanja</div>
                                <div class="fs-5 fw-bold">Rp {{ number_format($stats['total'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="bg-light rounded p-3 text-center">
                                <div class="small text-muted">Belanja Terakhir</div>
                                <div class="fs-6 fw-bold">{{ $stats['last'] ? \Carbon\Carbon::parse($stats['last'])->format('d M Y') : '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mt-4">Transaksi Terakhir</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr><th>No Faktur</th><th>Tanggal</th><th>Pembayaran</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $trx)
                            <tr>
                                <td><a href="{{ route('transaction.invoice', $trx->id) }}">{{ $trx->id }}</a></td>
                                <td>{{ $trx->date->format('d-m-Y') }}</td>
                                <td>{{ $trx->payment_method_label }}</td>
                                <td class="text-end">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada transaksi</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="mt-4">Pre-Order</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr><th>Kode PO</th><th>Produk</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Sisa</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($preOrders as $po)
                            <tr>
                                <td>{{ $po->code }}</td>
                                <td>{{ $po->product->name ?? '-' }}</td>
                                <td>{{ $po->status_label }}</td>
                                <td class="text-end">Rp {{ number_format($po->total_price, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada pre-order</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
