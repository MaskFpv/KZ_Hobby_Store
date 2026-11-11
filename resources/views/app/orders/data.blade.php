<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Pesanan Online</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Data Pesanan Online'])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Pemesan</th>
                <th>Member</th>
                <th class="center">Item</th>
                <th class="right">Total</th>
                <th>Metode</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $o->code }}</td>
                    <td>{{ $o->created_at->format('d-m-Y H:i') }}</td>
                    <td>{{ $o->name }}<br><span class="muted">{{ $o->phone }}</span></td>
                    <td>{{ $o->customer->kode_member ?? 'Tamu' }}</td>
                    <td class="center">{{ $o->items_count }}</td>
                    <td class="right">Rp {{ number_format($o->total, 0, ',', '.') }}</td>
                    <td>{{ $o->payment_method_label }}</td>
                    <td>{{ $o->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
