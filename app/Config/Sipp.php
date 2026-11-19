<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Integrasi opsional jadwal sidang dari database SIPP.
 *
 * Query harus menghasilkan kolom: hearing_time, case_number, agenda, room
 * dan menerima satu parameter (?) berupa tanggal (Y-m-d).
 * PERIKSA & SESUAIKAN dengan skema SIPP versi yang dipakai satker Anda
 * sebelum mengaktifkan pengaturan "Ambil jadwal dari SIPP".
 */
class Sipp extends BaseConfig
{
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
