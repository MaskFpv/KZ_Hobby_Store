@extends('layouts.store')

@section('title', 'PO ' . $po->code)

@section('content')
    <div class="wrap" style="padding:2rem 0 4rem">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.account') }}">AKUN</a> / <span>PRE-ORDER {{ $po->code }}</span></nav>
        <span class="eyebrow" style="color:var(--yellow)">予約詳細</span>
        <h1 class="section-title" style="font-family:var(--font-mono)" data-split>{{ $po->code }}</h1>

        <div class="shop-layout" style="margin-top:2rem">
            <div style="display:flex;flex-direction:column;gap:1.5rem">
                <section class="panel-glass" data-reveal>
                    <div style="display:grid;grid-template-columns:110px 1fr;gap:1rem;align-items:center">
                        <div style="border-radius:14px;overflow:hidden;aspect-ratio:1"><x-store.art :product="$po->product" style="width:100%;height:100%;object-fit:cover" /></div>
                        <div>
                            <span class="tag {{ $po->status === 'arrived' ? 'tag-cyan' : 'tag-yellow' }}">{{ strtoupper($po->status_label) }}</span>
                            <div style="font-weight:700;margin-top:.4rem">{{ $po->product->name }} × {{ $po->quantity }}</div>
                            <small class="muted">Dipesan {{ $po->order_date->translatedFormat('d M Y') }} · rilis {{ $po->product->release_estimate ?: 'menyusul' }}</small>
                        </div>
                    </div>
                    @if ($po->status === 'arrived')
                        <div class="notice" style="margin-top:1rem"><i class="bi bi-gift"></i><div><b>Barang sudah tiba!</b> Lunasi sisa tagihan lalu ambil di toko dengan menunjukkan kode PO ini.</div></div>
                    @endif
                </section>

                <section class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.2rem;margin:0 0 .6rem">Riwayat Pembayaran</h2>
                    @forelse ($po->payments as $pay)
                        <div class="list-line">
                            <div><b>{{ $pay->type_label }}</b><div class="muted mono" style="font-size:.75rem">{{ $pay->paid_at->translatedFormat('d M Y, H:i') }} · {{ App\Models\Transaction::PAYMENT_METHODS[$pay->payment_method] ?? $pay->payment_method }}</div></div>
                            <span class="mono">Rp {{ number_format($pay->amount, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="muted" style="margin:0">Belum ada pembayaran.</p>
                    @endforelse
                </section>
            </div>

            <aside class="summary" style="display:flex;flex-direction:column;gap:1rem">
                <div class="panel-glass" data-reveal>
                    <div class="list-line"><span class="muted">Total</span><span class="mono">Rp {{ number_format($po->total_price, 0, ',', '.') }}</span></div>
                    <div class="list-line"><span class="muted">Dibayar</span><span class="mono">Rp {{ number_format($po->paid_amount, 0, ',', '.') }}</span></div>
                    <div class="list-line" style="border:0;font-size:1.15rem"><b>Sisa</b><b class="mono" style="color:{{ $po->remaining_amount > 0 ? 'var(--yellow)' : 'var(--cyan)' }}">Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</b></div>
                </div>
                <button type="button" class="btn btn-ghost btn-block btn-sm" data-chat-open data-chat-attach="preorder:{{ $po->id }}"><i class="bi bi-chat-dots"></i> Tanya CS soal PO ini</button>

                @if ($pendingPayment)
                    <div class="panel-glass" data-reveal>
                        <b>Tagihan menunggu dibayar</b>
                        <p class="muted" style="margin:.3rem 0">{{ $pendingPayment->purpose_label }} Rp {{ number_format($pendingPayment->amount, 0, ',', '.') }} via {{ $pendingPayment->method_label }}</p>
                        <a href="{{ route('store.pay', $pendingPayment) }}" class="btn btn-block btn-sm">Lanjutkan Pembayaran</a>
                    </div>
                @endif

                @if ($po->isOpen() && $po->status !== 'pending_payment' && $po->remaining_amount > 0)
                    <form method="POST" action="{{ route('store.po.pay', $po->code) }}" class="panel-glass form-grid" data-reveal novalidate>
                        @csrf
                        <h2 class="display" style="font-size:1.15rem;margin:0">Bayar cicilan / pelunasan</h2>
                        <div class="field">
                            <label for="amount">Nominal (Rp)</label>
                            <input id="amount" type="number" name="amount" value="{{ old('amount', (int) $po->remaining_amount) }}" min="1" max="{{ (int) ceil($po->remaining_amount) }}" class="input mono @error('amount') is-invalid @enderror">
                            @error('amount')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        @include('store.partials.method-picker')
                        <button class="btn btn-block" data-magnetic>Buat Tagihan</button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
@endsection
