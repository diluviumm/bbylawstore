<?php
/** Modal detail produk (pop-up, tanpa pindah halaman). */
declare(strict_types=1);
?>
<div id="product-modal" class="fixed inset-0 z-[65] hidden overflow-y-auto" role="dialog"
     aria-modal="true" aria-labelledby="pd-title" aria-hidden="true">
    <a href="#" class="fixed inset-0 bg-black/75 backdrop-blur-md" data-pd-close aria-hidden="true" tabindex="-1"></a>
    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-4">
        <div class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-3xl border-2 border-pink-500/35 bg-[#180e22] p-5 text-white shadow-2xl sm:rounded-3xl sm:p-6">
            <button type="button" data-pd-close
                    class="absolute right-3 top-3 z-10 rounded-xl bg-[#180e22]/90 p-1.5 text-pink-200 ring-1 ring-pink-500/30 hover:bg-pink-500/20"
                    aria-label="Tutup detail produk">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
            <h2 id="pd-title" class="sr-only">Detail produk</h2>
            <div data-pd-body class="pr-8">
                <p class="text-xs text-pink-200/70">Memuat detail…</p>
            </div>
        </div>
    </div>
</div>
