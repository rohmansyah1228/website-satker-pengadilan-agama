<?php
/**
 * Fungsi HTTP (cURL, cadangan: stream context). Kompatibel PHP 5.2+.
 * Hasil: array('ok' => bool, 'kode' => int, 'isi' => string, 'pesan' => string)
 */
function http_request($metode, $url, $body, $header, $cfgWeb) {
    $timeout = isset($cfgWeb['timeout']) ? (int)$cfgWeb['timeout'] : 30;
    $verify  = isset($cfgWeb['verify_ssl']) ? (bool)$cfgWeb['verify_ssl'] : true;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify ? 2 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SIPP-API-Kantor/1.0');
        if (defined('CURL_SSLVERSION_TLSv1_2')) {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }
        if ($metode == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $isi  = curl_exec($ch);
        $kode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($isi === false) return array('ok' => false, 'kode' => 0, 'isi' => '', 'pesan' => 'cURL: ' . $err);
        return array('ok' => ($kode >= 200 && $kode < 300), 'kode' => $kode, 'isi' => $isi,
                     'pesan' => 'HTTP ' . $kode);
    }

    // Cadangan tanpa cURL
    $opsi = array(
        'http' => array(
            'method'        => $metode,
            'header'        => implode("\r\n", $header),
            'content'       => $metode == 'POST' ? $body : '',
            'timeout'       => $timeout,
            'ignore_errors' => true,
        ),
        'ssl' => array('verify_peer' => $verify, 'verify_peer_name' => $verify),
    );
    $isi = @file_get_contents($url, false, stream_context_create($opsi));
    $kode = 0;
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0] . ' ', $m)) {
        $kode = (int)$m[1];
    }
    if ($isi === false) return array('ok' => false, 'kode' => $kode, 'isi' => '', 'pesan' => 'Gagal terhubung ke ' . $url);
    return array('ok' => ($kode >= 200 && $kode < 300), 'kode' => $kode, 'isi' => $isi, 'pesan' => 'HTTP ' . $kode);
}

/** Pastikan semua string UTF-8 valid agar json_encode tidak gagal. */
function utf8_aman($data) {
    if (is_array($data)) {
        $hasil = array();
        foreach ($data as $k => $v) $hasil[$k] = utf8_aman($v);
        return $hasil;
    }
    if (is_string($data) && !preg_match('//u', $data)) return utf8_encode($data);
    return $data;
}

function json_aman($data) {
    $opsi = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) $opsi = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    return $opsi ? json_encode(utf8_aman($data), $opsi) : json_encode(utf8_aman($data));
}
