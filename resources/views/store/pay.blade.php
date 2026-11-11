@extends('layouts.store')

@section('title', 'Pembayaran ' . $payment->code)

@php
    $isOrder = $payable instanceof App\Models\Order;
    $pending = $payment->isPending();
@endphp

@section('content')
    <div class="wrap pay-shell">
        {{-- Ringkasan tagihan --}}
        <div data-reveal>
            <span class="eyebrow">お支払い · {{ strtoupper($payment->purpose_label) }}</span>
            <div class="muted mono" style="margin-top:1rem;font-size:.8rem">TOTAL TAGIHAN</div>
            <div class="pay-amount">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>

            @if ($pending)
                <div class="notice warn">
                    <i class="bi bi-alarm"></i>
                    <div>Bayar sebelum <b>{{ $payment->expires_at->translatedFormat('d M Y, H:i') }} WIB</b><br>
                        Sisa waktu <b class="timer" data-expires="{{ $payment->expires_at->toIso8601String() }}">--:--:--</b></div>
                </div>
            @endif

            <div class="panel-glass" style="margin-top:1.4rem">
                @if ($isOrder)
                    <div class="list-line"><span class="muted">Pesanan</span><b class="mono">{{ $payable->code }}</b></div>
                    @foreach ($payable->items as $item)
                        <div class="list-line"><span>{{ $item->product_name }} × {{ $item->qty }}</span><span class="mono">Rp {{ number_format($item->sub_total, 0, ',', '.') }}</span></div>
                    @endforeach
                @else
                    <div class="list-line"><span class="muted">Pre-Order</span><b class="mono">{{ $payable->code }}</b></div>
                    <div class="list-line"><span>{{ $payable->product->name }} × {{ $payable->quantity }}</span><span class="mono">Rp {{ number_format($payable->total_price, 0, ',', '.') }}</span></div>
                    <div class="list-line"><span class="muted">Sudah dibayar</span><span class="mono">Rp {{ number_format($payable->paid_amount, 0, ',', '.') }}</span></div>
                @endif
                <div class="list-line" style="border:0"><span class="muted">Metode</span><span>{{ $payment->method_label }}</span></div>
            </div>
            <p class="muted" style="font-size:.8rem;margin-top:1rem"><i class="bi bi-info-circle"></i> Ini <b>prototipe pembayaran</b> untuk demo. Tidak ada dana yang ditarik.</p>
        </div>

        {{-- Panel metode --}}
        <div class="panel-glass" data-reveal>
            @if ($payment->status === 'paid')
                <div class="empty" style="padding:2rem 0">
                    <div class="display" style="-webkit-text-stroke-color:var(--cyan)">完了</div>
                    <h2 style="margin:.5rem 0">Pembayaran berhasil</h2>
                    <p class="muted">Dibayar {{ $payment->paid_at->translatedFormat('d M Y, H:i') }} via {{ $payment->method_label }}.</p>
                    <a href="{{ $backUrl }}" class="btn">Lihat {{ $isOrder ? 'status pesanan' : 'detail PO' }} <i class="bi bi-arrow-right"></i></a>
                </div>
            @elseif (! $pending)
                <div class="empty" style="padding:2rem 0">
                    <div class="display">期限切れ</div>
                    <h2 style="margin:.5rem 0">Tagihan {{ strtolower($payment->status_label) }}</h2>
                    <p class="muted">Tagihan ini sudah tidak bisa dibayar.</p>
                    <a href="{{ $backUrl }}" class="btn btn-ghost">Kembali</a>
                </div>
            @elseif ($payment->method === 'qris')
                <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem;text-align:center">Scan QRIS</h2>
                <div class="qr-box" data-qr="{{ $payment->qris_payload }}" aria-label="Kode QR pembayaran"></div>
                <ol class="muted" style="font-size:.9rem;margin:1.4rem 0">
                    <li>Buka aplikasi e-wallet atau m-banking.</li>
                    <li>Pilih bayar dengan QRIS, lalu scan kode di atas.</li>
                    <li>Pastikan nominal Rp {{ number_format($payment->amount, 0, ',', '.') }}, lalu bayar.</li>
                </ol>
                <form method="POST" action="{{ url()->full() }}" data-processing>
                    @csrf
                    <button class="btn btn-block btn-cyan" data-magnetic><i class="bi bi-phone"></i> Simulasikan scan &amp; bayar</button>
                </form>
            @elseif ($payment->method === 'va')
                <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Transfer Virtual Account</h2>
                <div class="va-card">
                    <div class="muted mono" style="font-size:.75rem">{{ App\Models\OnlinePayment::BANKS[$payment->bank][0] }} VIRTUAL ACCOUNT</div>
                    <div class="va-number">
                        <span>{{ trim(chunk_split($payment->va_number, 4, ' ')) }}</span>
                        <button type="button" class="icon-btn" data-copy="{{ $payment->va_number }}" aria-label="Salin nomor VA"><i class="bi bi-copy"></i></button>
                    </div>
                    <div class="muted" style="font-size:.85rem;margin-top:.6rem">a.n. {{ config('store.name') }}</div>
                </div>
                <ol class="muted" style="font-size:.9rem;margin:1.4rem 0">
                    <li>Buka m-banking / ATM {{ App\Models\OnlinePayment::BANKS[$payment->bank][0] }}.</li>
                    <li>Pilih Transfer &rsaquo; Virtual Account, masukkan nomor di atas.</li>
                    <li>Nominal otomatis Rp {{ number_format($payment->amount, 0, ',', '.') }}. Konfirmasi.</li>
                </ol>
                <form method="POST" action="{{ url()->full() }}" data-processing>
                    @csrf
                    <button class="btn btn-block btn-cyan" data-magnetic><i class="bi bi-bank"></i> Simulasikan transfer</button>
                </form>
            @else
                <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Kartu Kredit / Debit</h2>
                <div class="credit-card" data-tilt aria-hidden="true">
                    <div style="display:flex;justify-content:space-between;align-items:center"><div class="chip"></div><i class="bi bi-wifi" style="transform:rotate(90deg);font-size:1.4rem"></i></div>
                    <div class="num" data-card-num>•••• •••• •••• ••••</div>
                    <div class="meta"><span data-card-name>NAMA PEMILIK</span><span data-card-exp>MM/YY</span></div>
                </div>
                <form method="POST" action="{{ url()->full() }}" class="form-grid" data-card-form data-processing autocomplete="off" novalidate>
                    @csrf
                    <div class="field">
                        <label for="card_number">Nomor kartu</label>
                        <input id="card_number" name="card_number" inputmode="numeric" class="input mono @error('card_number') is-invalid @enderror" placeholder="4111 1111 1111 1111" maxlength="23" required>
                        @error('card_number')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="card_name">Nama di kartu</label>
                        <input id="card_name" name="card_name" value="{{ old('card_name') }}" class="input @error('card_name') is-invalid @enderror" style="text-transform:uppercase" required>
                        @error('card_name')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-grid cols-2">
                        <div class="field">
                            <label for="card_expiry">Berlaku s/d</label>
                            <input id="card_expiry" name="card_expiry" inputmode="numeric" class="input mono @error('card_expiry') is-invalid @enderror" placeholder="MM/YY" maxlength="5" required>
                            @error('card_expiry')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="card_cvc">CVV</label>
                            <input id="card_cvc" name="card_cvc" type="password" inputmode="numeric" class="input mono @error('card_cvc') is-invalid @enderror" placeholder="•••" maxlength="4" required>
                            @error('card_cvc')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <p class="muted" style="font-size:.8rem;margin:0"><i class="bi bi-shield-lock"></i> Kartu uji: <span class="mono">4111 1111 1111 1111</span>, masa berlaku apa saja di masa depan. Nomor kartu & CVV tidak disimpan.</p>
                    <button class="btn btn-block" data-magnetic><i class="bi bi-lock"></i> Bayar Rp {{ number_format($payment->amount, 0, ',', '.') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="processing" role="status" aria-live="polite">
        <div class="spinner"></div>
        <div class="display" style="font-size:1.6rem">処理中…</div>
        <div class="muted">Memproses pembayaran, jangan tutup halaman.</div>
    </div>
@endsection

@push('scripts-before')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@endpush
