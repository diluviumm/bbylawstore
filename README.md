# Bbylaa Store

Website statis Bbylaa Store (APK premium, joki tugas, top up game) — HTML + Tailwind CDN + Alpine.js.

## Struktur

- `index.html` — halaman toko (katalog, keranjang, checkout via WhatsApp)
- `admin.html` — panel admin (PIN client-side)
- `kode_google_apps_script.js` — Apps Script untuk mencatat order ke Google Sheets
- `README_PANDUAN.md` — panduan setup Google Sheets + deploy Apps Script

## Deploy (produksi)

Static hosting di server, dilayani Caddy:

```caddyfile
http://bbylawstore.naygars.site {
    root * /srv/bbylawstore
    file_server
}
```

- Live: <https://bbylawstore.naygars.site>
- Admin: <https://bbylawstore.naygars.site/admin.html>

## Catatan

- Order dikirim ke Google Sheets lewat Apps Script (`googleSheetUrl` di `index.html`).
- Panel admin memakai PIN sisi klien; bukan autentikasi server.
