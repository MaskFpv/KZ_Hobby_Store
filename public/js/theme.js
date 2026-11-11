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

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        const r = btn.getBoundingClientRect();
        root.style.setProperty('--vt-x', `${r.left + r.width / 2}px`);
        root.style.setProperty('--vt-y', `${r.top + r.height / 2}px`);

        if (document.startViewTransition && !root.classList.contains('reduce-motion')) {
            document.startViewTransition(() => apply(next));
            return;
        }
        root.classList.add('theme-fade');
        apply(next);
        setTimeout(() => root.classList.remove('theme-fade'), 600);
    });
})();
