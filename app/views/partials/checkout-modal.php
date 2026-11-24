<?php
/**
 * Modal checkout pop-up (satu form untuk produk tunggal atau isi keranjang).
 * State diisi JS saat dibuka — tidak ada form lama yang tersisa antar produk.
 */
declare(strict_types=1);

$payments = array_values(array_filter(array_map('trim', explode(',', (string) setting('payments', 'QRIS,DANA / E-Wallet,Transfer Bank')))));
$paymentLabels = ['QRIS' => 'QRIS', 'DANA / E-Wallet' => 'E-Wallet', 'Transfer Bank' => 'Transfer'];
?>
<div id="checkout-modal" class="fixed inset-0 z-[70] hidden overflow-y-auto" role="dialog"
     aria-modal="true" aria-labelledby="cm-title" aria-hidden="true">
    <a href="#" class="fixed inset-0 bg-black/75 backdrop-blur-md" data-cm-close aria-hidden="true" tabindex="-1"></a>
    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-4">
        <div class="relative flex max-h-[94vh] w-full max-w-lg flex-col overflow-hidden rounded-t-3xl border-2 border-pink-500/35 bg-[#180e22] p-5 text-white shadow-2xl sm:rounded-3xl sm:p-6">
            <button type="button" data-cm-close
                    class="absolute right-3 top-3 z-10 rounded-xl bg-[#180e22]/90 p-1.5 text-pink-200 ring-1 ring-pink-500/30 hover:bg-pink-500/20"
                    aria-label="Tutup checkout">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>

            <div data-cm-main>
                <h2 id="cm-title" class="pr-10 text-base font-extrabold sm:text-lg">Konfirmasi Pemesanan</h2>
                <p class="mt-0.5 text-[11px] text-pink-200/70">Order tersimpan otomatis lalu diteruskan ke WhatsApp CS.</p>

                <div class="mt-4 rounded-2xl border border-pink-500/30 bg-pink-950/40 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <span data-cm-product class="min-w-0 text-sm font-extrabold">—</span>
                        <span data-cm-variant class="shrink-0 rounded-full border border-pink-500/40 bg-pink-900/80 px-2 py-0.5 text-[10px] font-bold text-pink-200">—</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[11px] font-semibold text-pink-200/80">
                        <span data-cm-total-label>Total Pembayaran</span>
                        <span data-cm-total class="text-base font-black text-pink-300">Rp 0</span>
                    </div>
                    <ul data-cm-lines class="mt-2 hidden space-y-1 text-[11px] text-pink-100/80"></ul>
                </div>

                <p data-cm-joki class="mt-3 hidden rounded-xl border border-amber-500/35 bg-amber-950/40 p-3 text-[11px] leading-relaxed text-amber-200">
                    <strong>Biaya menyesuaikan:</strong> harga di atas estimasi awal. Harga final disepakati lewat WhatsApp sesuai tingkat kesulitan & deadline.
                </p>

                <form id="checkout-modal-form" class="mt-4 space-y-3 overflow-y-auto pr-1" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="variant_id" value="0" data-cm-variant-id>
                    <input type="hidden" name="qty" value="1" data-cm-qty>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div>
                            <label for="cm-name" class="mb-1 block text-[11px] font-bold text-pink-100">Nama Pemesan <span class="text-rose-400">*</span></label>
                            <input id="cm-name" name="name" type="text" required maxlength="80" autocomplete="name"
                                   class="field py-2 text-xs" placeholder="Masukkan nama kamu">
                            <p class="mt-1 hidden text-[10px] font-semibold text-rose-200" data-cm-error="name"></p>
                        </div>
                        <div>
                            <label for="cm-phone" class="mb-1 block text-[11px] font-bold text-pink-100">No. WhatsApp <span class="text-rose-400">*</span></label>
                            <input id="cm-phone" name="phone" type="tel" required inputmode="tel" autocomplete="tel" maxlength="20"
                                   class="field py-2 text-xs" placeholder="Contoh: 0823xxxxxxxx">
                            <p class="mt-1 hidden text-[10px] font-semibold text-rose-200" data-cm-error="phone"></p>
                        </div>
                    </div>

                    <div data-cm-game-field class="hidden rounded-2xl border border-pink-500/30 bg-pink-950/40 p-3">
                        <label for="cm-game" data-cm-game-label class="mb-1 block text-[11px] font-bold text-pink-200">ID Game / Username <span class="text-rose-400">*</span></label>
                        <input id="cm-game" name="game_id" type="text" maxlength="120" data-cm-game-input
                               class="field py-2 text-xs" placeholder="Contoh: username">
                        <p class="mt-1 hidden text-[10px] font-semibold text-rose-200" data-cm-error="game_id"></p>
                    </div>

                    <fieldset>
                        <legend class="mb-1 block text-[11px] font-bold text-pink-100">Pilihan Pembayaran</legend>
                        <div class="grid grid-cols-3 gap-1.5">
                            <?php foreach ($payments as $i => $pay): ?>
                                <label data-pay-label class="flex cursor-pointer items-center justify-center rounded-xl border-2 border-pink-500/25 px-2 py-2 text-[11px] font-bold text-pink-100/70 transition-colors hover:border-pink-400/50">
                                    <input type="radio" name="payment" value="<?= e($pay) ?>" class="sr-only" <?= $i === 0 ? 'checked' : '' ?>>
                                    <span class="truncate"><?= e($paymentLabels[$pay] ?? $pay) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div>
                        <label for="cm-note" class="mb-1 block text-[11px] font-bold text-pink-100">Catatan Tambahan (Opsional)</label>
                        <textarea id="cm-note" name="note" rows="2" maxlength="400" data-cm-note
                                  class="field py-2 text-xs" placeholder="Contoh: email invite pribadi / catatan khusus"></textarea>
                    </div>

                    <p class="hidden rounded-xl bg-rose-500/15 px-3 py-2 text-[11px] font-semibold text-rose-100" data-cm-error="cart"></p>

                    <button type="submit" class="btn-primary w-full py-3 text-xs">
                        <i data-lucide="send" class="h-4 w-4"></i> <span data-cm-submit-label>Checkout via WhatsApp</span>
                    </button>
                    <p class="text-center text-[10px] text-pink-200/60">Data pesanan tercatat di sistem sebelum pesan WhatsApp dikirim.</p>
                </form>
            </div>

            <div data-cm-success class="hidden py-6 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                    <i data-lucide="check-circle-2" class="h-8 w-8"></i>
                </span>
                <h3 class="text-lg font-black">Pesanan tersimpan!</h3>
                <p class="mt-1 text-xs text-pink-100/70">Kode pesanan kamu:</p>
                <p data-cm-code class="mt-2 text-xl font-black tracking-wide text-pink-300">—</p>
                <div class="mt-5 flex flex-col gap-2">
                    <a data-cm-wa href="#" target="_blank" rel="noopener noreferrer" class="btn-primary w-full py-3 text-xs">
                        <i data-lucide="message-circle" class="h-4 w-4"></i> Lanjut ke WhatsApp
                    </a>
                    <a data-cm-detail href="#" class="btn-quiet w-full py-2.5 text-xs">Lihat detail pesanan</a>
                    <button type="button" data-cm-close class="btn-quiet w-full py-2.5 text-xs">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
