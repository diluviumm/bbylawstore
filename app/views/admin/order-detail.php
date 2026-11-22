<?php
/**
 * Detail pesanan.
 * @var array $order
 */
declare(strict_types=1);
$waText = 'Halo ' . $order['customer_name'] . ', terkait pesanan ' . $order['code'] . ' di Bbylaa Store.';
?>
<a href="/admin/pesanan" class="mb-4 inline-flex items-center gap-1.5 text-xs font-bold text-pink-800 hover:text-pink-950">
    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i> Semua pesanan
</a>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="space-y-4 xl:col-span-2">
        <div class="card p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-sm font-black text-pink-800"><?= e($order['code']) ?></p>
                    <p class="mt-1 text-lg font-black text-[#241520]"><?= e($order['customer_name']) ?></p>
                    <p class="font-mono text-xs text-[#6b5563]"><?= e($order['customer_phone']) ?></p>
                </div>
                <div class="text-right">
                    <span class="chip <?= status_classes($order['status']) ?>"><?= e(ORDER_STATUSES[$order['status']] ?? $order['status']) ?></span>
                    <p class="mt-1 text-[11px] text-[#8a7086]"><?= fmt_date($order['created_at']) ?></p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-1.5 border-t border-[#f6dbe6] pt-3">
                <?php foreach (ORDER_STATUSES as $key => $label): ?>
                    <button type="button" data-set-status="<?= (int) $order['id'] ?>" data-status="<?= e($key) ?>"
                            class="chip transition-colors <?= $order['status'] === $key
                                ? 'bg-pink-700 text-white ring-pink-700'
                                : 'bg-white text-[#5c4354] ring-[#f0d3e0] hover:bg-pink-50' ?>">
                        <?= e($label) ?>
                    </button>
                <?php endforeach; ?>
                <a href="https://wa.me/<?= e(wa_number($order['customer_phone'])) ?>?text=<?= rawurlencode($waText) ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="chip ml-auto bg-emerald-50 text-emerald-800 ring-emerald-200 hover:bg-emerald-100">
                    <i data-lucide="message-circle" class="h-3 w-3"></i> Chat pembeli
                </a>
            </div>
        </div>

        <div class="card overflow-hidden">
            <h2 class="border-b border-[#f6dbe6] px-4 py-3 text-xs font-black uppercase tracking-wider text-[#6b5563] sm:px-5">Item pesanan</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-xs">
                    <thead class="bg-[#fdf6f9]">
                        <tr>
                            <th class="table-head px-4 py-2">Produk</th>
                            <th class="table-head px-3 py-2">Paket</th>
                            <th class="table-head px-3 py-2 text-right">Harga</th>
                            <th class="table-head px-3 py-2 text-center">Qty</th>
                            <th class="table-head px-4 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f6dbe6]">
                        <?php foreach ($order['items'] as $it): ?>
                            <tr>
                                <td class="px-4 py-2.5">
                                    <p class="font-bold text-[#241520]"><?= e($it['product_name']) ?></p>
                                    <p class="text-[10px] text-[#8a7086]"><?= e($it['category_name']) ?></p>
                                </td>
                                <td class="px-3 py-2.5 text-[#5c4354]"><?= e($it['variant_label']) ?></td>
                                <td class="px-3 py-2.5 text-right text-[#5c4354]">
                                    <?= $it['price_max'] !== null
                                        ? rupiah((int) $it['unit_price']) . '–' . rupiah((int) $it['price_max'])
                                        : rupiah((int) $it['unit_price']) ?>
                                </td>
                                <td class="px-3 py-2.5 text-center font-bold text-[#241520]"><?= (int) $it['qty'] ?></td>
                                <td class="px-4 py-2.5 text-right font-black text-[#241520]"><?= rupiah((int) $it['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-[#f6dbe6] bg-[#fdf6f9]">
                            <td colspan="4" class="px-4 py-2.5 text-right text-xs font-bold uppercase text-[#6b5563]">Total</td>
                            <td class="px-4 py-2.5 text-right text-base font-black text-pink-800"><?= rupiah((int) $order['total']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card p-4 sm:p-5">
            <h2 class="mb-2 text-xs font-black uppercase tracking-wider text-[#6b5563]">Catatan dari pembeli</h2>
            <p class="rounded-xl bg-[#fdf6f9] px-3 py-2.5 text-xs text-[#241520]">
                <?= $order['note'] !== '' ? e($order['note']) : '<span class="text-[#8a7086]">— tidak ada —</span>' ?>
            </p>

            <?php if ($order['payment'] !== ''): ?>
                <p class="mt-2 text-xs"><span class="font-bold text-[#6b5563]">Metode bayar:</span> <span class="font-bold text-[#241520]"><?= e($order['payment']) ?></span></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card p-4 sm:p-5">
            <h2 class="mb-2 text-xs font-black uppercase tracking-wider text-[#6b5563]">Catatan internal</h2>
            <form action="/admin/pesanan/catatan" method="post" class="space-y-2">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <label for="admin_note" class="sr-only">Catatan internal</label>
                <textarea id="admin_note" name="admin_note" rows="4" maxlength="500"
                          class="field py-2 text-xs" placeholder="Contoh: sudah dibayar via QRIS, dikirim 20 Nov"><?= e($order['admin_note']) ?></textarea>
                <button type="submit" class="btn-primary w-full py-2 text-xs">
                    <i data-lucide="save" class="h-3.5 w-3.5"></i> Simpan catatan
                </button>
            </form>
        </div>

        <div class="card p-4 text-xs sm:p-5">
            <h2 class="mb-2 text-xs font-black uppercase tracking-wider text-[#6b5563]">Ringkasan</h2>
            <dl class="space-y-1.5">
                <div class="flex justify-between gap-2"><dt class="text-[#6b5563]">Jumlah item</dt><dd class="font-bold text-[#241520]"><?= (int) $order['item_count'] ?></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-[#6b5563]">Sumber</dt><dd class="font-bold text-[#241520]"><?= e($order['source']) ?></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-[#6b5563]">Dibuat</dt><dd class="font-bold text-[#241520]"><?= fmt_date($order['created_at']) ?></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-[#6b5563]">Diperbarui</dt><dd class="font-bold text-[#241520]"><?= fmt_date($order['updated_at']) ?></dd></div>
            </dl>
        </div>
    </div>
</div>
