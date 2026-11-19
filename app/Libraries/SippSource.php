<?php

namespace App\Libraries;

/**
 * Sumber jadwal sidang.
 * - Default: tabel `hearings` yang diisi operator lewat panel admin.
 * - Opsional: baca langsung dari database SIPP (pengaturan "Ambil jadwal dari SIPP").
 *   Koneksi didefinisikan sebagai grup `sipp` di .env (database.sipp.*), gunakan
 *   akun MariaDB READ-ONLY. Query dapat disesuaikan di app/Config/Sipp.php.
 */
class SippSource
{
    public function hearings(string $date): array
    {
        if (setting('sipp_sync') === '1') {
            try {
                $cfg = config('Sipp');
                $db  = db_connect('sipp');

                return $db->query($cfg->hearingsQuery, [$date])->getResultArray();
            } catch (\Throwable $e) {
                log_message('error', 'SIPP: ' . $e->getMessage());
            }
        }

        return db_connect()->table('hearings')->where('hearing_date', $date)
            ->orderBy('hearing_time')->orderBy('case_number')->get()->getResultArray();
    }
}
