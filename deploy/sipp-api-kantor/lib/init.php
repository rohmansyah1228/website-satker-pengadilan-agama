<?php
/** Muat konfigurasi & library. Kompatibel PHP 5.2+. Database SIPP hanya DIBACA. */
error_reporting(E_ALL & ~E_NOTICE & ~8192 & ~2048); // tanpa notice, deprecated, strict
$BASE = dirname(dirname(__FILE__));
$CONFIG_DASHBOARD = null;
require $BASE . '/config.php';
if (function_exists('date_default_timezone_set')) date_default_timezone_set($CONFIG['timezone']);
require $BASE . '/lib/db.php';
require $BASE . '/lib/http.php';
require $BASE . '/lib/simpan.php';
require $BASE . '/lib/pengaturan.php';
require $BASE . '/lib/kumpul.php';
require $BASE . '/lib/relaas.php';
require $BASE . '/lib/kirim.php';

// 1. Pengaturan permanen dari config.php hasil unduhan dashboard
if (is_array($CONFIG_DASHBOARD)) $CONFIG = gabung_array($CONFIG, $CONFIG_DASHBOARD);

// 2. Tempat penyimpanan: folder data/ (jika bisa ditulis) atau folder sementara
simpan_pilih();
$PENGATURAN_TERSIMPAN = baca_pengaturan();
$CONFIG = gabung_array($CONFIG, $PENGATURAN_TERSIMPAN);
if (!isset($CONFIG['dashboard_hash']))  $CONFIG['dashboard_hash']  = '';
if (!isset($CONFIG['dashboard_garam'])) $CONFIG['dashboard_garam'] = '';
// bawaan untuk config.php versi lama (sebelum ada pengiriman relaas)
if (!isset($CONFIG['ghaib_kirim_relaas'])) $CONFIG['ghaib_kirim_relaas'] = true;
if (!isset($CONFIG['relaas_dir']))         $CONFIG['relaas_dir']         = '';
if (!isset($CONFIG['relaas_maks_mb']))     $CONFIG['relaas_maks_mb']     = 5;

// 3. Koneksi database SIPP: dari pengaturan, atau otomatis dari aplikasi SIPP / SIMERAK di server ini
$GLOBALS['DB_SUMBER'] = ''; $GLOBALS['DB_SUMBER_FILE'] = '';
if ($CONFIG['sipp_db']['user'] != '' && db_connect($CONFIG['sipp_db'])) {
    $GLOBALS['DB_SUMBER'] = 'pengaturan';
} elseif (sambung_dari_sipp()) {
    $GLOBALS['DB_SUMBER'] = 'sipp';
} elseif (sambung_dari_simerak()) {
    $GLOBALS['DB_SUMBER'] = 'simerak';
}

/**
 * Perlu unduh config.php? Ya jika pengaturan hanya ada di folder sementara
 * dan belum sama dengan isi config.php.
 */
function perlu_unduh_config() {
    if ($GLOBALS['SIMPAN_JENIS'] != 'tmp') return false;
    $tmp = $GLOBALS['PENGATURAN_TERSIMPAN'];
    if (count($tmp) == 0) return false;
    $cfg = is_array($GLOBALS['CONFIG_DASHBOARD']) ? $GLOBALS['CONFIG_DASHBOARD'] : array();
    return json_aman(urut_kunci($tmp)) !== json_aman(urut_kunci($cfg));
}
function urut_kunci($a) {
    if (!is_array($a)) return $a;
    ksort($a);
    foreach ($a as $k => $v) $a[$k] = urut_kunci($v);
    return $a;
}

/** config.php lengkap: isi asli + blok pengaturan dari dashboard */
function buat_config_php($pengaturan) {
    $isi = file_get_contents(dirname(dirname(__FILE__)) . '/config.php');
    $pos = strpos($isi, '/* === PENGATURAN DARI DASHBOARD');
    if ($pos !== false) $isi = rtrim(substr($isi, 0, $pos)) . "\n";
    return rtrim($isi) . "\n\n/* === PENGATURAN DARI DASHBOARD (dibuat otomatis " . date('Y-m-d H:i') . ", jangan diubah manual) === */\n"
         . '$CONFIG_DASHBOARD = ' . var_export($pengaturan, true) . ";\n";
}

/** Cek pengaturan wajib, hasil: array kalimat yang mudah dipahami */
function cek_konfigurasi($cfg) {
    $err = array();
    if (!$GLOBALS['DB_LINK'] && ($cfg['sipp_db']['user'] == '' || $cfg['sipp_db']['name'] == ''))
        $err[] = 'Data koneksi database SIPP belum diisi.';
    if ($cfg['website']['url'] == '')               $err[] = 'Alamat website satker belum diisi.';
    if (strlen($cfg['website']['api_key']) < 16 || strlen($cfg['website']['secret']) < 32)
        $err[] = 'Kunci pengiriman belum dibuat.';
    if (!ada_password($cfg))                        $err[] = 'Password dashboard belum dibuat.';
    return $err;
}
