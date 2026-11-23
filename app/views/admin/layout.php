<?php
/**
 * Layout admin — sidebar ringkas + konten.
 */
declare(strict_types=1);

$title = $title ?? 'Admin';
$nav = $nav ?? '';
$admin = $admin ?? ['username' => ''];
$flashes = take_flashes();
$theme = $theme ?? 'light';

$navItems = [
    ['key' => 'orders',   'href' => '/admin/pesanan',  'icon' => 'receipt-text', 'label' => 'Pesanan'],
    ['key' => 'products', 'href' => '/admin/produk',   'icon' => 'package',     'label' => 'Produk & Stok'],
    ['key' => 'settings', 'href' => '/admin/pengaturan', 'icon' => 'settings',  'label' => 'Pengaturan'],
    ['key' => 'account',  'href' => '/admin/akun',     'icon' => 'user-round',  'label' => 'Akun'],
];

// Menu kategori ada di halaman Pengaturan; juga tampil sebagai pintasan di sini.
$openCount = 0;
foreach (categories_all() as $c) {
    if ((int) $c['is_open'] === 1) {
        $openCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — Admin Bbylaa Store</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="<?= $theme === 'dark' ? '#0f0813' : '#fdf6f9' ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" type="image/jpeg" href="/assets/img/logo-192.jpg">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= BBY_VERSION ?>">
    <script src="/assets/vendor/lucide.min.js" defer></script>
</head>
<body class="min-h-screen bg-[#fdf6f9] dark:bg-[#140b1a] text-[#241520] dark:text-[#f7ecf3] antialiased">

<a href="#admin-main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100]
                            focus:rounded-xl focus:bg-pink-700 focus:px-4 focus:py-2 focus:text-white focus:font-bold">
    Lompat ke konten
</a>

<?php if ($flashes): ?>
    <div class="fixed inset-x-3 top-3 z-[80] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-4 sm:items-end">
        <?php foreach ($flashes as $f):
            $isErr = ($f['type'] ?? '') === 'error'; ?>
            <div role="<?= $isErr ? 'alert' : 'status' ?>"
                 class="w-full max-w-md rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl ring-1
                        <?= $isErr ? 'bg-rose-50 dark:bg-rose-500/15 text-rose-900 dark:text-rose-100 ring-rose-300 dark:ring-rose-400/40' : 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-900 dark:text-emerald-100 ring-emerald-300 dark:ring-emerald-400/40' ?>">
                <?= e((string) ($f['message'] ?? '')) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="mx-auto flex min-h-screen max-w-7xl flex-col lg:flex-row">

    <aside class="bg-white dark:bg-[#1a1021] border-b border-[#f6dbe6] dark:border-[#3d2340] lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between gap-3 p-4 lg:block">
            <a href="/admin/pesanan" class="flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl ring-2 ring-pink-300">
                    <img src="/assets/img/logo-192.jpg" alt="" width="40" height="40" class="h-full w-full object-cover">
                </span>
                <span class="leading-none">
                    <span class="block text-sm font-black text-[#241520] dark:text-[#f7ecf3]">BBYLAA <span class="text-pink-700 dark:text-pink-300">ADMIN</span></span>
                    <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-widest text-pink-700 dark:text-pink-300">Panel Toko</span>
                </span>
            </a>
            <a href="/" class="rounded-xl px-2.5 py-1.5 text-[11px] font-bold text-pink-800 dark:text-pink-200 ring-1 ring-pink-200 hover:bg-pink-50 dark:hover:bg-pink-500/10 lg:hidden">
                Lihat toko
            </a>
        </div>

        <nav class="no-scrollbar flex gap-1 overflow-x-auto px-3 pb-3 lg:mt-2 lg:flex-col lg:gap-0.5 lg:overflow-visible lg:px-3 lg:pb-4"
             aria-label="Navigasi admin">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e($item['href']) ?>"
                   class="flex items-center gap-2 whitespace-nowrap rounded-2xl px-3 py-2 text-xs font-bold transition-colors
                          <?= $nav === $item['key']
                              ? 'bg-pink-700 text-white shadow-sm'
                              : 'text-[#5c4354] dark:text-[#dcc4d6] hover:bg-pink-50 dark:hover:bg-pink-500/10 hover:text-pink-900' ?>">
                    <i data-lucide="<?= e($item['icon']) ?>" class="h-4 w-4"></i>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="hidden border-t border-[#f6dbe6] dark:border-[#3d2340] p-3 lg:block">
            <div class="rounded-2xl bg-pink-50/60 dark:bg-pink-500/10 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-pink-700 dark:text-pink-300">Kategori terbuka</p>
                <p class="mt-0.5 text-lg font-black text-[#241520] dark:text-[#f7ecf3]"><?= $openCount ?> <span class="text-xs font-semibold text-[#6b5563] dark:text-[#c9aec4]">dari <?= count(categories_all()) ?></span></p>
                <a href="/admin/pengaturan" class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-bold text-pink-800 dark:text-pink-200 underline">
                    Kelola <i data-lucide="arrow-right" class="h-3 w-3"></i>
                </a>
            </div>
            <p class="mt-3 px-1 text-[10px] text-[#6f5568] dark:text-[#c6a9bf]">Masuk sebagai <strong><?= e($admin['username'] ?? '') ?></strong></p>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-[#f6dbe6] dark:border-[#3d2340] bg-[#fdf6f9]/90 dark:bg-[#140b1a]/90 px-4 py-3 backdrop-blur">
            <h1 class="text-base font-black text-[#241520] dark:text-[#f7ecf3] sm:text-lg"><?= e($title) ?></h1>
            <div class="flex items-center gap-2">
                <button type="button" data-theme-toggle
                        aria-label="<?= $theme === 'dark' ? 'Aktifkan tema terang' : 'Aktifkan tema gelap' ?>"
                        aria-pressed="<?= $theme === 'dark' ? 'true' : 'false' ?>"
                        title="<?= $theme === 'dark' ? 'Aktifkan tema terang' : 'Aktifkan tema gelap' ?>"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white dark:bg-[#1a1021]
                               text-[#5c4354] dark:text-[#dcc4d6] ring-1 ring-[#f0d3e0] dark:ring-[#4a2b50]
                               hover:bg-pink-50 dark:hover:bg-pink-500/10">
                    <i data-lucide="sun" class="h-4 w-4 dark:hidden"></i>
                    <i data-lucide="moon" class="hidden h-4 w-4 dark:block"></i>
                </button>
                <a href="/" target="_blank" rel="noopener"
                   class="hidden items-center gap-1.5 rounded-full bg-white dark:bg-[#1a1021] px-3 py-1.5 text-[11px] font-bold text-[#5c4354] dark:text-[#dcc4d6] ring-1 ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10 sm:inline-flex">
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i> Lihat toko
                </a>
                <form action="/admin/logout" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-white dark:bg-[#1a1021] px-3 py-1.5 text-[11px] font-bold text-[#5c4354] dark:text-[#dcc4d6] ring-1 ring-[#f0d3e0] dark:ring-[#4a2b50] hover:bg-pink-50 dark:hover:bg-pink-500/10">
                        <i data-lucide="log-out" class="h-3.5 w-3.5"></i> Keluar
                    </button>
                </form>
            </div>
        </header>

        <main id="admin-main" class="flex-1 p-4 sm:p-6">
            <?= $content ?>
        </main>
    </div>
</div>

<div id="toast" role="status" aria-live="polite"
     class="pointer-events-none fixed bottom-6 left-1/2 z-[90] hidden -translate-x-1/2 rounded-full
            bg-[#241520] px-4 py-2.5 text-xs font-bold text-white shadow-2xl"></div>

<script src="/assets/js/store.js?v=<?= BBY_VERSION ?>" defer></script>
<script src="/assets/js/admin.js?v=<?= BBY_VERSION ?>" defer></script>
</body>
</html>
