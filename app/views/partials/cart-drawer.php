<?php
/**
 * Drawer keranjang + formulir checkout.
 *
 * Rendering awal dilakukan server (tanpa framework JS), lalu JS memperbarui
 * daftar dari respons server setiap kali isi keranjang berubah.
 *
 * Catatan penting: jalur ini sengaja vanilla — CSP produksi melarang
 * 'unsafe-eval', dan Alpine.js membutuhkannya untuk mengevaluasi direktif.
 */
declare(strict_types=1);

$cartState = cart_lines();
$payments = array_values(array_filter(array_map('trim', explode(',', (string) setting('payments', 'QRIS,DANA / E-Wallet,Transfer Bank')))));
$paymentLabels = ['QRIS' => 'QRIS', 'DANA / E-Wallet' => 'E-Wallet', 'Transfer Bank' => 'Transfer'];

$hasGame = false;
foreach ($cartState['lines'] as $l) {
    if ($l['category_id'] === 'game') {
        $hasGame = true;
    }
}
$gameInfo = game_field_info($cartState['lines']);
$gameLabel = $gameInfo['label'];
$gamePlaceholder = $gameInfo['placeholder'];
?>

<noscript>
    <div class="border-b border-amber-300 bg-amber-50 px-4 py-2.5 text-center text-xs font-semibold text-amber-900 dark:border-amber-400/40 dark:bg-amber-500/15 dark:text-amber-100">
        JavaScript dimatikan di browser ini. Kamu tetap bisa melihat katalog, tetapi untuk memesan
        silakan hubungi admin lewat WhatsApp.
    </div>
</noscript>

