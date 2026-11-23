<?php
/**
 * Kelola produk + stok per varian.
 * @var array $products, $cats
 * @var array|null $edit
 */
declare(strict_types=1);

$catMap = [];
foreach ($cats as $c) {
    $catMap[$c['id']] = $c;
}
?>
<?php if ($edit): ?>
    <div class="card mb-4 overflow-x-clip p-4 sm:p-5">
        <div class="mb-3 flex items-start justify-between gap-2">
            <div>
                <h2 class="text-sm font-black text-[#241520] dark:text-[#f7ecf3]">Edit: <?= e($edit['name']) ?></h2>
                <p class="text-[11px] text-[#6b5563] dark:text-[#c9aec4]">ID <?= (int) $edit['id'] ?> • <?= e($catMap[$edit['category_id']]['name'] ?? $edit['category_name']) ?></p>
            </div>
            <a href="/admin/produk" class="chip bg-white dark:bg-[#1a1021] text-[#5c4354] dark:text-[#dcc4d6] ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10">
                <i data-lucide="x" class="h-3 w-3"></i> Tutup
            </a>
        </div>

        <form action="/admin/produk/simpan" method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $edit['id'] ?>">

            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="p-name" class="mb-1 block text-[11px] font-bold text-[#6b5563] dark:text-[#c9aec4]">Nama produk</label>
                    <input id="p-name" name="name" type="text" required maxlength="120" value="<?= e($edit['name']) ?>"
                           class="field py-2 text-sm">
                </div>
                <div>
                    <label for="p-badge" class="mb-1 block text-[11px] font-bold text-[#6b5563] dark:text-[#c9aec4]">Badge</label>
                    <input id="p-badge" name="badge" type="text" maxlength="40" value="<?= e($edit['badge']) ?>"
                           class="field py-2 text-sm" placeholder="Promo">
                </div>
            </div>

            <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-bold text-[#241520] dark:text-[#f7ecf3]">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" <?= (int) $edit['is_active'] === 1 ? 'checked' : '' ?>
                       class="h-4 w-4 rounded border-[#f0d3e0] dark:border-[#4a2b50] text-pink-700 dark:text-pink-300 focus:ring-pink-500">
                Tampilkan di katalog
            </label>

            <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel yang dapat digeser">
                <table class="w-full min-w-[500px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#f6dbe6] dark:border-[#3d2340]">
                            <th class="table-head py-2 pr-3">Paket</th>
                            <th class="table-head py-2 pr-3">Harga</th>
                            <th class="table-head py-2 pr-3">Harga maks (opsional)</th>
                            <th class="table-head py-2">Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($edit['variants'] as $v): ?>
                            <tr class="border-b border-[#f6dbe6]/60 dark:border-[#3d2340]/60 last:border-0">
                                <td class="py-2 pr-3 font-semibold text-[#241520] dark:text-[#f7ecf3]"><?= e($v['label']) ?></td>
                                <td class="py-2 pr-3">
                                    <label class="sr-only" for="price-<?= (int) $v['id'] ?>">Harga <?= e($v['label']) ?></label>
                                    <input id="price-<?= (int) $v['id'] ?>" name="price[<?= (int) $v['id'] ?>]" type="number" min="0" step="500"
                                           value="<?= (int) $v['price'] ?>" class="field py-1.5 text-xs">
                                </td>
                                <td class="py-2 pr-3">
                                    <label class="sr-only" for="pmax-<?= (int) $v['id'] ?>">Harga maks</label>
                                    <input id="pmax-<?= (int) $v['id'] ?>" name="price_max_<?= (int) $v['id'] ?>" type="number" min="0" step="500"
                                           value="<?= $v['price_max'] === null ? '' : (int) $v['price_max'] ?>" placeholder="—"
                                           class="field py-1.5 text-xs">
                                </td>
                                <td class="py-2">
                                    <div class="flex items-center gap-1.5">
                                        <label class="sr-only" for="stock-<?= (int) $v['id'] ?>">Stok</label>
                                        <input id="stock-<?= (int) $v['id'] ?>" type="text" inputmode="numeric"
                                               value="<?= $v['stock'] === null ? '' : (int) $v['stock'] ?>"
                                               placeholder="∞" data-stock-for="<?= (int) $v['id'] ?>"
                                               class="field w-20 py-1.5 text-xs">
                                        <span class="text-[10px] text-[#6f5568] dark:text-[#c6a9bf]" data-stock-label="<?= (int) $v['id'] ?>">
                                            <?= $v['stock'] === null ? 'tanpa batas' : ((int) $v['stock'] > 0 ? (int) $v['stock'] . ' tersisa' : 'habis') ?>
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-[10px] text-[#6f5568] dark:text-[#c6a9bf]">Kosongkan = tanpa batas</p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-[#f6dbe6] dark:border-[#3d2340] pt-3">
                <button type="submit" id="save-product" class="btn-primary px-4 py-2 text-xs">
                    <i data-lucide="save" class="h-3.5 w-3.5"></i> Simpan perubahan
                </button>
                <a href="/produk?id=<?= (int) $edit['id'] ?>" target="_blank" rel="noopener"
                   class="btn-quiet px-4 py-2 text-xs">
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i> Lihat di toko
                </a>
            </div>
            <p class="text-[10px] text-[#6f5568] dark:text-[#c6a9bf]">
                Stok disimpan otomatis saat kursor meninggalkan kolom. Riwayat perubahan tercatat di log audit.
            </p>
        </form>
    </div>
<?php endif; ?>

<div class="card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#f6dbe6] dark:border-[#3d2340] px-4 py-3 sm:px-5">
        <h2 class="text-xs font-black uppercase tracking-wider text-[#6b5563] dark:text-[#c9aec4]">
            <?= count($products) ?> produk aktif
        </h2>
        <p class="text-[11px] text-[#6f5568] dark:text-[#c6a9bf]">Klik baris untuk mengedit harga &amp; stok</p>
    </div>
    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel yang dapat digeser">
        <table class="w-full min-w-[640px] text-left text-xs">
            <thead class="bg-[#fdf6f9] dark:bg-[#140b1a]">
                <tr>
                    <th class="table-head px-4 py-2">Produk</th>
                    <th class="table-head px-3 py-2">Kategori</th>
                    <th class="table-head px-3 py-2">Varian</th>
                    <th class="table-head px-3 py-2 text-right">Harga mulai</th>
                    <th class="table-head px-3 py-2 text-center">Stok</th>
                    <th class="table-head px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#f6dbe6] dark:divide-[#3d2340]">
                <?php foreach ($products as $p):
                    $minPrice = min(array_column($p['variants'], 'price'));
                    $limited = 0;
                    $outOfStock = 0;
                    foreach ($p['variants'] as $v) {
                        if ($v['stock'] !== null) {
                            $limited++;
                            if ($v['stock'] <= 0) {
                                $outOfStock++;
                            }
                        }
                    }
                    ?>
                    <tr class="hover:bg-[#fdf6f9]/70 dark:bg-[#140b1a]/70">
                        <td class="px-4 py-2.5">
                            <p class="font-bold text-[#241520] dark:text-[#f7ecf3]"><?= e($p['name']) ?></p>
                            <p class="text-[10px] text-[#6f5568] dark:text-[#c6a9bf]"><?= e($p['subtext']) ?></p>
                        </td>
                        <td class="px-3 py-2.5 text-[#5c4354] dark:text-[#dcc4d6]"><?= e($catMap[$p['category_id']]['name'] ?? $p['category_name']) ?></td>
                        <td class="px-3 py-2.5 text-[#5c4354] dark:text-[#dcc4d6]"><?= count($p['variants']) ?></td>
                        <td class="px-3 py-2.5 text-right font-bold text-[#241520] dark:text-[#f7ecf3]"><?= rupiah($minPrice) ?></td>
                        <td class="px-3 py-2.5 text-center">
                            <?php if ($limited === 0): ?>
                                <span class="chip bg-slate-100 text-slate-700 ring-slate-200">∞</span>
                            <?php elseif ($outOfStock > 0): ?>
                                <span class="chip bg-rose-100 text-rose-800 dark:text-rose-200 ring-rose-200"><?= $outOfStock ?> habis</span>
                            <?php else: ?>
                                <span class="chip bg-emerald-100 text-emerald-800 dark:text-emerald-200 ring-emerald-200">terbatas</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <a href="/admin/produk?edit=<?= (int) $p['id'] ?>" class="chip bg-white dark:bg-[#1a1021] text-[#5c4354] dark:text-[#dcc4d6] ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10">
                                <i data-lucide="pencil" class="h-3 w-3"></i> Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
