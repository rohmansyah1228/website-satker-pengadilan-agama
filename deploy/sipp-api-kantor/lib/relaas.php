<?php
/**
 * Relaas panggilan ghaib: cari berkas PDF relaas Tergugat/Termohon di SIPP, lalu kirim ke website.
 * Cara mencari sama dengan halaman Monitoring Panggilan Ghaib di SIMERAK:
 *   1. tabel perkara_pelaksanaan_relaas (kolom doc_relaas*, tanggal*, pihak_id → perkara_pihak2)
 *   2. bila tabel/kolom tidak ada: cari langsung di folder dokumen SIPP
 *      {folder}/TAHUN/BULAN/PA.Tlb_2026_Pdt.G_0671_relaas_*.pdf
 * Database SIPP hanya DIBACA; berkas hanya DIBACA. Kompatibel PHP 5.2+.
 */

$GLOBALS['RELAAS_BERKAS'] = array(); // sha256 => array(path, nomor, nama, tanggal) untuk tahap kirim

/** Folder berisi subfolder TAHUN/BULAN dokumen SIPP? */
function relaas_folder_sah($d) {
    if ($d == '' || !@is_dir($d)) return false;
    if (preg_match('#/file/doc/?$#i', $d)) return true;
    $isi = @scandir($d);
    if ($isi) foreach ($isi as $x) if (preg_match('/^(19|20)\d\d$/', $x) && is_dir($d . '/' . $x)) return true;
    return false;
}

/**
 * Isian "Folder aplikasi SIPP" → folder dokumennya. Boleh diisi folder aplikasi SIPP
 * (C:/xampp/htdocs/sipp) maupun langsung folder dokumennya (.../sipp/resources/file/doc).
 */
function relaas_dari_isian($isian) {
    $d = rtrim(str_replace('\\', '/', trim((string)$isian)), '/');
    if ($d == '') return '';
    foreach (array($d . '/resources/file/doc', $d . '/file/doc', $d . '/doc', $d) as $k) {
        if (relaas_folder_sah($k)) return $k . '/';
    }
    return '';
}

/**
 * Info folder dokumen SIPP: array('dir' => '.../resources/file/doc/' atau '', 'asal' => 'pengaturan'|'otomatis'|'',
 * 'isian_salah' => bool). Hasil di-cache per permintaan.
 */
function relaas_folder_info($cfg) {
    if (isset($GLOBALS['RELAAS_INFO'])) return $GLOBALS['RELAAS_INFO'];
    $info = array('dir' => '', 'asal' => '', 'isian_salah' => false);
    $isian = isset($cfg['relaas_dir']) ? trim($cfg['relaas_dir']) : '';
    if ($isian != '') {
        $info['dir'] = relaas_dari_isian($isian);
        if ($info['dir'] != '') $info['asal'] = 'pengaturan'; else $info['isian_salah'] = true;
    }
    if ($info['dir'] == '') {
        $base = dirname(dirname(__FILE__)); // folder sipp-api-kantor
        $akar = array();
        // folder aplikasi SIPP yang ditemukan saat menyambung database otomatis
        $f = !empty($GLOBALS['DB_SUMBER_FILE']) ? $GLOBALS['DB_SUMBER_FILE'] : '';
        if ($f == '' && function_exists('dir_sementara') && is_file(dir_sementara() . '/lokasi_sipp.txt')) $f = trim(@file_get_contents(dir_sementara() . '/lokasi_sipp.txt'));
        $f = str_replace('\\', '/', $f);
        if ($f != '' && ($pos = strpos($f, '/application/')) !== false) $akar[] = substr($f, 0, $pos);
        $akar[] = dirname($base) . '/sipp';
        $akar[] = dirname(dirname($base)) . '/sipp';
        if (!empty($_SERVER['DOCUMENT_ROOT'])) $akar[] = $_SERVER['DOCUMENT_ROOT'] . '/sipp';
        $akar[] = 'C:/xampp/htdocs/sipp';
        $akar[] = 'D:/xampp/htdocs/sipp';
        $akar[] = '/var/www/html/sipp';
        $akar[] = '/opt/lampp/htdocs/sipp';
        // folder lain di web root yang namanya mengandung "sipp" (mis. SIPP, sipp_tuba)
        $isi = @scandir(dirname($base));
        if ($isi) foreach ($isi as $x) if ($x != '.' && $x != '..' && stripos($x, 'sipp') !== false && stripos($x, 'api') === false) $akar[] = dirname($base) . '/' . $x;
        foreach ($akar as $a) {
            $d = relaas_dari_isian($a);
            if ($d != '') { $info['dir'] = $d; $info['asal'] = 'otomatis'; break; }
        }
    }
    $GLOBALS['RELAAS_INFO'] = $info;
    return $info;
}

/** Folder dokumen SIPP di disk (resources/file/doc), atau '' bila tidak ditemukan. */
function relaas_folder($cfg) {
    $i = relaas_folder_info($cfg);
    return $i['dir'];
}

