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
        paintGameField();
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

    // -------------------------------------------------------- checkout pop-up
    var checkoutState = { mode: 'cart', variantId: 0 };

    function modal() { return document.getElementById('checkout-modal'); }

    function openCheckoutModal(mode, payload) {
        var m = modal();
        if (!m) return;
        checkoutState.mode = mode;
        checkoutState.variantId = payload.variantId || 0;

        var form = document.getElementById('checkout-modal-form');
        var main = m.querySelector('[data-cm-main]');
        var success = m.querySelector('[data-cm-success]');
        if (form) form.reset();
        if (main) main.classList.remove('hidden');
        if (success) success.classList.add('hidden');

        m.querySelectorAll('[data-cm-error]').forEach(function (slot) {
            slot.classList.add('hidden'); slot.textContent = '';
        });
        var variantInput = m.querySelector('[data-cm-variant-id]');
        if (variantInput) variantInput.value = String(checkoutState.variantId);
        m.querySelectorAll('[data-pay-label]').forEach(function (lab) {
            var checked = !!lab.querySelector('input:checked');
            lab.classList.toggle('pay-active', checked);
            lab.setAttribute('aria-checked', checked ? 'true' : 'false');
        });

        var set = function (sel, value) {
            var node = m.querySelector(sel);
            if (node) node.textContent = value;
        };
        set('[data-cm-product]', payload.name || 'Produk');
        set('[data-cm-variant]', payload.variantLabel || '—');
        set('[data-cm-total-label]', payload.joki ? 'Estimasi Biaya' : 'Total Pembayaran');
        set('[data-cm-total]', payload.priceText || rupiah(0));

        var joki = m.querySelector('[data-cm-joki]');
        if (joki) joki.classList.toggle('hidden', !payload.joki);

        var lines = m.querySelector('[data-cm-lines]');
        if (lines) {
            lines.textContent = '';
            if (mode === 'cart' && payload.lines && payload.lines.length > 1) {
                (payload.lines || []).forEach(function (l) {
                    var li = el('li', 'flex items-center justify-between gap-2', '');
                    li.appendChild(el('span', 'min-w-0 truncate', l.qty + '× ' + l.name + ' — ' + l.variant_label));
                    li.appendChild(el('span', 'shrink-0 font-black', rupiah(l.subtotal)));
                    lines.appendChild(li);
                });
                lines.classList.remove('hidden');
            } else {
                lines.classList.add('hidden');
            }
        }

        var gameField = m.querySelector('[data-cm-game-field]');
        var gameLabel = m.querySelector('[data-cm-game-label]');
        var gameInput = m.querySelector('[data-cm-game-input]');
        var needsGame = !!payload.game;
        if (gameField) gameField.classList.toggle('hidden', !needsGame);
        if (gameLabel) gameLabel.innerHTML = '';
        if (gameLabel) {
            gameLabel.appendChild(document.createTextNode((payload.gameLabel || 'ID Game / Username') + ' '));
            var star = el('span', 'text-rose-400', '*');
            gameLabel.appendChild(star);
        }
        if (gameInput) gameInput.placeholder = payload.gamePlaceholder || 'Contoh: username';

        var note = m.querySelector('[data-cm-note]');
        if (note) note.placeholder = payload.notePlaceholder || 'Contoh: email invite pribadi / catatan khusus';

        m.classList.remove('hidden');
        m.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var first = document.getElementById('cm-name');
        if (first) first.focus({ preventScroll: true });
        refreshIcons();
    }

    function closeCheckoutModal() {
        var m = modal();
        if (!m) return;
        m.classList.add('hidden');
        m.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function showCheckoutSuccess(res) {
        var m = modal();
        if (!m) return;
        var main = m.querySelector('[data-cm-main]');
        var success = m.querySelector('[data-cm-success]');
        if (main) main.classList.add('hidden');
        if (success) success.classList.remove('hidden');
        var code = m.querySelector('[data-cm-code]');
        if (code) code.textContent = res.code || '—';
        var wa = m.querySelector('[data-cm-wa]');
        if (wa && res.wa) wa.href = res.wa;
        var detail = m.querySelector('[data-cm-detail]');
        if (detail && res.redirect) detail.href = res.redirect;
        if (res.wa) window.open(res.wa, '_blank', 'noopener');
    }

    function initCheckoutModal() {
        var m = modal();
        if (!m) return;
        m.querySelectorAll('[data-cm-close]').forEach(function (b) {
            b.addEventListener('click', function (ev) { ev.preventDefault(); closeCheckoutModal(); });
        });
        var form = document.getElementById('checkout-modal-form');
        if (!form) return;
        form.querySelectorAll('input[name="payment"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                m.querySelectorAll('[data-pay-label]').forEach(function (lab) {
                    var checked = !!lab.querySelector('input:checked');
                    lab.classList.toggle('pay-active', checked);
                    lab.setAttribute('aria-checked', checked ? 'true' : 'false');
                });
            });
        });
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            if (cart.busy) return;
            var f = form.elements;
            var data = {
                name: f['name'] ? f['name'].value.trim() : '',
                phone: f['phone'] ? f['phone'].value.trim() : '',
                payment: (form.querySelector('input[name="payment"]:checked') || {}).value || '',
                note: f['note'] ? f['note'].value.trim() : '',
                game_id: f['game_id'] ? f['game_id'].value.trim() : ''
            };
            var url = '/api/checkout';
            if (checkoutState.mode === 'product') {
                url = '/api/checkout/direct';
                data.variant_id = checkoutState.variantId;
                data.qty = 1;
            }
            m.querySelectorAll('[data-cm-error]').forEach(function (slot) {
                slot.classList.add('hidden'); slot.textContent = '';
            });
            cart.busy = true;
            post(url, data).then(function (res) {
                cart.busy = false;
                if (!res.ok) {
                    if (res.errors) {
                        Object.keys(res.errors).forEach(function (key) {
                            var slot = m.querySelector('[data-cm-error="' + key + '"]');
                            if (slot) { slot.textContent = res.errors[key]; slot.classList.remove('hidden'); }
                        });
                    }
                    toast(res.error || 'Checkout gagal.', true);
                    return;
                }
                if (checkoutState.mode === 'cart') {
                    cart.lines = []; cart.count = 0; cart.total = 0;
                    paintLines(); paintCounters(); closeDrawer();
                }
                showCheckoutSuccess(res);
            }).catch(function () {
                cart.busy = false;
                toast('Koneksi gagal saat checkout. Coba lagi.', true);
            });
        });
    }

    // -------------------------------------------------- varian & beli langsung
    function variantPrice(option) {
        if (!option) return '';
        var p = Number(option.getAttribute('data-price') || 0);
        var max = option.getAttribute('data-price-max');
        return max ? rupiah(p) + ' – ' + rupiah(Number(max)) : rupiah(p);
    }

    function initVariants() {
        document.querySelectorAll('[data-variant-select]').forEach(function (select) {
            var card = select.closest('[data-product-card]');
            if (!card) return;
            var label = card.querySelector('[data-price-display]');
            var hidden = card.querySelectorAll('[data-card-variant]');
            select.addEventListener('change', function () {
                var opt = select.options[select.selectedIndex];
                if (label) label.textContent = variantPrice(opt);
                hidden.forEach(function (h) { h.value = select.value; });
            });
        });

        document.querySelectorAll('[data-buy-now]').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var card = form.closest('[data-product-card]');
                if (!card) return;
                var select = card.querySelector('[data-variant-select]');
                if (!select || !select.value) return;
                var opt = select.options[select.selectedIndex];
                openCheckoutModal('product', {
                    variantId: Number(select.value),
                    name: card.getAttribute('data-product-name') || '',
                    variantLabel: card.getAttribute('data-product-variant') || (opt ? opt.textContent.split('—')[0].trim() : ''),
                    priceText: card.getAttribute('data-product-price') || variantPrice(opt),
                    joki: card.getAttribute('data-product-joki') === '1',
                    game: card.getAttribute('data-product-category') === 'game',
                    gameLabel: card.getAttribute('data-game-label') || '',
                    gamePlaceholder: card.getAttribute('data-game-placeholder') || '',
                    notePlaceholder: card.getAttribute('data-note-placeholder') || ''
                });
            });
        });

        // Klik area kosong kartu membuka detail, tanpa mengganggu kontrol di dalamnya.
        document.querySelectorAll('[data-detail-url]').forEach(function (card) {
            card.addEventListener('click', function (ev) {
                if (ev.target.closest('a, button, select, input, textarea, form, label')) return;
                window.location.href = card.getAttribute('data-detail-url');
            });
        });

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                var m = modal();
                if (m && !m.classList.contains('hidden')) closeCheckoutModal();
            }
        });
    }

    // Label & placeholder game mengikuti produk di keranjang (Roblox vs Growtopia).
    function paintGameField() {
        var label = document.querySelector('[data-game-label]');
        var input = document.querySelector('[data-game-input]');
        var field = document.querySelector('[data-game-field]');
        if (!label || !input) return;
        var ids = (cart.lines || []).map(function (l) { return Number(l.product_id); });
        var needs = ids.indexOf(201) !== -1 || ids.indexOf(202) !== -1
            || (cart.lines || []).some(function (l) { return l.category_id === 'game'; });
        if (field) field.hidden = !needs;
        var text = 'ID Game / Username';
        var placeholder = 'Contoh: 12345678 / GrowID: Byla';
        if (ids.indexOf(201) !== -1) {
            text = 'Username Roblox';
            placeholder = 'Contoh: Username @bbylawstore';
        } else if (ids.indexOf(202) !== -1) {
            text = 'Tulis Growid & World Name';
            placeholder = 'Contoh: Growid  : bbylastore, World : bbylaworld';
        }
        label.textContent = text;
        input.placeholder = placeholder;
    }

    // ------------------------------------------------------------------- boot
    document.addEventListener('DOMContentLoaded', function () {
        seedFromDom();
        fetchState().then(function () { paintCounters(); paintLines(); });
        initVariants();
        paintGameField();
        initCheckoutModal();

        document.querySelectorAll('[data-cart-checkout]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (!cart.lines.length) return;
                var first = cart.lines[0];
                var needsGame = false, gameLabel = '', gamePlaceholder = '', ids = [];
                cart.lines.forEach(function (l) {
                    ids.push(Number(l.product_id));
                    if (l.category_id === 'game') needsGame = true;
                });
                if (ids.indexOf(201) !== -1) {
                    gameLabel = 'Username Roblox'; gamePlaceholder = 'Contoh: Username @bbylawstore';
                } else if (ids.indexOf(202) !== -1) {
                    gameLabel = 'Tulis Growid & World Name'; gamePlaceholder = 'Contoh: Growid  : bbylastore, World : bbylaworld';
                }
                openCheckoutModal('cart', {
                    name: first.name + (cart.lines.length > 1 ? ' (+' + (cart.lines.length - 1) + ')' : ''),
                    variantLabel: cart.lines.length > 1 ? cart.lines.length + ' produk' : first.variant_label,
                    priceText: rupiah(cart.total),
                    lines: cart.lines,
                    game: needsGame, gameLabel: gameLabel, gamePlaceholder: gamePlaceholder
                });
            });
        });

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

        document.querySelectorAll('[data-buy-form]').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                if (!window.bbyCart) return; // tanpa JS tidak sampai ke sini
                ev.preventDefault();
                var input = form.querySelector('[name="variant_id"]');
                var btn = form.querySelector('button[type="submit"]');
                if (!input) return;
                addToCart(Number(input.value), btn ? (btn.getAttribute('aria-label') || '') : '');
            });
        });

        refreshIcons();
    });
})();
