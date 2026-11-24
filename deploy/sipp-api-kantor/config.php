<?php
/**
 * KONFIGURASI SIPP API KANTOR
 * Aplikasi ini dipasang di server kantor (mis. 192.168.10.10), membaca database SIPP
 * internal, lalu MENGIRIM (push) data ke website satker secara terjadwal.
 *
 * TIDAK PERLU mengubah file ini: semua pengaturan bisa diisi lewat dashboard
 * (buka index.php di browser -> menu Pengaturan). Nilai di bawah hanya bawaan.
 */
$CONFIG = array(

    /* ---------- Database SIPP internal ---------- */
    'sipp_db' => array(
        'host' => 'localhost',          // <-- WAJIB (host MySQL/MariaDB SIPP)
        'user' => '',                   // <-- WAJIB (sebaiknya user read-only)
        'pass' => '',                   // <-- WAJIB
        'name' => 'sipp',               // <-- WAJIB (nama database SIPP)
    ),

    /* ---------- Tujuan: website satker ---------- */
    'website' => array(
        'url'        => '',   // <-- WAJIB, mis. https://pa-namasatker.go.id/api/sipp/terima (terisi otomatis bila diunduh dari panel website)
        'api_key'    => '',   // <-- WAJIB, harus SAMA dengan sipp.apiKey di .env website satker
        'secret'     => '',   // <-- WAJIB, harus SAMA dengan sipp.secret di .env website satker
        'verify_ssl' => true, // false hanya jika server kantor gagal memverifikasi sertifikat HTTPS
        'timeout'    => 30,
    ),

    /* ---------- Sumber cadangan (dipakai jika SIPP internal tidak bisa diakses) ---------- */
    // API jadwal sidang di sipp-publik (hosting). Kosongkan untuk menonaktifkan.
    // Contoh: 'https://sipp.pa-namasatker.go.id/api/jadwal_sidang'
    'cadangan_api_jadwal' => '',

    /* ---------- Keamanan dashboard ---------- */
    'dashboard_password' => '',   // <-- WAJIB, password login dashboard (index.php)
    'token_web'          => '',   // opsional: agar kirim.php bisa dipanggil lewat URL ?token=...

    /* ---------- Data yang dikirim ---------- */
    'aktif' => array(
        'jadwal_sidang'     => true,
        'statistik_perkara' => true,
        'panggilan_ghaib'   => true,
        'antrian'           => false,  // aktifkan setelah 'antrian_sql' diisi
    ),

    // Jadwal sidang: berapa hari ke depan (termasuk hari ini)
    'jadwal_hari_ke_depan'    => 14,
    // Jadwal sidang: kirim nama para pihak? (perkara cerai sebaiknya TIDAK)
    'jadwal_kirim_nama_pihak' => false,

    // Panggilan ghaib: kirim nama Tergugat/Termohon (isi pengumuman panggilan)
    'ghaib_kirim_nama'   => true,
    // Panggilan ghaib: kirim alamat terakhir Tergugat/Termohon?
    'ghaib_kirim_alamat' => false,

    // Panggilan ghaib: kirim berkas relaas (PDF) terbaru Tergugat/Termohon dari SIPP?
    'ghaib_kirim_relaas' => true,
    // Folder dokumen SIPP di disk. Kosongkan ('') agar dicari otomatis
    // (mis. C:/xampp/htdocs/sipp/resources/file/doc atau /var/www/html/sipp/resources/file/doc)
    'relaas_dir'         => '',
    // Ukuran relaas maksimal yang dikirim (MB). Sesuaikan dengan batas unggah hosting website.
    'relaas_maks_mb'     => 5,

    // Antrian: query SQL bebas ke database SIPP yang menghasilkan data antrian hari ini.
    // Contoh: "SELECT nomor_antrian, ruangan, status FROM antrian_sidang WHERE tanggal = CURDATE()"
    'antrian_sql' => '',

    'timezone' => 'Asia/Jakarta',
);
