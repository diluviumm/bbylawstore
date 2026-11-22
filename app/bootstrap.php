<?php
/**
 * Bbylaa Store — bootstrap.
 *
 * Aplikasi ini menggantikan situs statis lama yang "menyimpan" pesanan dengan
 * fetch(mode:'no-cors') ke Google Apps Script. Endpoint itu mengembalikan 404
 * sehingga tidak ada pesanan yang pernah tersimpan, dan status buka/tutup menu
 * disimpan di localStorage (per-perangkat) sehingga admin dan pembeli melihat
 * keadaan berbeda.
 *
 * Sekarang: satu database SQLite milik server. Semua status, stok, pesanan,
 * dan akun admin hidup di sini — bukan di browser pengunjung.
 */

declare(strict_types=1);

define('BBY_ROOT', dirname(__DIR__));
define('BBY_APP', __DIR__);
define('BBY_DATA', BBY_ROOT . '/data');

const BBY_VERSION = '2.0.0';

// ---------------------------------------------------------------- environment
// .env.local menang atas .env (dipakai untuk pengembangan lokal).
foreach ([BBY_ROOT . '/.env.local', BBY_ROOT . '/.env'] as $envFile) {
    if (!is_file($envFile)) {
        continue;
    }
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) > 1 && ($v[0] === '"' || $v[0] === "'") && $v[0] === $v[-1]) {
            $v = substr($v, 1, -1);
        }
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

// ------------------------------------------------------------------- database
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = env('BBY_DB', BBY_DATA . '/bbylaa.sqlite');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    return $pdo;
}

// ---------------------------------------------------------------------- utils
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    return strtolower(trim(explode(',', $proto)[0])) === 'https';
}

function base_url(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'bbylawstore.naygars.site';
    return (is_https() ? 'https://' : 'http://') . $host;
}

function rupiah(int|float|null $n): string
{
    return 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
}

require __DIR__ . '/views.php';
require __DIR__ . '/handlers.php';
require __DIR__ . '/pages.php';

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function redirect(string $to, int $status = 303): never
{
    header('Location: ' . $to, true, $status);
    exit;
}

function now(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))->format('Y-m-d H:i:s');
}

function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    $v = $cache[$key] ?? null;
    return ($v === null || $v === '') ? $default : $v;
}

function setting_set(string $key, string $value): void
{
    $st = db()->prepare(
        'INSERT INTO settings (key, value) VALUES (?, ?)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value'
    );
    $st->execute([$key, $value]);
}

