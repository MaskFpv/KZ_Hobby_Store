@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <x-flash-errors />

        <div class="card p-4">
            <h4 class="card-title">
                <a href="{{ route('stock-opnames.index') }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                Stok Opname
            </h4>
            <p class="text-muted mb-3">
                Hitung barang di rak, lalu isi kolom <b>Stok Fisik</b>. Hanya baris yang diisi yang disimpan.
                Stok sistem akan disamakan dengan stok fisik.
            </p>

            <form method="GET" action="{{ route('stock-opnames.create') }}" class="row g-2 align-items-end mb-3">
                <div class="col-8 col-md-4">
                    <label class="form-label small mb-1">Tampilkan kategori</label>
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected($categoryId == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-3">
                    <input type="search" id="opname-search" class="form-control form-control-sm" placeholder="Cari nama / SKU...">
                </div>
            </form>

            <form method="POST" action="{{ route('stock-opnames.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="opname_date" class="form-label">Tanggal Opname</label>
                        <input type="date" name="opname_date" id="opname_date" max="{{ date('Y-m-d') }}" required
                            class="form-control @error('opname_date') is-invalid @enderror"
                            value="{{ old('opname_date', date('Y-m-d')) }}">
                        @error('opname_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-9">
                        <label for="notes" class="form-label">Catatan umum</label>
                        <input type="text" name="notes" id="notes" maxlength="500" class="form-control"
                            value="{{ old('notes') }}" placeholder="Contoh: Opname akhir bulan">
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 60vh;">
                    <table class="table table-sm table-hover align-middle" id="opname-table">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th class="text-center" style="width: 110px;">Stok Sistem</th>
                                <th class="text-center" style="width: 140px;">Stok Fisik</th>
                                <th class="text-center" style="width: 90px;">Selisih</th>
                                <th style="width: 240px;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $p)
                                <tr data-search="{{ strtolower($p->name . ' ' . $p->sku) }}">
                                    <td>
                                        <div class="fw-semibold">{{ $p->name }}</div>
                                        <small class="text-muted">{{ $p->sku }}</small>
                                    </td>
                                    <td>{{ $p->category->name }}</td>
                                    <td class="text-center system">{{ $p->stock }}</td>
                                    <td>
                                        <input type="number" min="0" step="1" name="counts[{{ $p->id }}][physical]"
                                            value="{{ old("counts.{$p->id}.physical") }}"
                                            class="form-control form-control-sm text-center physical @error("counts.{$p->id}.physical") is-invalid @enderror">
                                    </td>
                                    <td class="text-center diff">-</td>
                                    <td>
                                        <input type="text" maxlength="255" name="counts[{{ $p->id }}][notes]"
                                            value="{{ old("counts.{$p->id}.notes") }}" class="form-control form-control-sm"
                                            placeholder="Rusak / hilang / salah input">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Tidak ada produk</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="filled-info">0 produk diisi</span>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan hasil opname? Stok sistem akan disesuaikan.')">
                        <i class="bi bi-save"></i> Simpan Hasil Opname
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const rows = document.querySelectorAll('#opname-table tbody tr[data-search]');
            const info = document.getElementById('filled-info');
            function refresh() {
                let filled = 0;
                rows.forEach(row => {
                    const input = row.querySelector('.physical');
                    const cell = row.querySelector('.diff');
                    if (input.value === '') { cell.textContent = '-'; cell.className = 'text-center diff'; return; }
                    filled++;
                    const d = parseInt(input.value) - parseInt(row.querySelector('.system').textContent);
                    cell.textContent = (d > 0 ? '+' : '') + d;
                    cell.className = 'text-center diff fw-bold ' + (d === 0 ? 'text-muted' : (d > 0 ? 'text-success' : 'text-danger'));
                });
                info.textContent = filled + ' produk diisi';
            }
            document.querySelectorAll('.physical').forEach(i => i.addEventListener('input', refresh));
            document.getElementById('opname-search').addEventListener('input', function() {
                const q = this.value.toLowerCase();
                rows.forEach(r => r.style.display = r.dataset.search.includes(q) ? '' : 'none');
            });
            refresh();
        })();
    </script>
@endpush
