<?php
/**
 * Halaman detail produk.
 *
 * @var array $product, $cat, $cart
 * @var bool $isOpen
 */
declare(strict_types=1);

$accent = accent_classes($product['accent']);
$adminWa = setting('admin_whatsapp', '6282318613826');
$hasRange = false;
foreach ($product['variants'] as $v) {
    if ($v['price_max'] !== null) {
        $hasRange = true;
    }
}
?>


<header class="border-b border-[#f6dbe6] bg-white/85 backdrop-blur-md dark:border-[#3d2340] dark:bg-[#140b1a]/90">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-3 px-3.5 sm:h-20 sm:px-6">
        <a href="/" class="flex items-center gap-2 text-sm font-bold text-pink-800 hover:text-pink-950 dark:text-pink-200 dark:hover:text-pink-100">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke katalog
        </a>
        <div class="flex items-center gap-1.5">
            <button type="button" data-theme-toggle
                    class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-[#5c4354] ring-1 ring-[#f0d3e0]
                           hover:bg-[#fdf2f7] dark:bg-[#241531] dark:text-[#e9d6e4] dark:ring-[#4a2b50] dark:hover:bg-[#2d1a3b]"
                    aria-label="Ganti tema terang atau gelap">
                <i data-lucide="sun" class="h-4 w-4 dark:hidden"></i>
                <i data-lucide="moon" class="hidden h-4 w-4 dark:block"></i>
            </button>
            <button type="button" data-cart-open
                    aria-label="Buka keranjang belanja"
                    class="relative inline-flex items-center gap-1.5 rounded-2xl bg-pink-50 px-3 py-2 text-xs font-bold text-pink-800
                           ring-1 ring-pink-200 hover:bg-pink-100 dark:bg-pink-500/15 dark:text-pink-200 dark:ring-pink-400/30">
                <i data-lucide="shopping-cart" class="h-4 w-4"></i>
                <span class="hidden sm:inline">Keranjang</span>
                <span data-cart-badge class="<?= ($cart['count'] ?? 0) > 0 ? '' : 'hidden' ?> inline-flex min-w-[18px] items-center justify-center rounded-full bg-pink-600 px-1.5 py-0.5 text-[10px] font-black text-white"><?= (int) ($cart['count'] ?? 0) ?></span>
            </button>
        </div>
    </div>
</header>

<main id="main" class="mx-auto w-full max-w-5xl flex-1 px-3.5 py-6 sm:px-6 sm:py-10">

    <nav aria-label="Breadcrumb" class="ink-muted mb-4 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold">
        <a href="/" class="hover:text-pink-700 dark:hover:text-pink-300">Beranda</a>
        <span aria-hidden="true">/</span>
        <a href="/?kategori=<?= e($product['category_id']) ?>" class="hover:text-pink-700 dark:hover:text-pink-300"><?= e($product['category_name']) ?></a>
        <span aria-hidden="true">/</span>
        <span class="ink"><?= e($product['name']) ?></span>
    </nav>

    <?php if (!$isOpen): ?>
        <div class="mb-4 flex items-start gap-2.5 rounded-2xl bg-amber-50 p-3.5 text-xs font-semibold text-amber-900 ring-1 ring-amber-300
                    dark:bg-amber-500/15 dark:text-amber-100 dark:ring-amber-400/40">
            <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <span><?= e(setting('store_closed_msg', 'Kategori ini sedang ditutup oleh admin. Kamu masih bisa melihat detail, tetapi pemesanan belum dibuka.')) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">

        <div class="lg:col-span-3">
            <div class="card overflow-hidden">
                <?php if ($product['images']): ?>
                    <div class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto" tabindex="0" role="group" aria-label="Galeri contoh produk">
                        <?php foreach ($product['images'] as $img): ?>
                            <img src="/assets/img/<?= e($img) ?>" alt="Contoh <?= e($product['name']) ?>"
                                 loading="lazy" width="800" height="450"
                                 class="h-56 w-full shrink-0 snap-center object-cover sm:h-80">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="p-5 sm:p-6">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="chip bg-pink-50 text-pink-800 ring-pink-200 dark:bg-pink-500/15 dark:text-pink-200 dark:ring-pink-400/30">
                            <?= e($product['category_name']) ?>
                        </span>
                        <?php if ($product['badge'] !== ''): ?>
                            <span class="chip <?= badge_classes($product['badge_tone']) ?>"><?= e($product['badge']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr <?= e($accent['grad']) ?> text-sm font-black text-white shadow-md sm:h-14 sm:w-14 sm:text-lg">
                            <?= e($product['icon']) ?>
                        </span>
                        <div class="min-w-0">
                            <h1 class="text-lg font-black leading-tight ink sm:text-2xl"><?= e($product['name']) ?></h1>
                            <p class="mt-1 text-xs font-semibold text-pink-700 dark:text-pink-300 sm:text-sm"><?= e($product['subtext']) ?></p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed ink-muted"><?= e($product['description']) ?></p>

                    <?php if ($product['features']): ?>
                        <h2 class="ink mt-5 mb-2 text-xs font-black uppercase tracking-wider">Yang kamu dapat</h2>
                        <ul class="grid grid-cols-1 gap-1.5 text-xs sm:grid-cols-2">
                            <?php foreach ($product['features'] as $feat): ?>
                                <li class="flex items-start gap-1.5 ink-muted">
                                    <i data-lucide="check-circle-2" class="mt-[1px] h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-300"></i>
                                    <span><?= e($feat) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="card sticky top-4 p-5 sm:p-6">
                <?php if ($product['is_quote']): ?>
                    <p class="ink-muted text-[11px] font-bold uppercase tracking-wide">Estimasi biaya</p>
                    <p class="mt-1 text-lg font-black text-pink-700 dark:text-pink-300">Menyesuaikan kesulitan &amp; deadline</p>
                    <p class="ink-muted mt-2 text-xs leading-relaxed">
                        Harga final disepakati bersama admin via WhatsApp sebelum pengerjaan dimulai.
                    </p>
                    <a href="https://wa.me/<?= e($adminWa) ?>?text=<?= rawurlencode('Halo Admin Bbylaa Store, saya mau konsultasi layanan: ' . $product['name']) ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="btn-primary mt-4 w-full py-3 text-sm">
                        <i data-lucide="message-circle" class="h-4 w-4"></i> Konsultasi via WhatsApp
                    </a>
                <?php else: ?>
                    <form id="buy-form" action="/keranjang/tambah" method="post" class="space-y-3">
                        <?= csrf_field() ?>
                        <div>
                            <label for="variant" class="ink mb-1.5 block text-xs font-bold">
                                <?= $product['category_id'] === 'game' ? 'Pilih nominal' : 'Pilih paket' ?>
                            </label>
                            <select id="variant" name="variant_id" class="field py-2.5 text-sm" <?= $isOpen ? '' : 'disabled' ?>>
                                <?php foreach ($product['variants'] as $i => $v):
                                    $avail = variant_available($v);
                                    $priceText = $v['price_max'] !== null
                                        ? rupiah($v['price']) . ' – ' . rupiah($v['price_max'])
                                        : rupiah($v['price']);
                                    $stockText = $v['stock'] === null ? '' : ($v['stock'] > 0 ? ' • sisa ' . $v['stock'] : ' • HABIS');
                                    ?>
                                    <option value="<?= (int) $v['id'] ?>" <?= $i === 0 ? 'selected' : '' ?> <?= $avail ? '' : 'disabled' ?>>
                                        <?= e($v['label']) ?> — <?= e($priceText . $stockText) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex items-end justify-between gap-3 border-t border-[#f6dbe6] pt-3 dark:border-[#3d2340]">
                            <div>
                                <span class="ink-muted block text-[10px] font-bold uppercase">Harga</span>
                                <span class="text-xl font-black text-pink-700 dark:text-pink-300" data-price>—</span>
                            </div>
                            <div class="flex items-center overflow-hidden rounded-xl border border-[#f0d3e0] dark:border-[#4a2b50]">
                                <button type="button" data-qty-step="-1" class="px-3 py-1.5 text-pink-700 hover:bg-pink-50 dark:text-pink-300 dark:hover:bg-pink-500/10" aria-label="Kurangi">−</button>
                                <input id="buy-qty" type="number" value="1" min="1" max="20" readonly
                                       class="w-12 border-0 bg-transparent text-center text-sm font-bold ink focus:outline-none"
                                       aria-label="Jumlah">
                                <button type="button" data-qty-step="1" class="px-3 py-1.5 text-pink-700 hover:bg-pink-50 dark:text-pink-300 dark:hover:bg-pink-500/10" aria-label="Tambah">+</button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full py-3 text-sm" <?= $isOpen ? '' : 'disabled' ?>>
                            <i data-lucide="shopping-cart" class="h-4 w-4"></i>
                            <?= $isOpen ? 'Tambah ke Keranjang' : 'Kategori Tutup' ?>
                        </button>
                        <a href="https://wa.me/<?= e($adminWa) ?>?text=<?= rawurlencode('Halo Admin Bbylaa Store, saya mau tanya produk: ' . $product['name']) ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="btn-quiet w-full py-2.5 text-xs">
                            <i data-lucide="message-circle" class="h-3.5 w-3.5"></i> Tanya admin dulu
                        </a>
                    </form>
                <?php endif; ?>

                <ul class="ink-muted mt-4 space-y-1.5 border-t border-[#f6dbe6] pt-3 text-[11px] dark:border-[#3d2340]">
                    <li class="flex items-center gap-1.5"><i data-lucide="shield-check" class="h-3.5 w-3.5 text-emerald-700 dark:text-emerald-300"></i> Garansi sesuai SnK toko</li>
                    <li class="flex items-center gap-1.5"><i data-lucide="zap" class="h-3.5 w-3.5 text-amber-700 dark:text-amber-300"></i> Proses cepat setelah pembayaran</li>
                    <li class="flex items-center gap-1.5"><i data-lucide="receipt-text" class="h-3.5 w-3.5 text-sky-700 dark:text-sky-300"></i> Pesanan tercatat &amp; bisa dilacak</li>
                </ul>
            </div>
        </div>
    </div>

    <?php if (!$product['is_quote'] && count($product['variants']) > 1): ?>
        <section class="card mt-6 p-5 sm:p-6">
            <h2 class="ink mb-3 text-sm font-black">Daftar harga</h2>
            <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Daftar harga">
                <table class="w-full min-w-[420px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#f6dbe6] dark:border-[#3d2340]">
                            <th class="table-head py-2 pr-3">Paket</th>
                            <th class="table-head py-2 pr-3">Harga</th>
                            <th class="table-head py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product['variants'] as $v): ?>
                            <tr class="border-b border-[#f6dbe6]/60 last:border-0 dark:border-[#3d2340]/60">
                                <td class="py-2 pr-3 font-semibold ink"><?= e($v['label']) ?></td>
                                <td class="py-2 pr-3 font-black ink">
                                    <?= $v['price_max'] !== null ? rupiah($v['price']) . ' – ' . rupiah($v['price_max']) : rupiah($v['price']) ?>
                                </td>
                                <td class="py-2">
                                    <?php if (variant_available($v)): ?>
                                        <span class="chip bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-200 dark:ring-emerald-400/40">Tersedia</span>
                                    <?php else: ?>
                                        <span class="chip bg-rose-100 text-rose-800 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-200 dark:ring-rose-400/40">Habis</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require BBY_APP . '/views/partials/cart-drawer.php'; ?>

<script>
(function () {
    var form = document.getElementById('buy-form');
    if (!form) return;
    var select = document.getElementById('variant');
    var priceEl = form.querySelector('[data-price]');
    var qtyEl = document.getElementById('buy-qty');

    var prices = <?= json_encode(array_map(static fn(array $v): array => [
        'id' => (int) $v['id'], 'price' => (int) $v['price'], 'max' => $v['price_max'],
    ], $product['variants']), JSON_UNESCAPED_UNICODE) ?>;

    function fmt(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

    function paint() {
        var cur = prices.filter(function (p) { return String(p.id) === select.value; })[0];
        if (!cur) return;
        priceEl.textContent = cur.max !== null ? (fmt(cur.price) + ' – ' + fmt(cur.max)) : fmt(cur.price);
    }
    select.addEventListener('change', paint);
    paint();

    form.querySelectorAll('[data-qty-step]').forEach(function (b) {
        b.addEventListener('click', function () {
            var next = Math.max(1, Math.min(20, (parseInt(qtyEl.value, 10) || 1) + parseInt(b.dataset.qtyStep, 10)));
            qtyEl.value = next;
        });
    });

    form.addEventListener('submit', function (ev) {
        if (!window.addToCart) return; // tanpa JS: biarkan form POST ke /keranjang/tambah
        ev.preventDefault();
        window.addToCart(select.value, <?= json_encode($product['name'], JSON_UNESCAPED_UNICODE) ?>, qtyEl.value);
    });
})();
</script>
