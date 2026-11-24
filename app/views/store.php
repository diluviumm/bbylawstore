<?php
/**
 * Halaman katalog.
 *
 * @var array $cats, $products, $openMap, $cart
 * @var string $q, $activeCat, $theme
 */
declare(strict_types=1);

$allOpenCount = 0;
foreach ($openMap as $isOpen) {
    if ($isOpen) {
        $allOpenCount++;
    }
}
$adminWa = setting('admin_whatsapp', '6282318613826');
$storeName = setting('store_name', 'Bbylaa Store');
$storeTagline = setting('store_tagline', 'APK, Joki & Top Up');
$marquee = setting('marquee_text', 'welcome to bbylaa store ~ Happy Shopping, Trusted 100%');
?>

<header class="sticky top-0 z-40 border-b border-[#f6dbe6] bg-white/85 backdrop-blur-md dark:border-[#3d2340] dark:bg-[#140b1a]/90">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-2.5 px-3.5 sm:h-20 sm:gap-4 sm:px-6 lg:px-8">

        <a href="/" class="flex shrink-0 items-center gap-2 sm:gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-2xl
                         ring-2 ring-pink-300 dark:ring-pink-500/50 sm:h-11 sm:w-11">
                <img src="/assets/img/logo-192.jpg" alt="Logo Bbylaa Store" width="44" height="44"
                     class="h-full w-full object-cover" decoding="async">
            </span>
            <span class="leading-none">
                <span class="block text-base font-black tracking-tight ink sm:text-xl">
                    <?php
                    // Nama toko dari pengaturan; kata terakhir diberi warna aksen.
                    $parts = preg_split('/\s+/', trim($storeName)) ?: [$storeName];
                    $last = array_pop($parts);
                    if ($parts) {
                        echo e(implode(' ', $parts)) . ' ';
                    }
                    ?>
                    <span class="text-pink-700 dark:text-pink-400"><?= e($last) ?></span>
                </span>
                <span class="mt-0.5 block text-[8.5px] font-bold uppercase tracking-widest text-pink-700 dark:text-pink-300 sm:text-[10px]"><?= e($storeTagline) ?></span>
            </span>
        </a>

        <form action="/" method="get" role="search" class="relative hidden max-w-sm flex-1 lg:block">
            <label for="q-desktop" class="sr-only">Cari produk</label>
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-pink-700 dark:text-pink-400">
                <i data-lucide="search" class="h-4 w-4"></i>
            </span>
            <input id="q-desktop" type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Cari: ChatGPT, Canva, Roblox…"
                   class="field mt-0 py-2 pl-9 pr-9">
            <?php if ($q !== ''): ?>
                <a href="/" class="absolute inset-y-0 right-0 flex items-center pr-3 text-pink-700 hover:text-pink-900 dark:text-pink-300"
                   aria-label="Hapus pencarian">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </a>
            <?php endif; ?>
        </form>

        <div class="flex items-center gap-1.5 sm:gap-2">
            <a href="<?= e(setting('wa_channel_url', 'https://whatsapp.com/channel/0029VbCfTrxI7BeNl8xXBw1f')) ?>"
               target="_blank" rel="noopener noreferrer"
               class="hidden items-center gap-1.5 rounded-2xl bg-pink-50 px-3 py-2 text-xs font-bold text-pink-800
                      ring-1 ring-pink-200 transition-colors hover:bg-pink-100
                      dark:bg-pink-500/15 dark:text-pink-200 dark:ring-pink-400/30 dark:hover:bg-pink-500/25 xl:inline-flex">
                <i data-lucide="bell" class="h-3.5 w-3.5"></i>
                <span>Saluran WA</span>
            </a>

            <a href="<?= e(setting('testi_url', 'https://t.me/testinabilastore')) ?>" target="_blank" rel="noopener noreferrer"
               class="hidden items-center gap-1.5 rounded-2xl bg-sky-50 px-3 py-2 text-xs font-bold text-sky-800
                      ring-1 ring-sky-200 transition-colors hover:bg-sky-100
                      dark:bg-sky-500/15 dark:text-sky-200 dark:ring-sky-400/30 dark:hover:bg-sky-500/25 md:inline-flex">
                <i data-lucide="star" class="h-3.5 w-3.5 text-amber-500"></i>
                <span>Testimoni</span>
            </a>

            <button type="button" data-cart-open
                    aria-label="Buka keranjang belanja"
                    class="relative inline-flex items-center gap-1.5 rounded-2xl
                           bg-pink-50 px-3 py-2 text-xs font-bold text-pink-800
                           ring-1 ring-pink-200 transition-colors hover:bg-pink-100
                           dark:bg-pink-500/15 dark:text-pink-200 dark:ring-pink-400/30 dark:hover:bg-pink-500/25">
                <i data-lucide="shopping-cart" class="h-4 w-4"></i>
                <span class="hidden sm:inline">Keranjang</span>
                <span data-cart-badge class="<?= ($cart['count'] ?? 0) > 0 ? '' : 'hidden' ?> inline-flex min-w-[18px] items-center justify-center rounded-full bg-pink-600 px-1.5 py-0.5 text-[10px] font-black text-white"><?= (int) ($cart['count'] ?? 0) ?></span>
            </button>

            <a href="https://wa.me/<?= e($adminWa) ?>" target="_blank" rel="noopener noreferrer"
               aria-label="Chat customer service via WhatsApp"
               class="btn-primary px-3.5 py-2 text-xs sm:px-4 sm:text-sm">
                <i data-lucide="message-circle" class="h-4 w-4"></i>
                <span class="hidden sm:inline">Chat CS</span>
            </a>
        </div>
    </div>

    <form action="/" method="get" role="search" class="border-t border-[#f6dbe6] px-3.5 pb-2.5 pt-2 dark:border-[#3d2340] lg:hidden">
        <div class="relative">
            <label for="q-mobile" class="sr-only">Cari produk</label>
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-pink-700 dark:text-pink-400">
                <i data-lucide="search" class="h-4 w-4"></i>
            </span>
            <input id="q-mobile" type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Cari kebutuhanmu (Netflix, Canva, Joki)…"
                   class="field mt-0 py-2 pl-9 pr-9 text-xs">
        </div>
    </form>
