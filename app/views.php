<?php
/**
 * Bbylaa Store — view renderer sederhana (tanpa framework).
 */

declare(strict_types=1);

function view(string $template, array $data = []): string
{
    $file = BBY_APP . '/views/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View tidak ditemukan: {$template}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

function layout(string $template, array $data = []): string
{
    $data['content'] = view($template, $data);
    return view('layout', $data);
}

/** Layout admin (sidebar + header terpisah dari layout publik). */
function admin_layout(string $template, array $data = []): string
{
    $data['content'] = view($template, $data);
    return view('admin/layout', $data);
}

/** Kelas Tailwind untuk aksen warna produk (harus literal agar terbaca scanner). */
function accent_classes(string $accent): array
{
    // Setiap entri: gradien latar + warna dasar solid.
    // Warna dasar WAJIB >= 4.5:1 terhadap teks putih (badge 10px butuh AA penuh),
    // jadi keluarga kuning/oranye memakai langkah 800 — bukan 500/600 yang gagal.
    static $map = [
        'pink'    => ['grad' => 'bg-pink-700 from-pink-600 to-rose-500',          'chip' => 'bg-pink-50 text-pink-800 ring-pink-200',          'text' => 'text-pink-700'],
        'rose'    => ['grad' => 'bg-rose-700 from-rose-600 to-red-500',           'chip' => 'bg-rose-50 text-rose-800 ring-rose-200',          'text' => 'text-rose-700'],
        'red'     => ['grad' => 'bg-red-700 from-red-600 to-rose-600',            'chip' => 'bg-red-50 text-red-800 ring-red-200',             'text' => 'text-red-700'],
        'orange'  => ['grad' => 'bg-orange-800 from-orange-700 to-amber-600',     'chip' => 'bg-orange-50 text-orange-900 ring-orange-200',    'text' => 'text-orange-800'],
        'amber'   => ['grad' => 'bg-amber-800 from-amber-700 to-yellow-600',      'chip' => 'bg-amber-50 text-amber-900 ring-amber-200',       'text' => 'text-amber-800'],
        'yellow'  => ['grad' => 'bg-yellow-800 from-yellow-700 to-amber-600',     'chip' => 'bg-yellow-50 text-yellow-900 ring-yellow-200',    'text' => 'text-yellow-800'],
        'emerald' => ['grad' => 'bg-emerald-700 from-emerald-600 to-teal-600',    'chip' => 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'text' => 'text-emerald-700'],
        'teal'    => ['grad' => 'bg-teal-700 from-teal-600 to-emerald-700',       'chip' => 'bg-teal-50 text-teal-800 ring-teal-200',          'text' => 'text-teal-700'],
        'cyan'    => ['grad' => 'bg-cyan-700 from-cyan-600 to-sky-600',           'chip' => 'bg-cyan-50 text-cyan-800 ring-cyan-200',          'text' => 'text-cyan-700'],
        'sky'     => ['grad' => 'bg-sky-700 from-sky-600 to-blue-600',            'chip' => 'bg-sky-50 text-sky-800 ring-sky-200',             'text' => 'text-sky-700'],
        'blue'    => ['grad' => 'bg-blue-700 from-blue-600 to-indigo-600',        'chip' => 'bg-blue-50 text-blue-800 ring-blue-200',          'text' => 'text-blue-700'],
        'indigo'  => ['grad' => 'bg-indigo-700 from-indigo-600 to-violet-600',    'chip' => 'bg-indigo-50 text-indigo-800 ring-indigo-200',    'text' => 'text-indigo-700'],
        'purple'  => ['grad' => 'bg-purple-700 from-purple-600 to-fuchsia-600',   'chip' => 'bg-purple-50 text-purple-800 ring-purple-200',    'text' => 'text-purple-700'],
        'fuchsia' => ['grad' => 'bg-fuchsia-700 from-fuchsia-600 to-pink-600',    'chip' => 'bg-fuchsia-50 text-fuchsia-800 ring-fuchsia-200', 'text' => 'text-fuchsia-700'],
    ];
    return $map[$accent] ?? $map['pink'];
}

/**
 * Warna badge memakai pasangan eksplisit agar kontras teks selalu >= 4.5:1
 * di tema terang MAUPUN gelap (kelas lama `bg-x-100 text-x-700` gagal di dark).
 */
function badge_classes(string $tone): string
{
    static $map = [
        'pink'    => 'bg-pink-100 text-pink-800 dark:bg-pink-500/20 dark:text-pink-200 ring-pink-300/60 dark:ring-pink-400/40',
        'rose'    => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200 ring-rose-300/60 dark:ring-rose-400/40',
        'red'     => 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-200 ring-red-300/60 dark:ring-red-400/40',
        'orange'  => 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200 ring-orange-300/60 dark:ring-orange-400/40',
        'amber'   => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200 ring-amber-300/60 dark:ring-amber-400/40',
        'yellow'  => 'bg-yellow-100 text-yellow-900 dark:bg-yellow-500/20 dark:text-yellow-100 ring-yellow-300/60 dark:ring-yellow-400/40',
        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200 ring-emerald-300/60 dark:ring-emerald-400/40',
        'teal'    => 'bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-200 ring-teal-300/60 dark:ring-teal-400/40',
        'cyan'    => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-200 ring-cyan-300/60 dark:ring-cyan-400/40',
        'sky'     => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200 ring-sky-300/60 dark:ring-sky-400/40',
        'blue'    => 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-200 ring-blue-300/60 dark:ring-blue-400/40',
        'indigo'  => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-200 ring-indigo-300/60 dark:ring-indigo-400/40',
        'purple'  => 'bg-purple-100 text-purple-800 dark:bg-purple-500/20 dark:text-purple-200 ring-purple-300/60 dark:ring-purple-400/40',
        'fuchsia' => 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-500/20 dark:text-fuchsia-200 ring-fuchsia-300/60 dark:ring-fuchsia-400/40',
    ];
    return $map[$tone] ?? $map['pink'];
}

function status_classes(string $status): string
{
    return match ($status) {
        'baru'     => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200 ring-sky-300/60 dark:ring-sky-400/40',
        'diproses' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200 ring-amber-300/60 dark:ring-amber-400/40',
        'selesai'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200 ring-emerald-300/60 dark:ring-emerald-400/40',
        'batal'    => 'bg-slate-200 text-slate-800 dark:bg-slate-600/40 dark:text-slate-200 ring-slate-300/60 dark:ring-slate-500/40',
        default    => 'bg-slate-200 text-slate-800 dark:bg-slate-600/40 dark:text-slate-200 ring-slate-300/60 dark:ring-slate-500/40',
    };
}

function fmt_date(?string $sql): string
{
    if (!$sql) {
        return '-';
    }
    try {
        return (new DateTimeImmutable($sql, new DateTimeZone('Asia/Makassar')))
            ->format('d M Y, H:i');
    } catch (Throwable) {
        return (string) $sql;
    }
}

function sitemap_xml(): string
{
    $base = base_url();
    $urls = [
        ['loc' => $base . '/', 'prio' => '1.0'],
        ['loc' => $base . '/lacak', 'prio' => '0.3'],
    ];
    foreach (products_all() as $p) {
        $cat = category_map()[$p['category_id']] ?? null;
        if (!$cat || !(int) $cat['is_open']) {
            continue;
        }
        $urls[] = ['loc' => $base . '/produk?id=' . $p['id'], 'prio' => '0.8'];
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . e($u['loc']) . '</loc><priority>' . $u['prio'] . "</priority></url>\n";
    }
    return $xml . '</urlset>' . "\n";
}
