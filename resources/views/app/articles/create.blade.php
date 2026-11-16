@extends('layouts.app')

@section('title', 'Tulis Artikel')

@section('content')
    <x-flash-errors />
    @include('app.articles.form')
@endsection
