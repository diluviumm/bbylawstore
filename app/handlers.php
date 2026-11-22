<?php
/**
 * Handler HTTP: keranjang, checkout, lacak pesanan, dan seluruh aksi admin.
 * Dimuat oleh public/index.php.
 */

declare(strict_types=1);

// ------------------------------------------------------------------ helpers
function post_str(string $key, int $max = 500): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) {
        return '';
    }
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '');
    return mb_substr($v, 0, $max);
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? null;
    return is_numeric($v) ? (int) $v : $default;
}

function wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($accept, 'application/json') || strtolower($xrw) === 'xmlhttprequest';
}

// ------------------------------------------------------------------ keranjang
function handle_cart_add(): never
{
    $vid = post_int('variant_id');
    $qty = max(1, min(20, post_int('qty', 1)));

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

    if (!$product || !$variant) {
        json_response(['ok' => false, 'error' => 'Produk tidak ditemukan.'], 404);
    }
    $cat = category_map()[$product['category_id']] ?? null;
    if (!$cat || !(int) $cat['is_open']) {
        json_response(['ok' => false, 'error' => 'Kategori ini sedang tutup.'], 409);
    }
    if (!variant_available($variant)) {
        json_response(['ok' => false, 'error' => 'Stok paket ini sedang habis.'], 409);
    }

    $state = cart_add($vid, $qty);
    json_response([
        'ok'      => true,
        'message' => $product['name'] . ' ditambahkan ke keranjang.',
        'count'   => $state['count'],
        'total'   => $state['total'],
    ]);
}

function handle_cart_set(): never
{
    $vid = post_int('variant_id');
    $qty = post_int('qty', 0);
    $state = cart_set_qty($vid, $qty);
    json_response(['ok' => true, 'count' => $state['count'], 'total' => $state['total']]);
}

function handle_cart_remove(): never
{
    $vid = post_int('variant_id');
    $state = cart_remove($vid);
    json_response(['ok' => true, 'count' => $state['count'], 'total' => $state['total']]);
}

// ------------------------------------------------------------------- checkout
/**
 * Inti perbaikan: pesanan disimpan ke database SEBELUM pembeli dikirim ke
 * WhatsApp. Harga, stok, dan status kategori divalidasi ulang dari server —
 * bukan dari apa pun yang dikirim browser.
 */
