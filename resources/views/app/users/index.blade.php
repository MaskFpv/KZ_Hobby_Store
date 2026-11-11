@extends('layouts.app')

@section('content')
    <x-flash-errors />

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 m-0">Akun Admin & Kasir</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('user-exportPdf') }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
                    <a href="{{ route('user-export') }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Export XLS</a>
                    @can('create', App\Models\User::class)
                        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Tambah User</a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="myTable">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-center">Transaksi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar" style="width:32px;height:32px;flex-basis:32px;font-size:.85rem;">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                        <span class="fw-semibold">{{ $user->name }}</span>
                                        @if ($user->is(auth()->user()))
                                            <span class="badge bg-light text-dark border">Anda</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @foreach ($user->roles as $role)
                                        <span class="badge {{ $role->name === 'super-admin' ? 'bg-primary' : 'bg-secondary' }}">{{ App\Support\Roles::label($role->name) }}</span>
                                    @endforeach
                                </td>
                                <td class="text-center">{{ $user->transactions_count }}</td>
                                <td class="text-center" style="width: 120px;">
                                    <div class="btn-group">
                                        @can('update', $user)
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-light" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        @endcan
                                        @can('delete', $user)
                                            @unless ($user->is(auth()->user()))
                                                <form action="{{ route('users.destroy', $user) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="btn btn-light text-danger btn-delete" title="Hapus"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @endunless
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