<div id="cart-drawer" class="fixed inset-0 z-50 hidden overflow-y-auto"
     role="dialog" aria-modal="true" aria-labelledby="cart-title" aria-hidden="true">

    <a href="#" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" data-cart-close aria-hidden="true" tabindex="-1"></a>

    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-4">
        <div class="relative w-full max-w-lg rounded-t-3xl bg-white shadow-2xl dark:bg-[#1a1021] sm:rounded-3xl">

            <header class="flex items-start justify-between gap-3 border-b border-[#f6dbe6] p-4 dark:border-[#3d2340] sm:p-5">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-pink-100 text-pink-800 dark:bg-pink-500/20 dark:text-pink-200">
                        <i data-lucide="shopping-cart" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h2 id="cart-title" class="text-sm font-extrabold ink sm:text-base">Keranjang</h2>
                        <p class="ink-muted text-[11px]"><span data-cart-count><?= (int) $cartState['count'] ?></span> item dipilih</p>
                    </div>
                </div>
                <button type="button" data-cart-close
                        class="rounded-xl p-1.5 ink-muted hover:bg-pink-50 hover:text-pink-700 dark:hover:bg-pink-500/10 dark:hover:text-pink-200"
                        aria-label="Tutup keranjang">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </header>

            <div class="max-h-[45vh] overflow-y-auto p-4 sm:max-h-[42vh] sm:p-5">
                <div data-cart-empty class="<?= $cartState['lines'] ? 'hidden' : '' ?> py-10 text-center">
                    <i data-lucide="shopping-bag" class="mx-auto mb-2 h-10 w-10 text-pink-300 dark:text-pink-500/50"></i>
                    <p class="text-sm font-bold ink">Keranjang masih kosong</p>
                    <p class="ink-muted mt-0.5 text-xs">Pilih layanan di katalog lalu tekan tombol Beli.</p>
                </div>

                <ul class="space-y-2.5" data-cart-lines></ul>
            </div>

            <form id="checkout-form" class="border-t border-[#f6dbe6] p-4 dark:border-[#3d2340] sm:p-5" novalidate>
                <div class="space-y-2.5">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div>
                            <label for="co-name" class="ink mb-1 block text-[11px] font-bold">Nama <span class="text-rose-700 dark:text-rose-300" aria-hidden="true">*</span><span class="sr-only">(wajib)</span></label>
                            <input id="co-name" name="name" type="text" required autocomplete="name" maxlength="80"
                                   class="field py-2 text-xs" placeholder="Nama kamu">
                            <p class="mt-1 hidden text-[10px] font-semibold text-rose-700 dark:text-rose-300" data-error="name"></p>
                        </div>
                        <div>
                            <label for="co-phone" class="ink mb-1 block text-[11px] font-bold">No. WhatsApp <span class="text-rose-700 dark:text-rose-300" aria-hidden="true">*</span><span class="sr-only">(wajib)</span></label>
                            <input id="co-phone" name="phone" type="tel" required inputmode="tel" autocomplete="tel" maxlength="20"
                                   class="field py-2 text-xs" placeholder="08xxxxxxxxxx">
                            <p class="mt-1 hidden text-[10px] font-semibold text-rose-700 dark:text-rose-300" data-error="phone"></p>
                        </div>
                    </div>

                        <div data-game-field <?= $hasGame ? '' : 'hidden' ?>>
                            <label for="co-game" data-game-label class="ink mb-1 block text-[11px] font-bold"><?= e($gameLabel) ?> <span class="text-rose-700 dark:text-rose-300" aria-hidden="true">*</span><span class="sr-only">(wajib)</span></label>
                            <input id="co-game" name="game_id" type="text" maxlength="120"
                                   data-game-input class="field py-2 text-xs" placeholder="<?= e($gamePlaceholder) ?>">
                            <p class="mt-1 hidden text-[10px] font-semibold text-rose-700 dark:text-rose-300" data-error="game_id"></p>
                        </div>

                    <fieldset>
                        <legend class="ink mb-1 block text-[11px] font-bold">Metode Pembayaran</legend>
                        <div class="grid grid-cols-3 gap-1.5">
                            <?php foreach ($payments as $i => $pay): ?>
                                <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 px-2 py-2 text-[11px] font-bold transition-colors
                                              border-[#f0d3e0] text-[#5c4354] hover:border-pink-300
                                              dark:border-[#4a2b50] dark:text-[#dcc4d6] dark:hover:border-pink-400/50
                                              has-[:checked]:border-pink-600 has-[:checked]:bg-pink-50 has-[:checked]:text-pink-800
                                              dark:has-[:checked]:border-pink-400 dark:has-[:checked]:bg-pink-500/15 dark:has-[:checked]:text-pink-200">
                                    <input type="radio" name="payment" value="<?= e($pay) ?>" class="sr-only" <?= $i === 0 ? 'checked' : '' ?>>
                                    <span class="truncate"><?= e($paymentLabels[$pay] ?? $pay) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div>
                        <label for="co-note" class="ink mb-1 block text-[11px] font-bold">Catatan (opsional)</label>
                        <textarea id="co-note" name="note" rows="2" maxlength="400"
                                  class="field py-2 text-xs" placeholder="Keterangan tambahan untuk admin"></textarea>
                    </div>
                </div>

                <div class="mt-3.5 flex items-center justify-between gap-3 border-t border-[#f6dbe6] pt-3 dark:border-[#3d2340]">
                    <div>
                        <span class="ink-muted block text-[10px] font-bold uppercase">Total</span>
                        <span class="text-base font-black text-pink-700 dark:text-pink-300" data-cart-total><?= rupiah((int) $cartState['total']) ?></span>
                    </div>
                    <button type="submit" id="checkout-btn" class="btn-primary px-4 py-2.5 text-xs"
                            aria-label="Buat pesanan dan lanjut ke WhatsApp" <?= $cartState['lines'] ? '' : 'disabled' ?>>
                        <i data-lucide="send" class="h-3.5 w-3.5"></i>
                        <span data-checkout-label>Buat Pesanan</span>
                    </button>
                </div>

                <p class="ink-muted mt-2.5 text-center text-[10px] leading-relaxed">
                    Pesanan tersimpan otomatis di sistem toko, lalu kamu diarahkan ke WhatsApp admin
                    untuk instruksi pembayaran.
                </p>
                <p class="mt-1.5 hidden rounded-xl bg-rose-50 px-3 py-2 text-[11px] font-semibold text-rose-800 dark:bg-rose-500/15 dark:text-rose-200" data-error="cart"></p>
            </form>
        </div>
    </div>
</div>
