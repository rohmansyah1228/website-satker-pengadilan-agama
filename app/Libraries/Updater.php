<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Pembaruan otomatis dari GitHub (tombol "Update Website" di Dashboard, khusus Super Admin).
 *
 * Alur aman:
 *  1. Ambil commit terbaru cabang (repo & cabang dibaca dari version.json LOKAL, bukan dari internet).
 *  2. Baca version.json pada commit itu → kumpulkan berkas yang berubah sejak build terpasang.
 *  3. Periksa setiap jalur: hanya folder kode yang diizinkan (tidak pernah .env, writable/, public/uploads/).
 *  4. Unduh SEMUA berkas lebih dulu & periksa (ukuran, sintaks PHP). Bila satu gagal → batal, tidak ada yang diubah.
 *  5. Cadangkan berkas lama ke writable/backups/pembaruan-…/ lalu tulis berkas baru.
 *     Bila penulisan gagal di tengah jalan → semua dikembalikan dari cadangan.
 *  6. Perbarui version.json, bersihkan cache, jalankan pembaruan database.
 *
 * Tanda tangan digital: rilis wajib disertai integrity.json + version.sig yang ditandatangani kunci
 * pengelola (Config\Release). Tanpa tanda tangan yang sah → pembaruan ditolak. Setiap berkas yang
 * diunduh harus sama persis dengan sidiknya di integrity.json (GitHub/jalur unduhan tidak bisa menyelipkan isi lain).
 */
class Updater
{
    private const RAW      = 'https://raw.githubusercontent.com/';
    private const API      = 'https://api.github.com/repos/';
    private const MAX_FILE = 8 * 1024 * 1024;   // 8 MB per berkas
    private const MAX_ALL  = 60 * 1024 * 1024;  // 60 MB total
    private const MAX_N    = 400;               // jumlah berkas per pembaruan

    /** Awalan folder yang boleh diperbarui otomatis. */
    private const ALLOW_PREFIX = ['app/', 'public/assets/', 'vendor/', 'deploy/', 'docs/'];

    /** Berkas tunggal di luar folder di atas yang boleh diperbarui. */
    private const ALLOW_FILE = ['public/index.php', 'public/.htaccess', 'public/robots.txt', '.htaccess', 'spark', 'preload.php',
        'composer.json', 'composer.lock', 'README.md', 'LICENSE', 'SECURITY.md', 'CONTRIBUTING.md', 'env'];

    private string $repo;
    private string $branch;

    public function __construct()
    {
        $local        = (new VersionCheck())->local();
        $this->repo   = (string) ($local['repo'] ?? '');
        $this->branch = (string) ($local['branch'] ?? 'main');
    }

    /** Folder cadangan pembaruan. */
    public static function backupRoot(): string
    {
        return WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
    }

