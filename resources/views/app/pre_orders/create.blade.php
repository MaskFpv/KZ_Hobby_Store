@extends('layouts.app')

@section('content')
    <div class="container">
        <x-flash-errors />

        <div class="card p-4">
            <h4 class="card-title">
                <a href="{{ route('pre-orders.index') }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                Buat Pre-Order
            </h4>
            <div class="card-body">
                <form method="POST" action="{{ route('pre-orders.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customer_id" class="form-label">Member <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                <option value="">Pilih member</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected(old('customer_id', $selectedCustomer) == $c->id)>
                                        {{ $c->kode_member }} - {{ $c->nama }}{{ $c->telepon ? ' (' . $c->telepon . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @can('create', App\Models\Customer::class)
                                <small><a href="{{ route('customers.create') }}" target="_blank">+ Member baru</a> (muat ulang halaman setelah disimpan)</small>
                            @endcan
                        </div>
                        <div class="col-md-6">
                            <label for="product_id" class="form-label">Produk <span class="text-danger">*</span></label>
                            <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">Pilih produk</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}" data-price="{{ (int) $p->selling_price }}"
                                        @selected(old('product_id', $selectedProduct) == $p->id)>
                                        {{ $p->type === 'pre_order' ? '[PO] ' : '' }}{{ $p->name }} ({{ $p->sku }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="quantity" class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" min="1" step="1" required
                                class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 1) }}">
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="unit_price" class="form-label">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="unit_price" id="unit_price" min="0" step="1" required
                                class="form-control @error('unit_price') is-invalid @enderror" value="{{ old('unit_price') }}">
                            @error('unit_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="order_date" class="form-label">Tanggal Pesan <span class="text-danger">*</span></label>
                            <input type="date" name="order_date" id="order_date" required max="{{ date('Y-m-d') }}"
                                class="form-control @error('order_date') is-invalid @enderror" value="{{ old('order_date', date('Y-m-d')) }}">
                            @error('order_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="estimated_arrival" class="form-label">Estimasi Tiba</label>
                            <input type="date" name="estimated_arrival" id="estimated_arrival"
                                class="form-control @error('estimated_arrival') is-invalid @enderror" value="{{ old('estimated_arrival') }}">
                            @error('estimated_arrival')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="bg-light rounded p-3">
                                <div class="d-flex justify-content-between fs-5 fw-bold mb-2">
                                    <span>Total PO</span><span id="total-text">Rp 0</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="dp_amount" class="form-label">Down Payment / DP (Rp)</label>
                                        <input type="number" name="dp_amount" id="dp_amount" min="0" step="1"
                                            class="form-control @error('dp_amount') is-invalid @enderror" value="{{ old('dp_amount') }}"
                                            placeholder="0 = tanpa DP">
                                        @error('dp_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="mt-1 d-flex gap-1">
                                            @foreach ([30, 50, 100] as $pct)
                                                <button type="button" class="btn btn-sm btn-outline-secondary dp-pct" data-pct="{{ $pct }}">{{ $pct }}%</button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="payment_method" class="form-label">Metode Pembayaran DP</label>
                                        <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                            @foreach (App\Models\Transaction::PAYMENT_METHODS as $value => $label)
                                                <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="small text-muted mt-1">Sisa tagihan: <b id="remaining-text">Rp 0</b></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" rows="2" maxlength="1000" class="form-control"
                                placeholder="Varian, bonus pre-order, preferensi pengambilan, dll.">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('pre-orders.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Pre-Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const product = document.getElementById('product_id');
            const price = document.getElementById('unit_price');
            const qty = document.getElementById('quantity');
            const dp = document.getElementById('dp_amount');
            const rp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
            const total = () => (parseFloat(price.value) || 0) * (parseInt(qty.value) || 0);
            function update() {
                document.getElementById('total-text').textContent = rp(total());
                document.getElementById('remaining-text').textContent = rp(Math.max(0, total() - (parseFloat(dp.value) || 0)));
            }
            product.addEventListener('change', () => {
                const opt = product.selectedOptions[0];
                if (opt && opt.dataset.price) price.value = opt.dataset.price;
                update();
            });
            document.querySelectorAll('.dp-pct').forEach(b => b.addEventListener('click', () => {
                dp.value = Math.round(total() * b.dataset.pct / 100);
                update();
            }));
            [price, qty, dp].forEach(el => el.addEventListener('input', update));
            if (!price.value && product.value) product.dispatchEvent(new Event('change'));
            update();
        })();
    </script>
@endpush