// ------------------------------------------------------------------- security
function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function ip_hash(): string
{
    return hash('sha256', client_ip() . '|' . (env('BBY_SALT', 'bbylaa') ?? 'bbylaa'));
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => is_https(),
        'samesite' => 'Lax',
    ]);
    session_name('bbylaa_sid');
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_ok(?string $token): bool
{
    start_session();
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Throttle berbasis tabel: aman untuk beberapa proses PHP-FPM sekaligus. */
function throttle(string $bucket, int $max, int $windowSeconds): bool
{
    $key = $bucket . ':' . ip_hash();
    $pdo = db();
    $now = time();

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT count, window_start FROM throttle WHERE key = ?');
        $st->execute([$key]);
        $row = $st->fetch();

        if (!$row || ($now - (int) $row['window_start']) >= $windowSeconds) {
            $pdo->prepare(
                'INSERT INTO throttle (key, count, window_start) VALUES (?, 1, ?)
                 ON CONFLICT(key) DO UPDATE SET count = 1, window_start = excluded.window_start'
            )->execute([$key, $now]);
            $pdo->commit();
            return true;
        }

        if ((int) $row['count'] >= $max) {
            $pdo->commit();
            return false;
        }

        $pdo->prepare('UPDATE throttle SET count = count + 1 WHERE key = ?')->execute([$key]);
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function throttle_reset(string $bucket): void
{
    db()->prepare('DELETE FROM throttle WHERE key = ?')->execute([$bucket . ':' . ip_hash()]);
}

function admin_user(): ?array
{
    start_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $st = db()->prepare('SELECT id, username FROM admin_users WHERE id = ?');
    $st->execute([(int) $_SESSION['admin_id']]);
    $row = $st->fetch();
    if (!$row) {
        unset($_SESSION['admin_id']);
        return null;
    }
    return $row;
}

function require_admin(): array
{
    $u = admin_user();
    if (!$u) {
        redirect('/admin/login');
    }
    return $u;
}

function audit(string $action, string $detail = '', ?string $actor = null): void
{
    try {
        db()->prepare(
            'INSERT INTO audit_log (actor, action, detail, ip_hash, created_at) VALUES (?,?,?,?,?)'
        )->execute([$actor ?? (admin_user()['username'] ?? 'guest'), $action, $detail, ip_hash(), now()]);
    } catch (Throwable) {
        // Audit tidak boleh menggagalkan alur pesanan.
    }
}

function flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    start_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ------------------------------------------------------------------- katalog
function categories_all(bool $onlyOpen = false): array
{
    $sql = 'SELECT * FROM categories';
    if ($onlyOpen) {
        $sql .= ' WHERE is_open = 1';
    }
    $sql .= ' ORDER BY sort, id';
    return db()->query($sql)->fetchAll();
}

function category_map(): array
{
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (categories_all() as $c) {
            $map[$c['id']] = $c;
        }
    }
    return $map;
}

function products_all(): array
{
    static $products = null;
    if ($products !== null) {
        return $products;
    }

    $products = [];
    $rows = db()->query(
        'SELECT * FROM products WHERE is_active = 1 ORDER BY sort, id'
    )->fetchAll();

    $variantRows = db()->query(
        'SELECT * FROM variants WHERE is_active = 1 ORDER BY sort, id'
    )->fetchAll();

    $byProduct = [];
    foreach ($variantRows as $v) {
        $byProduct[(int) $v['product_id']][] = [
            'id'         => (int) $v['id'],
            'label'      => $v['label'],
            'price'      => (int) $v['price'],
            'price_max'  => $v['price_max'] === null ? null : (int) $v['price_max'],
            'stock'      => $v['stock'] === null ? null : (int) $v['stock'],
            'sold'       => (int) $v['sold'],
        ];
    }

    foreach ($rows as $p) {
        $pid = (int) $p['id'];
        $variants = $byProduct[$pid] ?? [];
        if (!$variants) {
            continue; // produk tanpa varian aktif tidak bisa dijual
        }
        $p['id'] = $pid;
        $p['is_quote'] = (int) $p['is_quote'];
        $p['features'] = json_decode((string) $p['features'], true) ?: [];
        $p['images'] = array_values(array_filter(
            json_decode((string) $p['images'], true) ?: [],
            static fn(string $img): bool => is_file(BBY_ROOT . '/public/assets/img/' . basename($img))
        ));
        $p['variants'] = $variants;
        $products[] = $p;
    }

    return $products;
}

function product_by_id(int $id, bool $includeInactive = false): ?array
{
    foreach (products_all() as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    if (!$includeInactive) {
        return null;
    }
    return product_by_id_any($id);
}

/**
 * Ambil produk apa pun (termasuk yang disembunyikan dari katalog) — dipakai
 * panel admin supaya produk yang sudah dinonaktifkan masih bisa diaktifkan lagi.
 */
function product_by_id_any(int $id): ?array
{
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) {
        return null;
    }
    $p['id'] = (int) $p['id'];
    $p['is_quote'] = (int) $p['is_quote'];
    $p['features'] = json_decode((string) $p['features'], true) ?: [];
    $p['images'] = array_values(array_filter(
        json_decode((string) $p['images'], true) ?: [],
        static fn(string $img): bool => is_file(BBY_ROOT . '/public/assets/img/' . basename($img))
    ));

    $vs = $pdo->prepare('SELECT * FROM variants WHERE product_id = ? ORDER BY sort, id');
    $vs->execute([$id]);
    $p['variants'] = [];
    foreach ($vs->fetchAll() as $v) {
        $p['variants'][] = [
            'id'        => (int) $v['id'],
            'label'     => $v['label'],
            'price'     => (int) $v['price'],
            'price_max' => $v['price_max'] === null ? null : (int) $v['price_max'],
            'stock'     => $v['stock'] === null ? null : (int) $v['stock'],
            'sold'      => (int) $v['sold'],
        ];
    }
    return $p;
}

/** Semua produk termasuk yang non-aktif (khusus panel admin). */
function products_admin(): array
{
    $out = [];
    foreach (db()->query('SELECT id FROM products ORDER BY sort, id')->fetchAll() as $row) {
        $p = product_by_id_any((int) $row['id']);
        if ($p) {
            $out[] = $p;
        }
    }
    return $out;
}

function variant_of(array $product, int $variantId): ?array
{
    foreach ($product['variants'] as $v) {
        if ($v['id'] === $variantId) {
            return $v;
        }
    }
    return null;
}

function variant_available(array $variant): bool
{
    return $variant['stock'] === null || $variant['stock'] > 0;
}

// ---------------------------------------------------------------- keranjang
/**
 * Keranjang hidup di sesi server, bukan localStorage: pembeli yang membuka
 * dua perangkat tidak melihat keranjang yang berbeda, dan harga selalu
 * dihitung ulang dari database saat checkout.
 *
 * Struktur: [variant_id => qty]
 */
function cart_get(): array
{
    start_session();
    $cart = $_SESSION['cart'] ?? [];
    return is_array($cart) ? $cart : [];
}

function cart_set(array $cart): void
{
    start_session();
    $_SESSION['cart'] = $cart;
}

function cart_add(int $variantId, int $qty = 1): array
{
    $cart = cart_get();
    $cart[$variantId] = min(20, ($cart[$variantId] ?? 0) + max(1, $qty));
    cart_set($cart);
    return cart_lines();
}

function cart_remove(int $variantId): array
{
    $cart = cart_get();
    unset($cart[$variantId]);
    cart_set($cart);
    return cart_lines();
}

function cart_set_qty(int $variantId, int $qty): array
{
    $cart = cart_get();
    if ($qty <= 0) {
        unset($cart[$variantId]);
    } else {
        $cart[$variantId] = min(20, $qty);
    }
    cart_set($cart);
    return cart_lines();
}

function cart_clear(): void
{
    start_session();
    unset($_SESSION['cart'], $_SESSION['cart_token']);
}

/**
 * Baris keranjang yang sudah divalidasi terhadap database.
 * Varian yang stoknya habis / produknya dimatikan otomatis dikeluarkan.
 */
function cart_lines(): array
{
    $cart = cart_get();
    if (!$cart) {
        return ['lines' => [], 'count' => 0, 'total' => 0, 'dropped' => []];
    }

    $lines = [];
    $dropped = [];
    $count = 0;
    $total = 0;

    foreach ($cart as $vid => $qty) {
        $vid = (int) $vid;
        $qty = (int) $qty;
        $product = null;
        $variant = null;
        foreach (products_all() as $p) {
            $v = variant_of($p, $vid);
            if ($v) {
                $product = $p;
                $variant = $v;
                break;
            }
        }
        if (!$product || !$variant || !variant_available($variant)) {
            $dropped[] = $vid;
            continue;
        }
        $cat = category_map()[$product['category_id']] ?? null;
        if (!$cat || !(int) $cat['is_open']) {
            $dropped[] = $vid;
            continue;
        }
        $qty = min($qty, 20);
        if ($variant['stock'] !== null) {
            $qty = min($qty, $variant['stock']);
        }
        if ($qty <= 0) {
            $dropped[] = $vid;
            continue;
        }

        $lines[] = [
            'variant_id'    => $vid,
            'product_id'    => $product['id'],
            'name'          => $product['name'],
            'category_id'   => $product['category_id'],
            'category_name' => $product['category_name'],
            'variant_label' => $variant['label'],
            'unit_price'    => $variant['price'],
            'price_max'     => $variant['price_max'],
            'qty'           => $qty,
            'subtotal'      => $variant['price'] * $qty,
            'is_quote'      => $product['is_quote'],
            'icon'          => $product['icon'],
            'accent'        => $product['accent'],
            'stock'         => $variant['stock'],
        ];
        $count += $qty;
        $total += $variant['price'] * $qty;
    }

    if ($dropped) {
        $clean = $cart;
        foreach ($dropped as $vid) {
            unset($clean[$vid]);
        }
        cart_set($clean);
    }

    return ['lines' => $lines, 'count' => $count, 'total' => $total, 'dropped' => $dropped];
}

// ------------------------------------------------------------------- pesanan
function order_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $suffix = '';
    for ($i = 0; $i < 5; $i++) {
        $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return 'BBY-' . date('ymd') . '-' . $suffix;
}

function order_find(string $code): ?array
{
    $st = db()->prepare('SELECT * FROM orders WHERE code = ?');
    $st->execute([$code]);
    $order = $st->fetch();
    if (!$order) {
        return null;
    }
    $st = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $st->execute([(int) $order['id']]);
    $order['items'] = $st->fetchAll();
    return $order;
}

function normalize_phone(string $raw): ?string
{
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if ($digits === '') {
        return null;
    }
    if (str_starts_with($digits, '62')) {
        $digits = '0' . substr($digits, 2);
    } elseif (str_starts_with($digits, '8')) {
        $digits = '0' . $digits;
    }
    if (strlen($digits) < 9 || strlen($digits) > 15) {
        return null;
    }
    return $digits;
}

function wa_number(string $local): string
{
    $digits = preg_replace('/\D+/', '', $local) ?? '';
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return $digits;
}

/** Status pesanan yang dikenal. */
const ORDER_STATUSES = [
    'baru'     => 'Baru',
    'diproses' => 'Diproses',
    'selesai'  => 'Selesai',
    'batal'    => 'Dibatalkan',
];
