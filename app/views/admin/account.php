<?php
/**
 * Akun admin — ganti password.
 * @var array $admin
 */
declare(strict_types=1);
?>
<div class="max-w-lg">
    <div class="card p-4 sm:p-5">
        <h2 class="text-sm font-black text-[#241520]">Ganti password</h2>
        <p class="mb-3 text-[11px] text-[#6b5563]">
            Masuk sebagai <strong><?= e($admin['username']) ?></strong>. Minimal 10 karakter.
        </p>

        <form action="/admin/akun" method="post" class="space-y-2.5" autocomplete="off">
            <?= csrf_field() ?>
            <div>
                <label for="cur" class="mb-1 block text-[11px] font-bold text-[#6b5563]">Password saat ini</label>
                <input id="cur" name="current" type="password" required autocomplete="current-password" class="field py-2 text-sm">
            </div>
            <div>
                <label for="new" class="mb-1 block text-[11px] font-bold text-[#6b5563]">Password baru</label>
                <input id="new" name="new" type="password" required minlength="10" autocomplete="new-password" class="field py-2 text-sm">
            </div>
            <div>
                <label for="conf" class="mb-1 block text-[11px] font-bold text-[#6b5563]">Ulangi password baru</label>
                <input id="conf" name="confirm" type="password" required minlength="10" autocomplete="new-password" class="field py-2 text-sm">
            </div>
            <button type="submit" class="btn-primary w-full py-2 text-xs">
                <i data-lucide="key-round" class="h-3.5 w-3.5"></i> Simpan password
            </button>
        </form>

        <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-[11px] font-semibold text-amber-900 ring-1 ring-amber-200">
            Ganti password bawaan sebelum toko dipakai publik.
        </p>
    </div>
</div>
