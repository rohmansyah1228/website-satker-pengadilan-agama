<?php
/**
 * Lapisan database sederhana.
 * Memakai ekstensi mysql_* (server PHP lama) jika tersedia, atau mysqli jika tidak.
 * Kompatibel PHP 5.2+.
 */
$GLOBALS['DB_LINK']  = null;
$GLOBALS['DB_JENIS'] = '';
$GLOBALS['DB_ERROR'] = '';
$GLOBALS['DB_KOLOM'] = array();

function db_connect($c) {
    $GLOBALS['DB_ERROR'] = '';
    if (function_exists('mysql_connect')) {
        $link = @mysql_connect($c['host'], $c['user'], $c['pass']);
        if (!$link) { $GLOBALS['DB_ERROR'] = mysql_error(); return false; }
        if (!@mysql_select_db($c['name'], $link)) { $GLOBALS['DB_ERROR'] = mysql_error($link); return false; }
        @mysql_query("SET NAMES utf8", $link);
        $GLOBALS['DB_JENIS'] = 'mysql';
    } elseif (class_exists('mysqli')) {
        if (function_exists('mysqli_report')) mysqli_report(MYSQLI_REPORT_OFF); // PHP 8.1+: jangan lempar exception
        $link = @new mysqli($c['host'], $c['user'], $c['pass'], $c['name']);
        if ($link->connect_errno) { $GLOBALS['DB_ERROR'] = $link->connect_error; return false; }
        $link->set_charset('utf8');
        $GLOBALS['DB_JENIS'] = 'mysqli';
    } else {
        $GLOBALS['DB_ERROR'] = 'Ekstensi mysql/mysqli tidak tersedia di PHP ini.';
        return false;
    }
    $GLOBALS['DB_LINK'] = $link;
    return true;
}

/** Jalankan query SELECT, hasil berupa array baris (assoc), atau false jika gagal. */
function db_rows($sql) {
    $link = $GLOBALS['DB_LINK'];
    if (!$link) { $GLOBALS['DB_ERROR'] = 'Belum terhubung ke database.'; return false; }
    $rows = array();
    if ($GLOBALS['DB_JENIS'] == 'mysql') {
        // $link === true berarti memakai koneksi bawaan mysql_* (dibuka oleh koneksi.php SIMERAK)
        $h = ($link === true) ? @mysql_query($sql) : @mysql_query($sql, $link);
        if (!$h) { $GLOBALS['DB_ERROR'] = ($link === true) ? mysql_error() : mysql_error($link); return false; }
        if ($h === true) return $rows;
        while ($r = mysql_fetch_assoc($h)) $rows[] = $r;
        mysql_free_result($h);
    } else {
        $h = $link->query($sql);
        if (!$h) { $GLOBALS['DB_ERROR'] = $link->error; return false; }
        if ($h === true) return $rows;
        while ($r = $h->fetch_assoc()) $rows[] = $r;
        $h->free();
    }
    return $rows;
}

/** Pakai koneksi yang sudah terbuka; jika belum ada, buka dari pengaturan. */
function pastikan_db($cfg) {
    if ($GLOBALS['DB_LINK']) return true;
    if ($cfg['sipp_db']['user'] == '') { $GLOBALS['DB_ERROR'] = 'Koneksi database SIPP belum diatur.'; return false; }
    return db_connect($cfg['sipp_db']);
}

function db_error() { return $GLOBALS['DB_ERROR']; }

function db_escape($s) {
    $link = $GLOBALS['DB_LINK'];
    if ($GLOBALS['DB_JENIS'] == 'mysql') return ($link === true) ? mysql_real_escape_string($s) : mysql_real_escape_string($s, $link);
    if ($GLOBALS['DB_JENIS'] == 'mysqli') return $link->real_escape_string($s);
    return addslashes($s);
}

/** Cek apakah kolom ada di tabel (hasil di-cache). */
function db_kolom_ada($tabel, $kolom) {
    if (!isset($GLOBALS['DB_KOLOM'][$tabel])) {
        $GLOBALS['DB_KOLOM'][$tabel] = array();
        $rows = db_rows("SHOW COLUMNS FROM `" . str_replace('`', '', $tabel) . "`");
        if ($rows) foreach ($rows as $r) $GLOBALS['DB_KOLOM'][$tabel][strtolower($r['Field'])] = true;
    }
    return isset($GLOBALS['DB_KOLOM'][$tabel][strtolower($kolom)]);
}
