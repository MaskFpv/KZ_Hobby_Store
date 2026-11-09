<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Brand</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Data Brand / Pabrikan'])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Nama Brand</th>
                <th>Negara Asal</th>
                <th>Deskripsi</th>
                <th class="center">Jumlah Produk</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($brands as $brand)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $brand->name }}</td>
                    <td>{{ $brand->country ?? '-' }}</td>
                    <td>{{ $brand->description ?? '-' }}</td>
                    <td class="center">{{ $brand->products_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
