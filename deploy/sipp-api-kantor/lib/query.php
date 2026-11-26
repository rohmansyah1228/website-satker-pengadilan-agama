<?php
/**
 * Query buatan satker sendiri (menu "Query Sendiri") untuk jadwal sidang, statistik, panggilan ghaib, dan antrian.
 * Setiap data punya query bawaan; query sendiri dipakai bila diisi. Hanya SATU perintah SELECT yang boleh
 * dijalankan; database SIPP hanya DIBACA. Kompatibel PHP 5.2+.
 */

/**
 * Pindai SQL: buang komentar, tandai titik koma di luar teks, dan buat salinan "kode saja"
 * (isi teks '...' "..." `...` dikosongkan) untuk pemeriksaan kata kunci.
 * @return array(sql_bersih, kode_saja, ada_titik_koma_di_tengah, ada_komentar_eksekusi)
 */
function query_pindai($sql) {
    $sql = trim(str_replace("\r", '', $sql));
    $out = ''; $kode = ''; $n = strlen($sql); $i = 0; $semi = false; $exec = false;
    while ($i < $n) {
        $c = $sql[$i];
        $d = ($i + 1 < $n) ? $sql[$i + 1] : '';
        if ($c == "'" || $c == '"' || $c == '`') {          // teks / nama bertanda petik
            $j = $i + 1;
            while ($j < $n) {
                if ($sql[$j] == '\\' && $c != '`') { $j += 2; continue; }
                if ($sql[$j] == $c) {
                    if ($j + 1 < $n && $sql[$j + 1] == $c) { $j += 2; continue; } // petik ganda = petik biasa
                    break;
                }
                $j++;
            }
            $potong = substr($sql, $i, $j - $i + 1);
            $out .= $potong;
            $kode .= $c . str_repeat('x', max(0, strlen($potong) - 2)) . $c;
            $i = $j + 1;
            continue;
        }
        if ($c == '/' && $d == '*') {                         // komentar /* ... */
            if ($i + 2 < $n && $sql[$i + 2] == '!') $exec = true; // /*! ... */ dijalankan MySQL
            $j = strpos($sql, '*/', $i + 2);
            $i = ($j === false) ? $n : $j + 2;
            $out .= ' '; $kode .= ' ';
            continue;
        }
        if (($c == '-' && $d == '-' && ($i + 2 >= $n || preg_match('/\s/', $sql[$i + 2]))) || $c == '#') { // komentar sebaris
            $j = strpos($sql, "\n", $i);
            $i = ($j === false) ? $n : $j;
            continue;
        }
        if ($c == ';') {
            $sisa = trim(substr($sql, $i + 1));
            // titik koma di akhir boleh (diabaikan); selain itu berarti lebih dari satu perintah
            if ($sisa !== '' && !preg_match('/^(;|\s|--[^\n]*|#[^\n]*)*$/', $sisa)) $semi = true;
            $i++;
            continue;
        }
        $out .= $c; $kode .= $c;
        $i++;
    }
    return array(trim($out), strtoupper($kode), $semi, $exec);
}

/** Periksa query antrian. Kembalikan '' bila aman, atau pesan kesalahan. */
function query_cek($sql) {
    if (trim($sql) == '') return 'Query masih kosong.';
    if (strlen($sql) > 20000) return 'Query terlalu panjang (maksimal 20.000 karakter).';
    $p = query_pindai($sql);
    $kode = $p[1];
    if ($p[2]) return 'Hanya boleh SATU perintah. Hapus titik koma (;) di tengah query.';
    if ($p[3]) return 'Komentar khusus /*! ... */ tidak diizinkan.';
    if (!preg_match('/^\s*\(?\s*(SELECT|WITH)\b/', $kode)) return 'Query harus diawali SELECT (hanya membaca data).';
    $larang = array(
        '/\bINTO\b/'                         => 'SELECT ... INTO',
        '/\b(LOAD_FILE|SLEEP|BENCHMARK|GET_LOCK|RELEASE_LOCK|SYS_EXEC|SYS_EVAL)\s*\(/' => 'fungsi berbahaya',
        '/\bFOR\s+UPDATE\b/'                 => 'FOR UPDATE',
        '/\bLOCK\s+IN\s+SHARE\s+MODE\b/'     => 'LOCK IN SHARE MODE',
        '/\b(INSERT|UPDATE|DELETE|REPLACE|DROP|ALTER|CREATE|TRUNCATE|GRANT|REVOKE|RENAME|HANDLER|CALL|SHUTDOWN|KILL|SET)\b(?!\s*\()/' => 'perintah pengubah data',
    );
    foreach ($larang as $pola => $nama) {
        if (preg_match($pola, $kode)) return 'Query memuat ' . $nama . ' yang tidak diizinkan. Database SIPP hanya boleh dibaca.';
    }
    return '';
}

