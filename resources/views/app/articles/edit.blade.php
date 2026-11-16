@extends('layouts.app')

@section('title', 'Edit Artikel')

@section('content')
    <x-flash-errors />
    @include('app.articles.form')
@endsection
