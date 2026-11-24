<?php
/** Popup detail produk — versi ringkas dari app/views/product.php. */
declare(strict_types=1);

$accent = accent_classes($product['accent']);
$adminWa = setting('admin_whatsapp', '6282318613826');
$firstVariant = $product['variants'][0] ?? null;
$priceText = static function (array $v): string {
    return $v['price_max'] !== null
        ? rupiah($v['price']) . ' – ' . rupiah($v['price_max'])
        : rupiah($v['price']);
};
?>
<div class="space-y-4">
    <div class="flex items-start gap-3">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr <?= e($accent['grad']) ?> text-base font-black text-white ring-1 ring-white/30">
            <?= e($product['icon']) ?>
        </span>
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wide text-pink-300"><?= e($product['category_name']) ?></p>
            <h3 class="text-base font-black ink sm:text-lg"><?= e($product['name']) ?></h3>
            <p class="mt-0.5 text-xs ink-muted"><?= e($product['subtext']) ?></p>
        </div>
    </div>

    <?php if ($product['description'] !== ''): ?>
        <p class="text-xs leading-relaxed ink-muted"><?= e($product['description']) ?></p>
    <?php endif; ?>

    <?php if ($product['features']): ?>
        <ul class="space-y-1.5 rounded-2xl bg-pink-500/10 p-3 text-xs ink">
            <?php foreach ($product['features'] as $feat): ?>
                <li class="flex items-start gap-2">
                    <i data-lucide="check-circle-2" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-300"></i>
                    <span><?= e($feat) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($firstVariant): ?>
        <form action="/keranjang/tambah" method="post" data-detail-form class="space-y-3 rounded-2xl border border-pink-500/25 p-3">
            <?= csrf_field() ?>
            <div>
                <label for="pd-variant" class="mb-1 block text-[11px] font-bold ink-muted">Pilih paket</label>
                <select id="pd-variant" name="variant_id" class="field py-2 text-xs">
                    <?php foreach ($product['variants'] as $v): ?>
                        <option value="<?= (int) $v['id'] ?>" data-price="<?= e($priceText($v)) ?>" <?= variant_available($v) ? '' : 'disabled' ?>>
                            <?= e($v['label']) ?> — <?= e($priceText($v)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-[10px] font-bold uppercase ink-muted">Harga</span>
                    <span class="text-base font-black text-pink-300" data-detail-price><?= e($priceText($firstVariant)) ?></span>
                </div>
                <button type="submit" class="btn-primary px-4 py-2 text-xs">
                    <i data-lucide="shopping-cart" class="h-3.5 w-3.5"></i> Tambah ke keranjang
                </button>
            </div>
        </form>
    <?php else: ?>
        <a href="https://wa.me/<?= e($adminWa) ?>?text=<?= rawurlencode('Halo, saya mau tanya ' . $product['name']) ?>"
           target="_blank" rel="noopener noreferrer" class="btn-primary w-full py-2.5 text-xs">
            <i data-lucide="message-circle" class="h-4 w-4"></i> Tanya CS
        </a>
    <?php endif; ?>
</div>
