@extends('layouts.app')

@section('title', 'Tambah Member')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                @include('app.customers.form-inputs')
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('customers.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
