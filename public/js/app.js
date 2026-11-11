/* ==========================================================================
   Script global panel. Butuh: jQuery, DataTables, SweetAlert2.
   ========================================================================== */
(function () {
    'use strict';

    /* ---------- Sidebar: mini (desktop) / buka-tutup (mobile) ---------- */
    const body = document.body;
    const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;

    try {
        if (localStorage.getItem('kz-sidebar-mini') === '1') body.classList.add('sidebar-mini');
    } catch (e) { /* storage diblokir: abaikan */ }

    document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (isMobile()) {
                body.classList.toggle('sidebar-open');
                return;
            }
            body.classList.toggle('sidebar-mini');
            try {
                localStorage.setItem('kz-sidebar-mini', body.classList.contains('sidebar-mini') ? '1' : '0');
            } catch (e) { /* abaikan */ }
        });
    });
    document.querySelector('.sidebar-backdrop')?.addEventListener('click', () => body.classList.remove('sidebar-open'));

    /* ---------- Jam topbar (WIB) ---------- */
    const clock = document.getElementById('topbar-clock');
    if (clock) {
        const fmt = new Intl.DateTimeFormat('id-ID', {
            weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta',
        });
        const tick = () => { clock.textContent = fmt.format(new Date()) + ' WIB'; };
        tick();
        setInterval(tick, 30000);
    }

    /* ---------- Konfirmasi hapus (tombol .btn-delete di dalam <form>) ---------- */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-delete');
        if (!btn) return;
        e.preventDefault();
        const form = btn.closest('form');
        Swal.fire({
            icon: 'warning',
            title: 'Hapus data?',
            text: btn.dataset.confirm || 'Data yang dihapus tidak bisa dikembalikan.',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#e5484d',
            reverseButtons: true,
        }).then((res) => { if (res.isConfirmed) form.submit(); });
    });

    /* ---------- Angka statistik menghitung naik (mis. "Rp 1.250.000") ---------- */
    if (!document.documentElement.classList.contains('reduce-motion')) {
        document.querySelectorAll('.stat-value').forEach((el) => {
            const m = el.textContent.trim().match(/^(\D*?)([\d.]+)(\D*)$/);
            const end = m ? parseInt(m[2].replace(/\./g, ''), 10) : 0;
            if (!end) return;
            const t0 = performance.now();
            const step = (t) => {
                const k = Math.min(1, (t - t0) / 900);
                el.textContent = m[1] + Math.round(end * (1 - Math.pow(1 - k, 3))).toLocaleString('id-ID') + m[3];
                if (k < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        });
    }

    /* ---------- DataTables untuk semua tabel #myTable ---------- */
    if (window.jQuery && jQuery.fn.DataTable) {
        jQuery('#myTable').DataTable({
            order: [],
            pageLength: 10,
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(disaring dari _MAX_ data)',
                zeroRecords: 'Data tidak ditemukan',
                emptyTable: 'Belum ada data',
                paginate: { previous: '‹', next: '›' },
            },
        });
    }
})();
