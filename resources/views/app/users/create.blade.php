@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
    <x-flash-errors />
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('app.users.form-inputs')
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('users.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