/** Isi kolom doc_relaas (path atau nama berkas) → path berkas di disk, atau ''. */
function relaas_path_disk($doc, $dir) {
    $doc = trim(str_replace('\\', '/', (string)$doc));
    if ($doc == '' || $dir == '') return '';
    $pos = strpos($doc, 'file/doc/');
    if ($pos !== false) $doc = substr($doc, $pos + strlen('file/doc/'));
    $doc = ltrim($doc, '/');
    if (strpos($doc, '..') !== false) return '';
    if (strpos($doc, '/') !== false) return is_file($dir . $doc) ? $dir . $doc : '';
    $temu = glob($dir . '*/*/' . $doc);
    return ($temu && is_file($temu[0])) ? str_replace('\\', '/', $temu[0]) : '';
}

/** Cari berkas relaas langsung di folder (cadangan bila tabel relaas tidak ada). Hasil: array(path, waktu) terbaru, atau null. */
function relaas_cari_folder($nomorPerkara, $dir) {
    if ($dir == '') return null;
    $b = explode('/', $nomorPerkara);
    if (count($b) < 4) return null;
    $thn = (int)trim($b[2]);
    if ($thn < 2000) return null;
    $awalan = trim($b[3]) . '_' . $thn . '_' . trim($b[1]) . '_' . str_pad(trim($b[0]), 4, '0', STR_PAD_LEFT) . '_relaas_';
    $baru = null;
    for ($y = $thn; $y <= (int)date('Y'); $y++) {
        for ($m = 1; $m <= 12; $m++) {
            $folder = $dir . $y . '/' . str_pad($m, 2, '0', STR_PAD_LEFT) . '/';
            if (!is_dir($folder)) continue;
            $files = glob($folder . $awalan . '*');
            if (!$files) continue;
            foreach ($files as $f) {
                if (strtolower(substr($f, -4)) != '.pdf' || !is_file($f)) continue;
                $t = (int)@filemtime($f);
                if ($baru === null || $t >= $baru[1]) $baru = array(str_replace('\\', '/', $f), $t);
            }
        }
    }
    return $baru;
}

/**
 * Relaas terbaru (yang berkasnya ada) per perkara.
 * $perkara: array perkara_id => nomor_perkara. Hasil: perkara_id => array(path, tanggal).
 */
function relaas_terbaru($cfg, $perkara) {
    $hasil = array();
    $dir = relaas_folder($cfg);
    if ($dir == '' || count($perkara) == 0) return $hasil;

    $t = 'perkara_pelaksanaan_relaas';
    $kolDoc = ''; $kolTgl = ''; $cadTgl = ''; $adaPihak = false;
    $kolom = db_rows("SHOW COLUMNS FROM `" . $t . "`");
    if ($kolom) {
        foreach ($kolom as $k) {
            $f = strtolower($k['Field']);
            if ($f == 'pihak_id') $adaPihak = true;
            if ($kolDoc == '' && strpos($f, 'doc_rel') === 0) $kolDoc = $k['Field'];
            if (strpos($f, 'tanggal') === 0 || strpos($f, 'tgl') === 0) {
                if ($kolTgl == '' && (strpos($f, 'relaas') !== false || strpos($f, 'pelaksanaan') !== false || strpos($f, 'panggil') !== false)) $kolTgl = $k['Field'];
                if ($cadTgl == '') $cadTgl = $k['Field'];
            }
        }
        if ($kolTgl == '') $kolTgl = $cadTgl;
    }

    if ($kolDoc != '') {
        $ids = array();
        foreach ($perkara as $id => $no) $ids[] = (int)$id;
        // hanya relaas untuk Tergugat/Termohon (pihak_id ada di perkara_pihak2), terbaru lebih dulu
        $sql = "SELECT r.perkara_id, r.`" . $kolDoc . "` AS doc" . ($kolTgl != '' ? ", r.`" . $kolTgl . "` AS tgl" : ", NULL AS tgl")
             . " FROM `" . $t . "` r"
             . ($adaPihak ? " JOIN perkara_pihak2 h ON h.perkara_id = r.perkara_id AND h.pihak_id = r.pihak_id" : "")
             . " WHERE r.perkara_id IN (" . implode(',', $ids) . ") AND r.`" . $kolDoc . "` IS NOT NULL AND r.`" . $kolDoc . "` <> ''"
             . " ORDER BY r.perkara_id" . ($kolTgl != '' ? ", r.`" . $kolTgl . "` DESC" : '');
        $rows = db_rows($sql);
        if ($rows !== false) {
            foreach ($rows as $r) {
                $id = (int)$r['perkara_id'];
                if (isset($hasil[$id])) continue; // sudah dapat yang terbaru
                $path = relaas_path_disk($r['doc'], $dir);
                if ($path == '') continue;        // berkas belum diunggah di SIPP → coba relaas sebelumnya
                $tgl = (!empty($r['tgl']) && substr($r['tgl'], 0, 10) != '0000-00-00') ? substr($r['tgl'], 0, 10) : date('Y-m-d', (int)@filemtime($path));
                $hasil[$id] = array($path, $tgl);
            }
            return $hasil;
        }
    }
    // cadangan: cari di folder berdasarkan pola nama berkas
    foreach ($perkara as $id => $no) {
        $f = relaas_cari_folder($no, $dir);
        if ($f) $hasil[(int)$id] = array($f[0], date('Y-m-d', $f[1]));
    }
    return $hasil;
}

