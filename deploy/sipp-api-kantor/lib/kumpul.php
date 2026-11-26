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

/** Nilai "ya" dari SIPP (Y) atau query sendiri (Y/1/ya/true) */
function kumpul_ya($v) {
    $v = strtolower(trim((string)$v));
    return $v == 'y' || $v == '1' || $v == 'ya' || $v == 'true';
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
        $rows = query_ambil($cfg, 'jadwal');
        if ($rows !== false) {
            $data = array();
            foreach ($rows as $r) {
                $item = array(
                    'tanggal_sidang'  => $r['tanggal_sidang'],
                    'jam_mulai'       => isset($r['jam_sidang']) ? kumpul_jam($r['jam_sidang']) : null,
                    'jam_selesai'     => isset($r['sampai_jam']) ? kumpul_jam($r['sampai_jam']) : null,
                    'nomor_perkara'   => $r['nomor_perkara'],
                    'jenis_perkara'   => isset($r['jenis_perkara_text']) ? $r['jenis_perkara_text'] : '',
                    'agenda'          => isset($r['agenda']) ? trim(strip_tags((string)$r['agenda'])) : '',
                    'ruangan'         => (isset($r['ruangan']) && trim((string)$r['ruangan']) != '') ? trim($r['ruangan']) : 'Belum Ditentukan',
                    'sidang_keliling' => (isset($r['sidang_keliling']) && kumpul_ya($r['sidang_keliling'])),
                    'ditunda'         => (isset($r['ditunda']) && kumpul_ya($r['ditunda'])),
                );
                if ($cfg['jadwal_kirim_nama_pihak']) {
                    $item['pihak1'] = isset($r['pihak1_text']) ? strip_tags(str_replace('<br>', ', ', (string)$r['pihak1_text'])) : '';
                    $item['pihak2'] = isset($r['pihak2_text']) ? strip_tags(str_replace('<br>', ', ', (string)$r['pihak2_text'])) : '';
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

    $masuk      = query_ambil($cfg, 'stat_masuk');
    $putus      = query_ambil($cfg, 'stat_putus');
    $sisaLalu   = query_ambil($cfg, 'stat_sisa');
    $jenisMasuk = query_ambil($cfg, 'stat_jenis_masuk');
    $jenisPutus = query_ambil($cfg, 'stat_jenis_putus');

    if ($masuk === false || $putus === false || $sisaLalu === false || $jenisMasuk === false || $jenisPutus === false) {
        return kumpul_hasil(false, '', null, 0, db_error());
    }

    $namaBulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                       'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $mapMasuk = array(); foreach ($masuk as $r) $mapMasuk[(int)$r['bln']] = (int)$r['jml'];
    $mapPutus = array(); foreach ($putus as $r) $mapPutus[(int)$r['bln']] = (int)$r['jml'];

    $sisa = $sisaLalu ? (int)$sisaLalu[0]['jml'] : 0;
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
 * 3. PANGGILAN GHAIB - belum putus (pengumuman panggilan) + sudah putus 1 tahun terakhir
 * ==================================================================== */
function kumpul_panggilan_ghaib($cfg, $dbOk) {
    if (!$dbOk) return kumpul_hasil(false, '', null, 0, 'SIPP internal tidak terhubung');

    $rows = query_ambil($cfg, 'ghaib');
    if ($rows === false) return kumpul_hasil(false, '', null, 0, db_error());

    // perkara ghaib yang SUDAH PUTUS dalam 1 tahun terakhir (tabel "Sudah Putus" di website)
    $rowsPutus = query_ambil($cfg, 'ghaib_putus');
    if ($rowsPutus === false) return kumpul_hasil(false, '', null, 0, db_error());

    // relaas terbaru Tergugat/Termohon (berkas PDF dari folder dokumen SIPP)
    $relaas = array();
    $catatan = '';
    if (!empty($cfg['ghaib_kirim_relaas']) && count($rows) > 0) {
        $daftar = array();
        foreach ($rows as $r) $daftar[(int)$r['perkara_id']] = $r['nomor_perkara'];
        foreach ($rowsPutus as $r) $daftar[(int)$r['perkara_id']] = $r['nomor_perkara'];
        if (relaas_folder($cfg) == '') $catatan = 'folder dokumen SIPP tidak ditemukan, relaas tidak dikirim (isi "Folder aplikasi SIPP" di Pengaturan bagian 1)';
        else $relaas = relaas_terbaru($cfg, $daftar);
    }
    $maksByte = max(1, (int)(isset($cfg['relaas_maks_mb']) ? $cfg['relaas_maks_mb'] : 20)) * 1048576;
    $adaRelaas = 0; $besar = 0;

    $data = array();
    foreach ($rows as $r) {
        $item = array(
            'nomor_perkara'      => $r['nomor_perkara'],
            'jenis_perkara'      => isset($r['jenis_perkara_text']) ? $r['jenis_perkara_text'] : '',
            'tanggal_daftar'     => isset($r['tanggal_pendaftaran']) ? $r['tanggal_pendaftaran'] : null,
            'sidang_berikutnya'  => isset($r['sidang_berikutnya']) ? $r['sidang_berikutnya'] : null,
        );
        if ($cfg['ghaib_kirim_nama'])   $item['nama_pihak']   = isset($r['nama_ghaib']) ? $r['nama_ghaib'] : '';
        if ($cfg['ghaib_kirim_alamat']) $item['alamat_pihak'] = isset($r['alamat_ghaib']) ? $r['alamat_ghaib'] : '';
        if (isset($relaas[(int)$r['perkara_id']])) {
            $rl = $relaas[(int)$r['perkara_id']];
            $info = relaas_info($r['nomor_perkara'], $rl[0], $rl[1], $maksByte);
            if ($info) { $item['relaas'] = $info; $adaRelaas++; if (!empty($info['terlalu_besar'])) $besar++; }
        }
        $data[] = $item;
    }
    $dataPutus = array();
    foreach ($rowsPutus as $r) {
        if (empty($r['tanggal_putusan']) || substr($r['tanggal_putusan'], 0, 10) == '0000-00-00') continue;
        $item = array(
            'nomor_perkara'   => $r['nomor_perkara'],
            'jenis_perkara'   => isset($r['jenis_perkara_text']) ? $r['jenis_perkara_text'] : '',
            'tanggal_daftar'  => isset($r['tanggal_pendaftaran']) ? $r['tanggal_pendaftaran'] : null,
            'tanggal_putusan' => substr($r['tanggal_putusan'], 0, 10),
            'sidang_terakhir' => isset($r['sidang_terakhir']) ? $r['sidang_terakhir'] : null,
        );
        if ($cfg['ghaib_kirim_nama']) $item['nama_pihak'] = isset($r['nama_ghaib']) ? $r['nama_ghaib'] : '';
        if (isset($relaas[(int)$r['perkara_id']])) {
            $rl = $relaas[(int)$r['perkara_id']];
            $info = relaas_info($r['nomor_perkara'], $rl[0], $rl[1], $maksByte);
            if ($info) { $item['relaas'] = $info; $adaRelaas++; if (!empty($info['terlalu_besar'])) $besar++; }
        }
        $dataPutus[] = $item;
    }
    if (!empty($cfg['ghaib_kirim_relaas']) && $catatan == '') {
        $catatan = $adaRelaas . ' perkara punya relaas';
        if ($besar > 0) $catatan .= ', ' . $besar . ' relaas tidak dikirim karena lebih dari ' . round($maksByte / 1048576, 1) . ' MB';
    }
    $catatan = count($dataPutus) . ' sudah putus' . ($catatan != '' ? ', ' . $catatan : '');
    return kumpul_hasil(true, 'sipp_internal', array('perkara' => $data, 'putus' => $dataPutus), count($data), 'OK; ' . $catatan);
}

/* ======================================================================
 * 4. ANTRIAN (query bebas dari config)
 * ==================================================================== */
function kumpul_antrian($cfg, $dbOk) {
    $t = query_tersimpan($cfg, 'antrian');
    if ($t[0] == '') return kumpul_hasil(false, '', null, 0, 'Query antrian belum diisi (menu Query Sendiri).');
    if (!$dbOk) return kumpul_hasil(false, '', null, 0, 'SIPP internal tidak terhubung');
    $rows = query_ambil($cfg, 'antrian');
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
