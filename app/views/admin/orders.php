<?php
/**
 * Daftar pesanan + KPI.
 * @var array $orders, $itemsByOrder, $stats
 * @var string $status, $q
 */
declare(strict_types=1);

$kpis = [
    ['label' => 'Pesanan hari ini', 'value' => (string) $stats['hariIni'], 'sub' => 'Masuk sejak 00:00 WITA', 'icon' => 'inbox', 'tone' => 'sky'],
    ['label' => 'Perlu diproses',   'value' => (string) $stats['baru'],    'sub' => 'Status masih "Baru"',    'icon' => 'clock', 'tone' => 'amber'],
    ['label' => 'Total pesanan',    'value' => (string) $stats['total'],   'sub' => 'Sepanjang waktu',        'icon' => 'receipt-text', 'tone' => 'pink'],
    ['label' => 'Omzet (non-batal)','value' => rupiah($stats['omzet']),    'sub' => 'Hari ini: ' . rupiah($stats['omzetHariIni']), 'icon' => 'wallet', 'tone' => 'emerald'],
];

$toneMap = [
    'sky'     => 'bg-sky-100 dark:bg-sky-500/20 text-sky-800 dark:text-sky-200',
    'amber'   => 'bg-amber-100 text-amber-900',
    'pink'    => 'bg-pink-100 text-pink-800 dark:text-pink-200',
    'emerald' => 'bg-emerald-100 text-emerald-800 dark:text-emerald-200',
];
?>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($kpis as $k): ?>
        <div class="card p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#6f5568] dark:text-[#c6a9bf]"><?= e($k['label']) ?></p>
                    <p class="mt-1 truncate text-xl font-black text-[#241520] dark:text-[#f7ecf3]"><?= e($k['value']) ?></p>
                    <p class="mt-0.5 text-[11px] text-[#6f5568] dark:text-[#c6a9bf]"><?= e($k['sub']) ?></p>
                </div>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl <?= $toneMap[$k['tone']] ?>">
                    <i data-lucide="<?= e($k['icon']) ?>" class="h-4 w-4"></i>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form action="/admin/pesanan" method="get" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
    <div class="flex flex-1 gap-2">
        <label for="q" class="sr-only">Cari pesanan</label>
        <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Cari kode, nama, atau nomor WA…"
               class="field py-2 text-sm">
        <button type="submit" class="btn-quiet px-3 py-2 text-xs">
            <i data-lucide="search" class="h-3.5 w-3.5"></i> Cari
        </button>
    </div>
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto">
        <a href="/admin/pesanan<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>"
           class="chip <?= $status === '' ? 'bg-pink-700 text-white ring-pink-700' : 'bg-white dark:bg-[#1a1021] text-[#5c4354] dark:text-[#dcc4d6] ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10' ?>">Semua</a>
        <?php foreach (ORDER_STATUSES as $key => $label): ?>
            <a href="/admin/pesanan?status=<?= e($key) ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
               class="chip <?= $status === $key ? 'bg-pink-700 text-white ring-pink-700' : 'bg-white dark:bg-[#1a1021] text-[#5c4354] dark:text-[#dcc4d6] ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</form>

<?php if (!$orders): ?>
    <div class="card mt-4 px-4 py-16 text-center">
        <i data-lucide="inbox" class="mx-auto mb-3 h-12 w-12 text-pink-400"></i>
        <h2 class="text-sm font-bold text-[#241520] dark:text-[#f7ecf3]">Belum ada pesanan</h2>
        <p class="mt-1 text-xs text-[#6b5563] dark:text-[#c9aec4]">
            <?= $q !== '' || $status !== '' ? 'Tidak ada pesanan yang cocok dengan filter ini.' : 'Pesanan baru dari pembeli akan muncul di sini secara otomatis.' ?>
        </p>
        <a href="/" class="btn-primary mt-4 px-4 py-2 text-xs">Buka toko</a>
    </div>
<?php else: ?>
    <div class="mt-4 space-y-2.5">
        <?php foreach ($orders as $o):
            $items = $itemsByOrder[(int) $o['id']] ?? [];
            $first = $items[0] ?? null;
            $more = count($items) - 1;
            ?>
            <article class="card p-4" data-order="<?= (int) $o['id'] ?>">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="/admin/pesanan/<?= (int) $o['id'] ?>"
                               class="font-mono text-xs font-black text-pink-800 dark:text-pink-200 underline decoration-pink-300 hover:text-pink-950">
                                <?= e($o['code']) ?>
                            </a>
                            <span class="chip <?= status_classes($o['status']) ?>" data-status-chip><?= e(ORDER_STATUSES[$o['status']] ?? $o['status']) ?></span>
                        </div>
                        <p class="mt-1 text-sm font-bold text-[#241520] dark:text-[#f7ecf3]">
                            <?= e($o['customer_name']) ?>
                            <span class="ml-1 font-mono text-[11px] font-semibold text-[#6b5563] dark:text-[#c9aec4]"><?= e($o['customer_phone']) ?></span>
                        </p>
                        <?php if ($first): ?>
                            <p class="mt-0.5 text-[11px] text-[#6b5563] dark:text-[#c9aec4]">
                                <?= e($first['product_name']) ?> — <?= e($first['variant_label']) ?>
                                <?php if ($more > 0): ?><span class="font-bold text-pink-700 dark:text-pink-300">+<?= $more ?> lainnya</span><?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <p class="text-base font-black text-[#241520] dark:text-[#f7ecf3]"><?= rupiah((int) $o['total']) ?></p>
                        <p class="text-[11px] text-[#6f5568] dark:text-[#c6a9bf]"><?= fmt_date($o['created_at']) ?></p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-[#f6dbe6] dark:border-[#3d2340] pt-2.5">
                    <?php foreach (ORDER_STATUSES as $key => $label): ?>
                        <button type="button" data-set-status="<?= (int) $o['id'] ?>" data-status="<?= e($key) ?>"
                                class="chip transition-colors <?= $o['status'] === $key
                                    ? 'bg-pink-700 text-white ring-pink-700'
                                    : 'bg-white dark:bg-[#1a1021] text-[#5c4354] dark:text-[#dcc4d6] ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10' ?>">
                            <?= e($label) ?>
                        </button>
                    <?php endforeach; ?>
                    <a href="https://wa.me/<?= e(wa_number($o['customer_phone'])) ?>?text=<?= rawurlencode('Halo ' . $o['customer_name'] . ', terkait pesanan ' . $o['code'] . ' di Bbylaa Store.') ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="chip ml-auto bg-emerald-50 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-200 ring-emerald-200 hover:bg-emerald-100">
                        <i data-lucide="message-circle" class="h-3 w-3"></i> Chat pembeli
                    </a>
                    <a href="/admin/pesanan/<?= (int) $o['id'] ?>" class="chip bg-white dark:bg-[#1a1021] text-[#5c4354] dark:text-[#dcc4d6] ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10">
                        <i data-lucide="chevron-right" class="h-3 w-3"></i> Detail
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
