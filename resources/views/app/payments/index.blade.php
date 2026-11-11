@extends('layouts.app')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card"><span class="stat-icon tone-matcha"><i class="bi bi-check2-circle"></i></span>
                <div><p class="stat-label">Total lunas (sesuai filter)</p><p class="stat-value">Rp {{ number_format($paidTotal, 0, ',', '.') }}</p></div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card"><span class="stat-icon tone-sakura"><i class="bi bi-arrow-counterclockwise"></i></span>
                <div><p class="stat-label">Perlu / sudah direfund</p><p class="stat-value">Rp {{ number_format($refundTotal, 0, ',', '.') }}</p></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 m-0">Pembayaran Online</h2>
                    <small class="text-muted">Gateway prototipe: QRIS, Virtual Account, Kartu. Hanya 4 digit kartu yang tersimpan.</small>
                </div>
                <a href="{{ route('online-payments-export', $filters) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Export XLS</a>
            </div>

            <form method="GET" action="{{ route('online-payments.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (App\Models\OnlinePayment::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Metode</label>
                    <select name="method" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (App\Models\OnlinePayment::METHODS as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['method'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Dari</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('online-payments.index') }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="myTable">
                    <thead>
                        <tr><th>Kode</th><th>Dibuat</th><th>Untuk</th><th>Metode</th><th class="text-end">Nominal</th><th>Status</th><th>Dibayar</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $p)
                            @php $ref = $p->payable; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $p->code }}</td>
                                <td data-order="{{ $p->created_at->timestamp }}">{{ $p->created_at->format('d-m-Y H:i') }}</td>
                                <td>
                                    {{ $p->purpose_label }}
                                    @if ($ref instanceof App\Models\Order)
                                        <div class="small"><a href="{{ route('online-orders.show', $ref) }}">{{ $ref->code }}</a></div>
                                    @elseif ($ref)
                                        <div class="small"><a href="{{ route('pre-orders.show', $ref) }}">{{ $ref->code }}</a></div>
                                    @endif
                                </td>
                                <td>{{ $p->method_label }}@if ($p->va_number)<div class="small text-muted">{{ $p->va_number }}</div>@endif</td>
                                <td class="text-end" data-order="{{ (float) $p->amount }}">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                                <td><span class="badge {{ ['paid' => 'bg-success', 'pending' => 'bg-warning text-dark', 'refunded' => 'bg-danger'][$p->status] ?? 'bg-secondary' }}">{{ $p->status_label }}</span></td>
                                <td>{{ $p->paid_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
