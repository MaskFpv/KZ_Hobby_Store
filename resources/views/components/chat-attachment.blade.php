@props(['item', 'staff' => false])

{{-- Kartu lampiran chat (produk / pesanan online / PO). Link mengarah ke panel untuk staf, ke website untuk member. --}}
@if ($item)
    @php
        $rp = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
        [$icon, $title, $sub, $url] = match (true) {
            $item instanceof App\Models\Product => ['bi-box-seam', $item->name, $rp($item->selling_price),
                $staff ? route('products.show', $item) : route('store.product', $item->slug)],
            $item instanceof App\Models\Order => ['bi-bag-check', 'Pesanan ' . $item->code, $item->status_label . ' · ' . $rp($item->total),
                $staff ? route('online-orders.show', $item) : route('store.order', $item)],
            $item instanceof App\Models\PreOrder => ['bi-hourglass-split', 'PO ' . $item->code, ($item->product->name ?? '-') . ' · ' . $item->status_label,
                $staff ? route('pre-orders.show', $item) : route('store.po.show', $item->code)],
        };
    @endphp
    <a href="{{ $url }}" class="chat-att" target="_blank" rel="noopener">
        @if ($item instanceof App\Models\Product && $item->image_url)
            <img src="{{ $item->image_url }}" alt="" class="chat-att-thumb">
        @else
            <span class="chat-att-thumb"><i class="bi {{ $icon }}"></i></span>
        @endif
        <span class="chat-att-text"><b>{{ $title }}</b><small>{{ $sub }}</small></span>
    </a>
@endif
