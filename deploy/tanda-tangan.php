<?php
/**
 * Penandatangan rilis — dijalankan pengelola rilis (bukan di hosting), dipanggil oleh deploy/rilis.sh.
 *
 *   php deploy/tanda-tangan.php /lokasi/aman/rilis-private.pem
 *
 * 1. Membuat integrity.json: sidik SHA-256 setiap berkas yang tercatat di git (kecuali metadata rilis).
 * 2. Membuat version.sig: tanda tangan RSA-SHA256 atas version.json + integrity.json.
 * Kunci pribadi TIDAK BOLEH disimpan di dalam folder website / repositori.
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__);
require $root . '/app/Libraries/Integrity.php';

use App\Libraries\Integrity;

$keyFile = $argv[1] ?? (getenv('RILIS_KEY') ?: '');
if ($keyFile === '' || ! is_file($keyFile)) {
    fwrite(STDERR, "Kunci pribadi rilis tidak ditemukan. Pakai: php deploy/tanda-tangan.php /lokasi/rilis-private.pem\n");
    exit(1);
}
if (str_starts_with(realpath($keyFile), realpath($root) . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "Kunci pribadi tidak boleh berada di dalam folder website/repositori.\n");
    exit(1);
}
$key = openssl_pkey_get_private((string) file_get_contents($keyFile));
if (! $key) {
    fwrite(STDERR, "Berkas kunci pribadi tidak valid.\n");
    exit(1);
}

$versionJson = (string) file_get_contents($root . '/version.json');
$v           = json_decode($versionJson, true);
if (! is_array($v) || empty($v['build'])) {
    fwrite(STDERR, "version.json tidak valid.\n");
    exit(1);
}

// berkas yang tercatat di git (rilis.sh menjalankan "git add -A" lebih dulu)
exec('git -C ' . escapeshellarg($root) . ' ls-files -z', $out, $rc);
if ($rc !== 0) {
    fwrite(STDERR, "Gagal membaca daftar berkas git.\n");
    exit(1);
}
$files = [];
foreach (explode("\0", implode("\n", $out)) as $p) {
    $p = trim($p, "\n");
    if ($p === '' || in_array($p, Integrity::META, true) || str_starts_with($p, '.github/') || ! is_file($root . '/' . $p)) {
        continue;
    }
    $files[$p] = Integrity::hash((string) file_get_contents($root . '/' . $p));
}
ksort($files, SORT_STRING);

$integrity = json_encode(['build' => (int) $v['build'], 'name' => (string) ($v['name'] ?? ''), 'algo' => Integrity::ALGO, 'files' => $files],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
file_put_contents($root . '/integrity.json', $integrity);

if (! openssl_sign(Integrity::message((int) $v['build'], $versionJson, $integrity), $sig, $key, OPENSSL_ALGO_SHA256)) {
    fwrite(STDERR, "Gagal menandatangani.\n");
    exit(1);
}
file_put_contents($root . '/version.sig', base64_encode($sig) . "\n");

// periksa ulang dengan kunci publik yang tercantum di app/Config/Release.php
$cfg = file_get_contents($root . '/app/Config/Release.php');
preg_match_all('/-----BEGIN PUBLIC KEY-----.*?-----END PUBLIC KEY-----/s', (string) $cfg, $m);
if (! Integrity::verify((int) $v['build'], $versionJson, $integrity, base64_encode($sig), $m[0])) {
    fwrite(STDERR, "PERINGATAN: tanda tangan tidak cocok dengan kunci publik di app/Config/Release.php!\n");
    exit(1);
}
printf("Ditandatangani: versi %s (build %d), %d berkas.\n", $v['name'] ?? '-', $v['build'], count($files));