    /** Apakah jalur ini boleh ditulis oleh pembaruan otomatis. */
    public static function allowedPath(string $p): bool
    {
        if ($p === '' || strlen($p) > 255 || ! preg_match('#^[A-Za-z0-9_\-./ ]+$#', $p)
            || str_contains($p, '..') || str_contains($p, '//') || str_starts_with($p, '/') || str_ends_with($p, '/')) {
            return false;
        }
        $base = basename($p);
        if (str_starts_with($base, '.env') || preg_match('#^(writable|public/uploads)(/|$)#', $p) || $p === 'version.json') {
            return false;
        }
        if (in_array($p, self::ALLOW_FILE, true)) {
            return true;
        }
        foreach (self::ALLOW_PREFIX as $pre) {
            if (str_starts_with($p, $pre)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Jalankan pembaruan.
     * @return array{from:int,to:int,written:list<string>,removed:list<string>,backup:string,migrations:int,migrationError:?string}
     */
    public function run(): array
    {
        if (! preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $this->repo) || ! preg_match('#^[A-Za-z0-9_./-]+$#', $this->branch)) {
            throw new RuntimeException('Alamat repositori di version.json tidak valid.');
        }
        if (! function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL PHP tidak aktif, pembaruan otomatis tidak dapat dijalankan. Perbarui secara manual.');
        }
        @set_time_limit(600);
        @ignore_user_abort(true);

        // kunci agar tidak berjalan dua kali bersamaan
        if (! is_dir(WRITEPATH . 'cache')) {
            @mkdir(WRITEPATH . 'cache', 0755, true);
        }
        $lock = fopen(WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'update.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Pembaruan sedang berjalan. Tunggu beberapa saat lalu muat ulang Dashboard.');
        }

        try {
            return $this->doRun();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function doRun(): array
    {
        $local     = (new VersionCheck())->local();
        $fromBuild = (int) ($local['build'] ?? 0);

        // 1. commit rilis terbaru (yang mengubah version.sig); bila API GitHub tak terjangkau → commit terbaru cabang.
        //    Konten per-commit tidak berubah → berkas & version.json pasti sepasang.
        $ref = $this->latestRelease() ?? $this->latestCommit() ?? $this->branch;

        // 2. version.json pada commit tsb.
        $vjRaw  = $this->fetch(self::RAW . $this->repo . '/' . $ref . '/version.json', 512 * 1024);
        $remote = json_decode($vjRaw, true);
        if (! is_array($remote) || ! isset($remote['build'])) {
            throw new RuntimeException('version.json di GitHub tidak valid.');
        }
        $toBuild = (int) $remote['build'];
        if ($toBuild <= $fromBuild) {
            throw new RuntimeException('Website sudah versi terbaru (build ' . $fromBuild . ').');
        }
        if (($remote['repo'] ?? $this->repo) !== $this->repo) {
            throw new RuntimeException('version.json di GitHub menunjuk repositori lain — pembaruan dibatalkan.');
        }

        // 2b. tanda tangan digital rilis (wajib)
        try {
            $ijRaw  = $this->fetch(self::RAW . $this->repo . '/' . $ref . '/integrity.json', 4 * 1024 * 1024);
            $sigRaw = $this->fetch(self::RAW . $this->repo . '/' . $ref . '/version.sig', 8192);
        } catch (\Throwable $e) {
            throw new RuntimeException('Rilis di GitHub tidak memiliki tanda tangan digital (integrity.json / version.sig) — pembaruan ditolak demi keamanan.');
        }
        $signed = Integrity::verified($vjRaw, $ijRaw, $sigRaw, config('Release')->trustedKeys); // melempar galat bila tidak sah
        if ($signed['build'] !== $toBuild) {
            throw new RuntimeException('Tanda tangan rilis bukan untuk versi ini — pembaruan ditolak.');
        }

        $files = $removed = [];
        foreach ((array) ($remote['releases'] ?? []) as $rel) {
            if ((int) ($rel['build'] ?? 0) <= $fromBuild) {
                continue;
            }
            foreach ((array) ($rel['files'] ?? []) as $f) {
                $files[(string) $f] = true;
            }
            foreach ((array) ($rel['removed'] ?? []) as $f) {
                $removed[(string) $f] = true;
            }
        }
        // berkas yang dihapus di satu rilis lalu muncul lagi di rilis lain → tetap diunduh
        $removed = array_diff_key($removed, $files);
        $files   = array_keys($files);
        $removed = array_keys($removed);
        if (! $files && ! $removed) {
            throw new RuntimeException('Tidak ada daftar berkas pada rilis terbaru. Perbarui secara manual dari GitHub.');
        }
        if (count($files) > self::MAX_N) {
            throw new RuntimeException('Pembaruan terlalu besar (' . count($files) . ' berkas) untuk pembaruan otomatis. Perbarui secara manual.');
        }

        // 3. periksa jalur
        $bad = array_values(array_filter(array_merge($files, $removed), static fn ($p) => ! self::allowedPath($p)));
        if ($bad) {
            throw new RuntimeException('Pembaruan memuat berkas di luar folder yang boleh diperbarui otomatis (' . implode(', ', array_slice($bad, 0, 5))
                . '). Perbarui secara manual dari GitHub.');
        }

        // periksa izin tulis sebelum mengunduh
        $notWritable = [];
        foreach (array_merge($files, $removed) as $p) {
            $abs = ROOTPATH . $p;
            $dir = dirname($abs);
            while (! is_dir($dir) && strlen($dir) > strlen(ROOTPATH)) {
                $dir = dirname($dir);
            }
            if ((is_file($abs) && ! is_writable($abs)) || ! is_writable($dir)) {
                $notWritable[] = $p;
            }
        }
        foreach (Integrity::META as $m) {
            if (is_file(ROOTPATH . $m) ? ! is_writable(ROOTPATH . $m) : ! is_writable(ROOTPATH)) {
                $notWritable[] = $m;
            }
        }
        if ($notWritable) {
            throw new RuntimeException('Server tidak mengizinkan menulis berkas: ' . implode(', ', array_slice($notWritable, 0, 5))
                . (count($notWritable) > 5 ? ' (dan ' . (count($notWritable) - 5) . ' lainnya)' : '')
                . '. Minta penyedia hosting memberi izin tulis, atau perbarui secara manual.');
        }

        // 4. unduh & periksa semuanya dulu
        $new     = [];
        $skipped = [];
        $total   = 0;
        foreach ($files as $p) {
            $body  = $this->fetch(self::RAW . $this->repo . '/' . $ref . '/' . implode('/', array_map('rawurlencode', explode('/', $p))), self::MAX_FILE);
            $total += strlen($body);
            if ($total > self::MAX_ALL) {
                throw new RuntimeException('Ukuran pembaruan terlalu besar untuk pembaruan otomatis. Perbarui secara manual.');
            }
            if (! isset($signed['files'][$p]) || Integrity::hash($body) !== $signed['files'][$p]) {
                if (self::nonRuntime($p)) {
                    // berkas dokumentasi/alat (tidak dijalankan website) berbeda: lewati saja, jangan gagalkan pembaruan
                    $skipped[] = $p;

                    continue;
                }
                throw new RuntimeException('Berkas ' . $p . ' dari GitHub tidak cocok dengan sidik resmi bertanda tangan — pembaruan dibatalkan, tidak ada yang diubah.');
            }
            if (str_ends_with(strtolower($p), '.php')) {
                try {
                    token_get_all($body, TOKEN_PARSE);
                } catch (\ParseError $e) {
                    throw new RuntimeException('Berkas ' . $p . ' dari GitHub tidak lolos pemeriksaan sintaks — pembaruan dibatalkan.');
                }
            }
            $new[$p] = $body;
        }

        // 5a. cadangan database lebih dulu (wajib; bila gagal pembaruan dibatalkan)
        try {
            $dbBackup = (new Backup())->create('pembaruan');
        } catch (\Throwable $e) {
            throw new RuntimeException('Cadangan database sebelum pembaruan gagal (' . $e->getMessage() . ') — pembaruan dibatalkan, tidak ada yang diubah.');
        }

        // 5. cadangkan lalu tulis
        $backup = self::backupRoot() . 'pembaruan-' . date('Ymd-His') . '-build' . $fromBuild . '-ke-' . $toBuild . DIRECTORY_SEPARATOR;
        if (! @mkdir($backup, 0755, true) && ! is_dir($backup)) {
            throw new RuntimeException('Tidak dapat membuat folder cadangan di writable/backups/.');
        }
        @file_put_contents(self::backupRoot() . 'index.html', '');
        @file_put_contents(self::backupRoot() . '.htaccess', "Require all denied\nDeny from all\n");

        $manifest = ['from' => $fromBuild, 'to' => $toBuild, 'ref' => $ref, 'at' => date('c'), 'saved' => [], 'created' => [], 'removed' => [], 'database' => $dbBackup];
        foreach (array_merge(array_keys($new), $removed, Integrity::META) as $p) {
            $abs = ROOTPATH . $p;
            if (is_file($abs)) {
                $dst = $backup . 'files' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $p);
                @mkdir(dirname($dst), 0755, true);
                if (! @copy($abs, $dst)) {
                    throw new RuntimeException('Gagal mencadangkan ' . $p . ' — pembaruan dibatalkan, tidak ada berkas yang diubah.');
                }
                $manifest['saved'][] = $p;
            } elseif (isset($new[$p]) || (in_array($p, Integrity::META, true) && $p !== 'version.json')) {
                $manifest['created'][] = $p;
            }
        }
        file_put_contents($backup . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $done = [];
        try {
            foreach ($new as $p => $body) {
                if (basename($p) === '.htaccess' && is_file(ROOTPATH . $p)) {
                    // pertahankan baris pilihan versi PHP dari hosting (cPanel MultiPHP / CloudLinux)
                    $body = self::keepHostLines((string) @file_get_contents(ROOTPATH . $p), $body);
                }
                $this->writeAtomic(ROOTPATH . $p, $body);
                $done[] = $p;
            }
            foreach ($removed as $p) {
                if (is_file(ROOTPATH . $p) && ! @unlink(ROOTPATH . $p)) {
                    throw new RuntimeException('Gagal menghapus berkas lama ' . $p);
                }
                $manifest['removed'][] = $p;
            }
            $this->writeAtomic(ROOTPATH . 'integrity.json', $ijRaw);
            $this->writeAtomic(ROOTPATH . 'version.sig', $sigRaw);
            $this->writeAtomic(ROOTPATH . 'version.json', $vjRaw);
        } catch (\Throwable $e) {
            $this->restore($backup, $manifest);

            throw new RuntimeException('Penulisan berkas gagal (' . $e->getMessage() . '). Semua berkas dikembalikan seperti semula.');
        }
        file_put_contents($backup . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 6. bersihkan cache & jalankan pembaruan database
        $this->resetOpcache(array_merge($done, Integrity::META));
        cache()->clean();

        $migrations = 0;
        $migErr     = null;
        try {
            $runner = service('migrations', null, null, false);
            $runner->setNamespace('App');
            $before = count($runner->getHistory());
            $runner->latest();
            $migrations = max(0, count($runner->getHistory()) - $before);
        } catch (\Throwable $e) {
            $migErr = $e->getMessage();
            log_message('error', 'Pembaruan otomatis — migrasi: ' . $migErr);
        }

        try {
            (new Backup())->rotate(); // batasi jumlah cadangan lama
        } catch (\Throwable) {
        }

        return ['from' => $fromBuild, 'to' => $toBuild, 'written' => $done, 'removed' => $manifest['removed'], 'dbBackup' => $dbBackup,
            'backup' => basename(rtrim($backup, DIRECTORY_SEPARATOR)), 'migrations' => $migrations, 'migrationError' => $migErr];
    }

    /** Cadangan pembaruan terakhir (untuk tombol "Batalkan pembaruan terakhir"). */
    public static function lastBackup(): ?array
    {
        $dirs = glob(self::backupRoot() . 'pembaruan-*', GLOB_ONLYDIR) ?: [];
        rsort($dirs);
        foreach ($dirs as $d) {
            $m = json_decode((string) @file_get_contents($d . DIRECTORY_SEPARATOR . 'manifest.json'), true);
            if (is_array($m) && empty($m['rolledBack'])) {
                return ['dir' => $d . DIRECTORY_SEPARATOR, 'name' => basename($d), 'manifest' => $m];
            }
        }

        return null;
    }

    /** Kembalikan berkas dari cadangan pembaruan terakhir (database tidak diubah). */
    public function rollbackLast(): array
    {
        $b = self::lastBackup();
        if (! $b) {
            throw new RuntimeException('Tidak ada cadangan pembaruan yang dapat dikembalikan.');
        }
        $local = (new VersionCheck())->local();
        if ((int) ($local['build'] ?? 0) !== (int) ($b['manifest']['to'] ?? -1)) {
            throw new RuntimeException('Versi terpasang tidak sama dengan hasil pembaruan terakhir — pengembalian otomatis dibatalkan. Kembalikan manual dari writable/backups/' . $b['name'] . '.');
        }
        $this->restore($b['dir'], $b['manifest']);
        $m               = $b['manifest'];
        $m['rolledBack'] = date('c');
        @file_put_contents($b['dir'] . 'manifest.json', json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->resetOpcache(array_merge($m['saved'] ?? [], $m['created'] ?? []));
        cache()->clean();

        return $m;
    }

    // ------------------------------------------------------------------

    private function restore(string $backup, array $manifest): void
    {
        foreach ((array) ($manifest['saved'] ?? []) as $p) {
            if (! self::allowedPath($p) && ! in_array($p, Integrity::META, true)) {
                continue;
            }
            $src = $backup . 'files' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $p);
            if (is_file($src)) {
                @mkdir(dirname(ROOTPATH . $p), 0755, true);
                @copy($src, ROOTPATH . $p);
            }
        }
        foreach ((array) ($manifest['created'] ?? []) as $p) {
            if ((self::allowedPath($p) || in_array($p, Integrity::META, true)) && is_file(ROOTPATH . $p)) {
                @unlink(ROOTPATH . $p);
            }
        }
    }

    private function writeAtomic(string $abs, string $body): void
    {
        $dir = dirname($abs);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('tidak dapat membuat folder ' . $dir);
        }
        $tmp = $abs . '.upd-' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $body, LOCK_EX) !== strlen($body)) {
            @unlink($tmp);

            throw new RuntimeException('tidak dapat menulis ' . $abs);
        }
        if (is_file($abs)) {
            @chmod($tmp, fileperms($abs) & 0777);
        }
        if (! @rename($tmp, $abs)) {
            // sebagian sistem (Windows) gagal rename menimpa berkas yang sedang dibuka → salin
            $ok = @copy($tmp, $abs);
            @unlink($tmp);
            if (! $ok) {
                throw new RuntimeException('tidak dapat mengganti ' . $abs);
            }
        }
    }

    private function resetOpcache(array $files): void
    {
        if (function_exists('opcache_invalidate')) {
            foreach ($files as $p) {
                @opcache_invalidate(ROOTPATH . $p, true);
            }
        }
    }

    /** Blok pilihan versi PHP yang ditulis cPanel MultiPHP Manager di .htaccess. */
    private const HOST_BLOCK = '/^[ \t]*# php -- BEGIN cPanel-generated handler[^\n]*\n.*?^[ \t]*# php -- END cPanel-generated handler[^\n]*(\n|$)/ms';

    /**
     * Baris pemilih versi PHP yang wajar: AddHandler/AddType ke penangan PHP, HANYA untuk ekstensi PHP.
     * (Baris yang membuat .jpg/.txt dll. dijalankan sebagai PHP tidak termasuk — itu ciri sisipan penyusup.)
     */
    private static function handlerOk(string $line): bool
    {
        if (! preg_match('/^\s*(?:AddHandler|AddType)\s+(\S*(?:php|lsphp)\S*)\s+(.+?)\s*$/i', $line, $m)) {
            return false;
        }
        foreach (preg_split('/\s+/', $m[2]) as $ext) {
            if (! preg_match('/^\.?(php[0-9]*|phtml)$/i', $ext)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<array{0:int,1:int}> posisi [awal, panjang] blok/baris hosting yang sah di teks (LF) */
    private static function hostSpans(string $t): array
    {
        $spans = [];
        if (preg_match_all(self::HOST_BLOCK, $t, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as [$block, $pos]) {
                $ok = true;
                foreach (explode("\n", $block) as $l) {
                    $l = trim($l);
                    if ($l === '' || $l[0] === '#' || preg_match('#^</?IfModule\b[^>]*>$#i', $l) || self::handlerOk($l)) {
                        continue;
                    }
                    $ok = false;
                    break;
                }
                if ($ok) {
                    $spans[] = [$pos, strlen($block)];
                }
            }
        }
        $off = 0;
        foreach (explode("\n", $t) as $l) {
            $len = strlen($l) + 1;
            $in  = false;
            foreach ($spans as [$a, $n]) {
                if ($off >= $a && $off < $a + $n) {
                    $in = true;
                    break;
                }
            }
            if (! $in && self::handlerOk($l)) {
                $spans[] = [$off, min($len, strlen($t) - $off)];
            }
            $off += $len;
        }
        usort($spans, static fn ($x, $y) => $x[0] <=> $y[0]);

        return $spans;
    }

    /** Ambil blok/baris pilihan versi PHP dari hosting di .htaccess lama. */
    public static function hostLines(string $htaccess): string
    {
        $t   = str_replace("\r\n", "\n", $htaccess);
        $out = [];
        foreach (self::hostSpans($t) as [$a, $n]) {
            $out[] = rtrim(substr($t, $a, $n), "\n");
        }

        return implode("\n", array_unique($out));
    }

    /** .htaccess tanpa blok/baris pilihan versi PHP dari hosting (untuk dibandingkan dengan sidik resmi). */
    public static function withoutHostLines(string $htaccess): string
    {
        $t = str_replace("\r\n", "\n", $htaccess);
        foreach (array_reverse(self::hostSpans($t)) as [$a, $n]) {
            $t = substr($t, 0, $a) . substr($t, $a + $n);
        }

        return ltrim($t, "\n");
    }

    /** .htaccess baru dengan baris pilihan versi PHP dari .htaccess lama tetap di bagian atas. */
    public static function keepHostLines(string $old, string $new): string
    {
        $keep = self::hostLines($old);
        if ($keep === '') {
            return $new;
        }
        $base = self::withoutHostLines($new);
        $eol  = str_contains($new, "\r\n") ? "\r\n" : "\n";

        return str_replace("\n", $eol, $keep . "\n\n" . $base);
    }

    /** Berkas yang tidak dijalankan website (dokumentasi & alat manual). */
    private static function nonRuntime(string $p): bool
    {
        return in_array($p, ['README.md', 'LICENSE', 'SECURITY.md', 'CONTRIBUTING.md'], true)
            || str_starts_with($p, 'docs/') || str_starts_with($p, 'deploy/');
    }

    /** SHA commit rilis bertanda tangan terbaru (null bila tidak diketahui). */
    protected function latestRelease(): ?string
    {
        return VersionCheck::releaseShas($this->repo, $this->branch, 1)[0] ?? null;
    }

    /** SHA commit terbaru cabang (null bila GitHub tidak dapat dihubungi → pakai nama cabang). */
    protected function latestCommit(): ?string
    {
        return VersionCheck::latestSha($this->repo, $this->branch);
    }

    /** Unduh lewat HTTPS dari GitHub saja. */
    protected function fetch(string $url, int $max, array $headers = []): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! in_array($host, ['raw.githubusercontent.com', 'api.github.com'], true)) {
            throw new RuntimeException('Sumber pembaruan tidak diizinkan.');
        }
        UrlGuard::assertPublic($url);
        $buf = '';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION  => false,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_CONNECTTIMEOUT  => 8,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_USERAGENT       => 'WebsiteSatker-Updater',
            CURLOPT_HTTPHEADER      => array_merge(['Cache-Control: no-cache'], $headers),
            CURLOPT_WRITEFUNCTION   => static function ($c, $chunk) use (&$buf, $max) {
                $buf .= $chunk;

                return strlen($buf) > $max ? 0 : strlen($chunk);
            },
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_errno($ch) ? curl_error($ch) : '';
        try {
            UrlGuard::assertConnectedIpPublic($ch);
        } finally {
            curl_close($ch);
        }
        if (strlen($buf) > $max) {
            throw new RuntimeException('Berkas terlalu besar: ' . basename((string) parse_url($url, PHP_URL_PATH)));
        }
        if ($code !== 200) {
            $name = basename((string) parse_url($url, PHP_URL_PATH));

            throw new RuntimeException('Gagal mengunduh ' . $name . ' dari GitHub (' . ($err ?: 'HTTP ' . $code) . ').');
        }

        return $buf;
    }
}
