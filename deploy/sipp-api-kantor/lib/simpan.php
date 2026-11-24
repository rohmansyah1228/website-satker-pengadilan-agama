<?php
/**
 * Penyimpanan pengaturan, status & log TANPA perlu mengatur izin folder,
 * dan TANPA menulis apa pun ke database SIPP (database SIPP hanya dibaca).
 *   1. folder data/ (jika bisa ditulis)
 *   2. folder sementara server; agar permanen, pengguna mengunduh config.php
 *      dari dashboard lalu menyalinnya ke folder aplikasi.
 * Kompatibel PHP 5.2+.
 */
$GLOBALS['SIMPAN_JENIS'] = '';
$GLOBALS['SIMPAN_DIR']   = '';

function simpan_pilih() {
    $dir = dirname(dirname(__FILE__)) . '/data';
    if (is_file($dir . '/pengaturan.json') || (is_dir($dir) && is_writable($dir))) {
        $GLOBALS['SIMPAN_JENIS'] = 'file'; $GLOBALS['SIMPAN_DIR'] = $dir; return;
    }
    $GLOBALS['SIMPAN_JENIS'] = 'tmp'; $GLOBALS['SIMPAN_DIR'] = dir_sementara();
}

function simpan_baca($kunci) {
    $f = $GLOBALS['SIMPAN_DIR'] . '/' . $kunci;
    return is_file($f) ? file_get_contents($f) : '';
}

function simpan_tulis($kunci, $isi) {
    return @file_put_contents($GLOBALS['SIMPAN_DIR'] . '/' . $kunci, $isi, LOCK_EX) !== false;
}

/** Tambah baris log (disimpan maksimal 300 baris terakhir) */
function simpan_log($baris) {
    $lama = simpan_baca('log.txt');
    $semua = $lama == '' ? array() : explode("\n", rtrim($lama, "\n"));
    $semua[] = $baris;
    if (count($semua) > 300) $semua = array_slice($semua, -300);
    simpan_tulis('log.txt', implode("\n", $semua) . "\n");
}

function simpan_keterangan() {
    return $GLOBALS['SIMPAN_JENIS'] == 'file' ? 'folder data/' : 'folder sementara server (perlu config.php agar permanen)';
}

/* ------------------------------------------------------------------
 * Folder sementara (selalu bisa ditulis) untuk menyimpan koneksi database
 * yang diisi lewat formulir, sampai server dinyalakan ulang.
 * ------------------------------------------------------------------ */
function dir_sementara() {
    $d = (function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : '/tmp') . '/sipp-api-kantor-' . substr(md5(dirname(dirname(__FILE__))), 0, 8);
    if (!is_dir($d)) @mkdir($d, 0700);
    return $d;
}
/* ------------------------------------------------------------------
 * Koneksi database otomatis dari aplikasi SIPP di server yang sama
 * (application/config/database.php milik SIPP / CodeIgniter)
 * ------------------------------------------------------------------ */
function cari_config_sipp() {
    $base  = dirname(dirname(__FILE__));
    $akar  = array(dirname($base), dirname(dirname($base)), dirname(dirname(dirname($base))));
    if (!empty($_SERVER['DOCUMENT_ROOT'])) $akar[] = $_SERVER['DOCUMENT_ROOT'];
    $utama = array(); $lain = array(); $sudah = array();
    $ciri  = array('/application/config/database.php', '/application/config/production/database.php');
    foreach ($akar as $a) {
        $a = realpath($a);
        if (!$a || isset($sudah[$a]) || !is_dir($a)) continue;
        $sudah[$a] = 1;
        // periksa folder anak, dan cucu (satu tingkat lebih dalam)
        $daftar = array();
        $isi = @scandir($a);
        if (!$isi) continue;
        foreach ($isi as $d) {
            if ($d == '.' || $d == '..' || !is_dir($a . '/' . $d)) continue;
            $daftar[] = $a . '/' . $d;
            if (count($daftar) > 400) break;
            $isi2 = @scandir($a . '/' . $d);
            if (!$isi2 || is_dir($a . '/' . $d . '/application')) continue;
            foreach ($isi2 as $d2) {
                if ($d2 != '.' && $d2 != '..' && is_dir($a . '/' . $d . '/' . $d2 . '/application')) $daftar[] = $a . '/' . $d . '/' . $d2;
            }
        }
        foreach ($daftar as $folder) {
            foreach ($ciri as $c) {
                $f = $folder . $c;
                if (is_file($f) && !in_array($f, $utama) && !in_array($f, $lain)) {
                    if (stripos(basename($folder), 'sipp') !== false) $utama[] = $f; else $lain[] = $f;
                }
            }
        }
    }
    return array_merge($utama, $lain);   // folder bernama "sipp" dicoba lebih dulu
}

