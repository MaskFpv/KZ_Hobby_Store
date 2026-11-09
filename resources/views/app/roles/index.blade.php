@extends('layouts.app')

@section('content')
    <x-flash-errors />

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 m-0">Role</h2>
                    <small class="text-muted">Role menentukan menu dan aksi yang boleh dipakai user.</small>
                </div>
                @can('create', Spatie\Permission\Models\Role::class)
                    <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Role</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="myTable">
                    <thead>
                        <tr>
                            <th>Nama Role</th>
                            <th class="text-center">Jumlah Permission</th>
                            <th class="text-center">Jumlah User</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            @php $locked = in_array($role->name, App\Http\Controllers\RoleController::LOCKED, true); @endphp
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $role->name }}</span>
                                    @if ($locked)
                                        <span class="badge bg-light text-dark border" title="Role sistem"><i class="bi bi-lock-fill"></i> sistem</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $locked ? 'Semua' : $role->permissions_count }}</td>
                                <td class="text-center">{{ $role->users_count }}</td>
                                <td class="text-center" style="width: 120px;">
                                    <div class="btn-group">
                                        @can('update', $role)
                                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-light" title="Atur"><i class="bi bi-sliders"></i></a>
                                        @endcan
                                        @can('delete', $role)
                                            @unless ($locked)
                                                <form action="{{ route('roles.destroy', $role) }}" method="POST">
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
