{{-- Kartu produk pre-order dengan hitung mundur deadline. Param: $p (Product) --}}
<article class="po-card" data-reveal>
    <a href="{{ route('store.product', $p->slug) }}" class="po-media" aria-label="{{ $p->name }}"><x-store.art :product="$p" /></a>
    <div>
        <span class="p-brand mono" style="font-size:.7rem;letter-spacing:.14em;color:var(--cyan)">{{ strtoupper($p->brand->name) }}</span>
        <h3 style="margin:.2rem 0 .3rem;font-size:1.05rem"><a href="{{ route('store.product', $p->slug) }}">{{ $p->name }}</a></h3>
        <div class="mono" style="font-weight:700">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</div>
        <small class="muted">
            DP mulai Rp {{ number_format(ceil($p->selling_price * config('store.dp_min_percent') / 100), 0, ',', '.') }}
            @if ($p->release_estimate) · rilis {{ $p->release_estimate }} @endif
        </small>
        @if ($p->po_deadline)
            <div class="countdown" data-deadline="{{ $p->po_deadline->copy()->endOfDay()->toIso8601String() }}" aria-label="PO ditutup {{ $p->po_deadline->translatedFormat('d F Y') }}">
                <div><b data-unit="d">--</b><small>HARI</small></div>
                <div><b data-unit="h">--</b><small>JAM</small></div>
                <div><b data-unit="m">--</b><small>MNT</small></div>
                <div><b data-unit="s">--</b><small>DTK</small></div>
            </div>
        @else
            <span class="tag tag-yellow" style="margin-top:.5rem">PO DIBUKA</span>
        @endif
    </div>
</article>
