<?php
/**
 * Jalankan pengiriman data ke website satker.
 *
 * Dari command line (cron / Task Scheduler):
 *   php kirim.php                 -> kirim semua jenis yang aktif
 *   php kirim.php jadwal_sidang   -> kirim satu jenis saja
 *
 * Dari browser (opsional, jika token_web diisi di config.php):
 *   http://server/sipp-api-kantor/kirim.php?token=XXXX[&jenis=jadwal_sidang]
 */
require dirname(__FILE__) . '/lib/init.php';

$cli = (php_sapi_name() == 'cli');
$jenis = '';
if ($cli) {
    $jenis = isset($argv[1]) ? $argv[1] : '';
} else {
    header('Content-Type: text/plain; charset=utf-8');
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    if ($CONFIG['token_web'] == '' || $token !== $CONFIG['token_web']) {
        header('HTTP/1.1 403 Forbidden');
        echo "Akses ditolak.\n";
        exit;
    }
    $jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
}
if ($jenis != '' && !array_key_exists($jenis, daftar_jenis())) {
    echo "Jenis tidak dikenal: $jenis\n";
    exit(1);
}

$err = cek_konfigurasi($CONFIG);
if (count($err) > 0) {
    echo "Konfigurasi belum lengkap:\n - " . implode("\n - ", $err) . "\n";
    exit(1);
}

$hasil = jalankan_pengiriman($CONFIG, $jenis);
$gagal = 0;
foreach ($hasil as $j => $info) {
    echo str_pad($j, 20) . ($info['ok'] ? 'OK    ' : 'GAGAL ') . str_pad($info['jumlah'], 6) . $info['pesan'] . "\n";
    if (!$info['ok']) $gagal++;
}
if (count($hasil) == 0) echo "Tidak ada jenis data yang aktif.\n";
exit($gagal > 0 ? 1 : 0);
