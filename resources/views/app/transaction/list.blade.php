<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Transaksi</title>
    @include('pdf.style')
</head>

<body>
    @php
        $period = !empty($filters['from']) || !empty($filters['to'])
            ? 'Periode ' . ($filters['from'] ?? '...') . ' s/d ' . ($filters['to'] ?? '...')
            : null;
    @endphp
    @include('pdf.header', ['title' => 'Data Transaksi Penjualan', 'subtitle' => $period])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>No Faktur</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Kasir</th>
                <th>Pembayaran</th>
                <th class="right">Subtotal</th>
                <th class="right">Diskon</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $trx)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $trx->id }}{{ $trx->preOrder ? ' (PO)' : '' }}</td>
                    <td>{{ $trx->date->format('d-m-Y') }}</td>
                    <td>{{ $trx->customer->nama ?? 'Pelanggan Umum' }}</td>
                    <td>{{ $trx->user->name ?? '-' }}</td>
                    <td>{{ $trx->payment_method_label }}</td>
                    <td class="right">Rp {{ number_format($trx->subtotal, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($trx->discount, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
        @if ($data->count())
            <tfoot>
                <tr>
                    <th colspan="8" class="right">Total</th>
                    <th class="right">Rp {{ number_format($data->sum('total_price'), 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        @endif
    </table>
</body>

</html>
