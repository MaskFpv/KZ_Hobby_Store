@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card p-4">
            <h4 class="card-title">
                <a href="{{ route('stock-ins.show', $stockIn) }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                Edit Stok Masuk {{ $stockIn->code }}
            </h4>
            <div class="card-body">
                <form method="POST" action="{{ route('stock-ins.update', $stockIn) }}" class="mt-2">
                    @csrf
                    @method('PUT')
                    @include('app.stock_ins.form-inputs')

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('stock-ins.show', $stockIn) }}" class="btn btn-light">@lang('crud.common.back')</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
