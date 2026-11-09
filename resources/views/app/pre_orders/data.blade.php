<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Pre-Order</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Data Pre-Order'])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Kode PO</th>
                <th>Tgl Pesan</th>
                <th>Pelanggan</th>
                <th>Produk</th>
                <th class="center">Qty</th>
                <th class="right">Total</th>
                <th class="right">Dibayar</th>
                <th class="right">Sisa</th>
                <th>Estimasi Tiba</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($preOrders as $po)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $po->code }}</td>
                    <td>{{ $po->order_date->format('d-m-Y') }}</td>
                    <td>{{ $po->customer->nama }}<br><span class="muted">{{ $po->customer->kode_member }} · {{ $po->customer->telepon ?? '-' }}</span></td>
                    <td>{{ $po->product->name }}</td>
                    <td class="center">{{ $po->quantity }}</td>
                    <td class="right">Rp {{ number_format($po->total_price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($po->paid_amount, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</td>
                    <td>{{ $po->estimated_arrival?->format('d-m-Y') ?? '-' }}</td>
                    <td>{{ $po->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
        @if ($preOrders->count())
            @php $open = $preOrders->filter->isOpen(); @endphp
            <tfoot>
                <tr>
                    <th colspan="8" class="right">Sisa tagihan PO berjalan</th>
                    <th class="right">Rp {{ number_format($open->sum('remaining_amount'), 0, ',', '.') }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        @endif
    </table>
</body>

</html>
