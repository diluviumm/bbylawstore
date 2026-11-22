# Bbylaa Store

Storefront + panel admin untuk **bbylawstore.naygars.site**.

Versi ini menggantikan situs statis lama. Alasan utamanya: versi lama
"menyimpan" pesanan dengan `fetch(mode:'no-cors')` ke Google Apps Script yang
sudah tidak ada, dan menyimpan status buka/tutup menu di `localStorage`
masing-masing browser. Akibatnya pesanan hilang dan admin/pembeli melihat
keadaan yang berbeda. Sekarang semuanya lewat database server.

---

## Fakta singkat

| Aspek | Nilai |
|---|---|
| Stack | PHP 8.3 (tanpa framework) + SQLite (WAL) |
| Aset | Tailwind dikompilasi, Alpine **tidak dipakai**, Lucide di-vendor lokal |
| Tema | Terang & gelap, tersimpan di cookie, kontras WCAG AA terverifikasi |
| Katalog | 45 produk / 123 varian, dari `app/data/products.json` |
| Pesanan | Tersimpan sebelum pembeli diarahkan ke WhatsApp |
| Keamanan | CSRF, Argon2id, throttle berlapis, CSP tanpa `unsafe-eval` |

---

## Struktur

```
app/
  bootstrap.php      # env, PDO, sesi, CSRF, katalog, keranjang, util
  views.php          # renderer + palet warna (kelas Tailwind literal)
  handlers.php       # semua handler POST (keranjang, checkout, admin)
  pages.php          # renderer halaman (publik + admin)
  data/products.json # katalog sumber (di-seed ke SQLite)
  schema.sql         # dokumentasi skema
  views/             # template PHP
bin/
  install.php        # buat skema + seed katalog + admin pertama
  passwd.php         # ganti/reset password admin
public/
  index.php          # satu-satunya front controller
  assets/css/app.css # hasil build Tailwind (di-commit)
  assets/js/         # store.js (publik), admin.js (panel)
  assets/vendor/     # lucide.min.js
  assets/img/        # logo, banner, background
deploy/              # Caddyfile, skrip backup, panduan deploy
legacy/              # arsip situs statis lama (tidak dipakai)
data/                # (dibuat saat install) bbylaa.sqlite
```

---

## Menjalankan lokal

```bash
npm install
npx tailwindcss -i ./tailwind/input.css -o ./public/assets/css/app.css --minify

echo 'BBY_DB=/tmp/bbydev/bbylaa.sqlite' > .env.local
BBY_ADMIN_PASS='rahasia-minimal-10' php bin/install.php
php -d opcache.enable=0 -S 127.0.0.1:8181 -t public public/index.php
```

Buka <http://127.0.0.1:8181>. Panel admin di `/admin/login`.

> `-d opcache.enable=0` penting saat mengembangkan: dengan `revalidate_freq`
> bawaan 180 detik, file yang baru diedit masih disajikan dari cache dan
> perbaikan tampak "tidak jalan".

---

## Alur pesanan (yang membedakan dari versi lama)

1. Pembeli menambah item → `POST /api/cart/add` → keranjang disimpan di **sesi
   server**.
2. Checkout → `POST /api/checkout`:
   - harga & stok dihitung ulang dari database (input browser tidak dipercaya),
   - kategori yang ditutup ditolak di server,
   - pesanan + item ditulis dalam satu transaksi,
   - stok berbatas dikurangi dengan `UPDATE ... WHERE stock >= qty`
     (aman untuk pembelian bersamaan).
3. Server mengembalikan kode pesanan + teks WhatsApp; browser membuka WhatsApp
   **setelah** pesanan tercatat.
4. Admin melihat pesanan di `/admin/pesanan` dan mengubah status di sana.

---

## Perintah penting

```bash
php bin/install.php          # idempoten: skema, katalog, admin pertama
php bin/passwd.php --gen     # reset password admin
npm run build                # rebuild CSS setelah mengubah kelas Tailwind
```

---

## Sebelum dipakai publik

1. Ganti password admin (`/admin/akun` atau `bin/passwd.php --gen`).
2. Isi nomor WhatsApp & tautan testimoni di `/admin/pengaturan`.
3. Pastikan backup harian aktif (`deploy/backup.sh` + cron).
4. Baca `deploy/README.md` untuk langkah deploy & verifikasi.
