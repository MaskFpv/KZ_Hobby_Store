@extends('layouts.app')

@section('content')
    <x-flash-errors />

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 m-0">Permission</h2>
                    <small class="text-muted">Permission bertanda <i class="bi bi-lock-fill"></i> dipakai sistem; namanya tidak bisa diubah.</small>
                </div>
                @can('create', Spatie\Permission\Models\Permission::class)
                    <a href="{{ route('permissions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Permission</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="myTable">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Dimiliki Role</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            @php $system = App\Http\Controllers\PermissionController::isSystem($permission); @endphp
                            <tr>
                                <td>
                                    {{ $permission->name }}
                                    @if ($system)
                                        <i class="bi bi-lock-fill text-muted small" title="Permission sistem"></i>
                                    @endif
                                </td>
                                <td>
                                    @foreach ($permission->roles as $role)
                                        <span class="badge {{ $role->name === 'super-admin' ? 'bg-primary' : 'bg-secondary' }}">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td class="text-center" style="width: 120px;">
                                    <div class="btn-group">
                                        @can('update', $permission)
                                            <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-light" title="Atur"><i class="bi bi-sliders"></i></a>
                                        @endcan
                                        @can('delete', $permission)
                                            @unless ($system)
                                                <form action="{{ route('permissions.destroy', $permission) }}" method="POST">
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
