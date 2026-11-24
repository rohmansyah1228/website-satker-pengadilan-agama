<?php
/**
 * Pengiriman data ke website satker + pencatatan status/log.
 *
 * Format request ke website satker:
 *   POST {website.url}
 *   Header:
 *     Content-Type: application/json
 *     X-Api-Key:   {api_key}
 *     X-Timestamp: {unix time}
 *     X-Signature: hex(HMAC-SHA256(secret, timestamp + "." + body))
 *   Body (JSON):
 *     {"jenis": "...", "sumber": "...", "dibuat": "Y-m-d H:i:s", "jumlah": n, "data": {...}}
 */

function tanda_tangan($secret, $timestamp, $body) {
    return hash_hmac('sha256', $timestamp . '.' . $body, $secret);
}

function kirim_ke_website($cfg, $jenis, $hasil) {
    $paket = array(
        'jenis'  => $jenis,
        'sumber' => $hasil['sumber'],
        'dibuat' => date('Y-m-d H:i:s'),
        'jumlah' => $hasil['jumlah'],
        'data'   => $hasil['data'],
    );
    $body = json_aman($paket);
    if ($body === false || $body === null) {
        return array('ok' => false, 'kode' => 0, 'isi' => '', 'pesan' => 'Gagal membuat JSON');
    }
    $ts = (string)time();
    $header = array(
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Api-Key: ' . $cfg['website']['api_key'],
        'X-Timestamp: ' . $ts,
        'X-Signature: ' . tanda_tangan($cfg['website']['secret'], $ts, $body),
    );
    $res = http_request('POST', $cfg['website']['url'], $body, $header, $cfg['website']);
    if (!$res['ok']) {
        $j = json_decode($res['isi'], true);
        if (is_array($j) && isset($j['pesan'])) $res['pesan'] .= ' - ' . $j['pesan'];
    }
    return $res;
}

/* ---------- status & log (lihat lib/simpan.php) ---------- */
function baca_status() {
    $s = json_decode(simpan_baca('status.json'), true);
    return is_array($s) ? $s : array();
}

function simpan_status($jenis, $info) {
    $s = baca_status();
    $s[$jenis] = $info;
    simpan_tulis('status.json', json_aman($s));
}

function tulis_log($baris) {
    simpan_log('[' . date('Y-m-d H:i:s') . '] ' . $baris);
}

/**
 * Sidik isi data (tanpa waktu pembuatan) untuk mengetahui apakah data berubah sejak kiriman terakhir.
 * Relaas ikut dihitung lewat sidik sha256-nya, jadi relaas baru = data berubah.
 */
function sidik_data($data) {
    return sha1(json_aman(urut_kunci($data)));
}

/**
 * Jalankan semua (atau satu) jenis: kumpulkan, kirim, simpan status.
 * Hanya data yang BERUBAH (atau bertambah/berkurang) yang dikirim. Data yang sama dengan kiriman terakhir
 * yang berhasil tidak dikirim, kecuali sudah lebih dari 'kirim_ulang_jam' jam (tanda aplikasi kantor masih
 * berjalan) atau $paksa = true.
 * Hasil: array jenis => ringkasan.
 */
function jalankan_pengiriman($cfg, $hanyaJenis, $paksa = false) {
    $dbOk = pastikan_db($cfg);
    if (!$dbOk) tulis_log('Koneksi SIPP internal gagal: ' . db_error());

    $ringkas = array();
    foreach (daftar_jenis() as $jenis => $fungsi) {
        if ($hanyaJenis != '' && $hanyaJenis != $jenis) continue;
        if (empty($cfg['aktif'][$jenis])) continue;

        $hasil = call_user_func($fungsi, $cfg, $dbOk);
        $info = array('waktu' => date('Y-m-d H:i:s'), 'sumber' => $hasil['sumber'],
                      'jumlah' => $hasil['jumlah'], 'ok' => false, 'pesan' => $hasil['pesan']);
        $lama = baca_status();
        $lj   = isset($lama[$jenis]) ? $lama[$jenis] : array();
        $sidik = $hasil['ok'] ? sidik_data($hasil['data']) : '';
        $jedaUlang = max(1, (int)(isset($cfg['kirim_ulang_jam']) ? $cfg['kirim_ulang_jam'] : 6)) * 3600;
        $sama = $hasil['ok'] && !$paksa && !empty($lj['ok']) && isset($lj['sidik']) && $lj['sidik'] === $sidik
                && isset($lj['terakhir_sukses']) && (time() - strtotime($lj['terakhir_sukses'])) < $jedaUlang;
        if ($sama) {
            // tidak ada yang berubah: tidak dikirim, status lama dipertahankan
            $info['ok'] = true;
            $info['tidak_berubah'] = true;
            $info['sidik'] = $sidik;
            $info['terakhir_sukses'] = $lj['terakhir_sukses'];
            $info['pesan'] = 'Tidak ada perubahan, tidak dikirim (terakhir terkirim ' . $lj['terakhir_sukses'] . ')';
        } elseif ($hasil['ok']) {
            $res = kirim_ke_website($cfg, $jenis, $hasil);
            $info['ok']    = $res['ok'];
            $info['pesan'] = $res['ok'] ? ('Terkirim (' . $res['pesan'] . ')' . ($hasil['pesan'] != 'OK' ? '; ' . $hasil['pesan'] : ''))
                                        : ('Gagal kirim: ' . $res['pesan']);
            if ($res['ok']) { $info['terakhir_sukses'] = $info['waktu']; $info['sidik'] = $sidik; }
            // website menjawab sidik relaas yang belum dimilikinya → kirim berkasnya
            if ($res['ok'] && $jenis == 'panggilan_ghaib' && !empty($cfg['ghaib_kirim_relaas'])) {
                $j = json_decode($res['isi'], true);
                if (is_array($j) && isset($j['butuh_relaas']) && count($j['butuh_relaas']) > 0) {
                    $rl = kirim_relaas($cfg, $j['butuh_relaas'], isset($j['maks_relaas_byte']) ? (int)$j['maks_relaas_byte'] : 0);
                    if ($rl['pesan'] != '') $info['pesan'] .= '; ' . $rl['pesan'];
                    if ($rl['gagal'] > 0) unset($info['sidik']); // relaas yang gagal dicoba lagi pada putaran berikutnya
                }
            }
        }
        if (!$info['ok'] && isset($lama[$jenis]['terakhir_sukses'])) $info['terakhir_sukses'] = $lama[$jenis]['terakhir_sukses'];
        simpan_status($jenis, $info);
        tulis_log($jenis . ': ' . ($info['ok'] ? 'OK' : 'GAGAL') . ' | ' . $info['jumlah'] . ' data | ' . $info['pesan']);
        $ringkas[$jenis] = $info;
    }
    return $ringkas;
}
