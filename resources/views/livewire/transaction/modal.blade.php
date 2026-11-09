{{-- Modal kosongkan keranjang --}}
<div class="modal fade" wire:ignore.self id="modalDelete" tabindex="-1" aria-labelledby="ClearTransactionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="ClearTransactionModalLabel">Kosongkan semua isi keranjang?</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                <button type="button" data-bs-dismiss="modal" wire:click="clearPesan()" class="btn btn-danger">Kosongkan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal checkout --}}
<div class="modal fade" wire:ignore.self id="modalCheckout" tabindex="-1" aria-labelledby="modalCheckoutLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form wire:submit.prevent="checkout">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCheckoutLabel">Checkout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-md-flex justify-content-between align-items-center gap-3">
                        <p class="m-0">No Faktur: <strong>{{ $no_faktur }}</strong></p>
                        <div>
                            <select wire:model="customer_id" style="min-width: 260px;"
                                class="form-select form-select-sm @error('customer_id') is-invalid @enderror">
                                <option value="">Pilih Pelanggan / Member</option>
                                @foreach ($customers as $pelanggan)
                                    <option value="{{ $pelanggan->id }}">{{ $pelanggan->kode_member }} - {{ $pelanggan->nama }}</option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @error('stok-habis')
                        <div class="alert alert-danger py-2 mt-3 mb-0">{{ $message }}</div>
                    @enderror

                    {{-- Ringkasan item --}}
                    <div class="border rounded overflow-auto mt-3" style="max-height: 15em">
                        @foreach ($produk_detail as $item)
                            <div wire:key="checkout-{{ $item['id'] }}" class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                <span class="fw-semibold">{{ $item['name'] }} × {{ $item['quantity'] }}</span>
                                <span class="fw-bold">Rp {{ number_format($item['sub_total'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran</label>
                            <select wire:model="payment_method"
                                class="form-select @error('payment_method') is-invalid @enderror">
                                @foreach (\App\Models\Transaction::PAYMENT_METHODS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror"
                                wire:model.lazy="date" max="{{ date('Y-m-d') }}">
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diskon (Rp)</label>
                            <input type="number" min="0" step="any" wire:model.lazy="discount"
                                class="form-control @error('discount') is-invalid @enderror">
                            @error('discount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @if ($payment_method == 'cash')
                            <div class="col-md-6">
                                <label class="form-label">Uang Tunai Diterima (Rp)</label>
                                <input type="number" min="0" step="any" wire:model.lazy="total_pembayaran"
                                    class="form-control @error('total_pembayaran') is-invalid @enderror">
                                @error('total_pembayaran')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan <small class="text-muted">(opsional)</small></label>
                            <textarea class="form-control @error('keterangan') is-invalid @enderror" wire:model.lazy="keterangan"
                                id="keterangan" rows="2"></textarea>
                            @error('keterangan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Ringkasan bayar --}}
                <div class="px-3">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
                    <div class="d-flex justify-content-between text-danger"><span>Diskon</span><span>- Rp {{ number_format((float) $discount, 0, ',', '.') }}</span></div>
                    <div class="d-flex justify-content-between fs-5 fw-bold mt-1"><span>Total</span><span>Rp {{ number_format($total_price, 0, ',', '.') }}</span></div>
                    @if ($payment_method == 'cash')
                        <div class="d-flex justify-content-between fs-6 fw-bold">
                            <span>Kembalian</span>
                            @if (!is_string($kembalian))
                                <span class="text-success">Rp {{ number_format($kembalian, 0, ',', '.') }}</span>
                            @else
                                <span class="text-danger">{{ $kembalian }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="submit" class="w-100 btn btn-success" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="checkout">Bayar</span>
                        <span wire:loading wire:target="checkout">Memproses...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
