@extends('layouts.app')

@section('title', 'Tambah Permission')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('permissions.store') }}">
                @csrf
                @include('app.permissions.form-inputs')
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('permissions.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
