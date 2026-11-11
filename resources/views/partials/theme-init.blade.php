{{-- Tema terang/gelap & pilihan hemat animasi dipasang sebelum CSS dirender (tanpa kedip). Pilihan tersimpan di browser (kz-theme), dipakai website & panel. --}}
<script>
    (function () {
        var t = null;
        try { t = localStorage.getItem('kz-theme'); } catch (e) {}
        t = t || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        document.documentElement.setAttribute('data-theme', t);
        document.documentElement.setAttribute('data-bs-theme', t);
        // "Hemat animasi" (dipilih di footer website) berlaku juga untuk panel
        try { if (localStorage.getItem('kz-motion') === 'reduced') document.documentElement.classList.add('reduce-motion'); } catch (e) {}
    })();
</script>
