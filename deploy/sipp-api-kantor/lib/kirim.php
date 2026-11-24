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
 * Jalankan semua (atau satu) jenis: kumpulkan, kirim, simpan status.
 * Hasil: array jenis => ringkasan.
 */
function jalankan_pengiriman($cfg, $hanyaJenis) {
    $dbOk = pastikan_db($cfg);
    if (!$dbOk) tulis_log('Koneksi SIPP internal gagal: ' . db_error());

    $ringkas = array();
    foreach (daftar_jenis() as $jenis => $fungsi) {
        if ($hanyaJenis != '' && $hanyaJenis != $jenis) continue;
        if (empty($cfg['aktif'][$jenis])) continue;

        $hasil = call_user_func($fungsi, $cfg, $dbOk);
        $info = array('waktu' => date('Y-m-d H:i:s'), 'sumber' => $hasil['sumber'],
                      'jumlah' => $hasil['jumlah'], 'ok' => false, 'pesan' => $hasil['pesan']);
        if ($hasil['ok']) {
            $res = kirim_ke_website($cfg, $jenis, $hasil);
            $info['ok']    = $res['ok'];
            $info['pesan'] = $res['ok'] ? ('Terkirim (' . $res['pesan'] . ')' . ($hasil['pesan'] != 'OK' ? '; ' . $hasil['pesan'] : ''))
                                        : ('Gagal kirim: ' . $res['pesan']);
            if ($res['ok']) $info['terakhir_sukses'] = $info['waktu'];
        }
        $lama = baca_status();
        if (!$info['ok'] && isset($lama[$jenis]['terakhir_sukses'])) $info['terakhir_sukses'] = $lama[$jenis]['terakhir_sukses'];
        simpan_status($jenis, $info);
        tulis_log($jenis . ': ' . ($info['ok'] ? 'OK' : 'GAGAL') . ' | ' . $info['jumlah'] . ' data | ' . $info['pesan']);
        $ringkas[$jenis] = $info;
    }
    return $ringkas;
}
