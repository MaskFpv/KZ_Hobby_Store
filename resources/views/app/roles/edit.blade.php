@extends('layouts.app')

@section('title', 'Atur Role: ' . $role->name)

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')
                @include('app.roles.form-inputs')
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('roles.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                    @unless ($locked)
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                    @endunless
                </div>
            </form>
        </div>
    </div>
@endsection
