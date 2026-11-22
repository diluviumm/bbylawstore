<?php
/**
 * Render halaman publik + admin. Setiap fungsi di sini mengembalikan void dan
 * mencetak HTML; dipanggil dari router.
 */

declare(strict_types=1);

// ------------------------------------------------------------------- publik
function render_store(): void
{
    $cats = categories_all();
    $products = products_all();
    $q = trim((string) ($_GET['q'] ?? ''));
    $cat = trim((string) ($_GET['kategori'] ?? ''));
    $theme = theme_from_request();

    $openMap = [];
    foreach ($cats as $c) {
        $openMap[$c['id']] = (int) $c['is_open'] === 1;
    }

    $filtered = [];
    foreach ($products as $p) {
        if ($cat !== '' && $p['category_id'] !== $cat) {
            continue;
        }
        if ($q !== '') {
            $hay = mb_strtolower($p['name'] . ' ' . $p['subtext'] . ' ' . $p['description'] . ' ' . $p['category_name']);
            if (!str_contains($hay, mb_strtolower($q))) {
                continue;
            }
        }
        $filtered[] = $p;
    }

    $cart = cart_lines();

    echo layout('store', [
        'title'    => $cat !== '' ? (category_map()[$cat]['name'] ?? 'Katalog') : 'Katalog',
        'desc'     => 'Katalog Bbylaa Store: APK premium, top up game, joki tugas, streaming, dan handmade.',
        'cats'     => $cats,
        'products' => $filtered,
        'openMap'  => $openMap,
        'q'        => $q,
        'activeCat'=> $cat,
        'cart'     => $cart,
        'theme'    => $theme,
    ]);
}

function render_product(int $id): void
{
    $product = product_by_id($id);
    if (!$product) {
        http_response_code(404);
        echo layout('errors/404', ['title' => 'Produk tidak ditemukan', 'theme' => theme_from_request()]);
        return;
    }
    $cat = category_map()[$product['category_id']] ?? null;
    $isOpen = $cat && (int) $cat['is_open'] === 1;

    echo layout('product', [
        'title'    => $product['name'],
        'desc'     => $product['description'],
        'product'  => $product,
        'cat'      => $cat,
        'isOpen'   => $isOpen,
        'cart'     => cart_lines(),
        'theme'    => theme_from_request(),
    ]);
}

function render_cart(): void
{
    // Endpoint state keranjang untuk JS (dipakai setelah setiap mutasi).
    if (($_GET['json'] ?? '') === '1') {
        $state = cart_lines();
        json_response([
            'ok'    => true,
            'count' => $state['count'],
            'total' => $state['total'],
            'lines' => array_map(static fn(array $l): array => [
                'variant_id'    => $l['variant_id'],
                'name'          => $l['name'],
                'variant_label' => $l['variant_label'],
                'qty'           => $l['qty'],
                'subtotal'      => $l['subtotal'],
                'stock'         => $l['stock'],
                'icon'          => $l['icon'],
                'grad'          => accent_classes($l['accent'])['grad'],
            ], $state['lines']),
        ]);
    }

    echo layout('cart', [
        'title' => 'Keranjang',
        'desc'  => 'Keranjang belanja Bbylaa Store.',
        'cart'  => cart_lines(),
        'theme' => theme_from_request(),
    ]);
}

function render_checkout(): void
{
    $cart = cart_lines();
    if (!$cart['lines']) {
        flash('error', 'Keranjang kamu masih kosong.');
        redirect('/keranjang');
    }

    $hasGame = false;
    foreach ($cart['lines'] as $l) {
        if ($l['category_id'] === 'game') {
            $hasGame = true;
        }
    }
    echo layout('checkout', [
        'title'   => 'Checkout',
        'desc'    => 'Konfirmasi data pemesan dan selesaikan pesanan.',
        'cart'    => $cart,
        'hasGame' => $hasGame,
        'theme'   => theme_from_request(),
    ]);
}

function render_track(): void
{
    $code = strtoupper(trim((string) ($_GET['kode'] ?? '')));
    $order = $code !== '' ? order_find($code) : null;

    echo layout('track', [
        'title' => 'Lacak Pesanan',
        'desc'  => 'Lacak status pesanan Bbylaa Store dengan kode pesanan.',
        'code'  => $code,
        'order' => $order,
        'theme' => theme_from_request(),
    ]);
}

function render_order_result(): void
{
    $code = strtoupper(trim((string) ($_GET['kode'] ?? '')));
    $order = $code !== '' ? order_find($code) : null;

    echo layout('order-done', [
        'title' => 'Pesanan Diterima',
        'desc'  => 'Konfirmasi pesanan Bbylaa Store.',
        'code'  => $code,
        'order' => $order,
        'theme' => theme_from_request(),
    ]);
}

