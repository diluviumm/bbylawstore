<?php
/**
 * Uji cepat tanpa framework. Jalankan: php tests/run.php
 *
 * Fokus ke fungsi murni yang mudah salah dan pernah jadi akar bug:
 * normalisasi nomor telepon, perhitungan keranjang, dan pembentukan kode.
 */

declare(strict_types=1);

putenv('BBY_DB=' . sys_get_temp_dir() . '/bbylaa-test-' . getmypid() . '.sqlite');
require dirname(__DIR__) . '/app/bootstrap.php';

$fail = 0;
$pass = 0;

function t(string $label, bool $cond, string|int $detail = ""): void
{
    global $fail, $pass;
    if ($cond) {
        $pass++;
        echo "  ok   {$label}\n";
    } else {
        $fail++;
        echo "  FAIL {$label}" . ($detail !== '' ? " :: {$detail}" : '') . "\n";
    }
}

echo "\n[normalize_phone]\n";
$cases = [
    ['08123456789', '08123456789'],
    ['628123456789', '08123456789'],
    ['8123456789', '08123456789'],
    ['+62 812-3456-789', '08123456789'],
    ['  0812 3456 789  ', '08123456789'],
    ['0812', null],
    ['abc', null],
    ['', null],
    ['0812345678901234567890', null],
];
foreach ($cases as [$in, $want]) {
    $got = normalize_phone($in);
    t(sprintf('%-24s → %s', "'{$in}'", var_export($got, true)), $got === $want, "dapat " . var_export($got, true));
}

echo "\n[wa_number]\n";
t('08123456789 → 628123456789', wa_number('08123456789') === '628123456789', wa_number('08123456789'));
t('628123456789 → tetap', wa_number('628123456789') === '628123456789', wa_number('628123456789'));

echo "\n[rupiah]\n";
t('0', rupiah(0) === 'Rp 0', rupiah(0));
t('1500', rupiah(1500) === 'Rp 1.500', rupiah(1500));
t('1250000', rupiah(1250000) === 'Rp 1.250.000', rupiah(1250000));

echo "\n[order_code]\n";
$codes = [];
for ($i = 0; $i < 50; $i++) {
    $c = order_code();
    $codes[$c] = true;
    if (!preg_match('/^BBY-\d{6}-[A-Z0-9]{5}$/', $c)) {
        t('format kode', false, $c);
        break;
    }
}
t('format kode konsisten (50 sampel)', true);
t('tidak ada duplikat dalam 50', count($codes) === 50, count($codes));

echo "\n[variant_available]\n";
t('stok null = tersedia', variant_available(['stock' => null]) === true);
t('stok 0 = habis', variant_available(['stock' => 0]) === false);
t('stok 5 = tersedia', variant_available(['stock' => 5]) === true);

echo "\n[status dikenal]\n";
foreach (['baru', 'diproses', 'selesai', 'batal'] as $s) {
    t("status {$s}", isset(ORDER_STATUSES[$s]));
}
t('status asing ditolak', !isset(ORDER_STATUSES['hapus']));

// bersihkan db uji
@unlink(sys_get_temp_dir() . '/bbylaa-test-' . getmypid() . '.sqlite');

echo "\n=== {$pass} lulus, {$fail} gagal ===\n";
exit($fail ? 1 : 0);
