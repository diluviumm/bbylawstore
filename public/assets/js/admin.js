/**
 * Bbylaa Store — perilaku panel admin.
 *
 * Semua mutasi lewat endpoint JSON; UI memperbarui dirinya dari respons
 * server, bukan dari tebakan lokal.
 */

(function () {
    'use strict';

    var CSRF = (document.querySelector('meta[name="csrf-token"]') || { getAttribute: function () { return ''; } })
        .getAttribute('content') || '';

    function toast(message, isError) {
        var el = document.getElementById('toast');
        if (!el) return;
        el.textContent = message;
        el.classList.remove('hidden');
        el.classList.toggle('bg-rose-600', !!isError);
        clearTimeout(el._t);
        el._t = setTimeout(function () { el.classList.add('hidden'); }, 3200);
    }

    function post(url, data) {
        var body = new FormData();
        Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
        body.append('_token', CSRF);

        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body,
            credentials: 'same-origin'
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, error: 'Respons tidak valid.' }; });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();

        // ------------------------------------------------- status pesanan
        document.querySelectorAll('[data-set-status]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.dataset.setStatus;
                var status = btn.dataset.status;
                btn.disabled = true;

                post('/admin/pesanan/status', { order_id: id, status: status }).then(function (res) {
                    btn.disabled = false;
                    if (!res.ok) { toast(res.error || 'Gagal mengubah status.', true); return; }

                    var card = btn.closest('[data-order]');
                    if (card) {
                        // Repaint deterministik: jangan mengandalkan replace string.
                        card.querySelectorAll('[data-set-status]').forEach(function (b) {
                            var on = b.dataset.status === status;
                            b.className = 'chip transition-colors ' + (on
                                ? 'bg-pink-700 text-white ring-pink-700'
                                : 'bg-white text-[#5c4354] ring-[#f0d3e0] hover:bg-pink-50');
                        });
                        var chip = card.querySelector('[data-status-chip]');
                        if (chip) chip.textContent = res.label;
                    }
                    // Halaman detail: perbarui juga chip status besar.
                    document.querySelectorAll('[data-set-status]').forEach(function (b) {
                        if (b.closest('[data-order]')) return;
                        var on = b.dataset.status === status;
                        b.className = 'chip transition-colors ' + (on
                            ? 'bg-pink-700 text-white ring-pink-700'
                            : 'bg-white text-[#5c4354] ring-[#f0d3e0] hover:bg-pink-50');
                    });
                    toast('Status diubah ke ' + res.label);
                }).catch(function () {
                    btn.disabled = false;
                    toast('Koneksi gagal.', true);
                });
            });
        });

        // -------------------------------------------------- sakelar kategori
        document.querySelectorAll('[data-cat-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.dataset.catToggle;
                var next = btn.dataset.open === '1' ? 0 : 1;
                btn.disabled = true;

                post('/admin/kategori', { category_id: id, is_open: next }).then(function (res) {
                    btn.disabled = false;
                    if (!res.ok) { toast(res.error || 'Gagal mengubah kategori.', true); return; }

                    btn.dataset.open = res.is_open ? '1' : '0';
                    btn.setAttribute('aria-checked', res.is_open ? 'true' : 'false');
                    btn.classList.toggle('bg-pink-700', !!res.is_open);
                    btn.classList.toggle('bg-slate-300', !res.is_open);
                    var knob = btn.querySelector('span');
                    if (knob) {
                        knob.classList.toggle('translate-x-5', !!res.is_open);
                        knob.classList.toggle('translate-x-0', !res.is_open);
                    }

                    var label = document.querySelector('[data-cat-label="' + id + '"]');
                    if (label) {
                        label.className = 'flex items-center gap-1 text-[10px] font-bold ' +
                            (res.is_open ? 'text-emerald-700' : 'text-[#8a7086]');
                        label.innerHTML = '<span class="h-1.5 w-1.5 rounded-full ' +
                            (res.is_open ? 'bg-emerald-500' : 'bg-slate-400') + '"></span>' + res.label;
                    }
                    toast('Kategori ' + (res.is_open ? 'dibuka' : 'ditutup'));
                }).catch(function () {
                    btn.disabled = false;
                    toast('Koneksi gagal.', true);
                });
            });
        });

        // ------------------------------------------------------ stok varian
        document.querySelectorAll('[data-stock-for]').forEach(function (input) {
            var vid = input.dataset.stockFor;
            var last = input.value;

            function save() {
                if (input.value === last) return;
                var val = input.value.trim();
                if (val !== '' && !/^\d+$/.test(val)) {
                    input.value = last;
                    toast('Stok harus angka atau kosong.', true);
                    return;
                }
                input.disabled = true;
                post('/admin/produk/stok', { variant_id: vid, stock: val }).then(function (res) {
                    input.disabled = false;
                    if (!res.ok) {
                        input.value = last;
                        toast(res.error || 'Gagal menyimpan stok.', true);
                        return;
                    }
                    input.value = res.stock === null ? '' : res.stock;
                    last = input.value;
                    var lbl = document.querySelector('[data-stock-label="' + vid + '"]');
                    if (lbl) lbl.textContent = res.label;
                    toast('Stok disimpan: ' + res.label);
                }).catch(function () {
                    input.disabled = false;
                    input.value = last;
                    toast('Koneksi gagal.', true);
                });
            }

            input.addEventListener('blur', save);
            input.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
            });
        });
    });
})();
