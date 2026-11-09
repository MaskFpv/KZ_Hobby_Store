<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $currentMenu['label'] ?? 'Panel' }} · {{ config('store.name') }}</title>
    <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">

    {{-- Font & library (CDN, versi dikunci) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/notyf@3.10.0/notyf.min.css" rel="stylesheet">

    {{-- Tema aplikasi: public/css/app.css --}}
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">

    @livewireStyles
    @stack('styles')
</head>

<body>
    @include('layouts.partials.sidebar')
    <div class="sidebar-backdrop"></div>

    <div class="app-main">
        @include('layouts.partials.topbar')

        <main class="app-content">
            @yield('content')
        </main>
    </div>

    @stack('modals')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.6/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/notyf@3.10.0/notyf.min.js"></script>

    @livewireScripts
    <x-livewire-alert::scripts />

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>

    {{-- Notifikasi flash --}}
    <script>
        (function() {
            const notyf = new Notyf({ duration: 4000, dismissible: true, position: { x: 'right', y: 'top' } });
            @if (session('success'))
                notyf.success(@json(session('success')));
            @endif
            @foreach ($errors->all() as $error)
                notyf.error(@json($error));
            @endforeach
        })();
    </script>

    @stack('scripts')
</body>

</html>
