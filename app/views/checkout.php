<?php
/**
 * Checkout server-rendered — jalur lengkap untuk pengunjung tanpa JavaScript.
 * Memakai handler /api/checkout yang sama dengan jalur AJAX, hanya balasannya
 * berupa redirect (lihat checkout_respond()).
 *
 * @var array $cart
 * @var bool $hasGame
 * @var string $gameLabel, $gamePlaceholder
 */
declare(strict_types=1);

$payments = array_values(array_filter(array_map('trim', explode(',', (string) setting('payments', 'QRIS,DANA / E-Wallet,Transfer Bank')))));
$paymentLabels = ['QRIS' => 'QRIS', 'DANA / E-Wallet' => 'E-Wallet', 'Transfer Bank' => 'Transfer'];
$flashes = take_flashes();
?>

<main id="main" class="mx-auto w-full max-w-3xl flex-1 px-3.5 py-8 sm:px-6 sm:py-12">

    <a href="/keranjang" class="mb-5 inline-flex items-center gap-2 text-sm font-bold text-pink-800 hover:text-pink-950 dark:text-pink-200">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke keranjang
    </a>

    <h1 class="text-xl font-black ink sm:text-2xl">Konfirmasi pesanan</h1>
    <p class="ink-muted mt-1 text-sm">
        Isi data di bawah lalu tekan <strong class="ink">Buat Pesanan</strong>. Data tersimpan di sistem sebelum kamu diarahkan ke WhatsApp.
    </p>

    <?php if ($flashes): ?>
        <?php foreach ($flashes as $f): ?>
            <?php $isErr = ($f['type'] ?? '') === 'error'; ?>
            <p class="mt-4 rounded-2xl px-4 py-3 text-sm font-semibold ring-1
                      <?= $isErr ? 'bg-rose-50 text-rose-900 ring-rose-300 dark:bg-rose-500/15 dark:text-rose-100 dark:ring-rose-400/40'
                                 : 'bg-emerald-50 text-emerald-900 ring-emerald-300 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-400/40' ?>">
                <?= e((string) ($f['message'] ?? '')) ?>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-5">
        <form action="/api/checkout" method="post" class="card space-y-3 p-4 sm:p-6 lg:col-span-3" novalidate>
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                <div>
                    <label for="ck-name" class="ink mb-1 block text-xs font-bold">Nama pemesan <span class="text-rose-700 dark:text-rose-300" aria-hidden="true">*</span><span class="sr-only">(wajib)</span></label>
                    <input id="ck-name" name="name" type="text" required maxlength="80" autocomplete="name"
                           class="field py-2.5 text-sm" placeholder="Nama kamu">
                </div>
                <div>
                    <label for="ck-phone" class="ink mb-1 block text-xs font-bold">Nomor WhatsApp <span class="text-rose-700 dark:text-rose-300" aria-hidden="true">*</span><span class="sr-only">(wajib)</span></label>
                    <input id="ck-phone" name="phone" type="tel" required inputmode="tel" autocomplete="tel" maxlength="20"
                           class="field py-2.5 text-sm" placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <?php if ($hasGame): ?>
                <div>
                    <label for="ck-game" data-game-label class="ink mb-1 block text-xs font-bold"><?= e($gameLabel) ?> <span class="text-rose-700 dark:text-rose-300" aria-hidden="true">*</span><span class="sr-only">(wajib)</span></label>
                    <input id="ck-game" name="game_id" type="text" maxlength="120"
                           data-game-input class="field py-2.5 text-sm" placeholder="<?= e($gamePlaceholder) ?>">
                </div>
            <?php endif; ?>

            <fieldset>
                <legend class="ink mb-1.5 block text-xs font-bold">Metode pembayaran</legend>
                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-3">
                    <?php foreach ($payments as $i => $pay): ?>
                        <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 px-3 py-2.5 text-xs font-bold transition-colors
                                      border-[#f0d3e0] text-[#5c4354] has-[:checked]:border-pink-600 has-[:checked]:bg-pink-50 has-[:checked]:text-pink-800
                                      dark:border-[#4a2b50] dark:text-[#dcc4d6] dark:has-[:checked]:border-pink-400 dark:has-[:checked]:bg-pink-500/15 dark:has-[:checked]:text-pink-200">
                            <input type="radio" name="payment" value="<?= e($pay) ?>" class="sr-only" <?= $i === 0 ? 'checked' : '' ?>>
                            <span class="truncate"><?= e($paymentLabels[$pay] ?? $pay) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div>
                <label for="ck-note" class="ink mb-1 block text-xs font-bold">Catatan tambahan (opsional)</label>
                <textarea id="ck-note" name="note" rows="3" maxlength="400"
                          class="field py-2.5 text-sm" placeholder="Contoh: email invite pribadi / ID game tambahan"></textarea>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-sm">
                <i data-lucide="send" class="h-4 w-4"></i> Buat Pesanan
            </button>
            <p class="ink-muted text-center text-[11px] leading-relaxed">
                Setelah pesanan tersimpan, halaman akan menampilkan kode pesanan dan tombol untuk melanjutkan ke WhatsApp admin.
            </p>
        </form>

        <aside class="card h-fit p-4 sm:p-5 lg:col-span-2">
            <h2 class="ink mb-3 text-xs font-black uppercase tracking-wider">Ringkasan pesanan</h2>
            <ul class="divide-y divide-[#f6dbe6] dark:divide-[#3d2340]">
                <?php foreach ($cart['lines'] as $l): ?>
                    <li class="flex items-start justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-xs font-bold ink"><?= e($l['name']) ?></p>
                            <p class="ink-muted text-[11px]"><?= e($l['variant_label']) ?> • <?= (int) $l['qty'] ?>x</p>
                        </div>
                        <span class="shrink-0 text-xs font-black ink"><?= rupiah((int) $l['subtotal']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="mt-3 flex items-center justify-between rounded-2xl bg-pink-50 px-4 py-3 dark:bg-pink-500/10">
                <span class="ink-muted text-xs font-bold uppercase">Total</span>
                <span class="text-lg font-black text-pink-700 dark:text-pink-300"><?= rupiah((int) $cart['total']) ?></span>
            </div>
            <p class="ink-muted mt-3 text-[11px]">
                <i data-lucide="shield-check" class="mr-1 inline h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"></i>
                Harga sudah divalidasi server saat tombol ditekan.
            </p>
        </aside>
    </div>
</main>
