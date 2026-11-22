<?php
/**
 * Bbylaa Store — satu-satunya pintu masuk HTTP.
 *
 * Caddy mengarahkan semua request ke file ini (php_fastcgi). Aset statis
 * dilayani langsung oleh Caddy dari folder public/ sebelum sampai ke sini.
 */

declare(strict_types=1);

// Mode pengembangan: matikan cache opcache agar edit langsung terpakai.
// (Produksi tidak memakai ini; PHP-FPM punya revalidate sendiri.)
if (PHP_SAPI === 'cli-server') {
    @ini_set('opcache.enable', '0');
    @ini_set('opcache.enable_cli', '0');
}

// Mode pengembangan (php -S): biarkan server bawaan melayani aset statis.
// Di produksi, Caddy menangani /assets/* sebelum request sampai ke sini.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri    = rawurldecode($uri);
$path   = '/' . trim($uri, '/');

// ------------------------------------------------------------------ keamanan
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
// Cloudflare menyuntikkan beacon analitik di edge. Dua host itu harus
// diizinkan eksplisit, kalau tidak setiap kunjungan mencatat pelanggaran CSP
// di konsol pengunjung (dan menakut-nakuti pemilik toko).
header(
    "Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://static.cloudflareinsights.com; "
    . "style-src 'self' 'unsafe-inline'; "
    . "img-src 'self' data:; font-src 'self' data:; "
    . "connect-src 'self' https://cloudflareinsights.com; "
    . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'"
);

// Halaman dinamis tidak boleh di-cache lama: status buka/tutup & stok berubah.
header('Cache-Control: no-cache, must-revalidate');

// --------------------------------------------------------------- POST routes
try {
    if ($method === 'POST') {
        $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        // Semua POST wajib bertoken kecuali endpoint yang memang publik-stateless.
        if (!csrf_ok(is_string($token) ? $token : null)) {
            http_response_code(419);
            if (str_starts_with($path, '/api/')) {
                json_response(['ok' => false, 'error' => 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'], 419);
            }
            echo view('errors/419', ['title' => 'Sesi Kedaluwarsa']);
            exit;
        }

        switch ($path) {
            case '/api/cart/add':
                handle_cart_add();
                break;
            case '/api/cart/set':
                handle_cart_set();
                break;
            case '/api/cart/remove':
                handle_cart_remove();
                break;
            case '/api/checkout':
                handle_checkout();
                break;
            case '/lacak':
                handle_track();
                break;
            case '/admin/login':
                handle_admin_login();
                break;
            case '/admin/logout':
                handle_admin_logout();
                break;
            case '/admin/pesanan/status':
                handle_admin_status();
                break;
            case '/admin/pesanan/catatan':
                handle_admin_note();
                break;
            case '/admin/kategori':
                handle_admin_category();
                break;
            case '/admin/produk/simpan':
                handle_admin_product_save();
                break;
            case '/admin/produk/stok':
                handle_admin_stock();
                break;
            case '/admin/pengaturan':
                handle_admin_settings();
                break;
            case '/admin/akun':
                handle_admin_account();
                break;
            default:
                http_response_code(404);
                echo view('errors/404', ['title' => 'Halaman tidak ditemukan']);
        }
        exit;
    }

    if ($method !== 'GET' && $method !== 'HEAD') {
        http_response_code(405);
        header('Allow: GET, HEAD, POST');
        echo view('errors/405', ['title' => 'Metode tidak diizinkan']);
        exit;
    }

    // ------------------------------------------------------------ GET routes
    switch (true) {
        case $path === '/':
            render_store();
            break;

        case $path === '/keranjang':
            render_cart();
            break;

        case $path === '/lacak':
            render_track();
            break;

        case $path === '/pesanan':
            render_order_result();
            break;

        case $path === '/produk':
            $id = (int) ($_GET['id'] ?? 0);
            render_product($id);
            break;

        case $path === '/admin':
            redirect('/admin/pesanan');

        case $path === '/admin/login':
            render_admin_login();
            break;

        case $path === '/admin/pesanan':
            render_admin_orders();
            break;

        case str_starts_with($path, '/admin/pesanan/'):
            render_admin_order_detail((int) substr($path, strlen('/admin/pesanan/')));
            break;

        case $path === '/admin/produk':
            render_admin_products();
            break;

        case $path === '/admin/pengaturan':
            render_admin_settings();
            break;

        case $path === '/admin/akun':
            render_admin_account();
            break;

        case $path === '/robots.txt':
            header('Content-Type: text/plain; charset=utf-8');
            echo "User-agent: *\nDisallow: /admin\nDisallow: /keranjang\nDisallow: /pesanan\nDisallow: /lacak\n";
            break;

        case $path === '/sitemap.xml':
            header('Content-Type: application/xml; charset=utf-8');
            echo sitemap_xml();
            break;

        default:
            http_response_code(404);
            echo view('errors/404', ['title' => 'Halaman tidak ditemukan']);
    }
} catch (Throwable $ex) {
    error_log('[bbylaa] ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    http_response_code(500);
    if (str_starts_with($path, '/api/')) {
        json_response(['ok' => false, 'error' => 'Terjadi kesalahan di server. Coba lagi.'], 500);
    }
    echo view('errors/500', ['title' => 'Terjadi kesalahan']);
}
