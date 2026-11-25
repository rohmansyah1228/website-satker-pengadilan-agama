<?php

namespace App\Libraries;

/** Statistik kunjungan website (kriteria 5.1 Lampiran II). IP disimpan sebagai hash, bukan alamat asli. */
class VisitorStats
{
    public function hit(): void
    {
        $rq = service('request');
        $ua = $rq->getUserAgent();
        if ($ua->isRobot() || $ua->getAgentString() === '') {
            return;
        }
        $db    = db_connect();
        $today = date('Y-m-d');
        $hash  = hash('sha256', $rq->getIPAddress() . '|' . $today . '|' . (config('Encryption')->key ?: 'satker'));
        $new   = $db->table('visitor_hits')->ignore(true)->insert(['date' => $today, 'ip_hash' => $hash]) && $db->affectedRows() > 0;

        if ($db->table('visitor_daily')->where('date', $today)->countAllResults() === 0) {
            $db->table('visitor_daily')->ignore(true)->insert(['date' => $today, 'visitors' => 0, 'pageviews' => 0]);
            if ($db->affectedRows() > 0) {
                $this->housekeeping(); // kunjungan pertama hari ini: bersihkan data harian lama
            }
        }
        $b = $db->table('visitor_daily')->where('date', $today)->set('pageviews', 'pageviews + 1', false);
        if ($new) {
            $b->set('visitors', 'visitors + 1', false);
        }
        $b->update();

        if (mt_rand(1, 200) === 1) { // bersihkan hash lama sesekali
            $db->table('visitor_hits')->where('date <', date('Y-m-d', strtotime('-2 days')))->delete();
        }
    }

    /**
     * Pembersihan harian agar database tidak terus membesar:
     *  - visitor_hits hanya penanda "IP ini sudah dihitung hari ini" (hash, bukan IP) → cukup 2 hari terakhir.
     *    Jumlah kunjungan sendiri tersimpan permanen di visitor_daily (1 baris per hari).
     *  - catatan percobaan login lebih dari 90 hari.
     */
    public function housekeeping(): void
    {
        $db = db_connect();
        try {
            $db->table('visitor_hits')->where('date <', date('Y-m-d', strtotime('-1 day')))->delete();
            $db->table('login_attempts')->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))->delete();
        } catch (\Throwable $e) {
            log_message('warning', 'Pembersihan harian gagal: ' . $e->getMessage());
        }
        self::repairUploadsHtaccess();
        // cadangan database terjadwal: dibuat setelah halaman terkirim agar pengunjung tidak menunggu
        if (Backup::due()) {
            register_shutdown_function(static function (): void {
                if (function_exists('fastcgi_finish_request')) {
                    @fastcgi_finish_request();
                }
                Backup::runIfDue();
            });
        }
    }

    /**
     * public/uploads/.htaccess tidak ikut Update Website (folder unggahan dilindungi dari penulisan otomatis),
     * jadi versi lama yang hanya cocok untuk Apache 2.4 diperbarui di sini (Apache 2.2/LiteSpeed + blok .htaccess/.ini).
     */
    public static function repairUploadsHtaccess(): void
    {
        $f = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . '.htaccess';
        if (! is_file($f) || ! is_writable($f)) {
            return;
        }
        $cur = (string) @file_get_contents($f);
        if (str_contains($cur, 'mod_authz_core') || ! str_contains($cur, 'Require all denied')) {
            return; // sudah baru, atau sudah diubah sendiri oleh pengelola hosting
        }
        @file_put_contents($f, self::UPLOADS_HTACCESS, LOCK_EX);
    }

    private const UPLOADS_HTACCESS = <<<'HT'
        # Folder unggahan gambar: larang eksekusi skrip & listing direktori (SK 2722/2026 Bab II A.3)
        Options -Indexes -ExecCGI
        <FilesMatch "\.(php|phtml|php\d|phps|phar|pl|py|cgi|sh|jsp|asp|aspx|shtml|htaccess|htpasswd|ini)$">
            <IfModule mod_authz_core.c>
                Require all denied
            </IfModule>
            <IfModule !mod_authz_core.c>
                Order allow,deny
                Deny from all
            </IfModule>
        </FilesMatch>
        <IfModule mod_php.c>
            php_flag engine off
        </IfModule>
        <IfModule mod_php7.c>
            php_flag engine off
        </IfModule>

        HT;

    public function summary(): array
    {
        if ($c = cache('visitor_summary')) {
            return $c;
        }
        $db  = db_connect();
        $sum = fn ($from) => (int) ($db->table('visitor_daily')->selectSum('visitors')->where('date >=', $from)->get()->getRow()->visitors ?? 0);
        $out = [
            'today' => (int) ($db->table('visitor_daily')->where('date', date('Y-m-d'))->get()->getRow()->visitors ?? 0),
            'month' => $sum(date('Y-m-01')),
            'year'  => $sum(date('Y-01-01')),
            'total' => $sum('1970-01-01'),
        ];
        cache()->save('visitor_summary', $out, 300);

        return $out;
    }

    public function monthly(int $months): array
    {
        $from = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
        $rows = db_connect()->table('visitor_daily')->select('date, visitors, pageviews')->where('date >=', $from)->get()->getResultArray();
        $out  = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $k       = date('Y-m', strtotime("-{$i} months", strtotime(date('Y-m-01'))));
            $out[$k] = ['visitors' => 0, 'pageviews' => 0];
        }
        foreach ($rows as $r) {
            $k = substr($r['date'], 0, 7);
            if (isset($out[$k])) {
                $out[$k]['visitors'] += (int) $r['visitors'];
                $out[$k]['pageviews'] += (int) $r['pageviews'];
            }
        }

        return $out;
    }
}
