<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Katalog Produk</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Katalog Produk'])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th class="center">Foto</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Brand</th>
                <th>Kondisi</th>
                <th>Tipe</th>
                <th class="right">Harga Modal</th>
                <th class="right">Harga Jual</th>
                <th class="center">Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">
                        @if ($product->image_path)
                            <img src="{{ $product->image_path }}" style="width: 36px; height: 36px; object-fit: cover;">
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name }}</td>
                    <td>{{ $product->brand->name }}</td>
                    <td>{{ $product->condition === 'new' ? 'Baru (MIB)' : 'Second (BIB)' }}</td>
                    <td>{{ $product->type_label }}</td>
                    <td class="right">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                    <td class="center">{{ $product->stock }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
        @if ($products->count())
            <tfoot>
                <tr>
                    <th colspan="10" class="right">Total stok</th>
                    <th class="center">{{ $products->sum('stock') }}</th>
                </tr>
            </tfoot>
        @endif
    </table>
</body>

</html>