/** Nama database antrian (opsional) yang sah. */
function query_db_sah($nama) {
    return $nama === '' || preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $nama);
}

/** Pindah database aktif pada koneksi yang sama. */
function db_pilih($nama) {
    $link = $GLOBALS['DB_LINK'];
    if (!$link || $nama === '') return false;
    if ($GLOBALS['DB_JENIS'] == 'mysql') {
        $ok = ($link === true) ? @mysql_select_db($nama) : @mysql_select_db($nama, $link);
        if (!$ok) $GLOBALS['DB_ERROR'] = ($link === true) ? mysql_error() : mysql_error($link);
        return $ok;
    }
    $ok = @$link->select_db($nama);
    if (!$ok) $GLOBALS['DB_ERROR'] = $link->error;
    return $ok;
}

/**
 * Jalankan SATU query SELECT (diperiksa dulu) di database pilihan, lalu kembali ke database semula.
 * @return array baris (maks. $maks) atau false (lihat db_error()).
 */
function query_jalankan($sql, $db, $maks) {
    $db  = trim((string)$db);
    $err = query_cek($sql);
    if ($err != '') { $GLOBALS['DB_ERROR'] = $err; return false; }
    if (!query_db_sah($db)) { $GLOBALS['DB_ERROR'] = 'Nama database tidak valid.'; return false; }
    $p = query_pindai($sql);

    $asal = '';
    if ($db !== '') {
        $r = db_rows('SELECT DATABASE() AS db');
        $asal = ($r && isset($r[0]['db'])) ? $r[0]['db'] : '';
        if (!db_pilih($db)) { $GLOBALS['DB_ERROR'] = 'Database "' . $db . '" tidak bisa dibuka: ' . db_error(); return false; }
    }
    // lapis pengaman tambahan: transaksi baca-saja (MySQL 5.6+/MariaDB 10+; server lama cukup diabaikan)
    $ro = (db_rows('START TRANSACTION READ ONLY') !== false);
    $rows = db_rows($p[0]);
    $galat = db_error();
    if ($ro) db_rows('COMMIT');
    if ($asal !== '') db_pilih($asal);
    if ($rows === false) { $GLOBALS['DB_ERROR'] = $galat; return false; }
    if (count($rows) > $maks) $rows = array_slice($rows, 0, $maks);
    return $rows;
}

/* ------------------------------------------------------------------
 * Daftar query yang bisa diganti satker
 * ------------------------------------------------------------------ */

/** Nilai penanda tanggal: {dari} {sampai} (jadwal), {awal} {akhir} {tahun} (statistik) */
function query_nilai($cfg) {
    $hari = max(1, (int)(isset($cfg['jadwal_hari_ke_depan']) ? $cfg['jadwal_hari_ke_depan'] : 7));
    $t = (int)date('Y');
    return array(
        '{dari}'   => "'" . date('Y-m-d') . "'",
        '{sampai}' => "'" . date('Y-m-d', strtotime('+' . ($hari - 1) . ' day')) . "'",
        '{awal}'   => "'" . $t . "-01-01'",
        '{akhir}'  => "'" . $t . "-12-31'",
        '{tahun}'  => (string)$t,
    );
}

/** Ganti penanda tanggal dengan nilainya (nilai dibuat program, bukan dari isian). */
function query_isi_penanda($sql, $cfg) {
    $n = query_nilai($cfg);
    return str_replace(array_keys($n), array_values($n), $sql);
}

/**
 * Query yang bisa diganti: kunci => array(data, judul, kolom wajib, kolom opsional, penanda, keterangan)
 */
