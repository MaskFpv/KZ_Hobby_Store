{{-- Panel stok menipis + antrean PO. Dipakai semua role yang bisa membuka dashboard. --}}
<div class="panel">
    <h2 class="panel-title">
        Stok Menipis
        <a href="{{ route('products.index', ['stock_status' => 'low']) }}" class="small fw-normal">Lihat</a>
    </h2>
    @forelse ($lowStockProducts as $p)
        <a href="{{ route('products.show', $p) }}" class="list-row text-decoration-none text-reset">
            <div class="text-truncate">
                <div class="fw-semibold text-truncate">{{ $p->name }}</div>
                <small class="text-muted">{{ $p->sku }}</small>
            </div>
            <span class="badge {{ $p->stock == 0 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $p->stock == 0 ? 'Habis' : $p->stock . ' sisa' }}</span>
        </a>
    @empty
        <div class="empty-state"><i class="bi bi-check2-circle"></i>Semua stok aman</div>
    @endforelse

    @can('view-any', App\Models\Order::class)
        @php $activeOrders = App\Models\Order::whereIn('status', ['paid', 'processing', 'ready_pickup'])->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'); @endphp
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold">Pesanan Online</div>
                <small class="text-muted">{{ $activeOrders['paid'] ?? 0 }} perlu diproses &middot; {{ $activeOrders['processing'] ?? 0 }} disiapkan &middot; {{ $activeOrders['ready_pickup'] ?? 0 }} siap diambil</small>
            </div>
            <a href="{{ route('online-orders.index') }}" class="btn btn-sm btn-outline-primary">Kelola</a>
        </div>
    @endcan

    @can('view-any', App\Models\Conversation::class)
        @php $waitingChats = App\Models\Conversation::awaitingStaff()->count(); @endphp
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold">Chat Pelanggan</div>
                <small class="text-muted">{{ $waitingChats ? $waitingChats . ' percakapan menunggu balasan' : 'Semua chat sudah dibaca' }}</small>
            </div>
            <a href="{{ route('chats.index') }}" class="btn btn-sm btn-outline-primary">Buka</a>
        </div>
    @endcan

    @can('view-any', App\Models\PreOrder::class)
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold">Antrean Pre-Order</div>
                <small class="text-muted">{{ $poSummary['waiting'] ?? 0 }} menunggu &middot; {{ $poSummary['arrived'] ?? 0 }} siap diambil</small>
            </div>
            <a href="{{ route('pre-orders.index') }}" class="btn btn-sm btn-outline-primary">Kelola</a>
        </div>
        <small class="text-muted d-block mt-1">Sisa tagihan PO: <b>Rp {{ number_format($poOutstanding, 0, ',', '.') }}</b></small>
    @endcan
</div>
