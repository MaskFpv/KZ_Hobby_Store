{{-- Kop laporan PDF. Param: $title, $subtitle (opsional) --}}
<div class="header">
    <h2>{{ $title }}</h2>
    <p>{{ config('store.name') }} &middot; {{ config('store.address') }}</p>
    <p>{{ $subtitle ?? 'Per tanggal ' . now()->translatedFormat('d F Y') }}</p>
</div>