function query_slot_daftar() {
    return array(
        'jadwal' => array('jadwal_sidang', 'Jadwal Sidang',
            array('tanggal_sidang', 'nomor_perkara'),
            array('jenis_perkara_text', 'agenda', 'ruangan', 'jam_sidang', 'sampai_jam', 'sidang_keliling', 'ditunda', 'pihak1_text', 'pihak2_text'),
            '{dari} {sampai}', 'Jadwal sidang dari hari ini sampai beberapa hari ke depan. sidang_keliling & ditunda bernilai Y bila ya.'),
        'stat_masuk' => array('statistik_perkara', 'Statistik — perkara masuk per bulan',
            array('bln', 'jml'), array(), '{awal} {akhir} {tahun}', 'bln = nomor bulan 1–12, jml = jumlah perkara masuk.'),
        'stat_putus' => array('statistik_perkara', 'Statistik — perkara putus per bulan',
            array('bln', 'jml'), array(), '{awal} {akhir} {tahun}', 'bln = nomor bulan 1–12, jml = jumlah perkara putus.'),
        'stat_sisa' => array('statistik_perkara', 'Statistik — sisa perkara tahun lalu',
            array('jml'), array(), '{awal} {akhir} {tahun}', 'Satu baris: jml = perkara yang belum putus pada awal tahun.'),
        'stat_jenis_masuk' => array('statistik_perkara', 'Statistik — perkara masuk per jenis',
            array('jenis', 'jml'), array(), '{awal} {akhir} {tahun}', 'jenis = jenis perkara, jml = jumlah masuk tahun ini.'),
        'stat_jenis_putus' => array('statistik_perkara', 'Statistik — perkara putus per jenis',
            array('jenis', 'jml'), array(), '{awal} {akhir} {tahun}', 'jenis = jenis perkara, jml = jumlah putus tahun ini.'),
        'ghaib' => array('panggilan_ghaib', 'Panggilan Ghaib — belum putus',
            array('perkara_id', 'nomor_perkara'),
            array('jenis_perkara_text', 'tanggal_pendaftaran', 'nama_ghaib', 'alamat_ghaib', 'sidang_berikutnya'),
            '', 'Perkara yang Tergugat/Termohon-nya ghaib dan belum putus (pengumuman panggilan).'),
        'ghaib_putus' => array('panggilan_ghaib', 'Panggilan Ghaib — sudah putus',
            array('perkara_id', 'nomor_perkara', 'tanggal_putusan'),
            array('jenis_perkara_text', 'tanggal_pendaftaran', 'nama_ghaib', 'sidang_terakhir'),
            '', 'Perkara ghaib yang sudah putus 1 tahun terakhir (tabel "Sudah Putus" di website).'),
        'antrian' => array('antrian', 'Antrian Sidang',
            array(), array(), '', 'Bebas: kolom apa pun dikirim apa adanya ke website. Tidak ada query bawaan (tabel antrian berbeda-beda).'),
    );
}

/** Query sendiri yang tersimpan untuk satu kunci: array(sql, db). Antrian memakai kunci lama antrian_sql/antrian_db. */
function query_tersimpan($cfg, $slot) {
    if ($slot == 'antrian') return array(isset($cfg['antrian_sql']) ? trim($cfg['antrian_sql']) : '', isset($cfg['antrian_db']) ? trim($cfg['antrian_db']) : '');
    $q = isset($cfg['query_sendiri'][$slot]) ? $cfg['query_sendiri'][$slot] : array();
    return array(isset($q['sql']) ? trim($q['sql']) : '', isset($q['db']) ? trim($q['db']) : '');
}

/** Filter alamat ghaib bawaan */
function query_filter_ghaib() {
    return "(LOWER(h.alamat) LIKE '%tidak diketahui%'
                  OR LOWER(h.alamat) LIKE '%tdk diketahui%'
                  OR LOWER(h.alamat) LIKE '%tidak jelas%'
                  OR LOWER(h.alamat) REGEXP '(^|[^[:alpha:]])(ghaib|gaib)([^[:alpha:]]|$)')";
}

