# Deploy Bbylaa Store ke server dilu

Panduan ini menjalankan versi PHP + SQLite di `bbylawstore.naygars.site`.
Beda mendasar dari versi lama: **pesanan benar-benar tersimpan di server**,
bukan dikirim ke Google Apps Script yang sudah mati.

---

## 0. Prasyarat (sudah tersedia di dilu)

| Kebutuhan | Status di dilu |
|---|---|
| PHP 8.3 + `pdo_sqlite`, `sqlite3`, `mbstring`, `json` | ✓ |
| Caddy dengan `php_fastcgi` | ✓ (dipakai loker/padelio) |
| PHP-FPM pool `www-data` (`/run/php/php8.3-fpm.sock`) | ✓ |
| Node/npm (hanya untuk build CSS) | jalankan build di laptop |

---

## 1. Build aset di laptop (sekali per perubahan tampilan)

```bash
cd ~/me/github/bbylawstore
npm ci --omit=dev 2>/dev/null || npm install
npx tailwindcss -i ./tailwind/input.css -o ./public/assets/css/app.css --minify
```

`public/assets/css/app.css` ikut di-commit supaya server tidak perlu Node.

---

## 2. Kirim ke server

```bash
rsync -az --delete \
  --exclude '.git' --exclude 'node_modules' --exclude 'data/*.sqlite*' \
  --exclude '.env' --exclude '.env.local' --exclude 'legacy' \
  ~/me/github/bbylawstore/ dilu:/srv/bbylawstore/
```

> `legacy/` berisi situs statis lama (arsip). Tidak perlu ikut ke server.

---

## 3. Siapkan direktori data & izin

```bash
ssh dilu
sudo mkdir -p /srv/bbylawstore/data
sudo chown -R www-data:www-data /srv/bbylawstore/data
sudo chmod 750 /srv/bbylawstore/data
```

`data/` menyimpan `bbylaa.sqlite`. **Jangan** taruh di dalam `public/`.

---

## 4. Inisialisasi database + admin

```bash
ssh dilu
cd /srv/bbylawstore
sudo -u www-data BBY_ADMIN_PASS='GANTI-INI-JANGAN-PAKAI-BAWaan' php bin/install.php
```

Keluaran yang benar:

```
Admin dibuat → username: admin
Skema siap. {"produk":45,"varian":123,"kategori":8,"pesanan":0}
```

Install ulang aman: skema `IF NOT EXISTS`, katalog di-UPSERT, **pesanan tidak
pernah disentuh**, dan password admin tidak ditimpa.

---

## 5. Caddy

File `deploy/bbylawstore.caddy` (di repo ini) menggantikan blok `file_server`
lama. Inti perubahan:

```
root * /srv/bbylawstore/public
php_fastcgi unix//run/php/php8.3-fpm.sock
```

Pasang:

```bash
sudo cp deploy/bbylawstore.caddy /etc/caddy/naygars-sites/bbylawstore.caddy
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy
```

---

## 6. Verifikasi setelah deploy

```bash
curl -s -o /dev/null -w 'home=%{http_code}\n'   https://bbylawstore.naygars.site/
curl -s -o /dev/null -w 'admin=%{http_code}\n'  https://bbylawstore.naygars.site/admin/login
curl -s -o /dev/null -w 'css=%{http_code}\n'    https://bbylawstore.naygars.site/assets/css/app.css
curl -s -o /dev/null -w 'robots=%{http_code}\n' https://bbylawstore.naygars.site/robots.txt
```

Uji checkout nyata:

```bash
# tambah item + checkout lewat browser, lalu:
ssh dilu "sudo -u www-data sqlite3 /srv/bbylawstore/data/bbylaa.sqlite \
  'select code,customer_name,total,status,created_at from orders order by id desc limit 5;'"
```

Baris baru harus muncul **sebelum** pembeli menekan tombol WhatsApp.

---

## 7. Backup harian

`deploy/backup.sh` membuat salinan konsisten (`.backup` SQLite, bukan `cp`) dan
menyimpannya 14 hari:

```bash
sudo cp deploy/backup.sh /usr/local/bin/bbylawstore-backup
sudo chmod +x /usr/local/bin/bbylawstore-backup
sudo crontab -e   # tambahkan:
# 20 3 * * *  /usr/local/bin/bbylawstore-backup >/var/log/bbylawstore-backup.log 2>&1
```

Uji restore:

```bash
sudo systemctl stop php8.3-fpm
sudo cp /var/backups/bbylawstore/bbylaa-YYYYmmdd-HHMM.sqlite /srv/bbylawstore/data/bbylaa.sqlite
sudo chown www-data:www-data /srv/bbylawstore/data/bbylaa.sqlite
sudo systemctl start php8.3-fpm
```

---

## 8. Ganti password admin

Lewat web: `/admin/akun` (butuh password lama).
Lewat server bila password hilang:

```bash
sudo -u www-data php /srv/bbylawstore/bin/passwd.php --gen
```

---

## 9. Kalau ada masalah

| Gejala | Cek |
|---|---|
| 502 Bad Gateway | `sudo systemctl status php8.3-fpm` dan `sudo tail -50 /var/log/php8.3-fpm.log` |
| 500 di semua halaman | `sudo -u www-data php -r 'require "/srv/bbylawstore/app/bootstrap.php"; echo "ok\n";'` |
| Halaman tampil tanpa gaya | `app.css` 404 → cek `root` Caddy menunjuk ke `/public`, dan file hasil build ikut ter-rsync |
| Pesanan tidak muncul di admin | pastikan bukan kategori tutup; cek `select count(*) from orders;` |
| "Sesi kedaluwarsa" saat checkout | sesi PHP tidak bisa menulis → cek izin `session.save_path` |
