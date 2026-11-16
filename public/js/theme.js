/* Tombol mode terang / gelap untuk website & panel ([data-theme-toggle]).
   Pilihan disimpan di localStorage "kz-theme". Browser yang mendukung View Transitions
   menampilkan lingkaran yang membesar dari tombol; selain itu warna berganti halus. */
(function () {
    const root = document.documentElement;

    function apply(theme) {
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        try { localStorage.setItem('kz-theme', theme); } catch (e) {}
        window.dispatchEvent(new CustomEvent('themechange', { detail: theme }));
    }

    // Tema tujuan dicatat langsung saat klik: callback View Transition baru jalan di frame berikutnya,
    // jadi klik cepat beruntun tidak boleh membaca atribut yang belum berubah.
    let wanted = root.getAttribute('data-theme');

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;
        const next = wanted = wanted === 'dark' ? 'light' : 'dark';
        const r = btn.getBoundingClientRect();
        root.style.setProperty('--vt-x', `${r.left + r.width / 2}px`);
        root.style.setProperty('--vt-y', `${r.top + r.height / 2}px`);

        if (document.startViewTransition && !root.classList.contains('reduce-motion')) {
            let applied = false;
            const vt = document.startViewTransition(() => { applied = true; apply(next); });
            // Tanpa frame (tab latar / panel tersembunyi) callback tertahan: lewati animasinya, tema tetap diterapkan
            setTimeout(() => { if (!applied) vt.skipTransition(); }, 400);
            return;
        }
        root.classList.add('theme-fade');
        apply(next);
        setTimeout(() => root.classList.remove('theme-fade'), 600);
    });
})();
