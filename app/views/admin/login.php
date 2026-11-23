<?php
/**
 * Halaman masuk admin.
 */
declare(strict_types=1);
?>
<main id="main" class="relative flex min-h-screen items-center justify-center px-4 py-10">
    <button type="button" data-theme-toggle
            aria-label="<?= ($theme ?? 'light') === 'dark' ? 'Aktifkan tema terang' : 'Aktifkan tema gelap' ?>"
            aria-pressed="<?= ($theme ?? 'light') === 'dark' ? 'true' : 'false' ?>"
            title="<?= ($theme ?? 'light') === 'dark' ? 'Aktifkan tema terang' : 'Aktifkan tema gelap' ?>"
            class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-2xl
                   bg-white text-[#5c4354] ring-1 ring-[#f0d3e0] hover:bg-pink-50
                   dark:bg-[#241531] dark:text-[#e9d6e4] dark:ring-[#4a2b50] dark:hover:bg-[#2d1a3b]">
        <i data-lucide="sun" class="h-4 w-4 dark:hidden"></i>
        <i data-lucide="moon" class="hidden h-4 w-4 dark:block"></i>
    </button>
    <div class="w-full max-w-sm">
        <div class="card p-6 sm:p-8">
            <div class="mb-5 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl ring-2 ring-pink-300">
                    <img src="/assets/img/logo-192.jpg" alt="Logo Bbylaa Store" width="56" height="56" class="h-full w-full object-cover">
                </span>
                <h1 class="text-lg font-black ink">Masuk Admin</h1>
                <p class="ink-muted mt-1 text-xs">Panel pengelolaan Bbylaa Store.</p>
            </div>

            <form action="/admin/login" method="post" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label for="username" class="ink mb-1 block text-xs font-bold">Username</label>
                    <input id="username" name="username" type="text" required autocomplete="username"
                           class="field py-2.5 text-sm" placeholder="admin">
                </div>
                <div>
                    <label for="password" class="ink mb-1 block text-xs font-bold">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="field py-2.5 text-sm" placeholder="••••••••••">
                </div>
                <button type="submit" class="btn-primary w-full py-2.5 text-sm">
                    <i data-lucide="log-in" class="h-4 w-4"></i> Masuk
                </button>
            </form>

            <p class="ink-muted mt-4 text-center text-[11px]">
                Lupa password? Ganti lewat server:
                <code class="rounded bg-pink-50 px-1.5 py-0.5 text-[10px] text-pink-800 dark:bg-pink-500/15 dark:text-pink-200">php bin/passwd.php</code>
            </p>
        </div>

        <p class="mt-4 text-center text-[11px] ink-muted">
            <a href="/" class="font-bold text-pink-700 dark:text-pink-300 underline dark:text-pink-300">← Kembali ke toko</a>
        </p>
    </div>
</main>
