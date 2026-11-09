<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Laporan Penjualan', 'subtitle' => "Periode {$tanggalAwal} s/d {$tanggalAkhir}"])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>No Faktur</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Kasir</th>
                <th>Pembayaran</th>
                <th>Keterangan</th>
                <th class="right">Diskon</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($laporan as $trx)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $trx->id }}</td>
                    <td>{{ $trx->date->format('d-m-Y') }}</td>
                    <td>{{ $trx->customer->nama ?? 'Pelanggan Umum' }}</td>
                    <td>{{ $trx->user->name ?? '-' }}</td>
                    <td>{{ $trx->payment_method_label }}</td>
                    <td>{{ $trx->keterangan ?? '-' }}</td>
                    <td class="right">Rp {{ number_format($trx->discount, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">Tidak ada transaksi di periode ini</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7" class="right">Total ({{ $laporan->count() }} transaksi)</th>
                <th class="right">Rp {{ number_format($total_diskon, 0, ',', '.') }}</th>
                <th class="right">Rp {{ number_format($total_pendapatan, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
