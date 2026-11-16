/* ==========================================================================
   Website pelanggan — interaksi & animasi.
   Butuh (CDN, opsional): GSAP + ScrollTrigger, Lenis.
   Tanpa library / mode "hemat animasi": konten tetap tampil normal.
   ========================================================================== */
(function () {
    'use strict';

    window.__storeReady = true; // dicek oleh skrip pengaman di <head>
    const root = document.documentElement;
    // Animasi penuh secara bawaan; "hemat animasi" dipilih lewat tombol di footer (bukan setelan OS)
    const reduce = root.classList.contains('reduce-motion');
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
    // Huruf dibungkus per kata (nowrap) agar baris hanya patah di antara kata; elemen seperti <em> dipertahankan
    function splitChars(el) {
        el.setAttribute('aria-label', el.textContent.trim().replace(/\s+/g, ' '));
        const split = (src, dest) => [...src.childNodes].forEach((node) => {
            if (node.nodeType === 1) {
                const copy = node.cloneNode(false);
                split(node, copy);
                dest.appendChild(copy);
                return;
            }
            node.textContent.split(/(\s+)/).forEach((part) => {
                if (!part) return;
                if (/^\s+$/.test(part)) { dest.appendChild(document.createTextNode(' ')); return; }
                const word = document.createElement('span');
                word.className = 'split-word';
                [...part].forEach((ch) => {
                    const s = document.createElement('span');
                    s.className = 'split-char';
                    s.textContent = ch;
                    word.appendChild(s);
                });
                dest.appendChild(word);
            });
        });
        const holder = document.createElement('span');
        split(el, holder);
        holder.setAttribute('aria-hidden', 'true');
        el.replaceChildren(holder);
        return el.querySelectorAll('.split-char');
    }

    /* REMOVE_ME
            s.textContent = ch === ' ' ? ' ' : ch;
    REMOVE_ME */

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
    // Dipanggil ulang untuk kartu yang dimuat lewat fetch (filter katalog / artikel)
    function bindTilt(root) {
        if (!motion || !finePointer) return;
        root.querySelectorAll('[data-tilt]:not([data-tilt-bound])').forEach((el) => {
            el.setAttribute('data-tilt-bound', '');
            el.addEventListener('mousemove', (e) => {
                const r = el.getBoundingClientRect();
                const px = (e.clientX - r.left) / r.width - .5;
                const py = (e.clientY - r.top) / r.height - .5;
                gsap.to(el, { rotationY: px * 12, rotationX: -py * 12, transformPerspective: 900, duration: .4, ease: 'power2.out' });
            });
            el.addEventListener('mouseleave', () => gsap.to(el, { rotationY: 0, rotationX: 0, duration: .7, ease: 'elastic.out(1, .5)' }));
        });
    }
    bindTilt(document);

    if (motion && finePointer) {
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

    /* ---------- Artikel: daftar isi otomatis dari h2/h3 + sorot bagian yang sedang dibaca ---------- */
    const articleBody = document.querySelector('[data-article-body]');
    const toc = document.querySelector('[data-toc]');
    if (articleBody && toc) {
        const heads = [...articleBody.querySelectorAll('h2, h3')];
        if (heads.length > 1) {
            const list = toc.querySelector('ol');
            heads.forEach((h, i) => {
                h.id = h.id || `bagian-${i + 1}`;
                const li = document.createElement('li');
                li.className = h.tagName === 'H3' ? 'sub' : '';
                li.innerHTML = `<a href="#${h.id}" data-no-transition></a>`;
                li.firstChild.textContent = h.textContent;
                list.appendChild(li);
            });
            toc.hidden = false;
            toc.addEventListener('click', (e) => {
                const a = e.target.closest('a');
                if (!a) return;
                e.preventDefault();
                const target = document.querySelector(a.getAttribute('href'));
                lenis ? lenis.scrollTo(target, { offset: -100 }) : target.scrollIntoView({ behavior: 'smooth' });
            });
            const links = [...toc.querySelectorAll('a')];
            const spy = new IntersectionObserver((entries) => entries.forEach((en) => {
                if (!en.isIntersecting) return;
                links.forEach((l) => l.classList.toggle('active', l.getAttribute('href') === `#${en.target.id}`));
            }), { rootMargin: '-20% 0px -70% 0px' });
            heads.forEach((h) => spy.observe(h));
        }
        if (motion) {
            articleBody.querySelectorAll('img, blockquote, figure, table').forEach((el) => gsap.from(el, {
                opacity: 0, y: 40, scale: .97, duration: .9, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 88%' },
            }));
        }
    }

    /* ---------- Galeri foto produk: thumbnail + lightbox ---------- */
    const gallery = document.querySelector('[data-gallery]');
    const galleryData = gallery && gallery.querySelector('[data-gallery-urls]');
    if (galleryData) {
        const urls = JSON.parse(galleryData.textContent);
        const main = gallery.querySelector('[data-gallery-main]');
        const box = gallery.querySelector('[data-lightbox]');
        const big = box.querySelector('[data-lightbox-img]');
        let index = 0;
        const select = (i, animate = true) => {
            index = (i + urls.length) % urls.length;
            gallery.querySelectorAll('[data-gallery-thumb]').forEach((t) => t.classList.toggle('active', +t.dataset.galleryThumb === index));
            const swap = () => { main.src = urls[index]; big.src = urls[index]; box.querySelector('[data-lightbox-count]').textContent = `${index + 1} / ${urls.length}`; };
            if (motion && animate) {
                gsap.timeline()
                    .to([main, big], { opacity: 0, scale: .96, duration: .18, ease: 'power2.in', onComplete: swap })
                    .to([main, big], { opacity: 1, scale: 1, duration: .45, ease: 'power3.out' });
            } else swap();
        };
        gallery.addEventListener('click', (e) => {
            const thumb = e.target.closest('[data-gallery-thumb]');
            if (thumb) return select(+thumb.dataset.galleryThumb);
            const step = e.target.closest('[data-lightbox-step]');
            if (step) return select(index + +step.dataset.lightboxStep);
            if (e.target.closest('[data-lightbox-close]') || e.target === box) return box.close();
            if (e.target.closest('[data-gallery-open]')) {
                select(index, false);
                box.showModal();
                if (motion) gsap.fromTo(big, { scale: .85, opacity: 0 }, { scale: 1, opacity: 1, duration: .5, ease: 'back.out(1.4)' });
            }
        });
        gallery.querySelector('[data-gallery-open]').addEventListener('keydown', (e) => { if (e.key === 'Enter') e.currentTarget.click(); });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') select(index + 1);
            if (e.key === 'ArrowLeft') select(index - 1);
        });
        box.addEventListener('close', () => lenis?.start());
        box.addEventListener('toggle', () => { if (box.open) lenis?.stop(); });
    }

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

    /* ---------- Halaman Pre-Order: sorotan, kalkulator DP, filter & urutan ---------- */
    const poPage = document.querySelector('[data-po-page]');
    if (poPage) {
        const rp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

        // Kalkulator: DP = jumlah × harga × persen (dibulatkan ke atas), sisa dibayar saat barang tiba
        poPage.querySelectorAll('[data-spot]').forEach((spot) => {
            const price = +spot.dataset.price;
            const qty = spot.querySelector('[data-calc-qty] input');
            const range = spot.querySelector('[data-calc-range]');
            const link = spot.querySelector('[data-calc-link]');
            const base = link.getAttribute('href');
            const calc = () => {
                const total = price * (parseInt(qty.value, 10) || 1);
                const dp = Math.ceil(total * range.value / 100);
                spot.querySelector('[data-calc-pct]').textContent = range.value;
                spot.querySelector('[data-calc-dp]').textContent = rp(dp);
                spot.querySelector('[data-calc-rest]').textContent = rp(total - dp);
                range.style.setProperty('--fill', `${(range.value - range.min) / (range.max - range.min) * 100}%`);
                link.setAttribute('href', `${base}?qty=${qty.value}&dp=${dp}`);
            };
            qty.addEventListener('input', calc);
            range.addEventListener('input', calc);
            calc();
        });

        // Klik kartu = tampilkan di sorotan
        const show = (id) => {
            const next = poPage.querySelector(`[data-spot="${id}"]`);
            const current = poPage.querySelector('[data-spot].is-active');
            poPage.querySelectorAll('[data-po-card]').forEach((c) => c.classList.toggle('is-current', c.dataset.poCard === id));
            const area = poPage.querySelector('.po-spotlight');
            if (area.getBoundingClientRect().top < 0) (lenis ? lenis.scrollTo(area, { offset: -100 }) : area.scrollIntoView({ behavior: 'smooth' }));
            if (!next || next === current) return;
            const swap = () => {
                current.hidden = true;
                current.classList.remove('is-active');
                next.hidden = false;
                next.classList.add('is-active');
                if (motion) {
                    gsap.fromTo(next.querySelector('.spot-media'), { x: -60, opacity: 0, rotationY: 25 }, { x: 0, opacity: 1, rotationY: 0, duration: .8, ease: 'power3.out' });
                    gsap.fromTo(next.querySelectorAll('.spot-body > *'), { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: .6, stagger: .06, ease: 'power3.out' });
                }
            };
            motion ? gsap.to(current, { opacity: 0, y: -16, duration: .25, ease: 'power2.in', onComplete: () => { gsap.set(current, { clearProps: 'all' }); swap(); } }) : swap();
        };
        poPage.addEventListener('click', (e) => {
            const card = e.target.closest('[data-po-card]');
            if (!card || e.target.closest('a')) return; // link judul/gambar tetap ke halaman produk
            show(card.dataset.poCard);
        });
        poPage.addEventListener('keydown', (e) => {
            const card = e.target.closest('[data-po-card]');
            if (card && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); show(card.dataset.poCard); }
        });
        const first = poPage.querySelector('[data-po-card]');
        if (first) first.classList.add('is-current');

        // Filter & urutan instan
        const grid = poPage.querySelector('[data-po-grid]');
        const cards = [...grid.querySelectorAll('[data-po-card]')];
        let filter = '*';
        const apply = () => {
            const sort = poPage.querySelector('[data-po-sort]').value;
            const key = { deadline: (c) => +c.dataset.deadlineTs, cheap: (c) => +c.dataset.price, pricey: (c) => -c.dataset.price }[sort];
            const visible = cards.filter((c) => filter === '*' || c.dataset.tags.split(' ').includes(filter)).sort((a, b) => key(a) - key(b));
            cards.forEach((c) => { c.hidden = !visible.includes(c); });
            visible.forEach((c) => grid.appendChild(c));
            poPage.querySelector('[data-po-empty]').hidden = visible.length > 0;
            if (motion) gsap.fromTo(visible, { opacity: 0, y: 30, scale: .94 }, { opacity: 1, y: 0, scale: 1, duration: .55, stagger: .06, ease: 'back.out(1.4)', clearProps: 'transform' });
        };
        poPage.querySelectorAll('[data-po-filter]').forEach((chip) => chip.addEventListener('click', () => {
            poPage.querySelectorAll('[data-po-filter]').forEach((c) => c.classList.toggle('active', c === chip));
            filter = chip.dataset.poFilter;
            apply();
        }));
        poPage.querySelector('[data-po-sort]').addEventListener('change', apply);
    }

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

    /* ---------- Filter tanpa reload (katalog & artikel) ----------
       [data-catalog] membungkus halaman; [data-catalog-body] ditukar dengan HTML dari server (header X-Catalog).
       Link [data-catalog-link] & form [data-catalog-form] diambil lewat fetch; URL ikut berubah (back/forward jalan). */
    const catalog = document.querySelector('[data-catalog]');
    if (catalog) {
        const body = catalog.querySelector('[data-catalog-body]');
        const total = catalog.querySelector('[data-catalog-total]');
        // Semua elemen ber-animasi muncul (kartu, banner kategori, sorotan) ikut keluar-masuk saat ditukar
        const items = () => body.querySelectorAll('[data-reveal]');
        let pending = null;

        const load = async (url, push = true) => {
            pending?.abort();
            pending = new AbortController();
            body.classList.add('is-loading');
            if (motion && items().length) {
                gsap.to(items(), { opacity: 0, y: 24, scale: .95, duration: .22, stagger: .012, ease: 'power2.in', overwrite: true });
            }
            try {
                const [res] = await Promise.all([
                    fetch(url, { headers: { 'X-Catalog': '1' }, signal: pending.signal }),
                    new Promise((r) => setTimeout(r, motion ? 230 : 0)), // tunggu kartu lama selesai menghilang
                ]);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                body.innerHTML = await res.text();
                if (push) history.pushState({ catalog: true }, '', url);
                const count = body.querySelector('[data-total]');
                if (count && total) total.textContent = count.dataset.total;
                bindTilt(body);
                const fresh = items();
                if (motion) {
                    gsap.fromTo(fresh,
                        { opacity: 0, y: 46, scale: .9, rotationX: -14, filter: 'blur(8px)' },
                        { opacity: 1, y: 0, scale: 1, rotationX: 0, filter: 'blur(0px)', duration: .75, ease: 'power3.out', stagger: .055, clearProps: 'filter,transform' });
                    gsap.from(body.querySelectorAll('.chip.active, .cat-chip.active'), { scale: .8, duration: .5, ease: 'back.out(3)' });
                } else {
                    fresh.forEach((c) => { c.style.opacity = 1; });
                }
                const top = body.getBoundingClientRect().top;
                if (top < 0) (lenis ? lenis.scrollTo(body, { offset: -110 }) : body.scrollIntoView({ behavior: 'smooth' }));
            } catch (err) {
                if (err.name !== 'AbortError') window.location.href = url; // gagal: muat halaman biasa
            } finally {
                body.classList.remove('is-loading');
            }
        };

        catalog.addEventListener('click', (e) => {
            const a = e.target.closest('a[data-catalog-link]');
            if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
            e.preventDefault(); // juga mencegah tirai transisi halaman
            if (a.matches('.chip, .cat-chip')) {
                a.parentElement.querySelectorAll('.chip.active, .cat-chip.active').forEach((c) => c.classList.remove('active'));
                a.classList.add('active');
            }
            load(a.href);
        });
        catalog.addEventListener('submit', (e) => {
            const form = e.target.closest('form[data-catalog-form]');
            if (!form) return;
            e.preventDefault();
            // Ambil filter yang sedang aktif dari URL, timpa dengan isian form yang terlihat
            const url = new URL(window.location.href);
            [...new FormData(form)].forEach(([k, v]) => {
                if (form.elements[k]?.type === 'hidden') return;
                v ? url.searchParams.set(k, v) : url.searchParams.delete(k);
            });
            url.searchParams.delete('page');
            load(url.toString());
        });
        catalog.addEventListener('change', (e) => {
            if (e.target.matches('[data-catalog-auto]')) e.target.form.requestSubmit();
        });
        window.addEventListener('popstate', () => load(window.location.href, false));
    }

    /* ---------- Tombol hemat animasi (footer) ---------- */
    document.querySelectorAll('[data-motion-toggle]').forEach((btn) => {
        btn.textContent = reduce ? 'ANIMASI: HEMAT' : 'ANIMASI: PENUH';
        btn.setAttribute('aria-pressed', reduce ? 'true' : 'false');
        btn.addEventListener('click', () => {
            try { localStorage.setItem('kz-motion', reduce ? 'full' : 'reduced'); } catch (e) {}
            window.location.reload(); // GSAP & Lenis disiapkan ulang sesuai pilihan
        });
    });

    /* ---------- Bar progres scroll & kelopak sakura ---------- */
    const bar = document.querySelector('.scroll-progress');
    if (motion && bar) {
        gsap.to(bar, { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: .3 } });
    }
    const hero = document.querySelector('.hero');
    if (motion && hero) {
        const petals = document.createElement('div');
        petals.className = 'petals';
        petals.setAttribute('aria-hidden', 'true');
        for (let i = 0; i < 16; i++) {
            const p = document.createElement('i');
            const size = 8 + Math.random() * 10;
            Object.assign(p.style, {
                left: `${Math.random() * 100}%`, width: `${size}px`, height: `${size * .7}px`,
                animationDuration: `${9 + Math.random() * 9}s`, animationDelay: `${-Math.random() * 18}s`,
            });
            petals.appendChild(p);
        }
        hero.prepend(petals);
    }

    /* ---------- Mulai ---------- */
    if (curtain) revealPage(); else introAnimations();
})();
