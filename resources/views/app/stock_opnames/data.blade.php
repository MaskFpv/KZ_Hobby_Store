<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Riwayat Stok Opname</title>
    @include('pdf.style')
</head>

<body>
    @php
        $period = !empty($filters['from']) || !empty($filters['to'])
            ? 'Periode ' . ($filters['from'] ?? '...') . ' s/d ' . ($filters['to'] ?? '...')
            : null;
    @endphp
    @include('pdf.header', ['title' => 'Riwayat Stok Opname', 'subtitle' => $period])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Tanggal</th>
                <th>Produk</th>
                <th class="center">Stok Sistem</th>
                <th class="center">Stok Fisik</th>
                <th class="center">Selisih</th>
                <th>Catatan</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($opnames as $o)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $o->opname_date->format('d-m-Y') }}</td>
                    <td>{{ $o->product->name }}<br><span class="muted">{{ $o->product->sku }}</span></td>
                    <td class="center">{{ $o->system_stock }}</td>
                    <td class="center">{{ $o->physical_stock }}</td>
                    <td class="center">{{ $o->difference > 0 ? '+' : '' }}{{ $o->difference }}</td>
                    <td>{{ $o->notes ?? '-' }}</td>
                    <td>{{ $o->user->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
