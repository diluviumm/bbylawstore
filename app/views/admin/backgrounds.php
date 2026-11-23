<?php
/**
 * Riwayat background toko (tampilan user).
 * Upload tidak langsung mengganti background aktif — admin memilih "Pakai".
 * @var array $bgs
 */
declare(strict_types=1);
?>
<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="card p-4 sm:p-5 xl:col-span-1">
        <h2 class="mb-1 text-sm font-black ink">Unggah background baru</h2>
        <p class="mb-3 text-[11px] ink-muted">
            JPG/PNG/WebP, maksimal 5 MB. Gambar masuk ke riwayat dulu — tidak langsung dipakai.
        </p>
        <form action="/admin/background/upload" method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label for="bg_file" class="mb-1 block text-[11px] font-bold ink-muted">File gambar</label>
                <input id="bg_file" name="bg_file" type="file" accept="image/jpeg,image/png,image/webp"
                       required class="field py-2 text-xs">
            </div>
            <div>
                <label for="bg_label" class="mb-1 block text-[11px] font-bold ink-muted">Nama/nama file (opsional)</label>
                <input id="bg_label" name="bg_label" type="text" maxlength="80"
                       placeholder="Misal: ungu kawaii" class="field py-2 text-sm">
            </div>
            <button type="submit" class="btn-primary px-4 py-2 text-xs">
                <i data-lucide="upload" class="h-3.5 w-3.5"></i> Simpan ke riwayat
            </button>
        </form>
    </div>

    <div class="card p-4 sm:p-5 xl:col-span-2">
        <h2 class="mb-1 text-sm font-black ink">Riwayat background</h2>
        <p class="mb-3 text-[11px] ink-muted">
            Klik <strong class="ink">Pakai</strong> untuk mengaktifkan. Gambar lama tetap tersimpan,
            jadi bisa dipakai lagi kapan saja tanpa mencari ulang.
        </p>

        <?php if (!$bgs): ?>
            <p class="rounded-2xl bg-pink-500/10 p-4 text-xs ink-muted">Belum ada background.</p>
        <?php else: ?>
            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($bgs as $bg):
                    $active = (int) $bg['is_active'] === 1;
                    $file = '/assets/img/backgrounds/' . rawurlencode((string) $bg['filename']);
                ?>
                    <li class="rounded-2xl border border-[#f6dbe6] p-2.5 dark:border-[#3d2340] <?= $active ? 'ring-2 ring-pink-500' : '' ?>">
                        <div class="relative mb-2 h-24 overflow-hidden rounded-xl bg-[#241531]">
                            <img src="<?= e($file) ?>" alt="Pratinjau <?= e((string) $bg['original_name']) ?>"
                                 loading="lazy" class="h-full w-full object-cover">
                            <?php if ($active): ?>
                                <span class="absolute left-1.5 top-1.5 rounded-full bg-pink-700 px-2 py-0.5 text-[9px] font-black text-white">
                                    Sedang dipakai
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="truncate text-[11px] font-bold ink" title="<?= e((string) $bg['original_name']) ?>">
                            <?= e((string) $bg['original_name']) ?>
                        </p>
                        <p class="mt-0.5 text-[10px] ink-muted">
                            <?= e((string) $bg['created_at']) ?> · <?= round((int) $bg['size'] / 1024) ?> KB
                        </p>
                        <div class="mt-2 flex gap-1.5">
                            <?php if (!$active): ?>
                                <form action="/admin/background/aktif" method="post" class="flex-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="background_id" value="<?= (int) $bg['id'] ?>">
                                    <button type="submit" class="btn-quiet w-full px-2 py-1.5 text-[10px]">Pakai</button>
                                </form>
                                <form action="/admin/background/hapus" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="background_id" value="<?= (int) $bg['id'] ?>">
                                    <button type="submit" class="btn-quiet w-full px-2 py-1.5 text-[10px]"
                                            aria-label="Hapus background <?= e((string) $bg['original_name']) ?>">Hapus</button>
                                </form>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-emerald-300">Aktif</span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
