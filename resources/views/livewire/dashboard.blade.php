<div>
    @if ($canReports)
    {{-- KPI --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-sakura"><i class="bi bi-receipt"></i></span>
                <div>
                    <p class="stat-label">Transaksi hari ini</p>
                    <p class="stat-value">{{ $todayCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-matcha"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <p class="stat-label">Omset hari ini</p>
                    <p class="stat-value">Rp {{ number_format($todayOmset, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-lavender"><i class="bi bi-calendar3"></i></span>
                <div>
                    <p class="stat-label">Omset {{ now()->translatedFormat('F') }}</p>
                    <p class="stat-value">Rp {{ number_format($monthOmset, 0, ',', '.') }}</p>
                    @if ($growth !== null)
                        <p class="stat-sub mb-0 {{ $growth >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi {{ $growth >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                            {{ $growth >= 0 ? '+' : '' }}{{ $growth }}% vs periode sama bulan lalu
                        </p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            @if ($canProfit)
                <div class="stat-card">
                    <span class="stat-icon tone-yuzu"><i class="bi bi-graph-up-arrow"></i></span>
                    <div>
                        <p class="stat-label">Laba kotor bulan ini</p>
                        <p class="stat-value {{ $monthProfit < 0 ? 'text-danger' : '' }}">Rp {{ number_format($monthProfit, 0, ',', '.') }}</p>
                        <p class="stat-sub text-muted mb-0">Harga jual − modal − diskon</p>
                    </div>
                </div>
            @else
                <div class="stat-card">
                    <span class="stat-icon tone-yuzu"><i class="bi bi-hourglass-split"></i></span>
                    <div>
                        <p class="stat-label">PO berjalan</p>
                        <p class="stat-value">{{ ($poSummary['waiting'] ?? 0) + ($poSummary['arrived'] ?? 0) }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Grafik bulanan --}}
        <div class="col-xl-8">
            <div class="panel">
                <div class="panel-title">
                    <span>Penjualan Bulanan</span>
                    <select wire:model="year" class="form-select form-select-sm w-auto" aria-label="Pilih tahun">
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div wire:ignore style="height: 320px;"><canvas id="salesChart"></canvas></div>
            </div>
        </div>

        {{-- Kategori --}}
        <div class="col-xl-4">
            <div class="panel">
                <h2 class="panel-title">Penjualan per Kategori</h2>
                <div wire:ignore style="height: 260px;"><canvas id="categoryChart"></canvas></div>
                <p class="small text-muted mt-2 mb-0" id="category-empty" @if (count($chart['categoryTotals'])) hidden @endif>
                    Belum ada penjualan di tahun ini.
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Produk terlaris --}}
        <div class="col-lg-6 col-xl-4">
            <div class="panel">
                <h2 class="panel-title">Produk Terlaris</h2>
                @forelse ($topProducts as $row)
                    <div class="list-row">
                        <div class="d-flex align-items-center gap-2 text-truncate">
                            <span class="badge bg-primary">#{{ $loop->iteration }}</span>
                            <x-partials.thumbnail :src="$row->product?->image_url" size="36" />
                            <span class="text-truncate">{{ $row->product_name }}</span>
                        </div>
                        <div class="text-end text-nowrap">
                            <div class="fw-bold">{{ $row->total_qty }} terjual</div>
                            <small class="text-muted">Rp {{ number_format($row->total_sales, 0, ',', '.') }}</small>
                        </div>
                    </div>
                @empty
                    <div class="empty-state"><i class="bi bi-bar-chart"></i>Belum ada penjualan</div>
                @endforelse
            </div>
        </div>

        {{-- Transaksi terbaru --}}
        <div class="col-lg-6 col-xl-4">
            <div class="panel">
                <h2 class="panel-title">
                    Transaksi Terbaru
                    <a href="{{ route('transaction.data') }}" class="small fw-normal">Lihat semua</a>
                </h2>
                @forelse ($recentTransactions as $trx)
                    <a href="{{ route('transaction.invoice', $trx->id) }}" class="list-row text-decoration-none text-reset">
                        <div>
                            <div class="fw-semibold">#{{ $trx->id }}</div>
                            <small class="text-muted">{{ $trx->date->translatedFormat('d M') }} &middot; {{ $trx->customer->nama ?? 'Pelanggan Umum' }}</small>
                        </div>
                        <span class="fw-bold">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</span>
                    </a>
                @empty
                    <div class="empty-state"><i class="bi bi-receipt"></i>Belum ada transaksi</div>
                @endforelse
            </div>
        </div>

        {{-- Stok & PO --}}
        <div class="col-lg-12 col-xl-4">
            @include('livewire.partials.dashboard-stock')
        </div>
    </div>

    <p class="small text-muted mb-0">{{ $totalProducts }} produk &middot; {{ $totalCustomers }} pelanggan terdaftar. Omset PO dihitung saat pelunasan.</p>
    @else
    {{-- Tanpa akses laporan (Gudang, CS): ringkasan operasional saja --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-yuzu"><i class="bi bi-exclamation-triangle"></i></span>
                <div><p class="stat-label">Stok menipis</p><p class="stat-value">{{ $lowStockCount }}</p></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-sky"><i class="bi bi-truck"></i></span>
                <div><p class="stat-label">Kiriman belum diterima</p><p class="stat-value">{{ $pendingStockIns }}</p></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-lavender"><i class="bi bi-hourglass-split"></i></span>
                <div><p class="stat-label">PO menunggu barang</p><p class="stat-value">{{ $poSummary['waiting'] ?? 0 }}</p></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-matcha"><i class="bi bi-gift"></i></span>
                <div><p class="stat-label">PO siap diambil</p><p class="stat-value">{{ $poSummary['arrived'] ?? 0 }}</p></div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-xl-6">@include('livewire.partials.dashboard-stock')</div>
    </div>
    @endif
</div>

@if ($canReports)
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            const rp = (v) => 'Rp ' + Number(v).toLocaleString('id-ID');
            const palette = ['#ff4f8b', '#8b7cf6', '#3fb8f0', '#2fbf71', '#f5a524', '#e5484d', '#2a2147'];
            const initial = @json($chart);
            // Warna teks & garis grafik mengikuti mode terang / gelap
            const ink = () => document.documentElement.getAttribute('data-theme') === 'dark' ? { text: '#a7a1c8', grid: 'rgba(255,255,255,.08)' } : { text: '#6e6a85', grid: '#efeaf8' };
            Chart.defaults.color = ink().text;

            const sales = new Chart(document.getElementById('salesChart'), {
                type: 'bar',
                data: {
                    labels: initial.labels,
                    datasets: [{
                        label: 'Omset',
                        data: initial.totals,
                        backgroundColor: 'rgba(255, 79, 139, .75)',
                        hoverBackgroundColor: '#e23a76',
                        borderRadius: 8,
                        maxBarThickness: 38,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => rp(c.parsed.y) } } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 1000000, // grafik kosong tetap punya skala rupiah yang wajar
                            ticks: { precision: 0, callback: (v) => v >= 1e6 ? (v / 1e6).toLocaleString('id-ID') + ' jt' : rp(v) },
                            grid: { color: ink().grid }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });

            const category = new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: {
                    labels: initial.categoryLabels,
                    datasets: [{ data: initial.categoryTotals, backgroundColor: palette, borderWidth: 0 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } },
                        tooltip: { callbacks: { label: (c) => c.label + ': ' + rp(c.parsed) } }
                    }
                }
            });

            window.addEventListener('themechange', () => {
                Chart.defaults.color = ink().text;
                sales.options.scales.y.grid.color = ink().grid;
                sales.update();
                category.update();
            });

            window.addEventListener('dashboard-charts', (e) => {
                const d = e.detail;
                sales.data.labels = d.labels;
                sales.data.datasets[0].data = d.totals;
                sales.update();
                category.data.labels = d.categoryLabels;
                category.data.datasets[0].data = d.categoryTotals;
                category.update();
                document.getElementById('category-empty').hidden = d.categoryTotals.length > 0;
            });
        })();
    </script>
@endpush
@endif
