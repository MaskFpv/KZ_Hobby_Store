@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <x-flash-errors />

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
                    <h4 class="card-title m-0">Pelanggan / Member</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('customer-exportPdf') }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('customer-export') }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export XLS
                        </a>
                        @can('create', App\Models\Customer::class)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-file-earmark-plus"></i> Import XLS
                            </button>
                            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Tambah Member
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle" id="myTable">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kontak</th>
                                <th>Alamat</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-end">Total Belanja</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                @php $isWalkIn = $customer->kode_member === App\Http\Controllers\CustomerController::WALK_IN_CODE; @endphp
                                <tr>
                                    <td><span class="badge {{ $isWalkIn ? 'bg-secondary' : 'bg-primary' }}">{{ $customer->kode_member }}</span></td>
                                    <td>
                                        <a href="{{ route('customers.show', $customer) }}" class="fw-semibold text-decoration-none">{{ $customer->nama }}</a>
                                    </td>
                                    <td>
                                        <div>{{ $customer->telepon ?? '-' }}</div>
                                        <small class="text-muted">{{ $customer->email }}</small>
                                    </td>
                                    <td>{{ $customer->alamat ? \Illuminate\Support\Str::limit($customer->alamat, 40) : '-' }}</td>
                                    <td class="text-center">{{ $customer->transactions_count }}</td>
                                    <td class="text-end" data-order="{{ (float) $customer->total_belanja }}">
                                        Rp {{ number_format($customer->total_belanja ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center" style="width: 150px;">
                                        <div role="group" class="btn-group">
                                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-light" title="Riwayat">
                                                <i class="bi bi-clock-history"></i>
                                            </a>
                                            @can('update', $customer)
                                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-light" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            @endcan
                                            @if (!$isWalkIn)
                                                @can('delete', $customer)
                                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST">
                                                        @csrf @method('DELETE')
                                                        <button type="button" class="btn btn-light text-danger btn-delete" title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-import-modal :action="route('customer-import')" template="import/import-customer.xlsx"
        columns="nama, email, telepon, alamat" />
@endsection
