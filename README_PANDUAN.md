# Panduan Singkat Setup Database Google Sheets (Bbylaa Store)

Paket website Bbylaa Store ini sudah siap 100% dan terintegrasi dengan Google Sheets sebagai database pencatat pesanan otomatis.

## Cara Aktifkan Google Sheets:
1. Buka [sheets.google.com](https://sheets.google.com) lalu buat Spreadsheet baru dengan judul **Database Bbylaa Store**.
2. Pada baris ke-1 (Header Kolom A sampai I), tulis:
   - Kolom A: `Timestamp`
   - Kolom B: `Kode Order`
   - Kolom C: `Kategori`
   - Kolom D: `Nama Produk`
   - Kolom E: `Varian`
   - Kolom F: `Harga`
   - Kolom G: `Nama Pembeli`
   - Kolom H: `No WhatsApp`
   - Kolom I: `Data Game / Catatan`
3. Klik menu **Extensions** (Ekstensi) -> **Apps Script**.
4. Buka file `kode_google_apps_script.js` di dalam paket ini, salin isinya, dan tempel ke Apps Script.
5. Klik **Deploy** -> **New deployment** -> pilih **Web app**.
   - Execute as: **Me**
   - Who has access: **Anyone**
6. Klik **Deploy**, beri izin akun Googlemu, lalu salin **Web App URL** yang didapat.
7. Buka file `index.html` dengan text editor (Notepad / VS Code), cari baris:
   ```javascript
   googleSheetUrl: 'MASUKKAN_URL_WEB_APP_APPS_SCRIPT_DISINI',
   ```
   Ganti tulisan di dalam tanda kutip dengan Web App URL yang kamu salin tadi.
8. Simpan file! Sekarang setiap ada pesanan masuk, otomatis tercatat di Google Sheet & langsung terhubung ke WhatsApp.
