@extends('layouts.store')

@section('title', 'Akun Saya')

@section('content')
    <div class="wrap" style="padding:2.5rem 0 4rem">
        <span class="eyebrow">マイページ · AKUN SAYA</span>
        <h1 class="section-title" data-split>Halo, {{ strtok($customer->nama, ' ') }}-san!</h1>

        <div class="account-grid" style="margin-top:2rem">
            <aside style="display:flex;flex-direction:column;gap:1rem">
                <div class="member-card" data-tilt data-reveal>
                    <div>
                        <span class="mono muted" style="font-size:.7rem;letter-spacing:.2em">{{ strtoupper(config('store.name')) }} MEMBER</span>
                        <div style="font-weight:700;font-size:1.15rem;margin-top:.3rem">{{ $customer->nama }}</div>
                    </div>
                    <div class="code">{{ $customer->kode_member }}</div>
                </div>
                <p class="muted" style="font-size:.85rem;margin:0">Tunjukkan kode member di kasir saat mengambil pesanan atau PO.</p>
                <button type="button" class="btn btn-block btn-sm" data-chat-open><i class="bi bi-chat-heart"></i> Chat dengan CS</button>
                <form method="POST" action="{{ route('store.logout') }}">
                    @csrf
                    <button class="btn btn-ghost btn-block btn-sm"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                </form>
            </aside>

            <div style="display:flex;flex-direction:column;gap:1.5rem">
                <div class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Pesanan Online</h2>
                    @forelse ($orders as $order)
                        <a href="{{ route('store.order', $order) }}" class="list-line">
                            <div>
                                <div class="mono" style="font-weight:700">{{ $order->code }}</div>
                                <small class="muted">{{ $order->created_at->translatedFormat('d M Y') }} · Rp {{ number_format($order->total, 0, ',', '.') }}</small>
                            </div>
                            <span class="tag {{ $order->status === 'ready_pickup' ? 'tag-cyan' : ($order->isActive() ? 'tag-yellow' : 'tag-muted') }}">{{ strtoupper($order->status_label) }}</span>
                        </a>
                    @empty
                        <p class="muted" style="margin:0">Belum ada pesanan online. <a href="{{ route('store.catalog', ['tipe' => 'ready_stock']) }}" style="color:var(--cyan)">Belanja ready stock →</a></p>
                    @endforelse
                </div>

                <div class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Pre-Order Saya</h2>
                    @forelse ($preOrders as $po)
                        <a href="{{ route('store.po.show', $po->code) }}" class="list-line">
                            <div>
                                <div style="font-weight:700">{{ $po->product->name ?? '-' }}</div>
                                <small class="muted mono">{{ $po->code }} · {{ $po->order_date->translatedFormat('d M Y') }}</small>
                            </div>
                            <div style="text-align:right">
                                <span class="tag {{ $po->status === 'arrived' ? 'tag-cyan' : ($po->status === 'completed' ? 'tag-muted' : 'tag-yellow') }}">{{ strtoupper($po->status_label) }}</span>
                                @if ($po->isOpen() && $po->remaining_amount > 0)
                                    <div class="mono" style="font-size:.8rem;margin-top:.3rem">Sisa Rp {{ number_format($po->remaining_amount, 0, ',', '.') }}</div>
                                @endif
                            </div>
                        </a>
                    @empty
                        <p class="muted" style="margin:0">Belum ada pre-order. <a href="{{ route('store.preorders') }}" style="color:var(--cyan)">Lihat PO yang dibuka →</a></p>
                    @endforelse
                </div>

                <div class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Belanja di Toko</h2>
                    @forelse ($transactions as $trx)
                        <div class="list-line">
                            <div>
                                <div class="mono" style="font-weight:700">#{{ $trx->id }}</div>
                                <small class="muted">{{ $trx->date->translatedFormat('d M Y') }} · {{ $trx->payment_method_label }}</small>
                            </div>
                            <div class="mono" style="font-weight:700">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</div>
                        </div>
                    @empty
                        <p class="muted" style="margin:0">Belum ada transaksi tercatat atas akun ini.</p>
                    @endforelse
                </div>

                <div class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Profil</h2>
                    <form method="POST" action="{{ route('store.account.profile') }}" class="form-grid">
                        @csrf @method('PUT')
                        <div class="form-grid cols-2">
                            <div class="field">
                                <label for="nama">Nama</label>
                                <input id="nama" name="nama" value="{{ old('nama', $customer->nama) }}" class="input @error('nama') is-invalid @enderror" required>
                                @error('nama')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label for="telepon">Nomor WhatsApp</label>
                                <input id="telepon" name="telepon" value="{{ old('telepon', $customer->telepon) }}" class="input @error('telepon') is-invalid @enderror" required>
                                @error('telepon')<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" class="input @error('email') is-invalid @enderror" required>
                            @error('email')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="alamat">Alamat (opsional)</label>
                            <textarea id="alamat" name="alamat" rows="2" class="input">{{ old('alamat', $customer->alamat) }}</textarea>
                        </div>
                        <div><button class="btn btn-sm">Simpan Profil</button></div>
                    </form>
                </div>

                <div class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Ganti Password</h2>
                    <form method="POST" action="{{ route('store.account.password') }}" class="form-grid">
                        @csrf @method('PUT')
                        <div class="field">
                            <label for="current_password">Password lama</label>
                            <input id="current_password" type="password" name="current_password" class="input @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                            @error('current_password')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-grid cols-2">
                            <div class="field">
                                <label for="new_password">Password baru</label>
                                <input id="new_password" type="password" name="password" class="input @error('password') is-invalid @enderror" required autocomplete="new-password">
                                @error('password')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label for="new_password_confirmation">Ulangi password baru</label>
                                <input id="new_password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
                            </div>
                        </div>
                        <div><button class="btn btn-sm btn-ghost">Ganti Password</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
