<?php
/**
 * Pengumpul data dari database SIPP internal.
 * Setiap fungsi mengembalikan:
 *   array('ok' => bool, 'sumber' => 'sipp_internal'|'sipp_publik', 'data' => mixed, 'jumlah' => int, 'pesan' => string)
 * Kompatibel PHP 5.2+.
 */

function kumpul_hasil($ok, $sumber, $data, $jumlah, $pesan) {
    return array('ok' => $ok, 'sumber' => $sumber, 'data' => $data, 'jumlah' => $jumlah, 'pesan' => $pesan);
}

function kumpul_jam($v) {
    if ($v === null || $v === '') return null;
    return substr($v, 0, 5);
}

/* ======================================================================
 * 1. JADWAL SIDANG (hari ini s/d N hari ke depan)
 * ==================================================================== */
function kumpul_jadwal_sidang($cfg, $dbOk) {
    $hari  = max(1, (int)$cfg['jadwal_hari_ke_depan']);
    $dari  = date('Y-m-d');
    $sampai = date('Y-m-d', strtotime('+' . ($hari - 1) . ' day'));

    if ($dbOk) {
        $t = 'perkara_jadwal_sidang';
        $kolom = array('j.tanggal_sidang', 'a.nomor_perkara', 'a.jenis_perkara_text', 'j.agenda', 'j.ruangan');
        if (db_kolom_ada($t, 'id'))             $kolom[] = 'j.id';
        if (db_kolom_ada($t, 'jam_sidang'))     $kolom[] = 'j.jam_sidang';
        if (db_kolom_ada($t, 'sampai_jam'))     $kolom[] = 'j.sampai_jam';
        if (db_kolom_ada($t, 'sidang_keliling'))$kolom[] = 'j.sidang_keliling';
        if (db_kolom_ada($t, 'ditunda'))        $kolom[] = 'j.ditunda';
        if ($cfg['jadwal_kirim_nama_pihak'])    { $kolom[] = 'a.pihak1_text'; $kolom[] = 'a.pihak2_text'; }

        $urut = 'j.tanggal_sidang, j.ruangan' . (db_kolom_ada($t, 'jam_sidang') ? ', j.jam_sidang' : '') . ', a.nomor_perkara';
        $rows = db_rows("SELECT " . implode(', ', $kolom) . "
                         FROM perkara_jadwal_sidang j
                         JOIN perkara a ON a.perkara_id = j.perkara_id
                         WHERE j.tanggal_sidang BETWEEN '" . $dari . "' AND '" . $sampai . "'
                         ORDER BY " . $urut);
        if ($rows !== false) {
            $data = array();
            foreach ($rows as $r) {
                $item = array(
                    'tanggal_sidang'  => $r['tanggal_sidang'],
                    'jam_mulai'       => isset($r['jam_sidang']) ? kumpul_jam($r['jam_sidang']) : null,
                    'jam_selesai'     => isset($r['sampai_jam']) ? kumpul_jam($r['sampai_jam']) : null,
                    'nomor_perkara'   => $r['nomor_perkara'],
                    'jenis_perkara'   => $r['jenis_perkara_text'],
                    'agenda'          => trim(strip_tags((string)$r['agenda'])),
                    'ruangan'         => trim((string)$r['ruangan']) != '' ? trim($r['ruangan']) : 'Belum Ditentukan',
                    'sidang_keliling' => (isset($r['sidang_keliling']) && $r['sidang_keliling'] == 'Y'),
                    'ditunda'         => (isset($r['ditunda']) && $r['ditunda'] == 'Y'),
                );
                if ($cfg['jadwal_kirim_nama_pihak']) {
                    $item['pihak1'] = strip_tags(str_replace('<br>', ', ', (string)$r['pihak1_text']));
                    $item['pihak2'] = strip_tags(str_replace('<br>', ', ', (string)$r['pihak2_text']));
                }
                $data[] = $item;
            }
            return kumpul_hasil(true, 'sipp_internal',
                array('dari' => $dari, 'sampai' => $sampai, 'jadwal' => $data), count($data), 'OK');
        }
        $pesanDb = db_error();
    } else {
        $pesanDb = 'SIPP internal tidak terhubung';
    }

    // ---- cadangan: API sipp-publik ----
    if (!empty($cfg['cadangan_api_jadwal'])) {
        $url = rtrim($cfg['cadangan_api_jadwal'], '/') . '/' . $dari . '/' . $sampai;
        $res = http_request('GET', $url, '', array('Accept: application/json'), $cfg['website']);
        $json = $res['ok'] ? json_decode($res['isi'], true) : null;
        if (is_array($json) && isset($json['data'])) {
            $data = array();
            foreach ($json['data'] as $r) {
                $data[] = array(
                    'tanggal_sidang'  => $r['tanggal_sidang'],
                    'jam_mulai'       => $r['jam_mulai'],
                    'jam_selesai'     => $r['jam_selesai'],
                    'nomor_perkara'   => $r['nomor_perkara'],
                    'jenis_perkara'   => $r['jenis_perkara'],
                    'agenda'          => $r['agenda'],
                    'ruangan'         => $r['ruangan'],
                    'sidang_keliling' => !empty($r['sidang_keliling']),
                    'ditunda'         => false,
                );
            }
            return kumpul_hasil(true, 'sipp_publik',
                array('dari' => $dari, 'sampai' => $sampai, 'jadwal' => $data), count($data),
                'Memakai cadangan sipp-publik (' . $pesanDb . ')');
        }
        return kumpul_hasil(false, '', null, 0, $pesanDb . '; cadangan gagal: ' . $res['pesan']);
    }
    return kumpul_hasil(false, '', null, 0, $pesanDb);
}

/* ======================================================================
 * 2. STATISTIK PERKARA (tahun berjalan)
 * ==================================================================== */
function kumpul_statistik_perkara($cfg, $dbOk) {
    if (!$dbOk) return kumpul_hasil(false, '', null, 0, 'SIPP internal tidak terhubung');

    $tahun = (int)date('Y');
    $awal  = $tahun . '-01-01';
    $akhir = $tahun . '-12-31';

    $masuk = db_rows("SELECT MONTH(tanggal_pendaftaran) AS bln, COUNT(*) AS jml FROM perkara
                      WHERE tanggal_pendaftaran BETWEEN '$awal' AND '$akhir' GROUP BY MONTH(tanggal_pendaftaran)");
    $putus = db_rows("SELECT MONTH(c.tanggal_putusan) AS bln, COUNT(*) AS jml FROM perkara_putusan c
                      JOIN perkara a ON a.perkara_id = c.perkara_id
                      WHERE c.tanggal_putusan BETWEEN '$awal' AND '$akhir' GROUP BY MONTH(c.tanggal_putusan)");
    $sisaLalu = db_rows("SELECT COUNT(*) AS jml FROM perkara a
                         LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
                         WHERE a.tanggal_pendaftaran < '$awal'
                           AND (c.tanggal_putusan IS NULL OR c.tanggal_putusan >= '$awal')");
    $jenisMasuk = db_rows("SELECT a.jenis_perkara_text AS jenis, COUNT(*) AS jml FROM perkara a
                           WHERE a.tanggal_pendaftaran BETWEEN '$awal' AND '$akhir'
                           GROUP BY a.jenis_perkara_text ORDER BY jml DESC");
    $jenisPutus = db_rows("SELECT a.jenis_perkara_text AS jenis, COUNT(*) AS jml FROM perkara_putusan c
                           JOIN perkara a ON a.perkara_id = c.perkara_id
                           WHERE c.tanggal_putusan BETWEEN '$awal' AND '$akhir'
                           GROUP BY a.jenis_perkara_text");

    if ($masuk === false || $putus === false || $sisaLalu === false || $jenisMasuk === false || $jenisPutus === false) {
        return kumpul_hasil(false, '', null, 0, db_error());
    }

    $namaBulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                       'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $mapMasuk = array(); foreach ($masuk as $r) $mapMasuk[(int)$r['bln']] = (int)$r['jml'];
    $mapPutus = array(); foreach ($putus as $r) $mapPutus[(int)$r['bln']] = (int)$r['jml'];

    $sisa = (int)$sisaLalu[0]['jml'];
    $sisaAwalTahun = $sisa;
    $bulanIni = (int)date('n');
    $perBulan = array();
    $totMasuk = 0; $totPutus = 0;
    for ($b = 1; $b <= $bulanIni; $b++) {
        $m = isset($mapMasuk[$b]) ? $mapMasuk[$b] : 0;
        $p = isset($mapPutus[$b]) ? $mapPutus[$b] : 0;
        $perBulan[] = array('bulan' => $b, 'nama_bulan' => $namaBulan[$b],
                            'sisa_awal' => $sisa, 'masuk' => $m, 'putus' => $p, 'sisa_akhir' => $sisa + $m - $p);
        $sisa = $sisa + $m - $p;
        $totMasuk += $m; $totPutus += $p;
    }

    $mapJenisPutus = array(); foreach ($jenisPutus as $r) $mapJenisPutus[(string)$r['jenis']] = (int)$r['jml'];
    $perJenis = array();
    foreach ($jenisMasuk as $r) {
        $j = (string)$r['jenis'];
        $perJenis[] = array('jenis_perkara' => $j, 'masuk' => (int)$r['jml'],
                            'putus' => isset($mapJenisPutus[$j]) ? $mapJenisPutus[$j] : 0);
        unset($mapJenisPutus[$j]);
    }
    foreach ($mapJenisPutus as $j => $p) $perJenis[] = array('jenis_perkara' => $j, 'masuk' => 0, 'putus' => $p);

    $total = $sisaAwalTahun + $totMasuk;
    $data = array(
        'tahun'           => $tahun,
        'sisa_tahun_lalu' => $sisaAwalTahun,
        'masuk'           => $totMasuk,
        'putus'           => $totPutus,
        'sisa'            => $sisa,
        'persen_putus'    => $total > 0 ? round($totPutus / $total * 100, 2) : 0,
        'per_bulan'       => $perBulan,
        'per_jenis'       => $perJenis,
    );
    return kumpul_hasil(true, 'sipp_internal', $data, $totMasuk, 'OK');
}

/* ======================================================================
 * 3. PANGGILAN GHAIB (belum putus) - untuk pengumuman di website
 * ==================================================================== */
function kumpul_panggilan_ghaib($cfg, $dbOk) {
    if (!$dbOk) return kumpul_hasil(false, '', null, 0, 'SIPP internal tidak terhubung');

    $filterGhaib = "(LOWER(h.alamat) LIKE '%tidak diketahui%'
                  OR LOWER(h.alamat) LIKE '%tdk diketahui%'
                  OR LOWER(h.alamat) LIKE '%tidak jelas%'
                  OR LOWER(h.alamat) REGEXP '(^|[^[:alpha:]])(ghaib|gaib)([^[:alpha:]]|$)')";

    $rows = db_rows("SELECT a.perkara_id, a.nomor_perkara, a.jenis_perkara_text, a.tanggal_pendaftaran,
               (SELECT GROUP_CONCAT(h.nama ORDER BY h.urutan SEPARATOR ', ') FROM perkara_pihak2 h
                 WHERE h.perkara_id = a.perkara_id AND $filterGhaib)       AS nama_ghaib,
               (SELECT GROUP_CONCAT(h.alamat ORDER BY h.urutan SEPARATOR ' | ') FROM perkara_pihak2 h
                 WHERE h.perkara_id = a.perkara_id AND $filterGhaib)       AS alamat_ghaib,
               (SELECT MIN(i.tanggal_sidang) FROM perkara_jadwal_sidang i
                 WHERE i.perkara_id = a.perkara_id AND i.tanggal_sidang >= CURDATE()) AS sidang_berikutnya
        FROM perkara a
        LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
        WHERE c.tanggal_putusan IS NULL
          AND a.tanggal_pendaftaran >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
          AND EXISTS (SELECT 1 FROM perkara_pihak2 h WHERE h.perkara_id = a.perkara_id AND $filterGhaib)
        ORDER BY a.tanggal_pendaftaran ASC, a.perkara_id ASC");
    if ($rows === false) return kumpul_hasil(false, '', null, 0, db_error());

    // relaas terbaru Tergugat/Termohon (berkas PDF dari folder dokumen SIPP)
    $relaas = array();
    $catatan = '';
    if (!empty($cfg['ghaib_kirim_relaas']) && count($rows) > 0) {
        $daftar = array();
        foreach ($rows as $r) $daftar[(int)$r['perkara_id']] = $r['nomor_perkara'];
        if (relaas_folder($cfg) == '') $catatan = 'folder dokumen SIPP tidak ditemukan, relaas tidak dikirim (isi "Folder dokumen SIPP" di Pengaturan lanjutan)';
        else $relaas = relaas_terbaru($cfg, $daftar);
    }
    $maksByte = max(1, (int)(isset($cfg['relaas_maks_mb']) ? $cfg['relaas_maks_mb'] : 20)) * 1048576;
    $adaRelaas = 0; $besar = 0;

    $data = array();
    foreach ($rows as $r) {
        $item = array(
            'nomor_perkara'      => $r['nomor_perkara'],
            'jenis_perkara'      => $r['jenis_perkara_text'],
            'tanggal_daftar'     => $r['tanggal_pendaftaran'],
            'sidang_berikutnya'  => $r['sidang_berikutnya'],
        );
        if ($cfg['ghaib_kirim_nama'])   $item['nama_pihak']   = $r['nama_ghaib'];
        if ($cfg['ghaib_kirim_alamat']) $item['alamat_pihak'] = $r['alamat_ghaib'];
        if (isset($relaas[(int)$r['perkara_id']])) {
            $rl = $relaas[(int)$r['perkara_id']];
            $info = relaas_info($r['nomor_perkara'], $rl[0], $rl[1], $maksByte);
            if ($info) { $item['relaas'] = $info; $adaRelaas++; if (!empty($info['terlalu_besar'])) $besar++; }
        }
        $data[] = $item;
    }
    if (!empty($cfg['ghaib_kirim_relaas']) && $catatan == '') {
        $catatan = $adaRelaas . ' perkara punya relaas';
        if ($besar > 0) $catatan .= ', ' . $besar . ' relaas tidak dikirim karena lebih dari ' . round($maksByte / 1048576, 1) . ' MB';
    }
    return kumpul_hasil(true, 'sipp_internal', array('perkara' => $data), count($data), $catatan != '' ? 'OK; ' . $catatan : 'OK');
}

/* ======================================================================
 * 4. ANTRIAN (query bebas dari config)
 * ==================================================================== */
function kumpul_antrian($cfg, $dbOk) {
    if (trim($cfg['antrian_sql']) == '') return kumpul_hasil(false, '', null, 0, 'antrian_sql belum diisi di config.php');
    if (!$dbOk) return kumpul_hasil(false, '', null, 0, 'SIPP internal tidak terhubung');
    if (!preg_match('/^\s*SELECT\s/i', $cfg['antrian_sql'])) {
        return kumpul_hasil(false, '', null, 0, 'antrian_sql harus berupa query SELECT');
    }
    $rows = db_rows($cfg['antrian_sql']);
    if ($rows === false) return kumpul_hasil(false, '', null, 0, db_error());
    return kumpul_hasil(true, 'sipp_internal', array('tanggal' => date('Y-m-d'), 'antrian' => $rows), count($rows), 'OK');
}

/** Daftar jenis data => fungsi pengumpul */
function daftar_jenis() {
    return array(
        'jadwal_sidang'     => 'kumpul_jadwal_sidang',
        'statistik_perkara' => 'kumpul_statistik_perkara',
        'panggilan_ghaib'   => 'kumpul_panggilan_ghaib',
        'antrian'           => 'kumpul_antrian',
    );
}
