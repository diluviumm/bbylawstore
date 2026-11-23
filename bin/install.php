<?php
/**
 * Bbylaa Store — skema database + seed katalog.
 *
 * Dijalankan sebagai `php bin/install.php` (idempoten). Aman diulang: skema
 * memakai IF NOT EXISTS, seed memakai UPSERT sehingga katalog bisa
 * disinkronkan ulang tanpa menghapus pesanan yang sudah masuk.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS categories (
    id      TEXT PRIMARY KEY,
    name    TEXT NOT NULL,
    emoji   TEXT NOT NULL DEFAULT '',
    sort    INTEGER NOT NULL DEFAULT 0,
    is_open INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS products (
    id            INTEGER PRIMARY KEY,
    category_id   TEXT NOT NULL,
    category_name TEXT NOT NULL DEFAULT '',
    name          TEXT NOT NULL,
    subtext       TEXT NOT NULL DEFAULT '',
    description   TEXT NOT NULL DEFAULT '',
    features      TEXT NOT NULL DEFAULT '[]',
    images        TEXT NOT NULL DEFAULT '[]',
    badge         TEXT NOT NULL DEFAULT '',
    badge_tone    TEXT NOT NULL DEFAULT 'pink',
    accent        TEXT NOT NULL DEFAULT 'pink',
    icon          TEXT NOT NULL DEFAULT '',
    is_quote      INTEGER NOT NULL DEFAULT 0,
    is_active     INTEGER NOT NULL DEFAULT 1,
    sort          INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS variants (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    label      TEXT NOT NULL,
    price      INTEGER NOT NULL DEFAULT 0,
    price_max  INTEGER,
    stock      INTEGER,
    sold       INTEGER NOT NULL DEFAULT 0,
    sort       INTEGER NOT NULL DEFAULT 0,
    is_active  INTEGER NOT NULL DEFAULT 1,
    UNIQUE (product_id, label)
);

CREATE TABLE IF NOT EXISTS orders (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    code           TEXT NOT NULL UNIQUE,
    customer_name  TEXT NOT NULL,
    customer_phone TEXT NOT NULL,
    note           TEXT NOT NULL DEFAULT '',
    payment        TEXT NOT NULL DEFAULT '',
    status         TEXT NOT NULL DEFAULT 'baru',
    source         TEXT NOT NULL DEFAULT 'web',
    total          INTEGER NOT NULL DEFAULT 0,
    item_count     INTEGER NOT NULL DEFAULT 0,
    admin_note     TEXT NOT NULL DEFAULT '',
    created_at     TEXT NOT NULL,
    updated_at     TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_orders_status  ON orders(status);

CREATE TABLE IF NOT EXISTS order_items (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id      INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id    INTEGER NOT NULL,
    product_name  TEXT NOT NULL,
    variant_id    INTEGER NOT NULL,
    variant_label TEXT NOT NULL,
    unit_price    INTEGER NOT NULL DEFAULT 0,
    price_max     INTEGER,
    qty           INTEGER NOT NULL DEFAULT 1,
    subtotal      INTEGER NOT NULL DEFAULT 0,
    category_name TEXT NOT NULL DEFAULT ''
);

CREATE INDEX IF NOT EXISTS idx_items_order ON order_items(order_id);

CREATE TABLE IF NOT EXISTS admin_users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    actor      TEXT NOT NULL DEFAULT '',
    action     TEXT NOT NULL,
    detail     TEXT NOT NULL DEFAULT '',
    ip_hash    TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_log(created_at DESC);

CREATE TABLE IF NOT EXISTS throttle (
    key          TEXT PRIMARY KEY,
    count        INTEGER NOT NULL DEFAULT 0,
    window_start INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS backgrounds (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    filename      TEXT NOT NULL UNIQUE,
    original_name TEXT NOT NULL DEFAULT '',
    mime          TEXT NOT NULL DEFAULT '',
    size          INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT NOT NULL,
    is_active     INTEGER NOT NULL DEFAULT 0
);
SQL);

// --------------------------------------------------------------------- katalog
$cats = [
    ['ai',        'AI & Bot',                 '🤖', 10],
    ['design',    'Desain & Editing',         '🎨', 20],
    ['game',      'Top Up Game',              '🎮', 30],
    ['study',     'Produktivitas & Edu',      '📚', 40],
    ['joki',      'Joki Tugas',               '📝', 50],
    ['streaming', 'Streaming Film',           '🎬', 60],
    ['utility',   'VPN & Utilitas',           '🛡️', 70],
    ['handmade',  'Hand Made',                '✨', 80],
];

$catStmt = $pdo->prepare(
    'INSERT INTO categories (id, name, emoji, sort) VALUES (?, ?, ?, ?)
     ON CONFLICT(id) DO UPDATE SET name = excluded.name, emoji = excluded.emoji, sort = excluded.sort'
);
foreach ($cats as $c) {
    $catStmt->execute($c);
}

$seedJson = file_get_contents(BBY_APP . '/data/products.json');
if ($seedJson === false) {
    fwrite(STDERR, "Katalog app/data/products.json tidak ditemukan.\n");
    exit(1);
}
$seed = json_decode($seedJson, true);
if (!is_array($seed)) {
    fwrite(STDERR, "Katalog JSON tidak valid.\n");
    exit(1);
}

$prodStmt = $pdo->prepare(
    'INSERT INTO products
        (id, category_id, category_name, name, subtext, description, features, images,
         badge, badge_tone, accent, icon, is_quote, sort)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
     ON CONFLICT(id) DO UPDATE SET
        category_id   = excluded.category_id,
        category_name = excluded.category_name,
        name          = excluded.name,
        subtext       = excluded.subtext,
        description   = excluded.description,
        features      = excluded.features,
        images        = excluded.images,
        badge         = excluded.badge,
        badge_tone    = excluded.badge_tone,
        accent        = excluded.accent,
        icon          = excluded.icon,
        is_quote      = excluded.is_quote,
        sort          = excluded.sort'
);

// Harga tidak ditimpa saat seed ulang: admin mungkin sudah menyesuaikannya
// lewat dashboard. Hanya label/urutan yang disinkronkan.
$varStmt = $pdo->prepare(
    'INSERT INTO variants (product_id, label, price, price_max, sort)
     VALUES (?,?,?,?,?)
     ON CONFLICT(product_id, label) DO UPDATE SET
        sort = excluded.sort'
);

$pdo->beginTransaction();
try {
    foreach ($seed as $p) {
        $prodStmt->execute([
            $p['id'],
            $p['category'],
            $p['category_name'],
            $p['name'],
            $p['subtext'],
            $p['description'],
            json_encode($p['features'], JSON_UNESCAPED_UNICODE),
            json_encode($p['images'], JSON_UNESCAPED_UNICODE),
            $p['badge'],
            $p['badge_tone'],
            $p['accent'],
            $p['icon'],
            $p['is_quote'],
            $p['sort'],
        ]);
        $sort = 0;
        foreach ($p['variants'] as $v) {
            $varStmt->execute([$p['id'], $v['label'], $v['price'], $v['price_max'], $sort++]);
        }
    }
    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    fwrite(STDERR, "Seed gagal: {$ex->getMessage()}\n");
    exit(1);
}

// -------------------------------------------------------------------- settings
$defaults = [
    'store_name'       => 'Bbylaa Store',
    'store_tagline'    => 'APK Premium, Joki Tugas & Top Up Game',
    'admin_whatsapp'   => '6282318613826',
    'wa_channel_url'   => 'https://whatsapp.com/channel/0029VbCfTrxI7BeNl8xXBw1f',
    'testi_url'        => 'https://t.me/testinabilastore',
    'marquee_text'     => 'welcome to bbylaa store ~ Happy Shopping, Trusted 100% ~ Proses Cepat & Full Garansi SnK',
    'store_closed_msg' => 'Layanan ini sedang tutup. Silakan cek kategori lain atau hubungi admin.',
    'payments'         => 'QRIS,DANA / E-Wallet,Transfer Bank',
];
$pdo->exec("DELETE FROM settings WHERE key = 'default_theme'");

$setStmt = $pdo->prepare(
    'INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO NOTHING'
);
foreach ($defaults as $k => $v) {
    $setStmt->execute([$k, $v]);
}

// ------------------------------------------------------------------ background
$bgFile = BBY_ROOT . '/public/assets/img/backgrounds/bby-default.jpeg';
if (is_file($bgFile)) {
    $pdo->prepare(
        'INSERT OR IGNORE INTO backgrounds (filename, original_name, mime, size, created_at, is_active)
         VALUES (?, ?, ?, ?, ?, 1)'
    )->execute(['bby-default.jpeg', 'bg.jpeg', 'image/jpeg', (int) filesize($bgFile), now()]);
}
if (!(int) $pdo->query('SELECT COUNT(*) FROM backgrounds WHERE is_active = 1')->fetchColumn()) {
    $pdo->exec('UPDATE backgrounds SET is_active = 1 WHERE id = (SELECT MIN(id) FROM backgrounds)');
}

// ------------------------------------------------------------------ admin awal
$username = getenv('BBY_ADMIN_USER') ?: 'admin';
$password = getenv('BBY_ADMIN_PASS') ?: '';

$hasAdmin = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
if (!$hasAdmin) {
    if ($password === '') {
        $password = bin2hex(random_bytes(9));
        $generated = $password;
    }
    $pdo->prepare('INSERT INTO admin_users (username, password_hash, created_at) VALUES (?,?,?)')
        ->execute([$username, password_hash($password, PASSWORD_ARGON2ID), now()]);

    echo "Admin dibuat → username: {$username}\n";
    if (isset($generated)) {
        echo "Password sementara: {$generated}\n";
        echo "SEGERA ganti lewat /admin/akun setelah login.\n";
    }
}

$counts = [
    'produk'  => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'varian'  => (int) $pdo->query('SELECT COUNT(*) FROM variants')->fetchColumn(),
    'kategori'=> (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'pesanan' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
];

echo "Skema siap. " . json_encode($counts) . "\n";
echo "Database: " . env('BBY_DB', BBY_DATA . '/bbylaa.sqlite') . "\n";
