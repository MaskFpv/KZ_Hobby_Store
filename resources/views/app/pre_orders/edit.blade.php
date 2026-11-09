@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card p-4">
            <h4 class="card-title">
                <a href="{{ route('pre-orders.show', $preOrder) }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                Edit Pre-Order {{ $preOrder->code }}
            </h4>
            <p class="text-muted">Produk, jumlah, dan harga tidak bisa diubah setelah PO dibuat. Batalkan lalu buat PO baru bila perlu.</p>
            <div class="card-body">
                <form method="POST" action="{{ route('pre-orders.update', $preOrder) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="estimated_arrival" class="form-label">Estimasi Tiba</label>
                            <input type="date" name="estimated_arrival" id="estimated_arrival"
                                class="form-control @error('estimated_arrival') is-invalid @enderror"
                                value="{{ old('estimated_arrival', $preOrder->estimated_arrival?->format('Y-m-d')) }}">
                            @error('estimated_arrival')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" rows="3" maxlength="1000"
                                class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $preOrder->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('pre-orders.show', $preOrder) }}" class="btn btn-light">@lang('crud.common.back')</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
