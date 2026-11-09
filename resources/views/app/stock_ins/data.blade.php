<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Stok Masuk</title>
    @include('pdf.style')
</head>

<body>
    @php
        $period = !empty($filters['from']) || !empty($filters['to'])
            ? 'Periode ' . ($filters['from'] ?? '...') . ' s/d ' . ($filters['to'] ?? '...')
            : null;
    @endphp
    @include('pdf.header', ['title' => 'Data Stok Masuk', 'subtitle' => $period])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Kode</th>
                <th>Tgl Pesan</th>
                <th>Produk</th>
                <th>Supplier</th>
                <th class="center">Jumlah</th>
                <th class="right">Harga Beli</th>
                <th class="right">Total</th>
                <th>Status</th>
                <th>Diterima</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stockIns as $si)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $si->code }}</td>
                    <td>{{ $si->ordered_at->format('d-m-Y') }}</td>
                    <td>{{ $si->product->name }}<br><span class="muted">{{ $si->product->sku }}</span></td>
                    <td>{{ $si->supplier ?? '-' }}</td>
                    <td class="center">{{ $si->quantity }}</td>
                    <td class="right">Rp {{ number_format($si->purchase_price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($si->purchase_price * $si->quantity, 0, ',', '.') }}</td>
                    <td>{{ $si->status_label }}</td>
                    <td>{{ $si->received_at?->format('d-m-Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
        @if ($stockIns->count())
            @php $received = $stockIns->where('status', 'received'); @endphp
            <tfoot>
                <tr>
                    <th colspan="5" class="right">Total diterima</th>
                    <th class="center">{{ $received->sum('quantity') }}</th>
                    <th></th>
                    <th class="right">Rp {{ number_format($received->sum(fn ($s) => $s->purchase_price * $s->quantity), 0, ',', '.') }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        @endif
    </table>
</body>

</html>
