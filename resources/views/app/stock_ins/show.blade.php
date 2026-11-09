@extends('layouts.app')

@section('content')
    <div class="container">
        <x-flash-errors />
        @error('status')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title m-0">
                    <a href="{{ route('stock-ins.index') }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                    Stok Masuk {{ $stockIn->code }}
                    <span class="badge {{ $stockIn->status_badge }} fs-6 align-middle">{{ $stockIn->status_label }}</span>
                </h4>
                <div class="d-flex gap-2">
                    @can('update', $stockIn)
                        @if (in_array($stockIn->status, ['ordered', 'shipped']))
                            <a href="{{ route('stock-ins.edit', $stockIn) }}" class="btn btn-light"><i class="bi bi-pencil-square"></i> Edit</a>
                        @endif
                    @endcan
                    @can('delete', $stockIn)
                        @if ($stockIn->status !== 'received')
                            <form action="{{ route('stock-ins.destroy', $stockIn) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-light text-danger btn-delete"><i class="bi bi-trash"></i> Hapus</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-md-7">
                    <table class="table table-sm">
                        <tr><th style="width: 180px;">Produk</th>
                            <td>
                                <a href="{{ route('products.show', $stockIn->product) }}">{{ $stockIn->product->name }}</a>
                                <div class="small text-muted">{{ $stockIn->product->sku }} · stok sekarang {{ $stockIn->product->stock }}</div>
                            </td>
                        </tr>
                        <tr><th>Supplier</th><td>{{ $stockIn->supplier ?? '-' }}</td></tr>
                        <tr><th>Jumlah</th><td>{{ $stockIn->quantity }}</td></tr>
                        <tr><th>Harga Beli Satuan</th><td>Rp {{ number_format($stockIn->purchase_price, 0, ',', '.') }}</td></tr>
                        <tr><th>Total</th><td class="fw-bold">Rp {{ number_format($stockIn->purchase_price * $stockIn->quantity, 0, ',', '.') }}</td></tr>
                        <tr><th>Tanggal Pesan</th><td>{{ $stockIn->ordered_at->format('d M Y') }}</td></tr>
                        <tr><th>Diterima</th><td>{{ $stockIn->received_at?->format('d M Y H:i') ?? '-' }}</td></tr>
                        <tr><th>Dicatat oleh</th><td>{{ $stockIn->user->name ?? '-' }}</td></tr>
                        <tr><th>Catatan</th><td style="white-space: pre-line;">{{ $stockIn->notes ?? '-' }}</td></tr>
                    </table>
                </div>

                <div class="col-md-5" id="terima">
                    @can('update', $stockIn)
                        @if (in_array($stockIn->status, ['ordered', 'shipped']))
                            <div class="bg-light rounded p-3">
                                <h6 class="fw-bold">Update Status</h6>
                                @if ($stockIn->status === 'ordered')
                                    <form method="POST" action="{{ route('stock-ins.status', $stockIn) }}" class="mb-2">
                                        @csrf
                                        <input type="hidden" name="status" value="shipped">
                                        <button class="btn btn-outline-info w-100"><i class="bi bi-truck"></i> Tandai Dalam Pengiriman</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('stock-ins.status', $stockIn) }}" class="mb-2">
                                    @csrf
                                    <input type="hidden" name="status" value="received">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="update_cost" value="1" id="update_cost">
                                        <label class="form-check-label small" for="update_cost">
                                            Perbarui harga modal produk ke Rp {{ number_format($stockIn->purchase_price, 0, ',', '.') }}
                                        </label>
                                    </div>
                                    <button class="btn btn-success w-100"><i class="bi bi-box-arrow-in-down"></i> Terima Barang (+{{ $stockIn->quantity }} stok)</button>
                                </form>
                                <form method="POST" action="{{ route('stock-ins.status', $stockIn) }}"
                                    onsubmit="return confirm('Batalkan stok masuk ini?')">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Batalkan</button>
                                </form>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
