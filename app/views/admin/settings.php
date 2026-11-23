<?php
/**
 * Pengaturan toko + sakelar kategori (buka/tutup).
 * @var array $cats
 */
declare(strict_types=1);

$settings = [
    ['key' => 'store_name',       'label' => 'Nama toko',            'type' => 'text',   'hint' => 'Tampil di judul halaman.'],
    ['key' => 'store_tagline',    'label' => 'Tagline',              'type' => 'text',   'hint' => 'Satu baris di bawah nama.'],
    ['key' => 'admin_whatsapp',   'label' => 'Nomor WhatsApp admin', 'type' => 'tel',    'hint' => 'Format 628xxx. Dipakai semua tombol chat.'],
    ['key' => 'wa_channel_url',   'label' => 'URL saluran WhatsApp', 'type' => 'url',    'hint' => 'Tautan saluran broadcast.'],
    ['key' => 'testi_url',        'label' => 'URL testimoni',        'type' => 'url',    'hint' => 'Tautan bukti testimoni (Telegram).'],
    ['key' => 'marquee_text',     'label' => 'Teks berjalan',        'type' => 'text',   'hint' => 'Pisahkan bagian dengan karakter ~.'],
    ['key' => 'store_closed_msg', 'label' => 'Pesan saat tutup',     'type' => 'text',   'hint' => 'Ditampilkan pada kategori yang ditutup.'],
];

$themeNow = setting('default_theme', 'light');
?>
<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">

    <div class="card p-4 sm:p-5 xl:col-span-2">
        <h2 class="mb-1 text-sm font-black text-[#241520] dark:text-[#f7ecf3]">Informasi toko</h2>
        <p class="mb-3 text-[11px] text-[#6b5563] dark:text-[#c9aec4]">Perubahan langsung berlaku di seluruh halaman.</p>

        <form action="/admin/pengaturan" method="post" class="space-y-2.5">
            <?= csrf_field() ?>
            <?php foreach ($settings as $s): ?>
                <div>
                    <label for="s-<?= e($s['key']) ?>" class="mb-1 block text-[11px] font-bold text-[#6b5563] dark:text-[#c9aec4]"><?= e($s['label']) ?></label>
                    <input id="s-<?= e($s['key']) ?>" name="<?= e($s['key']) ?>" type="<?= e($s['type']) ?>"
                           value="<?= e((string) setting($s['key'], '')) ?>" class="field py-2 text-sm">
                    <p class="mt-0.5 text-[10px] text-[#6f5568] dark:text-[#c6a9bf]"><?= e($s['hint']) ?></p>
                </div>
            <?php endforeach; ?>
            <fieldset>
                <legend class="mb-1 block text-[11px] font-bold text-[#6b5563] dark:text-[#c9aec4]">Tema bawaan untuk pengunjung baru</legend>
                <div class="flex gap-2">
                    <?php foreach (['light' => 'Terang', 'dark' => 'Gelap'] as $val => $lbl): ?>
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 px-3 py-2 text-xs font-bold transition-colors
                                      border-[#f0d3e0] dark:border-[#4a2b50] text-[#5c4354] dark:text-[#dcc4d6] has-[:checked]:border-pink-600 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-500/10 has-[:checked]:text-pink-800 dark:has-[:checked]:text-pink-200">
                            <input type="radio" name="default_theme" value="<?= $val ?>" class="sr-only" <?= $themeNow === $val ? 'checked' : '' ?>>
                            <?= $lbl ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="mt-0.5 text-[10px] text-[#6f5568] dark:text-[#c6a9bf]">Pengunjung tetap bisa mengganti sendiri lewat tombol di header.</p>
            </fieldset>

            <button type="submit" class="btn-primary mt-1 px-4 py-2 text-xs">
                <i data-lucide="save" class="h-3.5 w-3.5"></i> Simpan pengaturan
            </button>
        </form>
    </div>

    <div class="card p-4 sm:p-5">
        <h2 class="mb-1 text-sm font-black text-[#241520] dark:text-[#f7ecf3]">Status kategori</h2>
        <p class="mb-3 text-[11px] text-[#6b5563] dark:text-[#c9aec4]">
            Sakelar di sini mengubah situs untuk SEMUA pengunjung, bukan hanya perangkat ini.
        </p>

        <ul class="space-y-2">
            <?php foreach ($cats as $c):
                $isOpen = (int) $c['is_open'] === 1; ?>
                <li class="flex items-center justify-between gap-3 rounded-2xl border border-[#f6dbe6] dark:border-[#3d2340] p-3">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-[#241520] dark:text-[#f7ecf3]"><?= e($c['emoji'] . ' ' . $c['name']) ?></p>
                        <p class="flex items-center gap-1 text-[10px] font-bold <?= $isOpen ? 'text-emerald-700 dark:text-emerald-300' : 'text-[#6f5568] dark:text-[#c6a9bf]' ?>"
                           data-cat-label="<?= e($c['id']) ?>">
                            <span class="h-1.5 w-1.5 rounded-full <?= $isOpen ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                            <?= $isOpen ? 'Buka' : 'Tutup' ?>
                        </p>
                    </div>
                    <button type="button"
                            role="switch" aria-checked="<?= $isOpen ? 'true' : 'false' ?>"
                            aria-label="Buka atau tutup kategori <?= e($c['name']) ?>"
                            data-cat-toggle="<?= e($c['id']) ?>" data-open="<?= $isOpen ? '1' : '0' ?>"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200
                                   <?= $isOpen ? 'bg-pink-700' : 'bg-slate-300' ?>">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-[#1a1021] shadow transition duration-200
                                     <?= $isOpen ? 'translate-x-5' : 'translate-x-0' ?>"></span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
