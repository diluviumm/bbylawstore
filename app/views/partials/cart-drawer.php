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

                <ul class="space-y-2.5" data-cart-lines>
                    <?php foreach ($cartState['lines'] as $l): ?>
                        <li class="flex items-center justify-between gap-3 rounded-2xl border border-pink-500/20 p-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold ink"><?= e($l['name']) ?></p>
                                <p class="truncate text-[10px] font-semibold text-pink-600 dark:text-pink-300"><?= e($l['variant_label']) ?> • <?= (int) $l['qty'] ?>x</p>
                                <p class="mt-0.5 text-[11px] font-black ink"><?= rupiah((int) $l['subtotal']) ?></p>
                            </div>
                            <form action="/keranjang/hapus" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="variant_id" value="<?= (int) $l['variant_id'] ?>">
                                <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                        aria-label="Hapus <?= e($l['name']) ?> dari keranjang">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="border-t border-[#f6dbe6] p-4 dark:border-[#3d2340] sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <span class="ink-muted block text-[10px] font-bold uppercase">Total</span>
                        <span class="text-lg font-black text-pink-700 dark:text-pink-300" data-cart-total><?= rupiah((int) $cartState['total']) ?></span>
                    </div>
                    <button type="button" data-cart-checkout
                            class="btn-primary px-4 py-2.5 text-xs"
                            aria-label="Lanjut checkout" <?= $cartState['lines'] ? '' : 'disabled' ?>>
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i> Checkout
                    </button>
                </div>
                <p class="ink-muted text-center text-[10px] leading-relaxed">
                    Pembelian diproses lewat pop-up — halaman katalog tetap terbuka.
                </p>
            </div>
        </div>
    </div>
</div>
