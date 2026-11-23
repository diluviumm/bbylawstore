<?php
/**
 * Halaman masuk admin.
 */
declare(strict_types=1);
?>
<main id="main" class="relative flex min-h-screen items-center justify-center px-4 py-10">
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