function handle_checkout(): never
{
    // Batas per IP, bukan per orang: pembeli di wifi kampus/kantor bisa berbagi
    // satu IP publik. 30/menit tetap memblokir skrip, tanpa menghukum pembeli sah.
    if (!throttle('checkout', 30, 60)) {
        json_response(['ok' => false, 'error' => 'Terlalu banyak percobaan checkout. Tunggu sebentar.'], 429);
    }

    $name  = post_str('name', 80);
    $phoneRaw = post_str('phone', 30);
    $note  = post_str('note', 400);
    $payment = post_str('payment', 40);
    $gameId  = post_str('game_id', 120);

    $errors = [];
    if (mb_strlen($name) < 2) {
        $errors['name'] = 'Nama minimal 2 karakter.';
    }
    $phone = normalize_phone($phoneRaw);
    if ($phone === null) {
        $errors['phone'] = 'Nomor WhatsApp tidak valid (contoh: 0821xxxxxxxx).';
    }

    $allowedPayments = array_filter(array_map('trim', explode(',', (string) setting('payments', 'QRIS,DANA / E-Wallet,Transfer Bank'))));
    if ($payment !== '' && !in_array($payment, $allowedPayments, true)) {
        $payment = $allowedPayments ? reset($allowedPayments) : '';
    }

    $state = cart_lines();
    if (!$state['lines']) {
        $errors['cart'] = 'Keranjang kosong atau semua item sudah tidak tersedia.';
    }

    // Data akun game wajib untuk produk game.
    $needsGameId = false;
    foreach ($state['lines'] as $l) {
        $p = product_by_id((int) $l['product_id']);
        if ($p && $p['category_id'] === 'game') {
            $needsGameId = true;
        }
    }
    if ($needsGameId && mb_strlen($gameId) < 3) {
        $errors['game_id'] = 'ID game / username wajib diisi untuk top up.';
    }

    if ($errors) {
        json_response(['ok' => false, 'error' => reset($errors), 'errors' => $errors], 422);
    }

    $noteParts = [];
    if ($gameId !== '') {
        $noteParts[] = 'Data Game: ' . $gameId;
    }
    if ($note !== '') {
        $noteParts[] = $note;
    }
    $finalNote = implode(' | ', $noteParts);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $code = order_code();
        // Pastikan kode unik (retry maksimal 5x).
        for ($i = 0; $i < 5; $i++) {
            $exists = $pdo->prepare('SELECT 1 FROM orders WHERE code = ?');
            $exists->execute([$code]);
            if (!$exists->fetchColumn()) {
                break;
            }
            $code = order_code();
        }

        $st = $pdo->prepare(
            'INSERT INTO orders (code, customer_name, customer_phone, note, payment, status, source, total, item_count, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $code, $name, $phone, $finalNote, $payment, 'baru', 'web',
            $state['total'], $state['count'], now(), now(),
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $insItem = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, variant_id, variant_label, unit_price, price_max, qty, subtotal, category_name)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        foreach ($state['lines'] as $l) {
            $insItem->execute([
                $orderId, $l['product_id'], $l['name'], $l['variant_id'], $l['variant_label'],
                $l['unit_price'], $l['price_max'], $l['qty'], $l['subtotal'], $l['category_name'],
            ]);

            // Potong stok HANYA untuk varian yang memang berstok terbatas.
            if ($l['stock'] !== null) {
                $dec = $pdo->prepare(
                    'UPDATE variants SET stock = stock - ?, sold = sold + ?
                     WHERE id = ? AND stock IS NOT NULL AND stock >= ?'
                );
                $dec->execute([$l['qty'], $l['qty'], $l['variant_id'], $l['qty']]);
                if ($dec->rowCount() === 0) {
                    throw new RuntimeException('Stok ' . $l['name'] . ' habis saat checkout.');
                }
            } else {
                $pdo->prepare('UPDATE variants SET sold = sold + ? WHERE id = ?')
                    ->execute([$l['qty'], $l['variant_id']]);
            }
        }

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        error_log('[bbylaa] checkout gagal: ' . $ex->getMessage());
        json_response(['ok' => false, 'error' => 'Pesanan gagal disimpan. Coba lagi atau hubungi admin.'], 500);
    }

    cart_clear();
    audit('order.create', $code . ' • ' . rupiah($state['total']) . ' • ' . $state['count'] . ' item', $name);

    // Pesan WhatsApp mengikuti data yang BARU disimpan, bukan input browser.
    $order = order_find($code);
    $waText = wa_message($order);

    json_response([
        'ok'       => true,
        'code'     => $code,
        'total'    => $state['total'],
        'redirect' => '/pesanan?kode=' . urlencode($code),
        'wa'       => 'https://wa.me/' . wa_number((string) setting('admin_whatsapp', '6282318613826'))
                      . '?text=' . rawurlencode($waText),
    ]);
}

function wa_message(array $order): string
{
    $lines = [];
    $lines[] = 'Halo Admin Bbylaa Store, saya baru saja checkout di website.';
    $lines[] = '';
    $lines[] = '*Kode Pesanan:* ' . $order['code'];
    $lines[] = '';
    $lines[] = '*Rincian Pesanan:*';
    foreach ($order['items'] as $i => $it) {
        $harga = $it['price_max'] !== null
            ? rupiah((int) $it['unit_price']) . ' – ' . rupiah((int) $it['price_max'])
            : rupiah((int) $it['unit_price']);
        $lines[] = ($i + 1) . '. ' . $it['product_name'];
        $lines[] = '   Paket: ' . $it['variant_label'];
        $lines[] = '   ' . $it['qty'] . 'x @ ' . $harga;
        $lines[] = '   Subtotal: ' . rupiah((int) $it['subtotal']);
    }
    $lines[] = '';
    $lines[] = '*Total:* ' . rupiah((int) $order['total']);
    $lines[] = '';
    $lines[] = '*Data Pemesan:*';
    $lines[] = '- Nama: ' . $order['customer_name'];
    $lines[] = '- WA: ' . $order['customer_phone'];
    if (!empty($order['payment'])) {
        $lines[] = '- Pembayaran: ' . $order['payment'];
    }
    if (!empty($order['note'])) {
        $lines[] = '- Catatan: ' . $order['note'];
    }
    $lines[] = '';
    $lines[] = 'Pesanan ini sudah tercatat di sistem. Mohon info instruksi pembayarannya ya admin. Terima kasih!';
    return implode("\n", $lines);
}

