@extends('layouts.app')

@section('title', 'Edit Member')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('customers.update', $customer) }}">
                @csrf
                @method('PUT')
                @include('app.customers.form-inputs')
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('customers.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
