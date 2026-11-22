/**
 * Bbylaa Store — perilaku klien (vanilla, tanpa framework).
 *
 * Sengaja tanpa Alpine/Vue: CSP produksi melarang 'unsafe-eval', dan framework
 * berbasis ekspresi string akan mati total di bawah kebijakan itu. Semua angka
 * yang tampil berasal dari respons server, bukan tebakan browser.
 */
(function () {
    'use strict';

    var CSRF = (document.querySelector('meta[name="csrf-token"]') || { getAttribute: function () { return ''; } })
        .getAttribute('content') || '';

    function rupiah(n) {
        return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
    }
    window.rupiah = rupiah;

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text != null) node.textContent = text;
        return node;
    }

    function icon(name, className) {
        var i = document.createElement('i');
        i.setAttribute('data-lucide', name);
        if (className) i.className = className;
        return i;
    }

    function refreshIcons() {
        if (window.lucide) window.lucide.createIcons();
    }

    function toast(message, isError) {
        var t = document.getElementById('toast');
        if (!t) return;
        t.textContent = message;
        t.classList.remove('hidden');
        t.classList.toggle('bg-rose-600', !!isError);
        clearTimeout(t._timer);
        t._timer = setTimeout(function () { t.classList.add('hidden'); }, 3200);
    }
    window.bbyToast = toast;

    function post(url, data) {
        var body = new FormData();
        Object.keys(data || {}).forEach(function (k) {
            if (data[k] !== undefined && data[k] !== null) body.append(k, data[k]);
        });
        body.append('_token', CSRF);

        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body,
            credentials: 'same-origin'
        }).then(function (r) {
            return r.json().catch(function () {
                return { ok: false, error: 'Server mengirim respons tidak valid.' };
            });
        });
    }
    window.bbyPost = post;

    // ------------------------------------------------------------------ tema
    function initTheme() {
        var html = document.documentElement;
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', html.classList.contains('dark') ? 'true' : 'false');
            btn.addEventListener('click', function () {
                var dark = html.classList.toggle('dark');
                document.cookie = 'bby_theme=' + (dark ? 'dark' : 'light') + ';path=/;max-age=31536000;SameSite=Lax';
                var metaTheme = document.querySelector('meta[name="theme-color"]');
                if (metaTheme) metaTheme.setAttribute('content', dark ? '#0f0813' : '#fdf6f9');
                btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
                refreshIcons();
            });
        });
    }

    // -------------------------------------------------------------- keranjang
    var cart = {
        lines: [],
        count: 0,
        total: 0,
        busy: false,
        lastFocus: null
    };
    window.bbyCart = cart;

    function paintCounters() {
        document.querySelectorAll('[data-cart-count]').forEach(function (n) {
            n.textContent = cart.count;
        });
        document.querySelectorAll('[data-cart-badge]').forEach(function (n) {
            n.textContent = cart.count;
            n.classList.toggle('hidden', cart.count === 0);
        });
        document.querySelectorAll('[data-cart-total]').forEach(function (n) {
            n.textContent = rupiah(cart.total);
        });
        var btn = document.getElementById('checkout-btn');
        if (btn) btn.disabled = cart.lines.length === 0 || cart.busy;
        var label = document.querySelector('[data-checkout-label]');
        if (label) label.textContent = cart.busy ? 'Menyimpan…' : 'Buat Pesanan';
    }

    function paintLines() {
        var list = document.querySelector('[data-cart-lines]');
        var empty = document.querySelector('[data-cart-empty]');
        if (!list) return;

        list.textContent = '';
        if (empty) empty.classList.toggle('hidden', cart.lines.length > 0);

        cart.lines.forEach(function (line) {
            var li = el('li', 'flex items-center justify-between gap-3 rounded-2xl border border-[#f6dbe6] p-3 dark:border-[#3d2340]');

            var left = el('div', 'flex min-w-0 items-center gap-2.5');
            var badge = el('span', 'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr text-[10px] font-black text-white ' + (line.grad || 'from-pink-500 to-rose-500'), line.icon || '');
            var mid = el('div', 'min-w-0');
            mid.appendChild(el('p', 'truncate text-xs font-bold ink', line.name));
            mid.appendChild(el('p', 'truncate text-[10px] font-semibold text-pink-700 dark:text-pink-300', line.variant_label));
            mid.appendChild(el('p', 'mt-0.5 text-[11px] font-black ink', rupiah(line.subtotal)));
            left.appendChild(badge);
            left.appendChild(mid);

            var right = el('div', 'flex shrink-0 items-center gap-1.5');
            var qtyWrap = el('div', 'flex items-center overflow-hidden rounded-xl border border-[#f0d3e0] dark:border-[#4a2b50]');

            var minus = el('button', 'px-2 py-1 text-pink-700 hover:bg-pink-50 dark:text-pink-300 dark:hover:bg-pink-500/10', '−');
            minus.type = 'button';
            minus.setAttribute('aria-label', 'Kurangi jumlah ' + line.name);
            minus.addEventListener('click', function () { stepLine(line.variant_id, -1); });

            var qty = el('span', 'min-w-[24px] px-1 text-center text-xs font-bold ink', String(line.qty));

            var plus = el('button', 'px-2 py-1 text-pink-700 hover:bg-pink-50 dark:text-pink-300 dark:hover:bg-pink-500/10', '+');
            plus.type = 'button';
            plus.setAttribute('aria-label', 'Tambah jumlah ' + line.name);
            plus.addEventListener('click', function () { stepLine(line.variant_id, 1); });

            qtyWrap.appendChild(minus);
            qtyWrap.appendChild(qty);
            qtyWrap.appendChild(plus);

            var del = el('button', 'p-1.5 text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300');
            del.type = 'button';
            del.setAttribute('aria-label', 'Hapus ' + line.name + ' dari keranjang');
            del.appendChild(icon('trash-2', 'h-3.5 w-3.5'));
            del.addEventListener('click', function () { removeLine(line.variant_id); });

            right.appendChild(qtyWrap);
            right.appendChild(del);

            li.appendChild(left);
            li.appendChild(right);
            list.appendChild(li);
        });

        refreshIcons();
        paintCounters();
    }

    function apply(payload) {
        if (!payload || payload.ok !== true) return;
        if (Array.isArray(payload.lines)) {
            cart.lines = payload.lines;
        }
        cart.count = Number(payload.count) || 0;
        cart.total = Number(payload.total) || 0;
        paintLines();
    }

    function fetchState() {
        return fetch('/keranjang?json=1', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (d) {
            apply({ ok: true, lines: d.lines, count: d.count, total: d.total });
        }).catch(function () {});
    }

    function seedFromDom() {
        var elSeed = document.getElementById('cart-seed');
        if (!elSeed) return;
        try {
            var parsed = JSON.parse(elSeed.textContent || '{}');
            apply({ ok: true, lines: parsed.lines || [], count: parsed.count || 0, total: parsed.total || 0 });
        } catch (e) { /* abaikan */ }
    }

    function addToCart(variantId, name, qty) {
        if (cart.busy) return Promise.resolve();
        cart.busy = true;
        paintCounters();
        return post('/api/cart/add', { variant_id: variantId, qty: qty || 1 }).then(function (res) {
            cart.busy = false;
            if (!res.ok) {
                toast(res.error || 'Gagal menambah ke keranjang.', true);
                paintCounters();
                return;
            }
            toast(name ? ('“' + name + '” masuk keranjang') : 'Masuk keranjang');
            openDrawer();
            return fetchState().then(function () { paintCounters(); });
        }).catch(function () {
            cart.busy = false;
            paintCounters();
            toast('Koneksi gagal. Coba lagi.', true);
        });
    }
    window.addToCart = addToCart;

    function stepLine(variantId, delta) {
        var line = cart.lines.filter(function (l) { return l.variant_id === variantId; })[0];
        if (!line) return;
        var next = line.qty + delta;
        if (next <= 0) { removeLine(variantId); return; }
        post('/api/cart/set', { variant_id: variantId, qty: next }).then(function (res) {
            if (!res.ok) { toast(res.error || 'Gagal mengubah jumlah.', true); return; }
            fetchState();
        });
    }

    function removeLine(variantId) {
        post('/api/cart/remove', { variant_id: variantId }).then(function (res) {
            if (!res.ok) { toast(res.error || 'Gagal menghapus item.', true); return; }
            apply(res);
            fetchState();
        });
    }

    // ------------------------------------------------------------------ drawer
    function drawer() {
        return document.getElementById('cart-drawer');
    }

    function openDrawer() {
        var d = drawer();
        if (!d) return;
        cart.lastFocus = document.activeElement;
        d.classList.remove('hidden');
        d.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var first = d.querySelector('input, button:not([data-cart-close])');
        if (first) first.focus({ preventScroll: true });
        refreshIcons();
    }

    function closeDrawer() {
        var d = drawer();
        if (!d) return;
        d.classList.add('hidden');
        d.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (cart.lastFocus && cart.lastFocus.focus) cart.lastFocus.focus({ preventScroll: true });
    }

    // ----------------------------------------------------------------- checkout
    function initCheckout() {
        var form = document.getElementById('checkout-form');
        if (!form) return;

        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            if (cart.busy) return;

            form.querySelectorAll('[data-error]').forEach(function (p) {
                p.classList.add('hidden');
                p.textContent = '';
            });

            var f = form.elements;
            var data = {
                name: f['name'] ? f['name'].value.trim() : '',
                phone: f['phone'] ? f['phone'].value.trim() : '',
                payment: (form.querySelector('input[name="payment"]:checked') || {}).value || '',
                note: f['note'] ? f['note'].value.trim() : '',
                game_id: f['game_id'] ? f['game_id'].value.trim() : ''
            };

            cart.busy = true;
            paintCounters();

            post('/api/checkout', data).then(function (res) {
                cart.busy = false;
                paintCounters();

                if (!res.ok) {
                    if (res.errors) {
                        Object.keys(res.errors).forEach(function (key) {
                            var slot = form.querySelector('[data-error="' + key + '"]');
                            if (slot) {
                                slot.textContent = res.errors[key];
                                slot.classList.remove('hidden');
                            }
                        });
                        var firstKey = Object.keys(res.errors)[0];
                        var field = form.querySelector('[name="' + firstKey + '"]');
                        if (field) field.focus();
                    }
                    toast(res.error || 'Checkout gagal. Periksa data kamu.', true);
                    return;
                }

                cart.lines = [];
                cart.count = 0;
                cart.total = 0;
                paintLines();
                closeDrawer();

                if (res.wa) window.open(res.wa, '_blank', 'noopener');
                window.location.href = res.redirect;
            }).catch(function () {
                cart.busy = false;
                paintCounters();
                toast('Koneksi gagal saat checkout. Coba lagi.', true);
            });
        });
    }

    // ------------------------------------------------------------------- boot
    document.addEventListener('DOMContentLoaded', function () {
        initTheme();
        seedFromDom();
        initCheckout();

        document.querySelectorAll('[data-cart-open]').forEach(function (b) {
            b.addEventListener('click', openDrawer);
        });
        document.querySelectorAll('[data-cart-close]').forEach(function (b) {
            b.addEventListener('click', closeDrawer);
        });
        document.addEventListener('keydown', function (ev) {
            var d = drawer();
            if (ev.key === 'Escape' && d && !d.classList.contains('hidden')) closeDrawer();
        });

        document.querySelectorAll('[data-buy]').forEach(function (b) {
            b.addEventListener('click', function () {
                addToCart(Number(b.dataset.buy), b.dataset.buyName || '');
            });
        });

        refreshIcons();
    });
})();
