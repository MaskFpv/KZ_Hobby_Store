@extends('layouts.app')

@section('title', 'Edit Kategori')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('app.categories.form-inputs')
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('categories.index') }}" class="btn btn-light">@lang('crud.common.back')</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
