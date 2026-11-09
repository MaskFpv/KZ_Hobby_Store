@php $editing = isset($stockIn) @endphp

<div class="row g-3">
    <div class="col-md-8">
        <label for="product_id" class="form-label">Produk <span class="text-danger">*</span></label>
        <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
            <option value="">Pilih produk</option>
            @foreach ($products as $p)
                <option value="{{ $p->id }}" data-price="{{ (int) $p->purchase_price }}"
                    @selected(old('product_id', $editing ? $stockIn->product_id : ($selectedProduct ?? null)) == $p->id)>
                    {{ $p->name }} ({{ $p->sku }}) · stok {{ $p->stock }}{{ $p->type === 'pre_order' ? ' · PO' : '' }}
                </option>
            @endforeach
        </select>
        @error('product_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label for="supplier" class="form-label">Supplier / Importir</label>
        <input type="text" name="supplier" id="supplier" class="form-control @error('supplier') is-invalid @enderror"
            value="{{ old('supplier', $editing ? $stockIn->supplier : '') }}" maxlength="255" placeholder="Contoh: AmiAmi, distributor lokal">
        @error('supplier')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="quantity" class="form-label">Jumlah <span class="text-danger">*</span></label>
        <input type="number" name="quantity" id="quantity" min="1" step="1" required
            class="form-control @error('quantity') is-invalid @enderror"
            value="{{ old('quantity', $editing ? $stockIn->quantity : 1) }}">
        @error('quantity')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label for="purchase_price" class="form-label">Harga Beli Satuan (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="purchase_price" id="purchase_price" min="0" step="1" required
            class="form-control @error('purchase_price') is-invalid @enderror"
            value="{{ old('purchase_price', $editing ? (int) $stockIn->purchase_price : '') }}">
        @error('purchase_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small id="total-info" class="form-text text-muted"></small>
    </div>
    <div class="col-md-3">
        <label for="ordered_at" class="form-label">Tanggal Pesan <span class="text-danger">*</span></label>
        <input type="date" name="ordered_at" id="ordered_at" required max="{{ date('Y-m-d') }}"
            class="form-control @error('ordered_at') is-invalid @enderror"
            value="{{ old('ordered_at', $editing ? $stockIn->ordered_at->format('Y-m-d') : date('Y-m-d')) }}">
        @error('ordered_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    @unless ($editing)
        <div class="col-md-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['ordered', 'shipped', 'received'] as $s)
                    <option value="{{ $s }}" @selected(old('status', 'ordered') === $s)>{{ App\Models\StockIn::STATUSES[$s] }}</option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12" id="update-cost-wrap">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="update_cost" value="1" id="update_cost" @checked(old('update_cost'))>
                <label class="form-check-label" for="update_cost">Perbarui harga modal produk dengan harga beli ini saat barang diterima</label>
            </div>
            <small class="text-muted">Status <b>Diterima</b> langsung menambah stok produk.</small>
        </div>
    @endunless

    <div class="col-12">
        <label for="notes" class="form-label">Catatan</label>
        <textarea name="notes" id="notes" rows="3" maxlength="1000"
            class="form-control @error('notes') is-invalid @enderror"
            placeholder="No. resi, no. invoice supplier, dll.">{{ old('notes', $editing ? $stockIn->notes : '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const product = document.getElementById('product_id');
            const price = document.getElementById('purchase_price');
            const qty = document.getElementById('quantity');
            const info = document.getElementById('total-info');
            // Isi harga beli default dari harga modal produk
            product.addEventListener('change', () => {
                const opt = product.selectedOptions[0];
                if (opt && opt.dataset.price && !price.value) price.value = opt.dataset.price;
                update();
            });
            function update() {
                const total = (parseFloat(price.value) || 0) * (parseInt(qty.value) || 0);
                info.textContent = total ? 'Total: Rp ' + total.toLocaleString('id-ID') : '';
            }
            price.addEventListener('input', update);
            qty.addEventListener('input', update);
            if (!price.value && product.value) product.dispatchEvent(new Event('change'));
            update();
        })();
    </script>
@endpush
