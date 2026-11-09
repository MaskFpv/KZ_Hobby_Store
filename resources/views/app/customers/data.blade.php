<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Pelanggan</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Data Pelanggan / Member'])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Telepon</th>
                <th>Email</th>
                <th>Alamat</th>
                <th class="center">Transaksi</th>
                <th class="right">Total Belanja</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $customer->kode_member }}</td>
                    <td>{{ $customer->nama }}</td>
                    <td>{{ $customer->telepon ?? '-' }}</td>
                    <td>{{ $customer->email ?? '-' }}</td>
                    <td>{{ $customer->alamat ?? '-' }}</td>
                    <td class="center">{{ $customer->transactions_count }}</td>
                    <td class="right">Rp {{ number_format($customer->total_belanja ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
