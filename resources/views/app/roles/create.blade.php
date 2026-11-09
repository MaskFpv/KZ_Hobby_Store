@extends('layouts.app')

@section('title', 'Tambah Role')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                @include('app.roles.form-inputs')
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('roles.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
