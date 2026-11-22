<?php
/**
 * Halaman "pesanan berhasil dibuat".
 * @var string $code
 * @var array|null $order
 */
declare(strict_types=1);

$adminWa = setting('admin_whatsapp', '6282318613826');
?>
<main id="main" class="mx-auto w-full max-w-2xl flex-1 px-3.5 py-8 sm:px-6 sm:py-12">

<?php if (!$order): ?>
    <div class="card px-4 py-14 text-center">
        <i data-lucide="search-x" class="mx-auto mb-3 h-12 w-12 text-rose-500"></i>
        <h1 class="text-lg font-black ink">Pesanan tidak ditemukan</h1>
        <p class="ink-muted mt-1 text-sm">Kode pesanan <strong><?= e($code) ?></strong> tidak ada di sistem.</p>
        <a href="/" class="btn-primary mt-4 px-4 py-2 text-sm">Buka katalog</a>
    </div>
<?php else: ?>

    <div class="card overflow-hidden">
        <div class="border-b border-[#f6dbe6] bg-emerald-50 p-5 text-center dark:border-[#3d2340] dark:bg-emerald-500/10 sm:p-7">
            <span class="mx-auto mb-2 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-600/30">
                <i data-lucide="check" class="h-7 w-7"></i>
            </span>
            <h1 class="text-lg font-black ink sm:text-xl">Pesanan berhasil dibuat</h1>
            <p class="ink-muted mt-1 text-xs sm:text-sm">
                Tersimpan di sistem toko. Lanjutkan konfirmasi ke admin via WhatsApp.
            </p>
            <p class="mt-3 inline-flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-xs font-black ink ring-1 ring-emerald-300 dark:bg-[#1a1021] dark:ring-emerald-400/40">
                <i data-lucide="hash" class="h-3.5 w-3.5"></i> <?= e($order['code']) ?>
            </p>
        </div>

        <div class="p-5 sm:p-6">
            <h2 class="ink mb-2 text-xs font-black uppercase tracking-wider">Rincian pesanan</h2>
            <ul class="divide-y divide-[#f6dbe6] dark:divide-[#3d2340]">
                <?php foreach ($order['items'] as $it): ?>
                    <li class="flex items-start justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-bold ink"><?= e($it['product_name']) ?></p>
                            <p class="ink-muted text-[11px]"><?= e($it['variant_label']) ?> • <?= (int) $it['qty'] ?>x</p>
                        </div>
                        <span class="shrink-0 text-sm font-black ink"><?= rupiah((int) $it['subtotal']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="mt-3 flex items-center justify-between rounded-2xl bg-pink-50 px-4 py-3 dark:bg-pink-500/10">
                <span class="text-xs font-bold uppercase ink-muted">Total</span>
                <span class="text-lg font-black text-pink-700 dark:text-pink-300"><?= rupiah((int) $order['total']) ?></span>
            </div>

            <dl class="ink-muted mt-4 grid grid-cols-1 gap-1.5 text-xs sm:grid-cols-2">
                <div><dt class="inline font-bold ink">Nama:</dt> <dd class="inline"><?= e($order['customer_name']) ?></dd></div>
                <div><dt class="inline font-bold ink">WhatsApp:</dt> <dd class="inline"><?= e($order['customer_phone']) ?></dd></div>
                <div><dt class="inline font-bold ink">Pembayaran:</dt> <dd class="inline"><?= e($order['payment'] ?: '-') ?></dd></div>
                <div><dt class="inline font-bold ink">Waktu:</dt> <dd class="inline"><?= fmt_date($order['created_at']) ?></dd></div>
                <?php if ($order['note'] !== ''): ?>
                    <div class="sm:col-span-2"><dt class="inline font-bold ink">Catatan:</dt> <dd class="inline"><?= e($order['note']) ?></dd></div>
                <?php endif; ?>
            </dl>

            <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                <a href="https://wa.me/<?= e($adminWa) ?>?text=<?= rawurlencode('Halo Admin, saya sudah checkout dengan kode ' . $order['code'] . '. Mohon instruksi pembayarannya.') ?>"
                   target="_blank" rel="noopener noreferrer" class="btn-primary flex-1 py-2.5 text-sm">
                    <i data-lucide="message-circle" class="h-4 w-4"></i> Konfirmasi ke WhatsApp
                </a>
                <a href="/lacak?kode=<?= urlencode($order['code']) ?>" class="btn-quiet flex-1 py-2.5 text-sm">
                    <i data-lucide="package-search" class="h-4 w-4"></i> Lacak pesanan
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>
</main>
