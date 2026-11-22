<?php
/** @var string $title */
declare(strict_types=1);
?>
<header class="border-b border-[#f6dbe6] bg-white/85 backdrop-blur-md dark:border-[#3d2340] dark:bg-[#140b1a]/90">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-3 px-3.5 sm:h-20 sm:px-6">
        <a href="/" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-2xl ring-2 ring-pink-300 dark:ring-pink-500/50">
                <img src="/assets/img/logo-192.jpg" alt="" width="36" height="36" class="h-full w-full object-cover">
            </span>
            <span class="text-sm font-black ink"><?= e(setting('store_name', 'Bbylaa Store')) ?></span>
        </a>
        <a href="/" class="btn-quiet px-3 py-1.5 text-xs"><i data-lucide="home" class="h-3.5 w-3.5"></i> Beranda</a>
    </div>
</header>
<main id="main" class="mx-auto flex w-full max-w-lg flex-1 flex-col items-center justify-center px-4 py-20 text-center">
    <i data-lucide="compass" class="mb-3 h-14 w-14 text-pink-500"></i>
    <h1 class="text-2xl font-black ink">404 — Halaman tidak ditemukan</h1>
    <p class="ink-muted mt-2 text-sm">Alamat yang kamu buka tidak ada atau sudah dipindahkan.</p>
    <a href="/" class="btn-primary mt-5 px-5 py-2.5 text-sm">Buka katalog</a>
</main>
