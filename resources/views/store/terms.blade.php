@extends('layouts.store')

@section('title', 'Syarat & Ketentuan')

@section('content')
    <div class="wrap" style="padding:2.5rem 0 4rem">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>SYARAT & KETENTUAN</span></nav>
        <span class="eyebrow">利用規約</span>
        <h1 class="section-title" data-split>Syarat &amp; Ketentuan</h1>
        <p class="muted mono" style="font-size:.8rem">BERLAKU SEJAK 11 NOVEMBER 2026</p>

        <div class="prose" data-reveal>
            <h2>1. Akun member</h2>
            <ul>
                <li>Data pendaftaran (nama, email, nomor WhatsApp) harus benar agar toko bisa menghubungi Anda.</li>
                <li>Satu email hanya untuk satu akun. Jaga kerahasiaan password Anda.</li>
                <li>Chat dengan CS dan pre-order online hanya tersedia untuk member yang sudah login.</li>
            </ul>

            <h2>2. Pesanan ready stock</h2>
            <ul>
                <li>Semua pesanan online <strong>diambil di toko</strong>: {{ config('store.address') }} ({{ config('store.hours') }}). Tidak ada pengiriman.</li>
                <li>Stok disisihkan untuk Anda sejak pesanan dibuat. Pesanan yang tidak dibayar dalam {{ config('store.payment_due_hours') }} jam dibatalkan otomatis.</li>
                <li>Pesanan yang sudah dibayar dapat diambil setelah berstatus <em>Siap Diambil</em>, dengan menunjukkan kode pesanan.</li>
            </ul>

            <h2>3. Pre-order (PO)</h2>
            <ul>
                <li>PO dikunci dengan DP minimal <strong>{{ config('store.dp_min_percent') }}%</strong> dari harga. Sisa tagihan boleh dicicil dan wajib lunas saat pengambilan.</li>
                <li>Tanggal rilis mengikuti pabrikan dan dapat mundur. Toko akan mengabari bila ada perubahan.</li>
                <li>DP tidak dapat dikembalikan bila pembatalan berasal dari pembeli, kecuali pabrikan membatalkan produk.</li>
                <li>Unit PO disisihkan sesuai urutan pemesanan; barang yang sudah tiba tidak dijual ke pembeli lain.</li>
            </ul>

            <h2>4. Pembayaran</h2>
            <ul>
                <li>Metode: QRIS, Virtual Account, dan kartu kredit/debit.</li>
                <li>Data kartu tidak disimpan. Toko hanya mencatat jenis kartu dan 4 digit terakhir.</li>
                <li><em>Catatan prototipe: pembayaran di website ini bersifat simulasi untuk keperluan demo; tidak ada dana yang ditarik.</em></li>
            </ul>

            <h2>5. Produk</h2>
            <ul>
                <li>Semua produk dijamin original. Kondisi <strong>MIB</strong> = baru segel, <strong>BIB</strong> = second dengan box.</li>
                <li>Foto dapat berbeda sedikit dengan produk asli karena pencahayaan.</li>
            </ul>

            <h2>6. Privasi</h2>
            <p>Data Anda hanya dipakai untuk memproses pesanan, PO, dan layanan chat. Data tidak dijual atau dibagikan ke pihak lain.</p>
        </div>
    </div>
@endsection