function handle_track(): never
{
    if (!throttle('track', 40, 60)) {
        flash('error', 'Terlalu banyak pencarian. Tunggu sebentar.');
        redirect('/lacak');
    }
    $code = strtoupper(post_str('code', 30));
    if ($code === '') {
        flash('error', 'Masukkan kode pesanan.');
        redirect('/lacak');
    }
    redirect('/lacak?kode=' . urlencode($code));
}

// ---------------------------------------------------------------------- admin
function handle_admin_login(): never
{
    if (!throttle('admin_login', 8, 300)) {
        flash('error', 'Terlalu banyak percobaan login. Coba lagi dalam 5 menit.');
        redirect('/admin/login');
    }

    $username = post_str('username', 60);
    $password = (string) ($_POST['password'] ?? '');

    $st = db()->prepare('SELECT * FROM admin_users WHERE username = ?');
    $st->execute([$username]);
    $user = $st->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        audit('admin.login.fail', $username, 'guest');
        flash('error', 'Username atau password salah.');
        redirect('/admin/login');
    }

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_ARGON2ID)) {
        db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_ARGON2ID), $user['id']]);
    }

    start_session();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    throttle_reset('admin_login');
    audit('admin.login.ok', $username, $username);

    redirect('/admin/pesanan');
}

function handle_admin_logout(): never
{
    start_session();
    $who = admin_user()['username'] ?? 'unknown';
    $_SESSION = [];
    session_destroy();
    audit('admin.logout', '', $who);
    redirect('/admin/login');
}

function handle_admin_status(): never
{
    $admin = require_admin();
    if (!wants_json()) {
        http_response_code(415);
        json_response(['ok' => false, 'error' => 'Butuh X-Requested-With.'], 415);
    }

    $id = post_int('order_id');
    $status = post_str('status', 20);
    if (!isset(ORDER_STATUSES[$status])) {
        json_response(['ok' => false, 'error' => 'Status tidak dikenal.'], 422);
    }

    $st = db()->prepare('SELECT code, status FROM orders WHERE id = ?');
    $st->execute([$id]);
    $order = $st->fetch();
    if (!$order) {
        json_response(['ok' => false, 'error' => 'Pesanan tidak ditemukan.'], 404);
    }

    db()->prepare('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?')
        ->execute([$status, now(), $id]);
    audit('order.status', $order['code'] . ': ' . $order['status'] . ' → ' . $status, $admin['username']);

    json_response(['ok' => true, 'status' => $status, 'label' => ORDER_STATUSES[$status]]);
}

function handle_admin_note(): never
{
    $admin = require_admin();
    $id = post_int('order_id');
    $note = post_str('admin_note', 500);

    $st = db()->prepare('SELECT code FROM orders WHERE id = ?');
    $st->execute([$id]);
    if (!$st->fetchColumn()) {
        flash('error', 'Pesanan tidak ditemukan.');
        redirect('/admin/pesanan');
    }

    db()->prepare('UPDATE orders SET admin_note = ?, updated_at = ? WHERE id = ?')
        ->execute([$note, now(), $id]);
    audit('order.note', (string) $id, $admin['username']);
    flash('ok', 'Catatan internal disimpan.');
    redirect('/admin/pesanan/' . $id);
}

function handle_admin_category(): never
{
    $admin = require_admin();
    $id = post_str('category_id', 40);
    $open = post_int('is_open') === 1 ? 1 : 0;

    $st = db()->prepare('SELECT name FROM categories WHERE id = ?');
    $st->execute([$id]);
    $name = $st->fetchColumn();
    if ($name === false) {
        json_response(['ok' => false, 'error' => 'Kategori tidak ditemukan.'], 404);
    }

    db()->prepare('UPDATE categories SET is_open = ? WHERE id = ?')->execute([$open, $id]);
    audit('category.toggle', $name . ' → ' . ($open ? 'buka' : 'tutup'), $admin['username']);

    json_response(['ok' => true, 'is_open' => $open, 'label' => $open ? 'Buka' : 'Tutup']);
}

