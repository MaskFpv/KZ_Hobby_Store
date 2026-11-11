/* Chat (widget storefront & inbox panel). Butuh Livewire.
   - Enter = kirim, Shift+Enter = baris baru (aman untuk input IME Jepang)
   - Textarea tumbuh mengikuti isi
   - Thread tetap di pesan terbaru bila pengguna memang sedang di bawah, selalu turun setelah mengirim */
(function () {
    if (!window.Livewire) return;

    const autosize = (ta) => { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 140) + 'px'; };
    const atBottom = (el) => el.scrollHeight - el.scrollTop - el.clientHeight < 120;
    const toBottom = (el) => { el.scrollTop = el.scrollHeight; };

    document.addEventListener('input', (e) => { if (e.target.matches('[data-chat-input]')) autosize(e.target); });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || e.shiftKey || e.isComposing || !e.target.matches('[data-chat-input]')) return;
        e.preventDefault();
        e.target.form.requestSubmit();
    });

    // data-chat-scroll berisi kunci thread (mis. id percakapan); kunci berubah = thread baru → turun ke bawah
    const before = new WeakMap();
    const forced = new WeakSet();
    document.addEventListener('submit', (e) => {
        const thread = e.target.closest('[wire\\:id]')?.querySelector('[data-chat-scroll]');
        if (thread) forced.add(thread);
    }, true);
    Livewire.hook('message.received', (message, component) => {
        component.el.querySelectorAll('[data-chat-scroll]').forEach((el) => before.set(el, { bottom: atBottom(el), key: el.dataset.chatScroll }));
    });
    Livewire.hook('message.processed', (message, component) => {
        component.el.querySelectorAll('[data-chat-scroll]').forEach((el) => {
            const prev = before.get(el);
            if (!prev || prev.bottom || prev.key !== el.dataset.chatScroll || forced.has(el)) toBottom(el);
            forced.delete(el);
        });
        component.el.querySelectorAll('[data-chat-input]').forEach(autosize);
    });

    // Gambar yang selesai dimuat menambah tinggi thread
    document.addEventListener('load', (e) => {
        const thread = e.target.tagName === 'IMG' && e.target.closest('[data-chat-scroll]');
        if (thread && thread.scrollHeight - thread.scrollTop - thread.clientHeight < 400) toBottom(thread);
    }, true);
    document.addEventListener('livewire:load', () => document.querySelectorAll('[data-chat-scroll]').forEach(toBottom));
})();