</header>

<div class="relative z-10 overflow-hidden border-b border-[#f6dbe6] bg-pink-50/80 py-2 dark:border-[#3d2340] dark:bg-[#1a1021]">
    <div class="marquee-track items-center gap-8 text-[11px] font-extrabold tracking-wide ink-muted sm:text-sm">
        <?php for ($i = 0; $i < 2; $i++): ?>
            <span class="flex items-center gap-3">
                <span class="text-amber-700 dark:text-amber-300">~</span>
                <span class="text-pink-800 dark:text-pink-200"><?= e($marquee) ?></span>
                <span class="text-emerald-700 dark:text-emerald-400">~</span>
            </span>
        <?php endfor; ?>
    </div>
</div>

<main id="main" class="mx-auto w-full max-w-7xl flex-1 px-3.5 py-5 sm:px-6 sm:py-8 lg:px-8">

    <?php if ($q === '' && $activeCat === ''): ?>
    <section class="mx-auto mb-6 max-w-4xl sm:mb-10" aria-label="Banner promosi">
        <div class="relative overflow-hidden rounded-3xl border-2 border-pink-200 bg-white shadow-xl shadow-pink-600/10 dark:border-pink-500/30 dark:bg-[#1a1021]">
            <picture>
                <source srcset="/assets/img/banner-1200.webp" type="image/webp">
                <img src="/assets/img/banner-1200.jpg" alt="Banner Bbylaa Store"
                     width="1200" height="673" fetchpriority="high" decoding="async"
                     class="block h-auto w-full object-contain">
            </picture>
            <div class="absolute inset-x-0 bottom-0 flex flex-wrap items-center gap-1.5 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-2.5 sm:gap-2 sm:p-3.5">
                <span class="chip bg-white/95 text-pink-800 ring-white/50">
                    <i data-lucide="heart" class="h-3 w-3"></i> Official Store
                </span>
                <span class="chip bg-white/95 text-emerald-800 ring-white/50">
                    <i data-lucide="shield-check" class="h-3 w-3"></i> Full Garansi SnK
                </span>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="mb-5 space-y-2.5 sm:mb-7 sm:space-y-3" aria-label="Filter kategori">
        <div class="no-scrollbar flex items-center gap-2 overflow-x-auto pb-1">
            <a href="/<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>"
               class="nav-pill <?= $activeCat === '' ? 'nav-pill-active' : '' ?>">
                <i data-lucide="layout-grid" class="mr-1 inline h-3.5 w-3.5"></i> Semua
            </a>
            <?php foreach ($cats as $c):
                $isOpen = (int) $c['is_open'] === 1;
                $href = '/?kategori=' . urlencode($c['id']) . ($q !== '' ? '&q=' . urlencode($q) : '');
                ?>
                <?php if ($isOpen): ?>
                    <a href="<?= e($href) ?>"
                       class="nav-pill <?= $activeCat === $c['id'] ? 'nav-pill-active' : '' ?>">
                        <span aria-hidden="true"><?= e($c['emoji']) ?></span>
                        <?= e($c['name']) ?>
                    </a>
                <?php else: ?>
                    <span class="nav-pill nav-pill-closed" aria-disabled="true" title="Kategori sedang tutup">
                        <span aria-hidden="true"><?= e($c['emoji']) ?></span>
                        <?= e($c['name']) ?>
                        <span class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 text-[9px] font-black text-rose-900 dark:bg-rose-500/20 dark:text-rose-100">TUTUP</span>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($q !== '' || $activeCat !== ''): ?>
            <p class="ink-muted text-xs font-semibold">
                <?= count($products) ?> layanan ditemukan<?= $q !== '' ? ' untuk “' . e($q) . '”' : '' ?>.
                <a href="/" class="ml-1 font-bold text-pink-700 underline hover:text-pink-900 dark:text-pink-300">Reset</a>
            </p>
        <?php endif; ?>
    </section>

    <?php if (!$products): ?>
        <div class="card px-4 py-16 text-center">
            <i data-lucide="package-search" class="mx-auto mb-3 h-12 w-12 text-pink-500 dark:text-pink-400"></i>
            <h2 class="text-base font-bold ink">Layanan tidak ditemukan</h2>
            <p class="ink-muted mt-1 text-sm">Coba kata kunci lain atau lihat semua kategori.</p>
            <a href="/" class="btn-primary mt-4 px-4 py-2 text-sm">Lihat semua</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($products as $p):
                $isOpen = $openMap[$p['category_id']] ?? false;
                $accent = accent_classes($p['accent']);
                $available = array_values(array_filter($p['variants'], 'variant_available'));
                $first = $available[0] ?? $p['variants'][0];
                $buyLabel = $p['category_id'] === 'game' ? 'Top Up' : ($p['category_id'] === 'handmade' ? 'Custom' : 'Beli');
                $packLabel = $p['category_id'] === 'game' ? 'Pilih Nominal Top Up:' : ($p['category_id'] === 'handmade' ? 'Pilih Paket Custom:' : 'Pilih Paket / Durasi:');
                $priceText = static fn(array $v): string => $v['price_max'] !== null
                    ? rupiah($v['price']) . ' – ' . rupiah($v['price_max']) : rupiah($v['price']);
                ?>
                <article class="card store-card flex flex-col p-4 transition-transform duration-200 <?= $isOpen ? 'hover:-translate-y-1' : '' ?> sm:p-5"
                         data-product-card="<?= (int) $p['id'] ?>">
                    <div class="relative z-10 mb-2 flex items-start justify-between gap-1.5 sm:mb-3">
                        <span class="chip bg-pink-950/85 text-pink-200 ring-pink-500/40 truncate">
                            <?= e($p['category_name']) ?>
                        </span>
                        <?php if (!$isOpen): ?>
                            <span class="chip bg-rose-500/20 text-rose-100 ring-rose-400/40">Tutup</span>
                        <?php elseif ($p['badge'] !== ''): ?>
                            <span class="chip <?= badge_classes($p['badge_tone']) ?> truncate"><?= e($p['badge']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="relative z-10 mb-3 flex items-start gap-2.5 sm:gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr <?= e($accent['grad']) ?>
                                     text-xs font-black text-white shadow-md ring-1 ring-white/30 sm:h-12 sm:w-12 sm:text-base">
                            <?= e($p['icon']) ?>
                        </span>
                        <div class="min-w-0">
                            <h3 class="line-clamp-2-safe text-sm font-extrabold leading-tight text-white drop-shadow sm:text-base">
                                <?= e($p['name']) ?>
                            </h3>
                            <p class="mt-0.5 truncate text-[10.5px] font-semibold text-pink-300 sm:text-[11.5px]">
                                <?= e($p['subtext']) ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($p['features']): ?>
                        <ul class="relative z-10 mb-3 space-y-1 text-[11px] font-medium text-slate-200 sm:text-xs">
                            <?php foreach (array_slice($p['features'], 0, 3) as $feat): ?>
                                <li class="flex items-start gap-1.5">
                                    <i data-lucide="check-circle-2" class="mt-[1px] h-3.5 w-3.5 shrink-0 text-emerald-300"></i>
                                    <span class="line-clamp-1"><?= e($feat) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="relative z-10 mt-auto border-t border-pink-500/20 pt-3">
                        <?php if ($p['is_quote']): ?>
                            <div class="mb-2 flex items-center justify-between rounded-2xl bg-pink-950/40 px-2.5 py-2 text-[11px] ring-1 ring-pink-500/25">
                                <span class="text-slate-300 font-medium">Biaya pengerjaan</span>
                                <span class="font-bold text-pink-200">Sesuai Kesepakatan</span>
                            </div>
                            <a href="https://wa.me/<?= e($adminWa) ?>?text=<?= rawurlencode('Halo Admin Bbylaa Store, saya mau konsultasi tugas: ' . $p['name']) ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="btn-primary w-full py-2.5 text-xs">
                                <i data-lucide="message-circle" class="h-4 w-4"></i> Konsultasi &amp; Order
                            </a>
                        <?php elseif (!$available): ?>
                            <p class="py-2 text-center text-xs font-bold text-rose-200">Stok habis</p>
                        <?php else: ?>
                            <label class="mb-1 block text-[10px] font-bold text-pink-200/80 sm:text-[11px]"
                                   for="variant-<?= (int) $p['id'] ?>"><?= e($packLabel) ?></label>
                            <select id="variant-<?= (int) $p['id'] ?>" data-variant-select
                                    class="field py-1.5 pr-2 text-[11px] sm:py-2 sm:text-xs"
                                    <?= $isOpen ? '' : 'disabled' ?>
                                    aria-label="Pilih paket <?= e($p['name']) ?>">
                                <?php foreach ($p['variants'] as $v): ?>
                                    <option value="<?= (int) $v['id'] ?>"
                                            data-price="<?= (int) $v['price'] ?>"
                                            data-price-max="<?= $v['price_max'] === null ? '' : (int) $v['price_max'] ?>"
                                            <?= variant_available($v) ? '' : 'disabled' ?>
                                            <?= $v['id'] === $first['id'] ? 'selected' : '' ?>>
                                        <?= e($v['label']) ?> — <?= e($priceText($v)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="mt-3 flex items-end justify-between gap-2">
                                <div class="min-w-0 pr-1">
                                    <span class="block text-[9px] font-extrabold uppercase tracking-wide text-slate-300">Harga</span>
                                    <span class="block break-words text-sm font-black text-pink-300 sm:text-base"
                                          data-price-display><?= e($priceText($first)) ?></span>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <form action="/keranjang/tambah" method="post" data-buy-form>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="variant_id" value="<?= (int) $first['id'] ?>" data-card-variant>
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit"
                                                class="flex h-9 w-9 items-center justify-center rounded-2xl bg-pink-950/70 text-pink-200 ring-1 ring-pink-500/40 transition hover:bg-pink-900/90 disabled:cursor-not-allowed disabled:opacity-50 sm:h-10 sm:w-10"
                                                <?= $isOpen ? '' : 'disabled' ?>
                                                aria-label="Tambah <?= e($p['name']) ?> ke keranjang">
                                            <i data-lucide="shopping-cart" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                    <form action="/keranjang/tambah" method="post" data-buy-now>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="variant_id" value="<?= (int) $first['id'] ?>" data-card-variant>
                                        <input type="hidden" name="qty" value="1">
                                        <input type="hidden" name="direct" value="1">
                                        <button type="submit"
                                                class="btn-primary px-3 py-2 text-[11px] sm:px-3.5 sm:text-xs"
                                                <?= $isOpen ? '' : 'disabled' ?>
                                                aria-label="<?= e($buyLabel) ?> <?= e($p['name']) ?>">
                                            <i data-lucide="zap" class="h-3.5 w-3.5"></i> <?= e($buyLabel) ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="mt-12 border-t border-[#f6dbe6] pt-10 dark:border-[#3d2340]" aria-label="Keunggulan">
        <div class="mx-auto mb-7 max-w-2xl text-center">
            <h2 class="text-xl font-black ink sm:text-2xl">Kenapa belanja di Bbylaa Store?</h2>
            <p class="ink-muted mt-1 text-xs sm:text-sm">Layanan ramah, respon kilat, terbukti amanah.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 sm:gap-6">
            <?php
            $perks = [
                ['award', 'sky', '3.000+ Testimoni', 'Bukti kepuasan pelanggan bisa dicek di saluran Telegram kami.'],
                ['rotate-ccw', 'pink', 'Full Garansi SnK', 'Garansi replace untuk akun aplikasi, revisi tuntas untuk tugas.'],
                ['zap', 'amber', 'Proses Cepat', 'Top up via ID resmi tanpa sandi, handmade rapi & estetik.'],
            ];
            foreach ($perks as [$icon, $tone, $head, $body]):
                $box = [
                    'sky' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
                    'pink' => 'bg-pink-100 text-pink-800 dark:bg-pink-500/20 dark:text-pink-200',
                    'amber' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200',
                ][$tone];
                ?>
                <div class="card flex items-start gap-4 p-5 sm:p-6">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl <?= $box ?>">
                        <i data-lucide="<?= e($icon) ?>" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-extrabold ink"><?= e($head) ?></h3>
                        <p class="mt-0.5 text-xs leading-relaxed ink-muted"><?= e($body) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php require BBY_APP . '/views/partials/cart-drawer.php'; ?>
