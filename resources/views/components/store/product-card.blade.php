@props(['product'])

@php
    $p = $product;
    $isPo = $p->type === 'pre_order';
    $stock = $p->available_stock;
@endphp

<article class="p-card" data-tilt data-reveal>
    <div class="p-media">
        <x-store.art :product="$p" />
        <div class="p-tags">
            @if ($isPo)
                <span class="tag tag-yellow">PRE-ORDER</span>
            @elseif ($stock < 1)
                <span class="tag tag-muted">HABIS</span>
            @elseif ($stock <= App\Models\Product::LOW_STOCK_THRESHOLD)
                <span class="tag tag-pink">SISA {{ $stock }}</span>
            @endif
            <span class="tag {{ $p->condition === 'new' ? 'tag-cyan' : 'tag-muted' }}">{{ $p->condition === 'new' ? 'MIB' : 'BIB' }}</span>
        </div>
    </div>
    <div class="p-body">
        <span class="p-brand">{{ $p->brand->name }}</span>
        <h3 class="p-name">{{ $p->name }}</h3>
        <div class="p-foot">
            <div>
                @if ($isPo && $p->release_estimate)
                    <small class="muted mono" style="font-size:.7rem">RILIS {{ strtoupper($p->release_estimate) }}</small>
                @endif
                <div class="p-price">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</div>
            </div>
            <span class="p-go" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
        </div>
    </div>
    <a href="{{ route('store.product', $p->slug) }}" class="stretched" aria-label="Lihat {{ $p->name }}"></a>
</article>
