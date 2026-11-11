@extends('layouts.app')

@section('content')
    @php $po = $preOrder; @endphp
    <div class="container">
        <x-flash-errors />
        @if ($errors->hasAny(['status', 'amount', 'payment_method']))
            <div class="alert alert-danger">
                @foreach (['status', 'amount', 'payment_method'] as $field)
                    @error($field)
                        <div>{{ $message }}</div>
                    @enderror
                @endforeach
            </div>
        @endif

        <div class="card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title m-0">
                    <a href="{{ route('pre-orders.index') }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                    {{ $po->code }}
                    <span class="badge {{ $po->status_badge }} fs-6 align-middle">{{ $po->status_label }}</span>
                </h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('pre-orders.receipt', $po) }}" target="_blank" class="btn btn-light"><i class="bi bi-printer"></i> Nota PO</a>
                    @if ($po->transaction_id)
                        <a href="{{ route('transaction.invoice', $po->transaction_id) }}" class="btn btn-light"><i class="bi bi-receipt"></i> Faktur #{{ $po->transaction_id }}</a>
                    @endif
                    @can('update', $po)
                        @if ($po->isOpen())
                            <a href="{{ route('pre-orders.edit', $po) }}" class="btn btn-light"><i class="bi bi-pencil-square"></i> Edit</a>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="row g-4 mt-1">
                {{-- Detail --}}
                <div class="col-lg-7">
                    <table class="table table-sm">
                        <tr><th style="width: 170px;">Member</th>
                            <td>
                                <a href="{{ route('customers.show', $po->customer) }}">{{ $po->customer->nama }}</a>
                                <span class="text-muted">({{ $po->customer->kode_member }})</span>
                                <div class="small text-muted">{{ $po->customer->telepon ?? '-' }} · {{ $po->customer->email ?? '-' }}</div>
                            </td>
                        </tr>
                        <tr><th>Produk</th>
                            <td>
                                <a href="{{ route('products.show', $po->product) }}">{{ $po->product->name }}</a>
                                <div class="small text-muted">{{ $po->product->sku }} · stok toko {{ $po->product->stock }}</div>
                            </td>
                        </tr>
                        <tr><th>Jumlah × Harga</th><td>{{ $po->quantity }} × Rp {{ number_format($po->unit_price, 0, ',', '.') }}</td></tr>
                        <tr><th>Tanggal Pesan</th><td>{{ $po->order_date->format('d M Y') }}</td></tr>
                        <tr><th>Estimasi Tiba</th>
                            <td>
                                {{ $po->estimated_arrival?->format('d M Y') ?? '-' }}
                                @if ($po->status === 'waiting' && $po->estimated_arrival?->isPast())
                                    <span class="badge bg-danger">Lewat estimasi</span>
                                @endif
                            </td>
                        </tr>
                        <tr><th>Dibuat oleh</th><td>{{ $po->user->name ?? '-' }}</td></tr>
                        <tr><th>Catatan</th><td style="white-space: pre-line;">{{ $po->notes ?? '-' }}</td></tr>
                    </table>

                    <h6 class="mt-3 fw-bold">Riwayat Pembayaran</h6>
                    <table class="table table-sm">
                        <thead><tr><th>Waktu</th><th>Jenis</th><th>Metode</th><th class="text-end">Nominal</th><th>Petugas</th></tr></thead>
                        <tbody>
                            @forelse ($po->payments as $pay)
                                <tr>
                                    <td>{{ $pay->paid_at->format('d-m-Y H:i') }}</td>
                                    <td>{{ $pay->type_label }}</td>
                                    <td>{{ App\Models\Transaction::PAYMENT_METHODS[$pay->payment_method] ?? $pay->payment_method }}</td>
                                    <td class="text-end">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                                    <td>{{ $pay->user->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Belum ada pembayaran</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Ringkasan & aksi --}}
                <div class="col-lg-5">
                    <div class="bg-light rounded p-3 mb-3">
                        <div class="d-flex justify-content-between"><span>Total PO</span><b>Rp {{ number_format($po->total_price, 0, ',', '.') }}</b></div>
                        <div class="d-flex justify-content-between text-success"><span>Sudah dibayar</span><b>Rp {{ number_format($po->paid_amount, 0, ',', '.') }}</b></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fs-5 {{ $po->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                            <span>Sisa</span><b>Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</b>
                        </div>
                    </div>

                    @can('update', $po)
                        @if ($po->isOpen() && $po->remaining_amount > 0)
                            <form method="POST" action="{{ route('pre-orders.payments.store', $po) }}" class="border rounded p-3 mb-3">
                                @csrf
                                <h6 class="fw-bold">{{ $po->payments->isEmpty() ? 'Bayar DP' : 'Tambah Cicilan' }}</h6>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="amount" min="1" max="{{ (int) ceil($po->remaining_amount) }}" step="1" required
                                        class="form-control" value="{{ old('amount') }}" placeholder="Nominal">
                                </div>
                                <select name="payment_method" class="form-select mb-2">
                                    @foreach (App\Models\Transaction::POS_METHODS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="payment_notes" maxlength="255" class="form-control mb-2" placeholder="Catatan (opsional)">
                                <button class="btn btn-outline-primary w-100"><i class="bi bi-cash-coin"></i> Simpan Pembayaran</button>
                            </form>
                        @endif

                        @if ($po->status === 'waiting')
                            <form method="POST" action="{{ route('pre-orders.arrive', $po) }}" class="mb-3">
                                @csrf
                                <button class="btn btn-info w-100"><i class="bi bi-box-seam"></i> Tandai Barang Tiba</button>
                                <small class="text-muted">Otomatis saat barang diterima di menu Stok Masuk.</small>
                            </form>
                        @endif

                        @if ($po->status === 'arrived')
                            <form method="POST" action="{{ route('pre-orders.complete', $po) }}" class="border border-success rounded p-3 mb-3"
                                onsubmit="return confirm('Lunasi & serahkan barang? PO akan dicatat sebagai transaksi penjualan.')">
                                @csrf
                                <h6 class="fw-bold text-success">Pelunasan & Serah Terima</h6>
                                @if ($po->remaining_amount > 0)
                                    <p class="mb-2">Terima pelunasan <b>Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</b> via:</p>
                                @else
                                    <p class="mb-2">Sudah lunas. Metode dicatat di faktur:</p>
                                @endif
                                <select name="payment_method" class="form-select mb-2">
                                    @foreach (App\Models\Transaction::POS_METHODS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-success w-100"><i class="bi bi-check2-circle"></i> Lunasi & Selesaikan</button>
                            </form>
                        @endif
                    @endcan

                    @can('delete', $po)
                        @if ($po->isOpen())
                            <form method="POST" action="{{ route('pre-orders.cancel', $po) }}"
                                onsubmit="return confirm('Batalkan PO ini? Tindakan ini tidak bisa dibatalkan.')">
                                @csrf
                                <input type="text" name="reason" maxlength="255" class="form-control form-control-sm mb-2" placeholder="Alasan pembatalan">
                                <button class="btn btn-outline-danger w-100 btn-sm"><i class="bi bi-x-circle"></i> Batalkan PO</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
