@extends('layouts.app')

@section('title', 'Edit Kategori Artikel')

@section('content')
    <x-flash-errors />
    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <a href="{{ route('article-categories.index') }}" class="me-2"><i class="bi bi-arrow-left"></i></a>
                Edit kategori "{{ $category->name }}"
            </h2>
            <form method="POST" action="{{ route('article-categories.update', $category) }}">
                @csrf @method('PUT')
                @include('app.article-categories.form-fields')
                <div class="mt-3 d-flex justify-content-between">
                    <a href="{{ route('article-categories.index') }}" class="btn btn-light">Batal</a>
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