/** Query bawaan (dengan penanda tanggal, belum diisi). '' bila tidak ada bawaan. */
function query_bawaan($slot, $cfg) {
    $g = query_filter_ghaib();
    switch ($slot) {
        case 'jadwal':
            $t = 'perkara_jadwal_sidang';
            $kolom = array('j.tanggal_sidang', 'a.nomor_perkara', 'a.jenis_perkara_text', 'j.agenda', 'j.ruangan');
            if (db_kolom_ada($t, 'id'))              $kolom[] = 'j.id';
            if (db_kolom_ada($t, 'jam_sidang'))      $kolom[] = 'j.jam_sidang';
            if (db_kolom_ada($t, 'sampai_jam'))      $kolom[] = 'j.sampai_jam';
            if (db_kolom_ada($t, 'sidang_keliling')) $kolom[] = 'j.sidang_keliling';
            if (db_kolom_ada($t, 'ditunda'))         $kolom[] = 'j.ditunda';
            if (!empty($cfg['jadwal_kirim_nama_pihak'])) { $kolom[] = 'a.pihak1_text'; $kolom[] = 'a.pihak2_text'; }
            $urut = 'j.tanggal_sidang, j.ruangan' . (db_kolom_ada($t, 'jam_sidang') ? ', j.jam_sidang' : '') . ', a.nomor_perkara';
            return "SELECT " . implode(', ', $kolom) . "
FROM perkara_jadwal_sidang j
JOIN perkara a ON a.perkara_id = j.perkara_id
WHERE j.tanggal_sidang BETWEEN {dari} AND {sampai}
ORDER BY " . $urut;
        case 'stat_masuk':
            return "SELECT MONTH(tanggal_pendaftaran) AS bln, COUNT(*) AS jml
FROM perkara
WHERE tanggal_pendaftaran BETWEEN {awal} AND {akhir}
GROUP BY MONTH(tanggal_pendaftaran)";
        case 'stat_putus':
            return "SELECT MONTH(c.tanggal_putusan) AS bln, COUNT(*) AS jml
FROM perkara_putusan c
JOIN perkara a ON a.perkara_id = c.perkara_id
WHERE c.tanggal_putusan BETWEEN {awal} AND {akhir}
GROUP BY MONTH(c.tanggal_putusan)";
        case 'stat_sisa':
            return "SELECT COUNT(*) AS jml
FROM perkara a
LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
WHERE a.tanggal_pendaftaran < {awal}
  AND (c.tanggal_putusan IS NULL OR c.tanggal_putusan >= {awal})";
        case 'stat_jenis_masuk':
            return "SELECT a.jenis_perkara_text AS jenis, COUNT(*) AS jml
FROM perkara a
WHERE a.tanggal_pendaftaran BETWEEN {awal} AND {akhir}
GROUP BY a.jenis_perkara_text
ORDER BY jml DESC";
        case 'stat_jenis_putus':
            return "SELECT a.jenis_perkara_text AS jenis, COUNT(*) AS jml
FROM perkara_putusan c
JOIN perkara a ON a.perkara_id = c.perkara_id
WHERE c.tanggal_putusan BETWEEN {awal} AND {akhir}
GROUP BY a.jenis_perkara_text";
        case 'ghaib':
            return "SELECT a.perkara_id, a.nomor_perkara, a.jenis_perkara_text, a.tanggal_pendaftaran,
  (SELECT GROUP_CONCAT(h.nama ORDER BY h.urutan SEPARATOR ', ') FROM perkara_pihak2 h
    WHERE h.perkara_id = a.perkara_id AND $g) AS nama_ghaib,
  (SELECT GROUP_CONCAT(h.alamat ORDER BY h.urutan SEPARATOR ' | ') FROM perkara_pihak2 h
    WHERE h.perkara_id = a.perkara_id AND $g) AS alamat_ghaib,
  (SELECT MIN(i.tanggal_sidang) FROM perkara_jadwal_sidang i
    WHERE i.perkara_id = a.perkara_id AND i.tanggal_sidang >= CURDATE()) AS sidang_berikutnya
FROM perkara a
LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
WHERE c.tanggal_putusan IS NULL
  AND a.tanggal_pendaftaran >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
  AND EXISTS (SELECT 1 FROM perkara_pihak2 h WHERE h.perkara_id = a.perkara_id AND $g)
ORDER BY a.tanggal_pendaftaran ASC, a.perkara_id ASC";
        case 'ghaib_putus':
            return "SELECT a.perkara_id, a.nomor_perkara, a.jenis_perkara_text, a.tanggal_pendaftaran, c.tanggal_putusan,
  (SELECT GROUP_CONCAT(h.nama ORDER BY h.urutan SEPARATOR ', ') FROM perkara_pihak2 h
    WHERE h.perkara_id = a.perkara_id AND $g) AS nama_ghaib,
  (SELECT MAX(i.tanggal_sidang) FROM perkara_jadwal_sidang i
    WHERE i.perkara_id = a.perkara_id AND i.tanggal_sidang <= c.tanggal_putusan) AS sidang_terakhir
FROM perkara a
JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
WHERE c.tanggal_putusan IS NOT NULL
  AND c.tanggal_putusan >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
  AND EXISTS (SELECT 1 FROM perkara_pihak2 h WHERE h.perkara_id = a.perkara_id AND $g)
ORDER BY c.tanggal_putusan DESC, a.perkara_id DESC";
    }
    return '';
}

