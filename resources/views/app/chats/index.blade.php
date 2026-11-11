@extends('layouts.app')

@section('content')
    @livewire('admin.chat-inbox')
@endsection

@push('scripts')
    <script src="{{ asset('js/chat.js') }}?v={{ filemtime(public_path('js/chat.js')) }}"></script>
@endpush
