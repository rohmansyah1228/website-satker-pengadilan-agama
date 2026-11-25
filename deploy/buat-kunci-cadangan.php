<?php
/**
 * Membuat KUNCI CADANGAN penandatangan rilis di komputer pengelola (bukan di hosting).
 *
 *   php deploy/buat-kunci-cadangan.php            (atau klik dua kali deploy/buat-kunci-cadangan.bat di Windows)
 *
 * - Kunci PRIBADI disimpan di folder pengguna:  <home>/satker-rilis/rilis-cadangan-private.pem
 *   (TIDAK di folder website). Simpan salinannya di flashdisk/tempat aman; jangan dikirim ke siapa pun.
 * - Kunci PUBLIK disalin ke writable/kunci-cadangan-public.pem agar bisa ditambahkan ke app/Config/Release.php.
 *   Kunci publik aman dibagikan.
 * Tidak pernah menimpa kunci yang sudah ada.
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__);
$home = getenv('USERPROFILE') ?: (getenv('HOME') ?: '');
if ($home === '' || ! is_dir($home)) {
    fwrite(STDERR, "Folder pengguna tidak ditemukan.\n");
    exit(1);
}
$dir  = rtrim($home, '\\/') . DIRECTORY_SEPARATOR . 'satker-rilis';
$priv = $dir . DIRECTORY_SEPARATOR . 'rilis-cadangan-private.pem';
$pub  = $dir . DIRECTORY_SEPARATOR . 'rilis-cadangan-public.pem';
$copy = $root . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'kunci-cadangan-public.pem';

$fp = static function (string $pem): string {
    $b64 = preg_replace('/-----[^-]+-----|\s+/', '', $pem);

    return substr(hash('sha256', (string) base64_decode($b64)), 0, 16);
};

if (is_file($priv)) {
    $pubPem = is_file($pub) ? (string) file_get_contents($pub) : '';
    echo "Kunci cadangan SUDAH ADA (tidak ditimpa):\n  $priv\n";
    if ($pubPem !== '') {
        @file_put_contents($copy, $pubPem);
        echo "Sidik kunci publik: " . $fp($pubPem) . "\nSalinan kunci publik: $copy\n";
    }
    exit(0);
}
if (! function_exists('openssl_pkey_new')) {
    fwrite(STDERR, "Ekstensi PHP openssl belum aktif (php.ini: extension=openssl).\n");
    exit(1);
}

// XAMPP Windows kadang butuh lokasi openssl.cnf yang ditulis jelas
$opts = ['private_key_bits' => 3072, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
$key  = @openssl_pkey_new($opts);
if (! $key) {
    $phpDir = dirname(PHP_BINARY);
    foreach ([getenv('OPENSSL_CONF') ?: '', $phpDir . '/extras/ssl/openssl.cnf', $phpDir . '/extras/openssl/openssl.cnf',
        dirname($phpDir) . '/apache/conf/openssl.cnf', dirname($phpDir) . '/apache/bin/openssl.cnf'] as $cnf) {
        if ($cnf !== '' && is_file($cnf) && ($key = @openssl_pkey_new($opts + ['config' => $cnf]))) {
            $opts['config'] = $cnf;
            break;
        }
    }
}
if (! $key) {
    fwrite(STDERR, "Gagal membuat kunci: " . (openssl_error_string() ?: 'openssl.cnf tidak ditemukan') . "\n");
    exit(1);
}
$pem = '';
if (! openssl_pkey_export($key, $pem, null, isset($opts['config']) ? ['config' => $opts['config']] : [])) {
    fwrite(STDERR, "Gagal menyimpan kunci: " . openssl_error_string() . "\n");
    exit(1);
}
$pubPem = openssl_pkey_get_details($key)['key'];

// uji: tanda tangan + verifikasi
openssl_sign('uji', $sig, $key, OPENSSL_ALGO_SHA256);
if (openssl_verify('uji', $sig, $pubPem, OPENSSL_ALGO_SHA256) !== 1) {
    fwrite(STDERR, "Uji kunci gagal.\n");
    exit(1);
}
if (! is_dir($dir) && ! mkdir($dir, 0700, true)) {
    fwrite(STDERR, "Tidak bisa membuat folder $dir\n");
    exit(1);
}
file_put_contents($priv, $pem);
@chmod($priv, 0600);
file_put_contents($pub, $pubPem);
@file_put_contents($copy, $pubPem);

echo "BERHASIL membuat kunci cadangan penandatangan rilis.\n\n";
echo "  Kunci PRIBADI (rahasia):  $priv\n";
echo "  Kunci publik           :  $pub\n";
echo "  Sidik kunci publik     :  " . $fp($pubPem) . "\n\n";
echo "PENTING: salin folder $dir ke flashdisk / tempat aman.\n";
echo "Jangan kirim kunci PRIBADI ke siapa pun (termasuk lewat WhatsApp/email/chat).\n";
echo "Kunci publik sudah disalin ke: $copy\n";
