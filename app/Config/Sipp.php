<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Integrasi SIPP.
 *
 * A. SIPP API Kantor (disarankan untuk hosting): server kantor MENGIRIM data ke website
 *    (POST /api/sipp/terima, API key + tanda tangan HMAC). Kunci diisi di Panel Admin →
 *    Pengaturan → Integrasi SIPP, atau di .env (diutamakan):
 *      sipp.apiKey = '...'
 *      sipp.secret = '...'
 *
 * B. Baca langsung database SIPP (hanya bila website satu jaringan dengan SIPP):
 *
 * Query harus menghasilkan kolom: hearing_time, case_number, agenda, room
 * dan menerima satu parameter (?) berupa tanggal (Y-m-d).
 * PERIKSA & SESUAIKAN dengan skema SIPP versi yang dipakai satker Anda
 * sebelum mengaktifkan pengaturan "Ambil jadwal dari SIPP".
 */
class Sipp extends BaseConfig
{
    /** SIPP API Kantor — kosong = memakai isian di Pengaturan → Integrasi SIPP */
    public string $apiKey = '';

    public string $secret = '';

    /** Selisih waktu maksimal (detik) antara server kantor dan website */
    public int $maksSelisihDetik = 600;

    /** Ukuran kiriman maksimal (byte) */
    public int $maksUkuran = 5242880;

    /** Ukuran kiriman berkas relaas maksimal (byte; PDF base64 ≈ 4/3 ukuran asli) */
    public int $maksUkuranRelaas = 11534336;

    /** Jenis data yang diterima (relaas_ghaib = berkas PDF relaas, tidak disimpan di sipp_data) */
    public array $jenis = ['jadwal_sidang', 'statistik_perkara', 'panggilan_ghaib', 'antrian', 'relaas_ghaib'];

    public string $hearingsQuery = <<<'SQL'
        SELECT  DATE_FORMAT(j.jam_sidang, '%H:%i') AS hearing_time,
                p.nomor_perkara                   AS case_number,
                j.agenda                          AS agenda,
                j.ruangan                         AS room
        FROM    perkara_jadwal_sidang j
        JOIN    perkara p ON p.perkara_id = j.perkara_id
        WHERE   j.tanggal_sidang = ?
        ORDER BY j.jam_sidang, p.nomor_perkara
        SQL;
}
