<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Kategori Produk</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Data Kategori Produk'])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th class="center">Jumlah Produk</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->description ?? '-' }}</td>
                    <td class="center">{{ $category->products_count }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
