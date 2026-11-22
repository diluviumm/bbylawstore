<?php
/**
 * Halaman lacak pesanan.
 * @var string $code
 * @var array|null $order
 */
declare(strict_types=1);

$statusLabels = ORDER_STATUSES;
?>
<main id="main" class="mx-auto w-full max-w-2xl flex-1 px-3.5 py-8 sm:px-6 sm:py-12">

    <a href="/" class="mb-5 inline-flex items-center gap-2 text-sm font-bold text-pink-800 hover:text-pink-950 dark:text-pink-200">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Beranda
    </a>

    <h1 class="text-xl font-black ink sm:text-2xl">Lacak pesanan</h1>
    <p class="ink-muted mt-1 text-sm">Masukkan kode pesanan yang kamu terima saat checkout.</p>

    <form action="/lacak" method="post" class="card mt-4 flex flex-col gap-2 p-4 sm:flex-row sm:items-end">
        <?= csrf_field() ?>
        <div class="flex-1">
            <label for="code" class="ink mb-1 block text-xs font-bold">Kode pesanan</label>
            <input id="code" name="code" type="text" required value="<?= e($code) ?>"
                   placeholder="BBY-261123-AB7XK" maxlength="30" autocomplete="off"
                   class="field py-2.5 text-sm uppercase tracking-wider">
        </div>
        <button type="submit" class="btn-primary px-5 py-2.5 text-sm">
            <i data-lucide="search" class="h-4 w-4"></i> Cari
        </button>
    </form>

    <?php if ($code !== '' && !$order): ?>
        <div class="card mt-4 border-rose-200 px-4 py-10 text-center dark:border-rose-500/40">
            <i data-lucide="search-x" class="mx-auto mb-2 h-10 w-10 text-rose-500"></i>
            <p class="font-bold ink">Pesanan <strong><?= e($code) ?></strong> tidak ditemukan</p>
            <p class="ink-muted mt-1 text-xs">Periksa kembali kodenya, atau hubungi admin lewat WhatsApp.</p>
        </div>
    <?php endif; ?>

    <?php if ($order):
        $status = $order['status'];
        $steps = ['baru' => 'Diterima', 'diproses' => 'Diproses', 'selesai' => 'Selesai'];
        $order_index = array_search($status, array_keys($steps), true);
        ?>
        <div class="card mt-4 overflow-hidden">
            <div class="border-b border-[#f6dbe6] p-4 dark:border-[#3d2340] sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-black ink"><?= e($order['code']) ?></p>
                    <span class="chip <?= status_classes($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
                </div>
                <p class="ink-muted mt-1 text-[11px]">Dibuat <?= fmt_date($order['created_at']) ?></p>
            </div>

            <?php if ($status !== 'batal'): ?>
                <ol class="flex items-center gap-2 p-4 sm:p-5" aria-label="Progres pesanan">
                    <?php foreach ($steps as $key => $label):
                        $idx = array_search($key, array_keys($steps), true);
                        $done = $order_index !== false && $idx <= $order_index; ?>
                        <li class="flex flex-1 flex-col items-center gap-1 text-center">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-black
                                         <?= $done ? 'bg-emerald-600 text-white' : 'bg-[#f3e3ec] text-[#8a7086] dark:bg-[#2a1735] dark:text-[#b79bb0]' ?>">
                                <?= $idx + 1 ?>
                            </span>
                            <span class="text-[10px] font-bold <?= $done ? 'text-emerald-700 dark:text-emerald-300' : 'ink-muted' ?>"><?= e($label) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <ul class="divide-y divide-[#f6dbe6] px-4 pb-2 dark:divide-[#3d2340] sm:px-5">
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

            <div class="flex items-center justify-between border-t border-[#f6dbe6] p-4 dark:border-[#3d2340] sm:px-5">
                <span class="text-xs font-bold uppercase ink-muted">Total</span>
                <span class="text-base font-black text-pink-700 dark:text-pink-300"><?= rupiah((int) $order['total']) ?></span>
            </div>
        </div>
    <?php endif; ?>
</main>
