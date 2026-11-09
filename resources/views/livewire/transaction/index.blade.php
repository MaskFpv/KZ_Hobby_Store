<div>
    <div class="container-fluid">
        <div class="row g-4">
            {{-- ================= Katalog ================= --}}
            <div class="col-lg-8">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="m-0">Katalog Produk</h2>
                    <div class="input-group" style="max-width: 360px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.debounce.400ms="search" class="form-control"
                            placeholder="Cari nama / SKU..." aria-label="Cari produk">
                    </div>
                </div>

                <hr>

                <div class="mb-2">
                    <h6 class="text-muted mb-2">Kategori</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" wire:click="changeCategory(0)"
                            class="btn btn-sm btn-{{ $kategori_id == 0 ? 'primary' : 'outline-primary' }}">Semua</button>
                        @foreach ($categories as $category)
                            <button wire:key="category-{{ $category->id }}" type="button"
                                wire:click="changeCategory({{ $category->id }})"
                                class="btn btn-sm btn-{{ $kategori_id == $category->id ? 'primary' : 'outline-primary' }}">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-3">
                    <h6 class="text-muted mb-2">Brand / Pabrikan</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" wire:click="changeBrand(0)"
                            class="btn btn-sm btn-{{ $brand_id == 0 ? 'secondary' : 'outline-secondary' }}">Semua</button>
                        @foreach ($brands as $brand)
                            <button wire:key="brand-{{ $brand->id }}" type="button"
                                wire:click="changeBrand({{ $brand->id }})"
                                class="btn btn-sm btn-{{ $brand_id == $brand->id ? 'secondary' : 'outline-secondary' }}">
                                {{ $brand->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <hr>

                @error('stok-habis')
                    <div class="alert alert-danger py-2">{{ $message }}</div>
                @enderror

                <div class="row g-3">
                    @forelse ($products as $item)
                        @php $available = $item->available_stock > 0; @endphp
                        <div wire:key="product-{{ $item->id }}" class="col-sm-6 col-xl-4">
                            <div class="card h-100 pos-product"
                                style="border-radius: 14px; cursor: {{ $available ? 'pointer' : 'not-allowed' }}; opacity: {{ $available ? 1 : .55 }};"
                                @if ($available) wire:click="pilihProduk({{ $item->id }})" @endif>
                                <div class="card-header bg-white d-flex justify-content-between align-items-center"
                                    style="border-radius: 14px 14px 0 0;">
                                    <small class="fw-semibold text-truncate">{{ $item->brand->name }}</small>
                                    <span class="badge {{ $item->condition === 'new' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ $item->condition === 'new' ? 'MIB' : 'BIB' }}
                                    </span>
                                </div>
                                <div class="text-center p-2">
                                    @if ($item->image)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                                            style="width: 120px; height: 120px; object-fit: cover; border-radius: 14px;">
                                    @else
                                        <div class="d-inline-flex align-items-center justify-content-center bg-light text-muted"
                                            style="width: 120px; height: 120px; border-radius: 14px;">
                                            <i class="bi bi-box-seam fs-1"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body pt-0">
                                    <div class="fw-semibold text-center">{{ $item->name }}</div>
                                    <div class="text-center text-muted small mb-2">{{ $item->sku }} · {{ $item->category->name }}</div>
                                    <div class="d-flex justify-content-between small">
                                        @if ($available)
                                            <span>Stok: {{ $item->available_stock }}
                                                @if ($item->available_stock < $item->stock)
                                                    <small class="text-muted">(+{{ $item->stock - $item->available_stock }} jatah PO)</small>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-danger">Stok habis</span>
                                        @endif
                                        <span class="fw-bold">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-search fs-2 d-block mb-2"></i>
                            Produk tidak ditemukan
                        </div>
                    @endforelse
                </div>

                <div class="d-flex justify-content-center mt-4 mb-2">
                    {{ $products->links() }}
                </div>
            </div>

            {{-- ================= Keranjang ================= --}}
            <div class="col-lg-4">
                <div class="card" style="min-height: 80vh;">
                    <div class="m-3 d-flex justify-content-between align-items-center">
                        <h4 class="m-0">Keranjang</h4>
                        @if (count($produk_detail) != 0)
                            <button data-bs-toggle="modal" data-bs-target="#modalDelete" type="button"
                                class="btn btn-light text-danger" title="Kosongkan keranjang">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </div>

                    <hr class="m-0">
                    <div class="flex-grow-1 px-3 py-2 mt-2 overflow-auto" style="max-height: 55vh;">
                        @forelse ($produk_detail as $item)
                            <div wire:key="cart-{{ $item['id'] }}" class="card py-2 px-3 mb-2" style="border-radius: 10px;">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div class="text-truncate">
                                        <div class="fw-semibold text-truncate">{{ $item['name'] }}</div>
                                        <small class="text-muted">Rp {{ number_format($item['price'], 0, ',', '.') }}</small>
                                        <div class="fw-bold">Rp {{ number_format($item['sub_total'], 0, ',', '.') }}</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" wire:click="decreaseQuantity({{ $item['id'] }})"
                                            class="btn btn-sm btn-outline-danger"><i class="bi bi-dash"></i></button>
                                        <span class="fw-semibold">{{ $item['quantity'] }}</span>
                                        <button type="button" wire:click="increaseQuantity({{ $item['id'] }})"
                                            class="btn btn-sm btn-outline-success"><i class="bi bi-plus"></i></button>
                                    </div>
                                </div>
                                @if ($item['id'] === $stokHabis)
                                    <small wire:poll.2s="hideStockMessage" class="text-danger">Stok tidak mencukupi!</small>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-muted p-4">
                                <i class="bi bi-cart3 fs-2 d-block mb-2"></i>
                                Klik produk untuk menambahkan ke keranjang
                            </div>
                        @endforelse
                    </div>
                    <div class="card-footer bg-white">
                        @if (count($produk_detail) != 0)
                            <div class="d-flex justify-content-between fw-bolder mb-2">
                                <span>Subtotal</span>
                                <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                            <button data-bs-toggle="modal" wire:click="setupNoFaktur()" data-bs-target="#modalCheckout"
                                type="button" class="btn btn-success w-100">Checkout</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.transaction.modal')
</div>
