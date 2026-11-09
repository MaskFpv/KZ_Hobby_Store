<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota {{ $preOrder->code }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <style>
        body { background: #eee; }
        .sheet { max-width: 720px; margin: 24px auto; background: #fff; border-radius: 1rem; padding: 2.5rem; }
        @media print { body { background: #fff; } .sheet { margin: 0; padding: 0; } }
    </style>
</head>

<body>
    @php $po = $preOrder; @endphp
    <div class="sheet">
        <div class="d-flex justify-content-between">
            <div>
                <h3 class="mb-0">{{ config('store.name') }}</h3>
                <div class="text-muted">{{ config('store.address') }}</div>
            </div>
            <div class="text-end">
                <h5 class="mb-0">NOTA PRE-ORDER</h5>
                <div class="fw-bold">{{ $po->code }}</div>
                <span class="badge {{ $po->status_badge }}">{{ $po->status_label }}</span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-6">
                <div class="text-muted small">Pelanggan</div>
                <div class="fw-semibold">{{ $po->customer->nama }} ({{ $po->customer->kode_member }})</div>
                <div>{{ $po->customer->telepon }}</div>
            </div>
            <div class="col-6 text-end">
                <div class="text-muted small">Tanggal Pesan</div>
                <div>{{ $po->order_date->format('d M Y') }}</div>
                <div class="text-muted small mt-1">Estimasi Tiba</div>
                <div>{{ $po->estimated_arrival?->format('d M Y') ?? 'Menunggu info rilis' }}</div>
            </div>
        </div>

        <table class="table mt-4">
            <thead><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $po->product->name }}<div class="small text-muted">{{ $po->product->sku }}</div></td>
                    <td class="text-center">{{ $po->quantity }}</td>
                    <td class="text-end">Rp {{ number_format($po->unit_price, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($po->total_price, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <h6 class="fw-bold">Pembayaran</h6>
        <table class="table table-sm">
            @forelse ($po->payments as $pay)
                <tr>
                    <td>{{ $pay->paid_at->format('d-m-Y H:i') }}</td>
                    <td>{{ $pay->type_label }}</td>
                    <td>{{ App\Models\Transaction::PAYMENT_METHODS[$pay->payment_method] ?? $pay->payment_method }}</td>
                    <td class="text-end">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td class="text-muted">Belum ada pembayaran</td></tr>
            @endforelse
            <tr class="fw-bold">
                <td colspan="3" class="text-end">Total dibayar</td>
                <td class="text-end">Rp {{ number_format($po->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold fs-5 {{ $po->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                <td colspan="3" class="text-end">Sisa tagihan</td>
                <td class="text-end">Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        <p class="small text-muted mb-0">
            Simpan nota ini sebagai bukti pemesanan. Pelunasan dilakukan saat barang tiba di toko.
        </p>

        <div class="d-print-none mt-4 text-end">
            <button onclick="window.print()" class="btn btn-success">Print</button>
            <a href="{{ route('pre-orders.show', $po) }}" class="btn btn-light">Kembali</a>
        </div>
    </div>
</body>

</html>
