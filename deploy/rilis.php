<?php
/**
 * Membuat rilis baru (versi PHP dari rilis.sh, tanpa bash/jq — bisa dari Windows).
 * Dijalankan di SALINAN REPOSITORI GitHub (git clone), bukan di folder website/hosting.
 *
 *   php deploy/rilis.php --cek                       daftar berkas yang berubah sejak rilis terakhir
 *   php deploy/rilis.php "Catatan perubahan" [1.9.2] naikkan versi, tanda tangani, commit (lalu: git push)
 *
 * Kunci pribadi: --kunci=lokasi.pem, atau RILIS_KEY, atau otomatis mencari
 *   %USERPROFILE%\satker-rilis\rilis-cadangan-private.pem  lalu  ~/.satker-rilis/rilis-private.pem
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__);
chdir($root);
$git = static function (string $args, ?int &$rc = null): array {
    exec('git ' . $args . ' 2>&1', $out, $rc);

    return $out;
};
$fail = static function (string $m): void {
    fwrite(STDERR, "GAGAL: $m\n");
    exit(1);
};

$git('rev-parse --is-inside-work-tree', $rc);
if ($rc !== 0) {
    $fail('folder ini bukan salinan repositori git. Jalankan deploy\\rilis-dari-pc.bat (mengunduh repositori otomatis).');
}

// argumen
$args = array_slice($argv, 1);
$key  = getenv('RILIS_KEY') ?: '';
$cek  = false;
foreach ($args as $i => $a) {
    if (str_starts_with($a, '--kunci=')) {
        $key = substr($a, 8);
        unset($args[$i]);
    } elseif ($a === '--cek') {
        $cek = true;
        unset($args[$i]);
    }
}
$args = array_values($args);

// berkas berubah sejak rilis terakhir (commit terakhir yang mengubah version.sig) + perubahan yang belum di-commit
$last = trim(implode('', $git('log -1 --format=%H -- version.sig')));
if ($last === '') {
    $fail('rilis sebelumnya (version.sig) tidak ditemukan di riwayat git.');
}
$git('add -A');
$skip    = '#^(\.github/|\.gitignore$|version\.json$|integrity\.json$|version\.sig$|writable/|public/uploads/)#';
$changed = [];
$removed = [];
foreach ($git('diff --cached --name-status -M ' . escapeshellarg($last)) as $line) {
    $p = preg_split('/\t/', $line);
    $st = $p[0][0] ?? '';
    if ($st === 'R') {
        $removed[] = $p[1];
        $changed[] = $p[2];
    } elseif ($st === 'D') {
        $removed[] = $p[1];
    } elseif (isset($p[1])) {
        $changed[] = $p[1];
    }
}
$changed = array_values(array_filter(array_unique($changed), static fn ($f) => ! preg_match($skip, $f)));
$removed = array_values(array_filter(array_unique($removed), static fn ($f) => ! preg_match($skip, $f)));
sort($changed);
sort($removed);

$v = json_decode((string) file_get_contents('version.json'), true);
if (! is_array($v) || empty($v['build'])) {
    $fail('version.json tidak valid.');
}
printf("Versi terakhir: %s (build %d)\n%d berkas berubah, %d dihapus sejak rilis terakhir:\n", $v['name'] ?? '-', $v['build'], count($changed), count($removed));
foreach ($changed as $f) {
    echo "  + $f\n";
}
foreach ($removed as $f) {
    echo "  - $f\n";
}

// pemeriksaan agar rilis pasti bisa dipasang di semua satker (hosting Linux, XAMPP Windows, PHP 8.2)
$upd0 = (string) file_get_contents('app/Libraries/Updater.php');
$lim  = static fn (string $n, int $d) => preg_match('/' . $n . '\s*=\s*([0-9 *]+);/', $upd0, $m) ? (int) array_product(array_map('intval', explode('*', $m[1]))) : $d;
$maxN = $lim('MAX_N', 400);
$maxF = $lim('MAX_FILE', 8 << 20);
$maxA = $lim('MAX_ALL', 60 << 20);
$errs = [];
if (count($changed) > $maxN) {
    $errs[] = count($changed) . " berkas berubah, batas Update Website $maxN berkas per rilis. Pecah menjadi beberapa rilis.";
}
$total = 0;
$lower = [];
foreach ($git('ls-files') as $f) {
    $lower[strtolower($f)][] = $f;
}
foreach ($lower as $same) {
    if (count($same) > 1) {
        $errs[] = 'nama berkas hanya beda huruf besar/kecil (bentrok di Windows): ' . implode(', ', $same);
    }
}
foreach ($changed as $f) {
    if (! preg_match('#^[A-Za-z0-9_\-./ ]+$#', $f) || strlen($f) > 255 || str_contains($f, '..')) {
        $errs[] = "$f: nama berkas memuat karakter yang ditolak Update Website (pakai huruf, angka, - _ . / spasi).";
    }
    $size   = (int) @filesize($f);
    $total += $size;
    if ($size > $maxF) {
        $errs[] = "$f: " . round($size / 1048576, 1) . ' MB, batas per berkas ' . ($maxF >> 20) . ' MB.';
    }
    $body = (string) @file_get_contents($f);
    if (preg_match('/\.php$/i', $f)) {
        $out = [];
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($f) . ' 2>&1', $out, $rc);
        if ($rc !== 0) {
            $errs[] = "$f: galat sintaks PHP — " . trim(implode(' ', $out));
        }
        if (! str_starts_with($f, 'vendor/') && preg_match('/\bconst\s+(?:string|int|array|bool|float|\?\w+)\s+[A-Z_]+\s*=|#\[\\\\?Override\]|\b(?:json_validate|mb_str_pad|str_increment|str_decrement|array_find|array_find_key|array_any|array_all|mb_trim|mb_ltrim|mb_rtrim|mb_ucfirst|mb_lcfirst)\s*\(|\bnew\s+[\w\\\\]+\([^()]*\)\s*->/', $body, $m)) {
            $errs[] = "$f: memakai fitur PHP 8.3+ (\"" . trim($m[0]) . '"), padahal sebagian satker masih PHP 8.2.';
        }
    }
    // tulisan yang sering membuat antivirus menghapus berkas (mis. Windows Defender di XAMPP)
    if (! str_starts_with($f, 'vendor/') && preg_match('/\b(Fil\x65sMan|c99sh\x65ll|r57sh\x65ll|b37\x34k)\b|eval\s*\(\s*(base64_decode|gzinflate|str_rot13)\s*\(/i', $body, $m)) {
        $errs[] = "$f: memuat tulisan \"{$m[0]}\" yang bisa membuat antivirus menghapus berkas ini. Samarkan (mis. \\x65 untuk e di dalam regex).";
    }
}
if ($total > $maxA) {
    $errs[] = 'ukuran total ' . round($total / 1048576, 1) . ' MB melebihi batas Update Website ' . ($maxA >> 20) . ' MB. Pecah menjadi beberapa rilis.';
}
if ($errs) {
    echo "\nMASALAH yang harus dibereskan sebelum rilis:\n  - " . implode("\n  - ", $errs) . "\n";
    if (! $cek) {
        $fail('rilis dibatalkan.');
    }
    exit(1);
}
echo "Pemeriksaan rilis: aman (ukuran, nama berkas, sintaks PHP 8.2, antivirus).\n";
if ($cek) {
    exit(0);
}
if (! $changed && ! $removed) {
    $fail('tidak ada perubahan untuk dirilis. Ambil perubahan terbaru dulu (git pull).');
}

// semua berkas harus bisa dipasang lewat "Update Website" (aturan sama dengan Updater::allowedPath)
$upd = (string) file_get_contents('app/Libraries/Updater.php');
preg_match('/ALLOW_PREFIX = \[(.*?)\];/s', $upd, $mp);
preg_match('/ALLOW_FILE = \[(.*?)\];/s', $upd, $mf);
$pre  = $mp ? array_map(static fn ($s) => trim($s, " '\n\r\t"), explode(',', $mp[1])) : [];
$one  = $mf ? array_map(static fn ($s) => trim($s, " '\n\r\t"), explode(',', $mf[1])) : [];
$bad  = array_filter(array_merge($changed, $removed), static function ($f) use ($pre, $one) {
    foreach ($pre as $p) {
        if ($p !== '' && str_starts_with($f, $p)) {
            return false;
        }
    }

    return ! in_array($f, $one, true);
});
if ($bad) {
    $fail("berkas berikut tidak bisa dipasang lewat Update Website:\n  " . implode("\n  ", $bad));
}

// nomor versi
$note = trim((string) ($args[0] ?? ''));
if (mb_strlen($note) < 15 || ! preg_match('/\s/', $note)) {
    $fail('catatan rilis terlalu singkat. Catatan ini dibaca semua satker di Dashboard, tulis apa yang berubah, mis.: "Perbaikan tampilan berita di HP".');
}
$old  = (string) ($v['name'] ?? '1.0.0');
$name = trim((string) ($args[1] ?? ''));
if ($name === '') {
    $p    = array_map('intval', explode('.', $old) + [0, 0, 0]);
    $name = $p[0] . '.' . $p[1] . '.' . ($p[2] + 1);
}
if (! preg_match('/^\d+\.\d+\.\d+$/', $name)) {
    $fail('nomor versi harus berbentuk 1.2.3');
}
$build = (int) $v['build'] + 1;
$date  = date('Y-m-d');
$rel   = ['build' => $build, 'name' => $name, 'version' => $date, 'notes' => $note, 'files' => $changed];
if ($removed) {
    $rel['removed'] = $removed;
}
$v['build']    = $build;
$v['name']     = $name;
$v['version']  = $date;
$v['releases'] = array_merge([$rel], $v['releases'] ?? []);
$json = json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$json = preg_replace_callback('/^( +)/m', static fn ($m) => str_repeat(' ', intdiv(strlen($m[1]), 2)), $json) . "\n"; // indentasi 2 spasi seperti sebelumnya
file_put_contents('version.json', $json);

// kunci pribadi
if ($key === '') {
    foreach ([getenv('USERPROFILE') ? getenv('USERPROFILE') . '/satker-rilis/rilis-cadangan-private.pem' : '',
        getenv('HOME') ? getenv('HOME') . '/.satker-rilis/rilis-private.pem' : ''] as $k) {
        if ($k !== '' && is_file($k)) {
            $key = $k;
            break;
        }
    }
}
if ($key === '' || ! is_file($key)) {
    $git('checkout -- version.json');
    $fail('kunci pribadi rilis tidak ditemukan (buat dengan deploy\\buat-kunci-cadangan.bat, atau pakai --kunci=lokasi.pem).');
}
$git('add -A');
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/deploy/tanda-tangan.php') . ' ' . escapeshellarg($key), $rc);
if ($rc !== 0) {
    $git('checkout -- version.json integrity.json version.sig');
    $fail('penandatanganan gagal, rilis dibatalkan.');
}
$git('add -A');
$msg = 'rilis: Versi ' . $name . ' (build ' . $build . ', ' . $date . ') — ' . mb_strimwidth($note, 0, 120, '…');
$out = $git('commit -q -m ' . escapeshellarg($msg), $rc);
if ($rc !== 0) {
    $fail("commit gagal:\n" . implode("\n", $out));
}
echo "\nRilis Versi $name (build $build) siap. Langkah berikutnya: git push origin main\n";
