<?php
/**
 * Pengaturan yang diisi lewat dashboard (disimpan di data/pengaturan.json),
 * menimpa nilai bawaan di config.php. Kompatibel PHP 5.2+.
 */

function baca_pengaturan() {
    $p = json_decode(simpan_baca('pengaturan.json'), true);
    return is_array($p) ? $p : array();
}

/** Pengaturan dari file data/pengaturan.json (dibaca sebelum database terhubung) */
function baca_pengaturan_file() {
    $f = dirname(dirname(__FILE__)) . '/data/pengaturan.json';
    if (!is_file($f)) return array();
    $p = json_decode(file_get_contents($f), true);
    return is_array($p) ? $p : array();
}

function simpan_pengaturan($p) {
    return simpan_tulis('pengaturan.json', json_aman($p));
}

/** Gabung array bertingkat (pengganti array_replace_recursive untuk PHP 5.2) */
function gabung_array($dasar, $timpa) {
    foreach ($timpa as $k => $v) {
        if (is_array($v) && isset($dasar[$k]) && is_array($dasar[$k])) $dasar[$k] = gabung_array($dasar[$k], $v);
        else $dasar[$k] = $v;
    }
    return $dasar;
}

/** Buat kunci acak heksadesimal */
function buat_kunci($byte) {
    if (function_exists('openssl_random_pseudo_bytes')) {
        $b = openssl_random_pseudo_bytes($byte);
        if ($b !== false) return bin2hex($b);
    }
    $s = '';
    while (strlen($s) < $byte * 2) $s .= sha1(uniqid(mt_rand(), true) . microtime());
    return substr($s, 0, $byte * 2);
}

function hash_password($pass, $garam) { return sha1($garam . '|' . $pass); }

/** Cek password dashboard (mendukung password di config.php maupun hasil pengaturan) */
function password_cocok($cfg, $pass) {
    if (!empty($cfg['dashboard_hash']) && !empty($cfg['dashboard_garam'])) {
        return hash_password($pass, $cfg['dashboard_garam']) === $cfg['dashboard_hash'];
    }
    return $cfg['dashboard_password'] != '' && $pass === $cfg['dashboard_password'];
}

function ada_password($cfg) {
    return !empty($cfg['dashboard_hash']) || $cfg['dashboard_password'] != '';
}

/** Alamat website -> URL penerima */
function url_penerima($alamat) {
    $alamat = trim($alamat);
    if ($alamat == '') return '';
    if (!preg_match('#^https?://#i', $alamat)) $alamat = 'https://' . $alamat;
    $alamat = rtrim($alamat, '/');
    if (strpos($alamat, '/api/sipp/terima') !== false) return $alamat;
    return $alamat . '/api/sipp/terima';
}

/**
 * Terjemahkan pesan teknis menjadi kalimat yang mudah dipahami + saran.
 * Hasil: array('judul' => ..., 'saran' => ..., 'teknis' => ...)
 */
function pesan_ramah($pesan) {
    $p = strtolower($pesan);
    $daftar = array(
        array(array('access denied'),
              'Nama pengguna atau password database SIPP salah.',
              'Buka Pengaturan, periksa "Nama pengguna" dan "Password" database SIPP.'),
        array(array('unknown database'),
              'Nama database SIPP tidak ditemukan.',
              'Buka Pengaturan, periksa "Nama database". Lihat nama database SIPP di SQLyog/phpMyAdmin.'),
        array(array('connection refused', "can't connect", 'no such file', 'getaddrinfo', 'unknown mysql server host', 'sipp internal tidak terhubung', 'belum terhubung'),
              'Database SIPP tidak bisa dihubungi.',
              'Buka Pengaturan, periksa "Alamat server database" lalu klik "Tes koneksi database".'),
        array(array('api key tidak valid'),
              'Kunci API tidak cocok dengan website.',
              'Buka Pengaturan, salin ulang teks .env lalu tempel ke file .env website satker.'),
        array(array('tanda tangan tidak valid'),
              'Kunci rahasia tidak cocok dengan website.',
              'Buka Pengaturan, salin ulang teks .env lalu tempel ke file .env website satker.'),
        array(array('timestamp'),
              'Jam server kantor berbeda dengan jam server website.',
              'Samakan jam (tanggal & waktu) di server kantor.'),
        array(array('lebih lama dari data'),
              'Jam server kantor mundur dibanding kiriman sebelumnya.',
              'Samakan jam (tanggal & waktu) di server kantor, lalu kirim ulang.'),
        array(array('terlalu besar', 'http 413'),
              'Berkas relaas terlalu besar untuk diterima hosting website.',
              'Kecilkan "Ukuran relaas maksimal" di config.php, atau minta pengelola website menaikkan post_max_size / upload_max_filesize di PHP hosting (mis. 16M).'),
        array(array('sidik tidak cocok'),
              'Berkas relaas rusak saat dikirim.',
              'Kirim ulang. Jika terus terjadi, periksa koneksi internet server kantor.'),
        array(array('belum dikonfigurasi'),
              'Penerima di website satker belum diisi kuncinya.',
              'Tempel teks .env dari halaman Pengaturan ke file .env website satker.'),
        array(array('http 404'),
              'Alamat penerima di website tidak ditemukan.',
              'Periksa "Alamat website satker" di Pengaturan, dan pastikan penerima sudah dipasang di website.'),
        array(array('http 403', 'http 419'),
              'Website menolak kiriman.',
              'Minta pengelola website mengecualikan alamat api/sipp/terima dari CSRF / firewall hosting.'),
        array(array('ssl', 'certificate', 'sertifikat'),
              'Koneksi aman (HTTPS) ke website gagal.',
              'Buka Pengaturan, hilangkan centang "Periksa sertifikat SSL", lalu coba lagi.'),
        array(array('could not resolve', 'gagal terhubung ke', 'name or service not known'),
              'Alamat website tidak ditemukan.',
              'Periksa penulisan "Alamat website satker" dan pastikan server kantor terhubung ke internet.'),
        array(array('timed out', 'timeout'),
              'Website tidak menjawab (waktu habis).',
              'Periksa koneksi internet server kantor, lalu coba lagi.'),
        array(array('http 500'),
              'Terjadi kesalahan di website satker.',
              'Minta pengelola website memeriksa log website (writable/logs).'),
        array(array('antrian_sql'),
              'Data antrian belum diatur.',
              'Isi "Query antrian" di Pengaturan bagian Lanjutan, atau matikan data Antrian.'),
    );
    foreach ($daftar as $d) {
        foreach ($d[0] as $kata) {
            if (strpos($p, $kata) !== false) return array('judul' => $d[1], 'saran' => $d[2], 'teknis' => $pesan);
        }
    }
    return array('judul' => $pesan, 'saran' => '', 'teknis' => '');
}