function theme_from_request(): string
{
    $c = $_COOKIE['bby_theme'] ?? '';
    if ($c === 'dark' || $c === 'light') {
        return $c;
    }
    return setting('default_theme', 'light') === 'dark' ? 'dark' : 'light';
}

// -------------------------------------------------------------------- admin
function render_admin_login(): void
{
    if (admin_user()) {
        redirect('/admin/pesanan');
    }
    echo layout('admin/login', [
        'title' => 'Masuk Admin',
        'desc'  => 'Halaman masuk admin Bbylaa Store.',
        'theme' => theme_from_request(),
    ]);
}

function admin_stats(): array
{
    $pdo = db();
    $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))->format('Y-m-d');

    $total = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $baru = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'baru'")->fetchColumn();
    $omzet = (int) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'batal'")->fetchColumn();
    $st = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE substr(created_at,1,10) = ?");
    $st->execute([$today]);
    $hariIni = (int) $st->fetchColumn();
    $st = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE substr(created_at,1,10) = ? AND status != 'batal'");
    $st->execute([$today]);
    $omzetHariIni = (int) $st->fetchColumn();

    return compact('total', 'baru', 'omzet', 'hariIni', 'omzetHariIni');
}

function render_admin_orders(): void
{
    $admin = require_admin();
    $pdo = db();
    $status = trim((string) ($_GET['status'] ?? ''));
    $q = trim((string) ($_GET['q'] ?? ''));

    $sql = 'SELECT * FROM orders WHERE 1=1';
    $args = [];
    if ($status !== '' && isset(ORDER_STATUSES[$status])) {
        $sql .= ' AND status = ?';
        $args[] = $status;
    }
    if ($q !== '') {
        $sql .= ' AND (code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
        $like = '%' . addcslashes($q, '%_\\') . '%';
        array_push($args, $like, $like, $like);
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 200';

    $st = $pdo->prepare($sql);
    $st->execute($args);
    $orders = $st->fetchAll();

    // Ambil item untuk daftar (hindari N+1: satu query untuk semua order).
    $itemsByOrder = [];
    if ($orders) {
        $ids = array_map(fn($o) => (int) $o['id'], $orders);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($in) ORDER BY id");
        $it->execute($ids);
        foreach ($it->fetchAll() as $row) {
            $itemsByOrder[(int) $row['order_id']][] = $row;
        }
    }

    echo admin_layout('admin/orders', [
        'title'        => 'Pesanan',
        'desc'         => 'Dashboard pesanan Bbylaa Store.',
        'admin'        => $admin,
        'orders'       => $orders,
        'itemsByOrder' => $itemsByOrder,
        'stats'        => admin_stats(),
        'status'       => $status,
        'q'            => $q,
        'theme'        => 'light',
        'nav'          => 'orders',
    ]);
}

function render_admin_order_detail(int $id): void
{
    $admin = require_admin();
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$id]);
    $order = $st->fetch();
    if (!$order) {
        http_response_code(404);
        echo layout('errors/404', ['title' => 'Pesanan tidak ditemukan', 'theme' => 'light']);
        return;
    }
    $st = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $st->execute([$id]);
    $order['items'] = $st->fetchAll();

    echo admin_layout('admin/order-detail', [
        'title' => 'Pesanan ' . $order['code'],
        'desc'  => 'Detail pesanan.',
        'admin' => $admin,
        'order' => $order,
        'theme' => 'light',
        'nav'   => 'orders',
    ]);
}

function render_admin_products(): void
{
    $admin = require_admin();
    $editId = (int) ($_GET['edit'] ?? 0);
    $edit = $editId ? product_by_id($editId, true) : null;

    echo admin_layout('admin/products', [
        'title'    => 'Produk & Stok',
        'desc'     => 'Kelola katalog Bbylaa Store.',
        'admin'    => $admin,
        'products' => products_admin(),
        'cats'     => categories_all(),
        'edit'     => $edit,
        'theme'    => 'light',
        'nav'      => 'products',
    ]);
}

function render_admin_settings(): void
{
    $admin = require_admin();
    echo admin_layout('admin/settings', [
        'title'  => 'Pengaturan',
        'desc'   => 'Pengaturan toko Bbylaa Store.',
        'admin'  => $admin,
        'cats'   => categories_all(),
        'theme'  => 'light',
        'nav'    => 'settings',
    ]);
}

function render_admin_account(): void
{
    $admin = require_admin();
    echo admin_layout('admin/account', [
        'title' => 'Akun Admin',
        'desc'  => 'Ganti password admin.',
        'admin' => $admin,
        'theme' => 'light',
        'nav'   => 'account',
    ]);
}
