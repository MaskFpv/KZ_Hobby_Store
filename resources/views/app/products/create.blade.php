@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card p-4">
            <h4 class="card-title">
                <a href="{{ route('products.index') }}" class="me-3"><i class="bi bi-arrow-left"></i></a>
                Tambah Produk
            </h4>
            <div class="card-body">
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="mt-2">
                    @csrf
                    @include('app.products.form-inputs')

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('products.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