function handle_admin_product_save(): never
{
    $admin = require_admin();
    $id = post_int('product_id');
    $product = product_by_id($id, true);
    if (!$product) {
        flash('error', 'Produk tidak ditemukan.');
        redirect('/admin/produk');
    }

    $name = post_str('name', 120);
    if (mb_strlen($name) < 2) {
        flash('error', 'Nama produk minimal 2 karakter.');
        redirect('/admin/produk?edit=' . $id);
    }

    db()->prepare('UPDATE products SET name = ?, badge = ?, is_active = ? WHERE id = ?')
        ->execute([$name, post_str('badge', 40), post_int('is_active') === 1 ? 1 : 0, $id]);

    // Harga per varian: hanya field yang dikirim yang diubah.
    $prices = $_POST['price'] ?? [];
    if (is_array($prices)) {
        $upd = db()->prepare('UPDATE variants SET price = ?, price_max = ? WHERE id = ? AND product_id = ?');
        foreach ($prices as $vid => $price) {
            $vid = (int) $vid;
            if ($vid <= 0) {
                continue;
            }
            $p = max(0, (int) $price);
            $maxKey = 'price_max_' . $vid;
            $max = isset($_POST[$maxKey]) && $_POST[$maxKey] !== '' ? max(0, (int) $_POST[$maxKey]) : null;
            if ($max !== null && $max < $p) {
                $max = $p;
            }
            $upd->execute([$p, $max, $vid, $id]);
        }
    }

    audit('product.update', $product['name'] . ' → ' . $name, $admin['username']);
    flash('ok', 'Produk diperbarui.');
    redirect('/admin/produk?edit=' . $id);
}

function handle_admin_stock(): never
{
    $admin = require_admin();
    $vid = post_int('variant_id');
    $raw = post_str('stock', 20);

    $st = db()->prepare(
        'SELECT v.id, v.label, p.name FROM variants v
         JOIN products p ON p.id = v.product_id WHERE v.id = ?'
    );
    $st->execute([$vid]);
    $row = $st->fetch();
    if (!$row) {
        json_response(['ok' => false, 'error' => 'Varian tidak ditemukan.'], 404);
    }

    // Kosong = stok tak terbatas (digital). Angka = batas stok.
    $stock = ($raw === '' || strtolower($raw) === 'unlimited') ? null : max(0, (int) $raw);
    db()->prepare('UPDATE variants SET stock = ? WHERE id = ?')->execute([$stock, $vid]);
    audit('variant.stock', $row['name'] . ' / ' . $row['label'] . ' = ' . ($stock ?? 'unlimited'), $admin['username']);

    json_response([
        'ok'    => true,
        'stock' => $stock,
        'label' => $stock === null ? 'Tersedia' : ($stock > 0 ? $stock . ' tersisa' : 'Habis'),
    ]);
}

function handle_admin_settings(): never
{
    $admin = require_admin();
    $fields = ['store_name', 'store_tagline', 'admin_whatsapp', 'wa_channel_url', 'testi_url', 'marquee_text', 'store_closed_msg'];

    $theme = post_str('default_theme', 10);
    if (in_array($theme, ['light', 'dark'], true)) {
        setting_set('default_theme', $theme);
    }

    foreach ($fields as $f) {
        $v = post_str($f, 400);
        if ($f === 'admin_whatsapp') {
            $n = normalize_phone($v);
            if ($n === null) {
                flash('error', 'Nomor WhatsApp admin tidak valid.');
                redirect('/admin/pengaturan');
            }
            $v = wa_number($n);
        }
        setting_set($f, $v);
    }

    audit('settings.update', implode(',', $fields), $admin['username']);
    flash('ok', 'Pengaturan disimpan.');
    redirect('/admin/pengaturan');
}

function handle_admin_account(): never
{
    $admin = require_admin();
    start_session();

    $current = (string) ($_POST['current'] ?? '');
    $new     = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $st = db()->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
    $st->execute([(int) $admin['id']]);
    $hash = (string) $st->fetchColumn();

    if (!password_verify($current, $hash)) {
        flash('error', 'Password saat ini salah.');
        redirect('/admin/akun');
    }
    if (strlen($new) < 10) {
        flash('error', 'Password baru minimal 10 karakter.');
        redirect('/admin/akun');
    }
    if ($new !== $confirm) {
        flash('error', 'Konfirmasi password tidak sama.');
        redirect('/admin/akun');
    }

    db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($new, PASSWORD_ARGON2ID), (int) $admin['id']]);
    session_regenerate_id(true);
    audit('admin.password', '', $admin['username']);
    flash('ok', 'Password admin diperbarui.');
    redirect('/admin/akun');
}
