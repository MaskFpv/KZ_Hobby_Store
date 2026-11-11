@extends('layouts.app')

@section('content')
    @php $g = $greeting; @endphp

    {{-- Hero sapaan --}}
    <section class="hero mb-4">
        @foreach ([[8, 0], [22, 2.5], [48, 5], [70, 1.2], [88, 6.5]] as [$left, $delay])
            <i class="bi bi-flower1 petal" style="left: {{ $left }}%; top: -10px; animation-delay: {{ $delay }}s;" aria-hidden="true"></i>
        @endforeach

        <div class="position-relative">
            <div class="jp">{{ $g['jp'] }} &middot; {{ $g['romaji'] }}</div>
            <div class="greeting">{{ $g['romaji'] }}, {{ $g['name'] }}!</div>
            <p class="mb-1 opacity-75">{{ $g['meaning'] }} &mdash; {{ $g['title_jp'] }} &middot; {{ $g['title'] }}</p>
            <p class="mb-3">{{ $g['message'] }}</p>

            <div class="quote">
                <div class="fw-bold">{{ $g['proverb']['jp'] }} <span class="fw-normal opacity-75">({{ $g['proverb']['romaji'] }})</span></div>
                <small>{{ $g['proverb']['meaning'] }}</small>
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-sakura"><i class="bi bi-receipt"></i></span>
                <div>
                    <p class="stat-label">{{ $g['sees_all'] ? 'Transaksi hari ini' : 'Transaksimu hari ini' }}</p>
                    <p class="stat-value">{{ $stats['trx_count'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-matcha"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <p class="stat-label">{{ $g['sees_all'] ? 'Omset hari ini' : 'Penjualanmu hari ini' }}</p>
                    <p class="stat-value">Rp {{ number_format($stats['trx_total'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon tone-lavender"><i class="bi bi-gift"></i></span>
                <div>
                    <p class="stat-label">PO siap diambil</p>
                    <p class="stat-value">{{ $stats['po_ready'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            @if ($g['sees_all'])
                <div class="stat-card">
                    <span class="stat-icon tone-yuzu"><i class="bi bi-exclamation-triangle"></i></span>
                    <div>
                        <p class="stat-label">Perlu perhatian</p>
                        <p class="stat-value fs-6 mb-0">{{ $stats['low_stock'] }} stok menipis</p>
                        <p class="stat-sub text-muted mb-0">{{ $stats['pending_stock_in'] }} kiriman &middot; {{ $stats['po_overdue'] }} PO lewat estimasi</p>
                    </div>
                </div>
            @else
                <div class="stat-card">
                    <span class="stat-icon tone-yuzu"><i class="bi bi-box-seam"></i></span>
                    <div>
                        <p class="stat-label">Produk stok menipis</p>
                        <p class="stat-value">{{ $stats['low_stock'] }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        {{-- Aksi cepat --}}
        <div class="col-lg-7">
            <div class="panel">
                <h2 class="panel-title">Aksi Cepat</h2>
                <div class="row g-3">
                    @forelse ($actions as $a)
                        <div class="col-sm-6">
                            <a href="{{ route($a['route']) }}" class="quick-action">
                                <span class="stat-icon {{ $a['tone'] }}" style="width:44px;height:44px;flex:0 0 44px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:1.25rem;">
                                    <i class="bi {{ $a['icon'] }}"></i>
                                </span>
                                <span>{{ $a['label'] }}<small>{{ $a['hint'] }}</small></span>
                            </a>
                        </div>
                    @empty
                        <div class="col-12 empty-state"><i class="bi bi-lock"></i>Belum ada akses menu. Hubungi admin.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- PO siap diambil --}}
        <div class="col-lg-5">
            <div class="panel">
                <h2 class="panel-title">
                    Pre-Order Siap Diambil
                    @if (Route::has('pre-orders.index') && $readyPreOrders->isNotEmpty())
                        <a href="{{ route('pre-orders.index', ['status' => 'arrived']) }}" class="small fw-normal">Lihat semua</a>
                    @endif
                </h2>
                @forelse ($readyPreOrders as $po)
                    <a href="{{ route('pre-orders.show', $po) }}" class="list-row text-decoration-none text-reset">
                        <div class="text-truncate">
                            <div class="fw-semibold text-truncate">{{ $po->product->name }}</div>
                            <small class="text-muted">{{ $po->customer->nama }} &middot; {{ $po->customer->telepon ?? 'tanpa telepon' }}</small>
                        </div>
                        <span class="badge {{ $po->remaining_amount > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                            {{ $po->remaining_amount > 0 ? 'Sisa Rp ' . number_format($po->remaining_amount, 0, ',', '.') : 'Lunas' }}
                        </span>
                    </a>
                @empty
                    <div class="empty-state"><i class="bi bi-inbox"></i>Belum ada PO yang menunggu diambil</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
