@props(['product'])

{{-- Foto produk; bila belum ada foto, ilustrasi SVG khas per kategori (bukan kotak kosong). --}}
@if ($product->image_url)
    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" {{ $attributes }}>
@else
    @php
        $kanji = $product->category->kanji ?? '品';
        // Warna stabil per produk: diambil dari id
        $hues = [[330, 190], [280, 330], [190, 260], [345, 45], [210, 300]];
        [$h1, $h2] = $hues[$product->id % count($hues)];
        $gid = 'g' . $product->id . '-' . substr(md5(uniqid('', true)), 0, 6);
        $initials = collect(preg_split('/\s+/', $product->name))->take(3)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    @endphp
    <svg viewBox="0 0 400 400" role="img" aria-label="{{ $product->name }}" preserveAspectRatio="xMidYMid slice" {{ $attributes }}>
        <defs>
            <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="hsl({{ $h1 }} 85% 22%)" />
                <stop offset="1" stop-color="hsl({{ $h2 }} 80% 10%)" />
            </linearGradient>
        </defs>
        <rect width="400" height="400" fill="url(#{{ $gid }})" />
        <g fill="none" stroke="hsl({{ $h1 }} 100% 70% / .25)">
            <circle cx="300" cy="90" r="120" />
            <circle cx="300" cy="90" r="80" stroke-dasharray="4 8" />
            <path d="M0 330 L400 250" />
            <path d="M0 360 L400 280" stroke="hsl({{ $h2 }} 100% 70% / .2)" />
        </g>
        <text x="40" y="300" font-family="Dela Gothic One, sans-serif" font-size="230" fill="none"
            stroke="hsl({{ $h1 }} 100% 75% / .55)" stroke-width="2">{{ $kanji }}</text>
        <text x="40" y="360" font-family="JetBrains Mono, monospace" font-size="22" letter-spacing="6"
            fill="hsl({{ $h2 }} 100% 85% / .8)">{{ strtoupper($initials) }} ・ ORIGINAL</text>
    </svg>
@endif
