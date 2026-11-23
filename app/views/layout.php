<?php
/**
 * Layout publik. $content diisi oleh layout().
 *
 * Tema: gelap permanen. Latar halaman diatur admin lewat /admin/background
 * dan disimpan di riwayat; tidak ada lagi pilihan tema terang.
 */

declare(strict_types=1);

$storeName = setting('store_name', 'Bbylaa Store');
$title = $title ?? $storeName;
$desc  = $desc ?? 'Bbylaa Store — APK premium, joki tugas, top up game, dan produk handmade. Proses cepat, full garansi.';
$theme = 'dark';
$canonical = $canonical ?? (base_url() . ($_SERVER['REQUEST_URI'] ?? '/'));
$adminWhatsapp = setting('admin_whatsapp', '6282318613826');
$flashes = take_flashes();
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> — <?= e($storeName) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="theme-color" content="#0f0813">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($title) ?> — <?= e($storeName) ?>">
    <meta property="og:description" content="<?= e($desc) ?>">
    <meta property="og:image" content="<?= e(base_url()) ?>/assets/img/banner-1200.jpg">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/jpeg" href="/assets/img/logo-192.jpg">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= BBY_VERSION ?>">
    <script src="/assets/vendor/lucide.min.js" defer></script>
</head>
<body class="min-h-screen flex flex-col antialiased"
      style="background-image: <?= e(background_css()) ?>;">

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100]
                       focus:rounded-xl focus:bg-pink-600 focus:px-4 focus:py-2 focus:text-white focus:font-bold">
    Lompat ke konten
</a>

<?php if ($flashes): ?>
    <div class="fixed inset-x-3 top-3 z-[80] flex flex-col items-center gap-2 pointer-events-none sm:inset-x-auto sm:right-4 sm:items-end">
        <?php foreach ($flashes as $f):
            $isErr = ($f['type'] ?? '') === 'error'; ?>
            <div role="<?= $isErr ? 'alert' : 'status' ?>"
                 class="pointer-events-auto w-full max-w-md rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl ring-1
                        <?= $isErr
                            ? 'bg-rose-50 text-rose-900 ring-rose-300 dark:bg-rose-500/15 dark:text-rose-100 dark:ring-rose-400/40'
                            : 'bg-emerald-50 text-emerald-900 ring-emerald-300 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-400/40' ?>">
                <?= e((string) ($f['message'] ?? '')) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $content ?>

<footer class="mt-auto border-t border-[#f6dbe6] dark:border-[#3d2340] bg-white/70 dark:bg-[#140b1a]/80 backdrop-blur">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 text-xs sm:flex-row sm:px-6 lg:px-8">
        <p class="ink-muted font-semibold">
            <span class="ink font-black"><?= e($storeName) ?></span> &copy; <?= date('Y') ?> — Semua hak dilindungi.
        </p>
        <nav class="flex flex-wrap items-center justify-center gap-2" aria-label="Tautan footer">
            <a href="/lacak" class="btn-quiet px-3 py-1.5 text-[11px]">
                <i data-lucide="package-search" class="h-3.5 w-3.5"></i> Lacak Pesanan
            </a>
            <a href="<?= e(setting('wa_channel_url', 'https://whatsapp.com/channel/0029VbCfTrxI7BeNl8xXBw1f')) ?>"
               target="_blank" rel="noopener noreferrer" class="btn-quiet px-3 py-1.5 text-[11px]">
                <i data-lucide="bell" class="h-3.5 w-3.5"></i> Saluran WA
            </a>
            <a href="https://wa.me/<?= e($adminWhatsapp) ?>" target="_blank" rel="noopener noreferrer"
               class="btn-quiet px-3 py-1.5 text-[11px]">
                <i data-lucide="message-circle" class="h-3.5 w-3.5"></i> Chat Admin
            </a>
            <a href="/admin/login" class="btn-quiet px-3 py-1.5 text-[11px]">
                <i data-lucide="lock" class="h-3.5 w-3.5"></i> Admin
            </a>
        </nav>
    </div>
</footer>

<div id="toast" role="status" aria-live="polite"
     class="pointer-events-none fixed bottom-24 left-1/2 z-[90] hidden -translate-x-1/2 rounded-full
            bg-[#241520] px-4 py-2.5 text-xs font-bold text-white shadow-2xl
            ring-1 ring-white/20 dark:bg-white dark:text-[#241520] sm:bottom-8"></div>

<script src="/assets/js/store.js?v=<?= BBY_VERSION ?>" defer></script>
</body>
</html>
