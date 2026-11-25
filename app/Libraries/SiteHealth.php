<?php

namespace App\Libraries;

/**
 * Kesehatan Website (Dashboard, Super Admin): peringatan ringan yang mudah diperbaiki.
 * Setiap butir: ['level' => danger|warn, 'title', 'advice', 'link' => jalur admin|null]
 */
class SiteHealth
{
    /** Akhir dukungan keamanan PHP (sumber: https://www.php.net/supported-versions.php). Perbarui saat ada cabang baru. */
    private const PHP_EOL = ['8.1' => '2025-12-31', '8.2' => '2026-12-31', '8.3' => '2027-12-31', '8.4' => '2028-12-31', '8.5' => '2029-12-31', '8.6' => '2030-12-31'];

    /** Alamat panel admin yang mudah ditebak. */
    private const GUESSABLE = ['panel-satker', 'administrator', 'admin', 'panel', 'backend', 'login', 'adminpanel', 'cpanel', 'dashboard'];

    public function checks(): array
    {
        $out = [];

        // 1. versi PHP
        $branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $eol    = self::PHP_EOL[$branch] ?? null;
        if ($eol && strtotime($eol . ' 23:59:59') < time()) {
            $out[] = ['danger', 'PHP ' . PHP_VERSION . ' sudah tidak mendapat perbaikan keamanan (berakhir ' . tanggal_id($eol) . ')',
                'Ganti ke PHP 8.3 atau lebih baru di panel hosting (cPanel → Select PHP Version / MultiPHP Manager).', null];
        } elseif ($eol && strtotime($eol) - time() < 90 * 86400) {
            $out[] = ['warn', 'Dukungan keamanan PHP ' . $branch . ' berakhir ' . tanggal_id($eol),
                'Rencanakan pindah ke PHP 8.3 atau lebih baru di panel hosting sebelum tanggal tersebut.', null];
        }

        // 2. HTTPS (tidak berlaku di komputer lokal)
        $rq   = service('request');
        $host = strtolower((string) $rq->getServer('HTTP_HOST'));
        $host = preg_replace('/:\d+$/', '', $host);
        $local = $host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true) || preg_match('/\.(test|local|localhost)$/', $host)
            || preg_match('/^(10|192\.168|172\.(1[6-9]|2\d|3[01]))\./', $host);
        if (! $local && ! $rq->isSecure()) {
            $out[] = ['danger', 'Website belum memakai HTTPS',
                'Aktifkan sertifikat SSL (cPanel → SSL/TLS Status → Run AutoSSL), lalu isi app.baseURL dengan https:// di berkas .env.', null];
        }

        // 3. admin tanpa MFA
        try {
            $noMfa = array_column(db_connect()->table('users')->select('name')->where('is_active', 1)
                ->groupStart()->where('totp_enabled', 0)->orWhere('totp_enabled', null)->groupEnd()->get()->getResultArray(), 'name');
            if ($noMfa) {
                $out[] = [setting('require_2fa', '1') === '1' ? 'warn' : 'danger',
                    count($noMfa) . ' pengguna belum mengaktifkan MFA: ' . implode(', ', array_slice($noMfa, 0, 5)) . (count($noMfa) > 5 ? ', …' : ''),
                    'Minta setiap pengguna mengaktifkan MFA di menu Akun Saya. Wajibkan MFA di Pengaturan → Keamanan.', 'pengguna'];
            }
        } catch (\Throwable) {
        }

        // 4. alamat panel admin mudah ditebak
        $path = strtolower(trim((string) config('App')->adminPath, '/'));
        if (in_array($path, self::GUESSABLE, true)) {
            $out[] = ['warn', 'Alamat panel admin mudah ditebak (/' . $path . ')',
                'Ganti dengan nama yang sulit ditebak di berkas .env, mis. app.adminPath = kelola-' . substr(bin2hex(random_bytes(3)), 0, 5) . ', lalu beri tahu semua admin alamat barunya.', null];
        }

        // 5. cadangan database
        $bk   = new Backup();
        $list = $bk->list();
        $err  = (string) setting('backup_last_error', '');
        if ($err !== '' && (int) setting('backup_last_auto', '0') < strtotime(substr($err, 0, 16) ?: 'now')) {
            $out[] = ['danger', 'Cadangan database terjadwal gagal (' . substr($err, 0, 16) . ')', 'Periksa ruang disk hosting, lalu klik Buat cadangan sekarang. Rincian: ' . substr($err, 17), 'cadangan'];
        }
        if (Backup::schedule() === 'off') {
            $out[] = ['warn', 'Cadangan database terjadwal dinonaktifkan', 'Aktifkan cadangan Harian atau Mingguan di menu Cadangan.', 'cadangan'];
        } elseif (! $list) {
            $out[] = ['warn', 'Belum ada cadangan database', 'Klik Buat cadangan sekarang di menu Cadangan.', 'cadangan'];
        }
        $dl = (int) setting('backup_last_download', '0');
        if ($list && time() - $dl > 30 * 86400) {
            $out[] = ['warn', $dl ? 'Cadangan terakhir diunduh lebih dari 30 hari lalu' : 'Cadangan belum pernah diunduh ke komputer',
                'Cadangan di hosting tidak menolong bila hostingnya rusak/diretas. Unduh cadangan terbaru ke komputer kantor secara rutin (mis. sebulan sekali).', 'cadangan'];
        }

        // 6. ruang disk
        $free = function_exists('disk_free_space') ? @disk_free_space(WRITEPATH) : false;
        if ($free !== false && $free < 500 * 1048576) {
            $out[] = [$free < 100 * 1048576 ? 'danger' : 'warn', 'Ruang disk hosting tersisa ' . number_format($free / 1048576, 0, ',', '.') . ' MB',
                'Hapus cadangan/berkas lama atau tambah kuota hosting. Bila disk penuh, website bisa berhenti.', 'cadangan'];
        }

        return array_map(static fn ($c) => ['level' => $c[0], 'title' => $c[1], 'advice' => $c[2], 'link' => $c[3]], $out);
    }
}
