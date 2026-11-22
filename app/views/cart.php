<?php
/**
 * Halaman keranjang (fallback tanpa JS) + ringkasan.
 * @var array $cart
 */
declare(strict_types=1);

$adminWa = setting('admin_whatsapp', '6282318613826');
?>


<main id="main" class="mx-auto w-full max-w-3xl flex-1 px-3.5 py-8 sm:px-6 sm:py-12">
    <a href="/" class="mb-5 inline-flex items-center gap-2 text-sm font-bold text-pink-800 hover:text-pink-950 dark:text-pink-200">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Lanjut belanja
    </a>

    <h1 class="text-xl font-black ink sm:text-2xl">Keranjang belanja</h1>

    <?php if (!$cart['lines']): ?>
        <div class="card mt-5 px-4 py-14 text-center">
            <i data-lucide="shopping-bag" class="mx-auto mb-3 h-12 w-12 text-pink-300 dark:text-pink-500/50"></i>
            <p class="font-bold ink">Keranjang masih kosong</p>
            <p class="ink-muted mt-1 text-sm">Pilih layanan di katalog untuk mulai memesan.</p>
            <a href="/" class="btn-primary mt-4 px-4 py-2 text-sm">Buka katalog</a>
        </div>
    <?php else: ?>
        <div class="card mt-5 overflow-hidden">
            <ul class="divide-y divide-[#f6dbe6] dark:divide-[#3d2340]">
                <?php foreach ($cart['lines'] as $l):
                    $accent = accent_classes($l['accent']); ?>
                    <li class="flex items-center justify-between gap-3 p-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr <?= e($accent['grad']) ?> text-[10px] font-black text-white">
                                <?= e($l['icon']) ?>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold ink"><?= e($l['name']) ?></p>
                                <p class="truncate text-[11px] font-semibold text-pink-700 dark:text-pink-300"><?= e($l['variant_label']) ?></p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-black ink"><?= rupiah((int) $l['subtotal']) ?></p>
                            <p class="ink-muted text-[11px]"><?= (int) $l['qty'] ?>x</p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="flex items-center justify-between gap-3 border-t border-[#f6dbe6] p-4 dark:border-[#3d2340]">
                <div>
                    <span class="ink-muted block text-[10px] font-bold uppercase">Total</span>
                    <span class="text-lg font-black text-pink-700 dark:text-pink-300"><?= rupiah((int) $cart['total']) ?></span>
                </div>
                <button type="button" data-cart-open class="btn-primary px-4 py-2.5 text-sm">
                    <i data-lucide="send" class="h-4 w-4"></i> Lanjut checkout
                </button>
            </div>
        </div>
        <p class="ink-muted mt-3 text-xs">
            Butuh bantuan? <a href="https://wa.me/<?= e($adminWa) ?>" target="_blank" rel="noopener noreferrer"
                              class="font-bold text-pink-700 underline dark:text-pink-300">Chat admin</a>.
        </p>
    <?php endif; ?>
</main>

<?php require BBY_APP . '/views/partials/cart-drawer.php'; ?>
