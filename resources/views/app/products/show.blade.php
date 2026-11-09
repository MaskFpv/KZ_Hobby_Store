@extends('layouts.app')

@section('content')
    <div class="container">
        <x-flash-errors />

        <div class="card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title m-0">
                    <a href="{{ route('products.index') }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                    Detail Produk
                </h4>
                <div class="d-flex flex-wrap gap-2">
                    @can('create', App\Models\StockIn::class)
                        <a href="{{ route('stock-ins.create', ['product_id' => $product->id]) }}" class="btn btn-light"><i class="bi bi-truck"></i> Catat Stok Masuk</a>
                    @endcan
                    @can('create', App\Models\PreOrder::class)
                        <a href="{{ route('pre-orders.create', ['product_id' => $product->id]) }}" class="btn btn-light"><i class="bi bi-hourglass-split"></i> Buat PO</a>
                    @endcan
                    @can('update', $product)
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
                    @endcan
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-md-4 text-center">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded border"
                            style="max-height: 320px; object-fit: contain;">
                    @else
                        <div class="bg-light rounded border text-muted py-5"><i class="bi bi-image fs-1 d-block"></i>Belum ada foto</div>
                    @endif
                </div>
                <div class="col-md-8">
                    <h3 class="mb-1">{{ $product->name }}</h3>
                    <p class="text-muted mb-3">{{ $product->sku }}</p>
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <span class="badge {{ $product->condition === 'new' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $product->condition_label }}</span>
                        <span class="badge {{ $product->type === 'pre_order' ? 'bg-info text-dark' : 'bg-secondary' }}">{{ $product->type_label }}</span>
                    </div>
                    <table class="table table-sm w-auto">
                        <tr><th class="pe-4">Kategori</th><td>{{ $product->category->name }}</td></tr>
                        <tr><th class="pe-4">Brand</th><td>{{ $product->brand->name }}</td></tr>
                        @can('update', $product)
                            <tr><th class="pe-4">Harga Modal</th><td>Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td></tr>
                        @endcan
                        <tr><th class="pe-4">Harga Jual</th><td class="fw-bold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td></tr>
                        @can('update', $product)
                            <tr>
                                <th class="pe-4">Margin</th>
                                <td class="{{ $product->margin < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($product->margin, 0, ',', '.') }}</td>
                            </tr>
                        @endcan
                        <tr>
                            <th class="pe-4">Stok</th>
                            <td>
                                {{ $product->stock }}
                                @if ($product->available_stock < $product->stock)
                                    <small class="text-muted">({{ $product->stock - $product->available_stock }} jatah PO, {{ $product->available_stock }} bisa dijual)</small>
                                @endif
                            </td>
                        </tr>
                        <tr><th class="pe-4">Total Terjual</th><td>{{ $totalSold }}</td></tr>
                    </table>
                    @if ($product->description)
                        <p class="mb-0" style="white-space: pre-line;">{{ $product->description }}</p>
                    @endif
                </div>
            </div>

            @if ($openPreOrders->isNotEmpty())
                <h5 class="mt-4">Pre-Order Berjalan ({{ $openPreOrders->sum('quantity') }} unit)</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Kode</th><th>Member</th><th class="text-center">Qty</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($openPreOrders as $po)
                                <tr>
                                    <td><a href="{{ route('pre-orders.show', $po) }}">{{ $po->code }}</a></td>
                                    <td>{{ $po->customer->nama }} ({{ $po->customer->kode_member }})</td>
                                    <td class="text-center">{{ $po->quantity }}</td>
                                    <td><span class="badge {{ $po->status_badge }}">{{ $po->status_label }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <h5 class="mt-4">Riwayat Stok</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Referensi</th><th class="text-center">Perubahan</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        @forelse ($stockHistory as $h)
                            <tr>
                                <td>{{ $h['date']?->format('d-m-Y') }}</td>
                                <td>{{ $h['type'] }}</td>
                                <td>
                                    @if ($h['url'])
                                        <a href="{{ $h['url'] }}">{{ $h['ref'] }}</a>
                                    @else
                                        {{ $h['ref'] }}
                                    @endif
                                </td>
                                <td class="text-center">{{ $h['qty'] }}</td>
                                <td>{{ $h['note'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat stok masuk / opname</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="mt-4">Penjualan Terakhir</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>No Faktur</th>
                            <th>Tanggal</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr>
                                <td><a href="{{ route('transaction.invoice', $sale->transaction_id) }}">{{ $sale->transaction_id }}</a></td>
                                <td>{{ $sale->transaction?->date?->format('d-m-Y') }}</td>
                                <td class="text-center">{{ $sale->qty }}</td>
                                <td class="text-end">Rp {{ number_format($sale->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($sale->sub_total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum pernah terjual</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
