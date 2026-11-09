<div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Tanggal mulai</label>
                    <input type="date" wire:model.lazy="start_date" class="form-control @error('start_date') is-invalid @enderror">
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Tanggal akhir</label>
                    <input type="date" wire:model.lazy="end_date" class="form-control @error('end_date') is-invalid @enderror">
                    @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2">
                    <button type="button" wire:click="setRange('today')" class="btn btn-sm btn-outline-secondary">Hari ini</button>
                    <button type="button" wire:click="setRange('7d')" class="btn btn-sm btn-outline-secondary">7 hari</button>
                    <button type="button" wire:click="setRange('month')" class="btn btn-sm btn-outline-secondary">Bulan ini</button>
                    <button type="button" wire:click="setRange('last_month')" class="btn btn-sm btn-outline-secondary">Bulan lalu</button>
                    @unless ($errors->any())
                        <a href="{{ route('laporan-exportPdf', [$start_date, $end_date]) }}" class="btn btn-sm btn-danger ms-auto">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('list-export', ['from' => $start_date, 'to' => $end_date]) }}" class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-excel"></i> XLS
                        </a>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl">
            <div class="stat-card"><span class="stat-icon tone-sakura"><i class="bi bi-receipt"></i></span>
                <div><p class="stat-label">Transaksi</p><p class="stat-value">{{ $summary['count'] }}</p></div></div>
        </div>
        <div class="col-6 col-xl">
            <div class="stat-card"><span class="stat-icon tone-sky"><i class="bi bi-box-seam"></i></span>
                <div><p class="stat-label">Item terjual</p><p class="stat-value">{{ $summary['items'] }}</p></div></div>
        </div>
        <div class="col-6 col-xl">
            <div class="stat-card"><span class="stat-icon tone-matcha"><i class="bi bi-cash-stack"></i></span>
                <div><p class="stat-label">Omset</p><p class="stat-value">Rp {{ number_format($summary['omset'], 0, ',', '.') }}</p></div></div>
        </div>
        <div class="col-6 col-xl">
            <div class="stat-card"><span class="stat-icon tone-yuzu"><i class="bi bi-percent"></i></span>
                <div><p class="stat-label">Total diskon</p><p class="stat-value">Rp {{ number_format($summary['discount'], 0, ',', '.') }}</p></div></div>
        </div>
        @if ($summary['profit'] !== null)
            <div class="col-12 col-xl">
                <div class="stat-card"><span class="stat-icon tone-lavender"><i class="bi bi-graph-up-arrow"></i></span>
                    <div><p class="stat-label">Laba kotor</p><p class="stat-value">Rp {{ number_format($summary['profit'], 0, ',', '.') }}</p></div></div>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive" wire:loading.class="opacity-50">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No Faktur</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th>Pembayaran</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $trx)
                            <tr wire:key="trx-{{ $trx->id }}">
                                <td><a href="{{ route('transaction.invoice', $trx->id) }}">{{ $trx->id }}</a></td>
                                <td>{{ $trx->date->format('d-m-Y') }}</td>
                                <td>{{ $trx->customer->nama ?? 'Pelanggan Umum' }}</td>
                                <td>{{ $trx->user->name ?? '-' }}</td>
                                <td>{{ $trx->payment_method_label }}</td>
                                <td class="text-end">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-x"></i>Tidak ada transaksi di periode ini</div></td></tr>
                        @endforelse
                    </tbody>
                    @if ($transactions->count())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end">Total omset</td>
                                <td class="text-end">Rp {{ number_format($summary['omset'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
