@extends('layouts.app')

@section('title', 'Pesanan ' . $order->code)

@section('content')
    <x-flash-errors />
    @error('status')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h4 m-0">{{ $order->code }} <span class="badge {{ $order->status_badge }} fs-6 align-middle">{{ $order->status_label }}</span></h2>
                            <small class="text-muted">Dibuat {{ $order->created_at->translatedFormat('d M Y, H:i') }}</small>
                        </div>
                        <a href="{{ route('online-orders.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</a>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="small text-muted">Pemesan</div>
                                <div class="fw-bold">{{ $order->name }}</div>
                                <div>{{ $order->phone }} · {{ $order->email }}</div>
                                <div class="small mt-1">
                                    @if ($order->customer)
                                        Member <a href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->kode_member }}</a>
                                    @else
                                        Checkout sebagai tamu
                                    @endif
                                </div>
                                @if ($order->notes)
                                    <div class="small mt-2"><b>Catatan:</b> {{ $order->notes }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3 h-100">
                                <div class="small text-muted">Pembayaran</div>
                                @forelse ($order->payments as $pay)
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $pay->method_label }} <small class="text-muted">{{ $pay->code }}</small></span>
                                        <span class="badge {{ $pay->status === 'paid' ? 'bg-success' : ($pay->status === 'refunded' ? 'bg-danger' : 'bg-secondary') }}">{{ $pay->status_label }}</span>
                                    </div>
                                @empty
                                    <div>-</div>
                                @endforelse
                                <div class="small mt-2">
                                    @if ($order->paid_at)
                                        Dibayar {{ $order->paid_at->translatedFormat('d M Y, H:i') }}
                                    @elseif ($order->status === 'pending_payment')
                                        Batas bayar {{ $order->payment_due_at->translatedFormat('d M Y, H:i') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table align-middle">
                        <thead><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <x-partials.thumbnail :src="$item->product?->image_url" size="40" />
                                            <div>
                                                {{ $item->product_name }}
                                                @if ($item->product)<div class="small text-muted">{{ $item->product->sku }} · stok toko {{ $item->product->stock }}</div>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->sub_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold fs-5"><td colspan="3" class="text-end">Total</td><td class="text-end">Rp {{ number_format($order->total, 0, ',', '.') }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <h2 class="panel-title">Tindakan</h2>
                @php $act = false; @endphp

                @if ($order->status === 'paid')
                    @can('update', $order)
                        @php $act = true; @endphp
                        <form method="POST" action="{{ route('online-orders.process', $order) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-primary w-100"><i class="bi bi-box-seam"></i> Mulai Siapkan Barang</button>
                        </form>
                    @endcan
                @endif

                @if (in_array($order->status, ['paid', 'processing']))
                    @can('update', $order)
                        @php $act = true; @endphp
                        <form method="POST" action="{{ route('online-orders.ready', $order) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-info w-100"><i class="bi bi-shop"></i> Tandai Siap Diambil</button>
                        </form>
                    @endcan
                @endif

                @if ($order->status === 'ready_pickup')
                    @can('handover-orders')
                        @php $act = true; @endphp
                        <form method="POST" action="{{ route('online-orders.handover', $order) }}" class="border border-success rounded p-3 mb-2"
                            onsubmit="return confirm('Serahkan pesanan ke pelanggan? Pesanan akan dicatat sebagai transaksi penjualan.')">
                            @csrf
                            <p class="small mb-2">Cocokkan kode <b>{{ $order->code }}</b> dan nama <b>{{ $order->name }}</b> dengan pelanggan.</p>
                            <button class="btn btn-success w-100"><i class="bi bi-bag-check"></i> Serahkan ke Pelanggan</button>
                        </form>
                    @endcan
                @endif

                @if ($order->transaction_id)
                    <a href="{{ route('transaction.invoice', $order->transaction_id) }}" class="btn btn-light w-100 mb-2"><i class="bi bi-receipt"></i> Faktur #{{ $order->transaction_id }}</a>
                @endif

                @if ($order->isActive())
                    @can('delete', $order)
                        @php $act = true; @endphp
                        <form method="POST" action="{{ route('online-orders.cancel', $order) }}" class="mt-3"
                            onsubmit="return confirm('Batalkan pesanan? Stok dikembalikan{{ $order->wasPaid() ? ' dan dana harus dikembalikan ke pelanggan' : '' }}.')">
                            @csrf
                            <input type="text" name="reason" maxlength="255" class="form-control form-control-sm mb-2" placeholder="Alasan pembatalan">
                            <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-x-circle"></i> Batalkan{{ $order->wasPaid() ? ' & Refund' : '' }}</button>
                        </form>
                    @endcan
                @endif

                @unless ($act || $order->transaction_id)
                    <p class="text-muted small mb-0">
                        {{ $order->status === 'pending_payment' ? 'Menunggu pelanggan membayar.' : 'Tidak ada tindakan untuk status ini.' }}
                    </p>
                @endunless

                <hr>
                <div class="small text-muted">
                    @if ($order->handler)Ditangani: {{ $order->handler->name }}<br>@endif
                    @if ($order->ready_at)Siap: {{ $order->ready_at->translatedFormat('d M, H:i') }}<br>@endif
                    @if ($order->completed_at)Diambil: {{ $order->completed_at->translatedFormat('d M, H:i') }}<br>@endif
                    @if ($order->cancel_reason)Alasan: {{ $order->cancel_reason }}@endif
                </div>
            </div>
        </div>
    </div>
@endsection