/** Keterangan asal galat query sendiri */
function query_catatan($slot) {
    $d = query_slot_daftar();
    return ' [query sendiri "' . (isset($d[$slot]) ? $d[$slot][1] : $slot) . '" — perbaiki di menu Query Sendiri]';
}

/** Kolom wajib yang tidak ada di hasil query ('' bila lengkap / hasil kosong). */
function query_kolom_kurang($slot, $rows) {
    $d = query_slot_daftar();
    if (!$rows || !isset($d[$slot])) return '';
    $ada = array_change_key_case($rows[0], CASE_LOWER);
    $kurang = array();
    foreach ($d[$slot][2] as $k) if (!array_key_exists($k, $ada)) $kurang[] = $k;
    return implode(', ', $kurang);
}

/**
 * Ambil baris untuk satu kunci: query sendiri bila diisi, selain itu query bawaan.
 * @param string $sqlUji / $dbUji  untuk uji dari dashboard (null = pakai yang tersimpan)
 * @return array baris, atau false (db_error() berisi alasannya)
 */
function query_ambil($cfg, $slot, $sqlUji = null, $dbUji = null) {
    if ($sqlUji === null) { $t = query_tersimpan($cfg, $slot); $sql = $t[0]; $db = $t[1]; }
    else { $sql = trim($sqlUji); $db = trim((string)$dbUji); }
    if ($sql === '') {
        $b = query_bawaan($slot, $cfg);
        if ($b === '') { $GLOBALS['DB_ERROR'] = 'Query belum diisi.'; return false; }
        $rows = db_rows(query_isi_penanda($b, $cfg));
        if ($rows === false) return false;
    } else {
        $rows = query_jalankan(query_isi_penanda($sql, $cfg), $db, 5000);
        if ($rows === false) { $GLOBALS['DB_ERROR'] .= query_catatan($slot); return false; }
    }
    // nama kolom tidak peka huruf besar/kecil (antrian dikirim apa adanya)
    if ($slot != 'antrian') foreach ($rows as $i => $r) $rows[$i] = array_change_key_case($r, CASE_LOWER);
    $kurang = query_kolom_kurang($slot, $rows);
    if ($kurang != '') { $GLOBALS['DB_ERROR'] = 'Hasil query tidak memuat kolom wajib: ' . $kurang . '. Beri nama kolom dengan AS.' . ($sql !== '' ? query_catatan($slot) : ''); return false; }
    return $rows;
}

/** Cari tabel yang namanya mengandung kata tertentu di semua database yang bisa dibaca akun ini. */
function query_cari_tabel($kata) {
    $kata = preg_replace('/[^A-Za-z0-9_]/', '', (string)$kata);
    if ($kata === '') $kata = 'antri';
    return db_rows("SELECT table_schema AS db, table_name AS tabel, table_rows AS baris
                  FROM information_schema.tables
                  WHERE table_name LIKE '%" . $kata . "%' AND table_schema NOT IN ('information_schema','mysql','performance_schema','sys')
                  ORDER BY table_schema, table_name LIMIT 100");
}
