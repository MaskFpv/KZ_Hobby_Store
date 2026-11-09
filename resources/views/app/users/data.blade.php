<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data User</title>
    @include('pdf.style')
</head>

<body>
    @include('pdf.header', ['title' => 'Data User'])
    <table class="table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th class="center">Jumlah Transaksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->getRoleNames()->implode(', ') ?: '-' }}</td>
                    <td class="center">{{ $user->transactions_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
