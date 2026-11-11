@extends('layouts.store')

@section('title', 'Pesanan ' . $order->code)

@php
    $flowIndex = array_search($order->status, App\Models\Order::FLOW, true);
    $steps = [
        'pending_payment' => ['bi-receipt', 'Pesanan dibuat', $order->created_at],
        'paid' => ['bi-credit-card', 'Pembayaran diterima', $order->paid_at],
        'processing' => ['bi-box-seam', 'Disiapkan toko', null],
        'ready_pickup' => ['bi-shop', 'Siap diambil', $order->ready_at],
        'completed' => ['bi-bag-check', 'Sudah diambil', $order->completed_at],
    ];
@endphp

@section('content')
    <div class="wrap" style="padding:2rem 0 4rem">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>PESANAN {{ $order->code }}</span></nav>
        <span class="eyebrow">注文状況 · STATUS PESANAN</span>
        <h1 class="section-title mono" style="font-family:var(--font-mono)" data-split>{{ $order->code }}</h1>
        <p class="muted">Dipesan {{ $order->created_at->translatedFormat('d F Y, H:i') }} WIB atas nama {{ $order->name }}.</p>

        <div class="shop-layout" style="margin-top:2rem">
            <div style="display:flex;flex-direction:column;gap:1.5rem">
                <section class="panel-glass" data-reveal>
                    @if (in_array($order->status, ['cancelled', 'expired']))
                        <div class="notice warn"><i class="bi bi-x-octagon"></i>
                            <div><b>Pesanan {{ strtolower($order->status_label) }}.</b> {{ $order->cancel_reason }}
                                @if ($order->wasPaid()) <br>Dana akan dikembalikan oleh toko; hubungi kami bila ada pertanyaan. @endif
                            </div>
                        </div>
                    @else
                        <ol class="timeline">
                            <span class="fill" aria-hidden="true"></span>
                            @foreach ($steps as $key => [$icon, $label, $time])
                                @php $i = array_search($key, App\Models\Order::FLOW, true); @endphp
                                <li class="{{ $i < $flowIndex || $order->status === 'completed' ? 'done' : ($i === $flowIndex ? 'current' : '') }}">
                                    <span class="node"><i class="bi {{ $icon }}"></i></span>
                                    <div>
                                        <strong>{{ $label }}</strong>
                                        @if ($time)<div class="muted mono" style="font-size:.75rem">{{ $time->translatedFormat('d M Y, H:i') }}</div>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                <section class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.2rem;margin:0 0 .6rem">Barang</h2>
                    @foreach ($order->items as $item)
                        <div class="cart-row" style="grid-template-columns:72px 1fr auto">
                            <div class="thumb" style="width:72px">@if ($item->product)<x-store.art :product="$item->product" />@endif</div>
                            <div><b>{{ $item->product_name }}</b><div class="muted mono" style="font-size:.8rem">{{ $item->qty }} × Rp {{ number_format($item->unit_price, 0, ',', '.') }}</div></div>
                            <div class="mono" style="font-weight:700">Rp {{ number_format($item->sub_total, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                    <div class="list-line" style="border:0;font-size:1.2rem"><b>Total</b><b class="mono">Rp {{ number_format($order->total, 0, ',', '.') }}</b></div>
                </section>
            </div>

            <aside class="summary" style="display:flex;flex-direction:column;gap:1rem">
                @if ($order->status === 'pending_payment' && $payUrl)
                    <div class="panel-glass" data-reveal>
                        <h2 class="display" style="font-size:1.2rem;margin:0 0 .6rem">Menunggu pembayaran</h2>
                        <p class="muted" style="margin:0 0 .4rem">Batas bayar {{ $order->payment_due_at->translatedFormat('d M, H:i') }} WIB</p>
                        <b class="timer" data-expires="{{ $order->payment_due_at->toIso8601String() }}">--:--:--</b>
                        <a href="{{ $payUrl }}" class="btn btn-block" style="margin-top:1rem" data-magnetic>Bayar Sekarang</a>
                    </div>
                @endif

                @if (in_array($order->status, ['paid', 'processing', 'ready_pickup']))
                    <div class="panel-glass pickup-code" data-reveal>
                        <div class="muted mono" style="font-size:.75rem">KODE PENGAMBILAN</div>
                        <div class="qr-mini" data-qr="{{ $order->code }}" aria-label="QR kode pesanan"></div>
                        <div class="code">{{ $order->code }}</div>
                        <p class="muted" style="font-size:.85rem;margin:.6rem 0 0">
                            {{ $order->status === 'ready_pickup' ? 'Pesanan siap! Tunjukkan kode ini di kasir.' : 'Kami kabari via WhatsApp saat pesanan siap diambil.' }}
                        </p>
                    </div>
                @endif

                <div class="panel-glass" data-reveal>
                    <div class="list-line"><span class="muted">Status</span><b>{{ $order->status_label }}</b></div>
                    <div class="list-line"><span class="muted">Pembayaran</span><span>{{ $payment?->method_label ?? $order->payment_method_label }}</span></div>
                    <div class="list-line" style="border:0"><span class="muted">Ambil di</span><span style="text-align:right">{{ config('store.address') }}</span></div>
                </div>

                @if ($order->customer_id && (int) auth('customer')->id() === (int) $order->customer_id)
                    <button type="button" class="btn btn-ghost btn-block" data-chat-open data-chat-attach="order:{{ $order->id }}"><i class="bi bi-chat-dots"></i> Tanya CS soal pesanan ini</button>
                @endif
            </aside>
        </div>
    </div>
@endsection

@push('scripts-before')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@endpush
