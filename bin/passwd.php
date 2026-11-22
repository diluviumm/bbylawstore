<?php
/**
 * Utilitas: ganti password admin dari server (tanpa web).
 *
 * Pemakaian:
 *   php bin/passwd.php                    # interaktif
 *   php bin/passwd.php admin rahasia123   # langsung (hati-hati riwayat shell)
 *   php bin/passwd.php --gen              # buat password acak
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$args = array_slice($argv, 1);

if (in_array('--gen', $args, true)) {
    $password = bin2hex(random_bytes(9));
    $username = 'admin';
    $has = db()->prepare('SELECT id FROM admin_users WHERE username = ?');
    $has->execute([$username]);
    $id = $has->fetchColumn();

    if ($id) {
        db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_ARGON2ID), $id]);
    } else {
        db()->prepare('INSERT INTO admin_users (username, password_hash, created_at) VALUES (?,?,?)')
            ->execute([$username, password_hash($password, PASSWORD_ARGON2ID), now()]);
    }

    echo "Username: {$username}\n";
    echo "Password baru: {$password}\n";
    echo "Simpan di tempat aman dan ganti lewat /admin/akun setelah login.\n";
    exit(0);
}

$username = $args[0] ?? null;
$password = $args[1] ?? null;

if ($username === null) {
    fwrite(STDOUT, 'Username admin [admin]: ');
    $line = trim((string) fgets(STDIN));
    $username = $line !== '' ? $line : 'admin';
}

if ($password === null) {
    fwrite(STDOUT, 'Password baru (min 10 karakter): ');
    $password = trim((string) fgets(STDIN));
}

if (strlen((string) $password) < 10) {
    fwrite(STDERR, "Password minimal 10 karakter.\n");
    exit(1);
}

$st = db()->prepare('SELECT id FROM admin_users WHERE username = ?');
$st->execute([$username]);
$id = $st->fetchColumn();

if ($id) {
    db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash((string) $password, PASSWORD_ARGON2ID), $id]);
    echo "Password untuk '{$username}' diperbarui.\n";
} else {
    db()->prepare('INSERT INTO admin_users (username, password_hash, created_at) VALUES (?,?,?)')
        ->execute([$username, password_hash((string) $password, PASSWORD_ARGON2ID), now()]);
    echo "Admin '{$username}' dibuat.\n";
}
