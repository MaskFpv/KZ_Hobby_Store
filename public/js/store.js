/* ==========================================================================
   Website pelanggan — interaksi & animasi.
   Butuh (CDN, opsional): GSAP + ScrollTrigger, Lenis.
   Tanpa library / dengan "reduce motion": konten tetap tampil normal.
   ========================================================================== */
(function () {
    'use strict';

    window.__storeReady = true; // dicek oleh skrip pengaman di <head>
    const root = document.documentElement;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const hasGsap = !!(window.gsap && window.ScrollTrigger);
    const motion = hasGsap && !reduce;

    if (!motion) root.classList.remove('js-motion');
    if (hasGsap) gsap.registerPlugin(ScrollTrigger);

    /* ---------- Smooth scroll (Lenis) ---------- */
    let lenis = null;
    if (motion && window.Lenis) {
        lenis = new Lenis({ duration: 1.1, smoothWheel: true });
        lenis.on('scroll', ScrollTrigger.update);
        gsap.ticker.add((t) => lenis.raf(t * 1000));
        gsap.ticker.lagSmoothing(0);
    }

    /* ---------- Header & menu mobile ---------- */
    const header = document.querySelector('.site-header');
    const onScroll = () => header && header.classList.toggle('is-scrolled', window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    document.querySelectorAll('[data-menu-toggle]').forEach((btn) => btn.addEventListener('click', () => {
        const open = document.body.classList.toggle('menu-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.querySelector('i').className = open ? 'bi bi-x-lg' : 'bi bi-list';
        if (lenis) open ? lenis.stop() : lenis.start();
    }));

    document.querySelectorAll('[data-filters-toggle]').forEach((btn) => btn.addEventListener('click', () => {
        document.body.classList.toggle('filters-open');
    }));

    /* ---------- Tirai transisi halaman ---------- */
    const curtain = document.querySelector('.page-curtain');
    const panels = curtain ? curtain.querySelectorAll('span') : [];
    const curtainLogo = curtain ? curtain.querySelector('.curtain-logo') : null;

    function revealPage() {
        if (!curtain) return;
        if (!motion) { curtain.style.display = 'none'; return; }
        gsap.set(panels, { scaleY: 1, transformOrigin: 'bottom' });
        gsap.timeline()
            .to(curtainLogo, { opacity: 1, duration: .25 })
            .to(curtainLogo, { opacity: 0, duration: .25, delay: .1 })
            .to(panels, { scaleY: 0, duration: .7, ease: 'power4.inOut', stagger: .06 }, '-=.1')
            .add(introAnimations, '-=.45');
    }

    function leaveTo(url) {
        gsap.set(panels, { transformOrigin: 'top' });
        gsap.timeline({ onComplete: () => { window.location.href = url; } })
            .to(panels, { scaleY: 1, duration: .55, ease: 'power4.inOut', stagger: .05 })
            .to(curtainLogo, { opacity: 1, duration: .2 }, '-=.2');
    }

    if (motion && curtain) {
        document.addEventListener('click', (e) => {
            const a = e.target.closest('a[href]');
            if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const url = new URL(a.href, window.location.href);
            if (a.target === '_blank' || a.hasAttribute('download') || a.dataset.noTransition !== undefined) return;
            if (url.origin !== window.location.origin || url.pathname.startsWith('/admin')) return;
            if (url.pathname === window.location.pathname && url.hash) return;
            e.preventDefault();
            leaveTo(url.href);
        });
        // Kembali via tombol back (bfcache): tutup tirai lagi
        window.addEventListener('pageshow', (e) => { if (e.persisted) gsap.set(panels, { scaleY: 0 }); });
    }

    /* ---------- Animasi masuk & saat scroll ---------- */
    function splitChars(el) {
        const text = el.textContent;
        el.setAttribute('aria-label', text);
        el.innerHTML = '';
        [...text].forEach((ch) => {
            const s = document.createElement('span');
            s.className = 'split-char';
            s.setAttribute('aria-hidden', 'true');
            s.textContent = ch === ' ' ? ' ' : ch;
            el.appendChild(s);
        });
        return el.querySelectorAll('.split-char');
    }

    let introDone = false;
    function introAnimations() {
        if (introDone || !motion) return;
        introDone = true;

        document.querySelectorAll('[data-split]').forEach((el) => {
            const chars = splitChars(el);
            gsap.from(chars, {
                yPercent: 110, rotate: 8, opacity: 0, duration: .9, ease: 'power4.out', stagger: .025,
                scrollTrigger: el.closest('.hero') ? null : { trigger: el, start: 'top 85%' },
            });
        });

        ScrollTrigger.batch('[data-reveal]', {
            start: 'top 88%',
            onEnter: (els) => gsap.to(els, { opacity: 1, y: 0, duration: 1, ease: 'power3.out', stagger: .08, overwrite: true }),
        });

        document.querySelectorAll('[data-parallax]').forEach((el) => {
            gsap.to(el, {
                yPercent: parseFloat(el.dataset.parallax) * 100, ease: 'none',
                scrollTrigger: { trigger: el.closest('section') || el, start: 'top bottom', end: 'bottom top', scrub: true },
            });
        });

        const line = document.querySelector('.steps .progress-line');
        if (line) {
            gsap.to(line, {
                width: '88%', ease: 'none',
                scrollTrigger: { trigger: '.steps', start: 'top 75%', end: 'bottom 55%', scrub: true },
            });
            gsap.from('.step .dot', {
                scale: .4, opacity: 0, duration: .6, stagger: .15, ease: 'back.out(2)',
                scrollTrigger: { trigger: '.steps', start: 'top 75%' },
            });
        }

        document.querySelectorAll('[data-count]').forEach((el) => {
            const end = parseInt(el.dataset.count, 10) || 0;
            const obj = { v: 0 };
            gsap.to(obj, { v: end, duration: 1.6, ease: 'power2.out', delay: .3, onUpdate: () => { el.textContent = Math.round(obj.v); } });
        });
    }

    /* ---------- Karosel kartu unggulan di hero ---------- */
    const cards = [...document.querySelectorAll('.hero-card')];
    if (cards.length) {
        const slots = [
            { x: 0, z: 120, rotY: 0, scale: 1, opacity: 1, blur: 0, zIndex: 5 },
            { x: 58, z: -60, rotY: -24, scale: .82, opacity: .65, blur: 1, zIndex: 4 },
            { x: -58, z: -60, rotY: 24, scale: .82, opacity: .65, blur: 1, zIndex: 3 },
            { x: 0, z: -260, rotY: 0, scale: .6, opacity: 0, blur: 4, zIndex: 1 },
        ];
        let start = 0;
        const place = (animate) => cards.forEach((card, i) => {
            const s = slots[Math.min((i - start + cards.length) % cards.length, slots.length - 1)];
            const props = {
                // x/y = 0: buang translate(-50%) dari CSS agar tidak dobel dengan xPercent/yPercent
                x: 0, y: 0, xPercent: -50 + s.x, yPercent: -50, z: s.z, rotationY: s.rotY, scale: s.scale,
                opacity: s.opacity, filter: `blur(${s.blur}px)`, zIndex: s.zIndex,
            };
            if (hasGsap) gsap.to(card, { ...props, duration: animate && motion ? 1.1 : 0, ease: 'power3.inOut' });
            else Object.assign(card.style, { opacity: s.opacity, zIndex: s.zIndex });
        });
        place(false);
        if (cards.length > 1 && motion) setInterval(() => { start = (start + 1) % cards.length; place(true); }, 3600);
    }

    /* ---------- Tilt 3D & tombol magnet (mouse saja) ---------- */
    if (motion && finePointer) {
        document.querySelectorAll('[data-tilt]').forEach((el) => {
            el.addEventListener('mousemove', (e) => {
                const r = el.getBoundingClientRect();
                const px = (e.clientX - r.left) / r.width - .5;
                const py = (e.clientY - r.top) / r.height - .5;
                gsap.to(el, { rotationY: px * 12, rotationX: -py * 12, transformPerspective: 900, duration: .4, ease: 'power2.out' });
            });
            el.addEventListener('mouseleave', () => gsap.to(el, { rotationY: 0, rotationX: 0, duration: .7, ease: 'elastic.out(1, .5)' }));
        });

        document.querySelectorAll('[data-magnetic]').forEach((el) => {
            el.addEventListener('mousemove', (e) => {
                const r = el.getBoundingClientRect();
                gsap.to(el, { x: (e.clientX - r.left - r.width / 2) * .3, y: (e.clientY - r.top - r.height / 2) * .4, duration: .4 });
            });
            el.addEventListener('mouseleave', () => gsap.to(el, { x: 0, y: 0, duration: .6, ease: 'elastic.out(1, .4)' }));
        });

        // Kursor kustom
        const dot = document.querySelector('.cursor-dot');
        const ring = document.querySelector('.cursor-ring');
        if (dot && ring) {
            const xDot = gsap.quickTo(dot, 'x', { duration: .1 });
            const yDot = gsap.quickTo(dot, 'y', { duration: .1 });
            const xRing = gsap.quickTo(ring, 'x', { duration: .45, ease: 'power3' });
            const yRing = gsap.quickTo(ring, 'y', { duration: .45, ease: 'power3' });
            window.addEventListener('mousemove', (e) => { xDot(e.clientX); yDot(e.clientY); xRing(e.clientX); yRing(e.clientY); });
            document.addEventListener('mouseover', (e) => ring.classList.toggle('is-hover', !!e.target.closest('a, button, [data-tilt], label')));
        }
    } else {
        document.querySelectorAll('.cursor-dot, .cursor-ring').forEach((el) => el.remove());
    }

    /* ---------- Zoom foto produk ---------- */
    document.querySelectorAll('[data-zoom]').forEach((box) => {
        const media = box.firstElementChild;
        if (!media || !finePointer) return;
        box.addEventListener('mousemove', (e) => {
            const r = box.getBoundingClientRect();
            media.style.transformOrigin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
            media.style.transform = 'scale(1.8)';
        });
        box.addEventListener('mouseleave', () => { media.style.transform = ''; });
    });

    /* ---------- Hitung mundur deadline PO ---------- */
    const countdowns = document.querySelectorAll('[data-deadline]');
    if (countdowns.length) {
        const tick = () => countdowns.forEach((el) => {
            const diff = Math.max(0, new Date(el.dataset.deadline).getTime() - Date.now());
            const parts = {
                d: Math.floor(diff / 864e5),
                h: Math.floor(diff / 36e5) % 24,
                m: Math.floor(diff / 6e4) % 60,
                s: Math.floor(diff / 1e3) % 60,
            };
            Object.entries(parts).forEach(([k, v]) => {
                const b = el.querySelector(`[data-unit="${k}"]`);
                if (b) b.textContent = String(v).padStart(2, '0');
            });
        });
        tick();
        setInterval(tick, 1000);
    }

    /* ---------- Stepper jumlah ---------- */
    document.querySelectorAll('[data-qty]').forEach((box) => {
        const input = box.querySelector('input');
        const max = parseInt(input.max, 10) || 99;
        box.querySelectorAll('button').forEach((b) => b.addEventListener('click', () => {
            const v = (parseInt(input.value, 10) || 1) + (b.dataset.step === '+' ? 1 : -1);
            input.value = Math.min(max, Math.max(1, v));
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }));
    });

    /* ---------- Refresh captcha ---------- */
    document.querySelectorAll('[data-captcha-refresh]').forEach((btn) => btn.addEventListener('click', () => {
        const img = document.querySelector('[data-captcha-img]');
        img.src = img.dataset.src + '?t=' + Date.now();
    }));

    /* ---------- Toast ---------- */
    function hideLater(t) {
        setTimeout(() => { t.style.transition = 'opacity .5s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 500); }, 6000);
    }
    function toast(message, isError) {
        const t = document.createElement('div');
        t.className = 'toast' + (isError ? ' is-error' : '');
        t.setAttribute('role', 'status');
        t.innerHTML = `<i class="bi ${isError ? 'bi-exclamation-octagon' : 'bi-stars'}"></i><span></span>`;
        t.querySelector('span').textContent = message;
        document.body.appendChild(t);
        if (motion) gsap.from(t, { y: 40, opacity: 0, duration: .5, ease: 'back.out(1.6)' });
        hideLater(t);
    }
    document.querySelectorAll('.toast').forEach((t) => {
        if (motion) gsap.from(t, { y: 40, opacity: 0, duration: .6, ease: 'back.out(1.6)', delay: 1 });
        hideLater(t);
    });

    /* ---------- Tambah ke keranjang: kirim via fetch + animasi terbang ---------- */
    document.querySelectorAll('form[data-add-to-cart]').forEach((form) => form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button:not([type="button"])');
        btn.disabled = true;
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            const data = await res.json();
            if (!res.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menambahkan.');
                toast(first, true);
                return;
            }
            flyToCart(document.querySelector('[data-fly-source]') || btn);
            const count = document.querySelector('[data-cart-count]');
            if (count) { count.hidden = false; count.textContent = data.count; }
            toast(data.message, false);
        } catch (err) {
            form.submit(); // fallback tanpa JS fetch
        } finally {
            btn.disabled = false;
        }
    }));

    function flyToCart(source) {
        const target = document.querySelector('[data-cart-icon]');
        if (!motion || !target || !source) return;
        const s = source.getBoundingClientRect();
        const t = target.getBoundingClientRect();
        const dot = document.createElement('div');
        dot.className = 'fly-dot';
        const media = source.querySelector('img, svg');
        if (media) dot.appendChild(media.cloneNode(true));
        document.body.appendChild(dot);
        gsap.set(dot, { left: s.left + s.width / 2 - 27, top: s.top + s.height / 2 - 27, scale: 1.6 });
        gsap.timeline({ onComplete: () => dot.remove() })
            .to(dot, { left: t.left + t.width / 2 - 27, duration: .9, ease: 'power2.inOut' }, 0)
            .to(dot, { top: t.top + t.height / 2 - 27, duration: .9, ease: 'back.in(1.4)' }, 0)
            .to(dot, { scale: .25, opacity: .6, duration: .9, ease: 'power2.in' }, 0);
        gsap.fromTo(target, { scale: 1 }, { scale: 1.25, duration: .2, yoyo: true, repeat: 1, delay: .85 });
    }

    /* ---------- Pilihan metode bayar: tampilkan daftar bank hanya untuk VA ---------- */
    document.querySelectorAll('[data-method-picker]').forEach((picker) => {
        const banks = document.querySelector('[data-bank-picker]');
        const sync = () => {
            const checked = picker.querySelector('input:checked');
            if (banks) banks.hidden = !checked || checked.value !== 'va';
        };
        picker.addEventListener('change', sync);
        sync();
    });

    /* ---------- Hitung mundur batas bayar ---------- */
    document.querySelectorAll('[data-expires]').forEach((el) => {
        const end = new Date(el.dataset.expires).getTime();
        const tick = () => {
            const d = Math.max(0, end - Date.now());
            const h = String(Math.floor(d / 36e5)).padStart(2, '0');
            const m = String(Math.floor(d / 6e4) % 60).padStart(2, '0');
            const s = String(Math.floor(d / 1e3) % 60).padStart(2, '0');
            el.textContent = d ? `${h}:${m}:${s}` : 'KADALUARSA';
        };
        tick();
        setInterval(tick, 1000);
    });

    /* ---------- QR (QRIS prototipe & kode ambil barang) ---------- */
    document.querySelectorAll('[data-qr]').forEach((el) => {
        if (window.QRCode) new QRCode(el, { text: el.dataset.qr, width: 512, height: 512, correctLevel: QRCode.CorrectLevel.M });
    });

    /* ---------- Salin teks ---------- */
    document.querySelectorAll('[data-copy]').forEach((btn) => btn.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(btn.dataset.copy); toast('Disalin: ' + btn.dataset.copy, false); } catch (e) { /* abaikan */ }
    }));

    /* ---------- Pratinjau kartu & animasi proses bayar ---------- */
    const cardForm = document.querySelector('[data-card-form]');
    if (cardForm) {
        const num = cardForm.querySelector('[name="card_number"]');
        const name = cardForm.querySelector('[name="card_name"]');
        const exp = cardForm.querySelector('[name="card_expiry"]');
        num.addEventListener('input', () => {
            const d = num.value.replace(/\D/g, '').slice(0, 19);
            num.value = d.replace(/(.{4})/g, '$1 ').trim();
            document.querySelector('[data-card-num]').textContent = (d + '•'.repeat(Math.max(0, 16 - d.length))).replace(/(.{4})/g, '$1 ').trim();
        });
        name.addEventListener('input', () => { document.querySelector('[data-card-name]').textContent = name.value || 'NAMA PEMILIK'; });
        exp.addEventListener('input', () => {
            const d = exp.value.replace(/\D/g, '').slice(0, 4);
            exp.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
            document.querySelector('[data-card-exp]').textContent = exp.value || 'MM/YY';
        });
    }
    document.querySelectorAll('form[data-processing]').forEach((form) => form.addEventListener('submit', (e) => {
        if (form.dataset.sent) return;
        e.preventDefault();
        form.dataset.sent = '1';
        const overlay = document.querySelector('.processing');
        if (overlay) overlay.classList.add('show');
        setTimeout(() => form.submit(), motion ? 1600 : 0); // jeda supaya terasa seperti gateway sungguhan
    }));

    /* ---------- Isi timeline status sesuai progres ---------- */
    document.querySelectorAll('.timeline').forEach((tl) => {
        const fill = tl.querySelector('.fill');
        const nodes = tl.querySelectorAll('li.done, li.current');
        if (!fill || !nodes.length) return;
        const last = nodes[nodes.length - 1];
        const h = last.offsetTop + 20 - 8;
        if (motion) gsap.fromTo(fill, { height: 0 }, { height: h, duration: 1.4, ease: 'power3.out', delay: .6 });
        else fill.style.height = h + 'px';
    });

    /* ---------- Chat CS ---------- */
    // Tamu: kartu ajakan masuk (chat khusus member)
    const guestChat = document.querySelector('[data-chat-guest]');
    const toggleGuestChat = (force) => {
        if (!guestChat) return;
        const panel = guestChat.querySelector('.chat-panel');
        const open = force ?? panel.hidden;
        panel.hidden = !open;
        guestChat.classList.toggle('is-open', open);
        guestChat.querySelector('.chat-launcher').setAttribute('aria-expanded', String(open));
    };
    document.querySelectorAll('[data-chat-guest-toggle]').forEach((b) => b.addEventListener('click', () => toggleGuestChat()));

    // Tombol "Tanya CS" di halaman mana pun. data-chat-attach="product:12" ikut melampirkan item.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-chat-open]');
        if (!btn) return;
        e.preventDefault();
        if (!window.Livewire) return toggleGuestChat(true);
        const [type, id] = (btn.dataset.chatAttach || '').split(':');
        window.Livewire.emit('chat-open', type || null, id || null);
    });

    /* ---------- Mulai ---------- */
    if (curtain) revealPage(); else introAnimations();
})();