function muat_config_ci($__file) {
    if (!defined('BASEPATH')) define('BASEPATH', dirname($__file) . '/');
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'production');
    $db = array(); $active_group = 'default';
    ob_start();
    @include $__file;
    ob_end_clean();
    if (!isset($db[$active_group])) return null;
    $g = $db[$active_group];
    if (empty($g['username']) || empty($g['database'])) return null;
    return array('host' => isset($g['hostname']) ? $g['hostname'] : 'localhost',
                 'user' => $g['username'], 'pass' => isset($g['password']) ? $g['password'] : '',
                 'name' => $g['database']);
}

/** Coba setiap config CodeIgniter; pakai yang databasenya berisi tabel perkara SIPP. */
function sambung_dari_sipp() {
    // lokasi yang pernah ditemukan disimpan agar tidak mencari ulang setiap kali
    $cache = dir_sementara() . '/lokasi_sipp.txt';
    $daftar = array();
    if (is_file($cache)) {
        $f = trim(file_get_contents($cache));
        if ($f != '' && is_file($f)) $daftar[] = $f;
    }
    $cari = null;
    for ($i = 0; $i < 2; $i++) {
        foreach ($daftar as $f) {
            $c = muat_config_ci($f);
            if (!$c) continue;
            if (db_connect($c) && db_rows("SELECT perkara_id FROM perkara LIMIT 1") !== false) {
                $GLOBALS['DB_SUMBER_FILE'] = $f;
                @file_put_contents($cache, $f);
                return true;
            }
            $GLOBALS['DB_LINK'] = null;
        }
        if ($cari !== null) break;
        $cari = cari_config_sipp();
        $daftar = $cari;
    }
    return false;
}

/* ------------------------------------------------------------------
 * Koneksi database otomatis dari SIMERAK (simerak/config/koneksi.php)
 * ------------------------------------------------------------------ */
function cari_koneksi_simerak() {
    $base = dirname(dirname(__FILE__));
    $kandidat = array(
        $base . '/../simerak/config/koneksi.php',
        $base . '/../../simerak/config/koneksi.php',
        $base . '/../../../simerak/config/koneksi.php',
    );
    if (!empty($_SERVER['DOCUMENT_ROOT'])) $kandidat[] = $_SERVER['DOCUMENT_ROOT'] . '/simerak/config/koneksi.php';
    foreach ($kandidat as $k) {
        if (is_file($k)) return realpath($k);
    }
    return '';
}

/** Muat koneksi.php SIMERAK di ruang terpisah, lalu pakai koneksinya. */
function sambung_dari_simerak() {
    $file = cari_koneksi_simerak();
    if ($file == '') return false;
    $GLOBALS['DB_SUMBER_FILE'] = $file;
    $vars = muat_file_koneksi($file);

    // 1. Ada objek mysqli di koneksi.php?
    foreach ($vars as $v) {
        if (is_object($v) && $v instanceof mysqli && !$v->connect_errno) {
            $GLOBALS['DB_LINK'] = $v; $GLOBALS['DB_JENIS'] = 'mysqli';
            $v->set_charset('utf8');
            return true;
        }
    }
    // 2. Ada resource koneksi mysql_*?
    foreach ($vars as $v) {
        if (is_resource($v) && get_resource_type($v) == 'mysql link') {
            $GLOBALS['DB_LINK'] = $v; $GLOBALS['DB_JENIS'] = 'mysql';
            @mysql_query("SET NAMES utf8", $v);
            return true;
        }
    }
    // 3. koneksi.php memakai koneksi bawaan mysql_* tanpa variabel
    if (function_exists('mysql_query') && @mysql_query('SELECT 1')) {
        $GLOBALS['DB_LINK'] = true; $GLOBALS['DB_JENIS'] = 'mysql';
        @mysql_query("SET NAMES utf8");
        return true;
    }
    // 4. Susun ulang dari variabel host/user/password/database
    $ambil = array(
        'host' => array('host', 'server', 'hostname', 'dbhost', 'db_host', 'servername'),
        'user' => array('user', 'username', 'dbuser', 'db_user', 'usr'),
        'pass' => array('pass', 'password', 'dbpass', 'db_pass', 'pwd', 'passwd'),
        'name' => array('database', 'dbname', 'db_name', 'db', 'nama_database', 'namadb'),
    );
    $c = array('host' => 'localhost', 'user' => '', 'pass' => '', 'name' => '');
    foreach ($ambil as $kunci => $nama) {
        foreach ($nama as $n) {
            if (isset($vars[$n]) && is_string($vars[$n])) { $c[$kunci] = $vars[$n]; break; }
        }
    }
    if ($c['user'] != '' && $c['name'] != '') return db_connect($c);
    return false;
}

/** include di dalam fungsi agar variabelnya tidak bercampur; keluaran (echo) dibuang */
function muat_file_koneksi($__file) {
    ob_start();
    include $__file;
    ob_end_clean();
    $hasil = get_defined_vars();
    unset($hasil['__file']);
    return $hasil;
}