/** Keterangan singkat relaas untuk dikirim bersama data ghaib (tanpa path di server). */
function relaas_info($nomor, $path, $tgl, $maksByte) {
    $ukuran = (int)@filesize($path);
    if ($ukuran <= 0) return null;
    $sha = hash_file('sha256', $path);
    if (!$sha) return null;
    $info = array('nama' => basename($path), 'tanggal' => $tgl, 'ukuran' => $ukuran, 'sha256' => $sha);
    if ($ukuran > $maksByte) {
        $info['terlalu_besar'] = true;
        unset($info['sha256']); // website tidak akan memintanya
    } else {
        $GLOBALS['RELAAS_BERKAS'][$sha] = array('path' => $path, 'nomor' => $nomor, 'nama' => basename($path), 'tanggal' => $tgl);
    }
    return $info;
}

/** Naikkan memory_limit bila lebih kecil (tidak pernah menurunkan). */
function relaas_naikkan_memori($target) {
    $kini = trim((string)ini_get('memory_limit'));
    if ($kini == '-1') return;
    $byte = 0; $n = (int)$kini; $u = strtolower(substr($kini, -1));
    $byte = $u == 'g' ? $n * 1073741824 : ($u == 'm' ? $n * 1048576 : ($u == 'k' ? $n * 1024 : $n));
    if ($byte < (int)$target * 1048576) @ini_set('memory_limit', $target);
}

/**
 * Kirim berkas relaas yang diminta website (daftar sha256 dari jawaban panggilan_ghaib).
 * $maksWebsite: ukuran PDF terbesar yang bisa diterima website (byte, dari jawaban; 0 = tidak diketahui).
 * Hasil: array('kirim' => n, 'gagal' => n, 'pesan' => '...')
 */
function kirim_relaas($cfg, $butuh, $maksWebsite) {
    $hasil = array('kirim' => 0, 'gagal' => 0, 'pesan' => '');
    if (!is_array($butuh)) return $hasil;
    $batas = 30; // per putaran, agar tidak terlalu lama; sisanya pada pengiriman berikutnya
    // berkas besar (s.d. 20 MB → ± 28 MB base64): beri waktu & memori cukup
    if (isset($cfg['website']['timeout']) && (int)$cfg['website']['timeout'] < 300) $cfg['website']['timeout'] = 300;
    if (function_exists('set_time_limit')) @set_time_limit(0);
    relaas_naikkan_memori('512M');
    $pesanGagal = array();
    foreach ($butuh as $sha) {
        if (!is_string($sha) || !isset($GLOBALS['RELAAS_BERKAS'][$sha])) continue;
        if ($hasil['kirim'] + $hasil['gagal'] >= $batas) break;
        $b = $GLOBALS['RELAAS_BERKAS'][$sha];
        if ($maksWebsite > 0 && (int)@filesize($b['path']) > $maksWebsite) {
            $hasil['gagal']++;
            $pesanGagal[] = $b['nomor'] . ': relaas lebih besar dari batas hosting website (' . round($maksWebsite / 1048576, 1) . ' MB), terlalu besar';
            continue;
        }
        $isi = @file_get_contents($b['path']);
        if ($isi === false || substr($isi, 0, 5) != '%PDF-' || hash('sha256', $isi) !== $sha) {
            $hasil['gagal']++;
            $pesanGagal[] = $b['nomor'] . ': berkas tidak terbaca atau bukan PDF';
            continue;
        }
        $res = kirim_ke_website($cfg, 'relaas_ghaib', array(
            'sumber' => 'sipp_internal', 'jumlah' => 1,
            'data'   => array('nomor_perkara' => $b['nomor'], 'nama' => $b['nama'], 'tanggal' => $b['tanggal'],
                              'sha256' => $sha, 'pdf' => base64_encode($isi)),
        ));
        unset($isi);
        if ($res['ok']) $hasil['kirim']++;
        else { $hasil['gagal']++; $pesanGagal[] = $b['nomor'] . ': ' . $res['pesan']; }
    }
    if ($hasil['kirim'] > 0) $hasil['pesan'] = $hasil['kirim'] . ' relaas terkirim';
    if ($hasil['gagal'] > 0) $hasil['pesan'] .= ($hasil['pesan'] != '' ? ', ' : '') . $hasil['gagal'] . ' relaas gagal (' . implode('; ', array_slice($pesanGagal, 0, 3)) . ')';
    return $hasil;
}
