<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Pemeriksaan Keamanan (Super Admin):
 *  1. Berkas: dibandingkan dengan sidik resmi versi terpasang (integrity.json bertanda tangan, diambil dari
 *     GitHub bila bisa) → berkas yang DIUBAH, berkas resmi yang HILANG, dan berkas skrip ASING
 *     (mis. webshell .php, .htaccess sisipan, .user.ini).
 *  2. Isi database: kata kunci judi online / slot, tautan tersembunyi, skrip sisipan pada halaman,
 *     berita, menu, tampilan beranda, pengaturan, dll.
 * Hasil disimpan; dijalankan otomatis paling lambat 24 jam sekali saat Super Admin membuka Dashboard.
 */
class SecurityScan
{
    private const CACHE_RESULT = 'security_scan_result';
    private const CACHE_REF    = 'security_scan_ref';
    public const MAX_AGE       = 86400;

    /** Folder yang diperiksa menyeluruh (relatif ROOTPATH). */
    private const DIRS = ['app', 'public', 'vendor', 'deploy', 'docs', 'writable'];

    /** Folder yang dilewati (isi milik satker / cadangan / sementara). */
    private const SKIP = ['.git', 'node_modules', 'writable/cache', 'writable/session', 'writable/logs', 'writable/backups',
        'writable/karantina', 'writable/debugbar'];

    /** Ekstensi yang dapat dijalankan server / disalahgunakan bila disisipkan. */
    private const SCRIPT = '/\.(php\d?|phtml|phar|pht|phps|inc|shtml|cgi|pl|py|asp|aspx|jsp|sh)$/i';

    /** Berkas konfigurasi server yang bisa mengubah perilaku PHP/Apache. */
    private const SERVERCFG = '/(^|\/)(\.htaccess|\.user\.ini|php\.ini|web\.config)$/i';

    /** Berkas bawaan website (dipakai bila daftar sidik resmi belum tersedia). */
    private const BASE_OK = ['.htaccess', 'preload.php', 'spark', 'public/.htaccess', 'public/index.php', 'public/uploads/.htaccess', 'writable/.htaccess',
        'app/Libraries/SecurityScan.php', 'app/Views/admin/guide.php']; // berkas ini & Panduan memuat contoh kata kunci yang dicari

    /** Berkas contoh bawaan CodeIgniter yang sering tertinggal saat instalasi lewat Composer. */
    private const CI_LEFTOVER = ['app/Controllers/Home.php', 'app/Views/welcome_message.php'];

    /** Berkas asing yang wajar di akar website (dibuat penyedia hosting / instalasi). */
    private const ROOT_OK = ['.env', 'error_log', '.well-known', 'cgi-bin', '.ftpquota', '.htpasswds'];

    /** Ciri khas webshell / pintu belakang (dipakai untuk berkas asing di vendor/). */
    private const SHELL = '/(eval\s*\(\s*(base64_decode|gzinflate|gzuncompress|str_rot13|\$_(POST|GET|REQUEST|COOKIE))|assert\s*\(\s*\$_(POST|GET|REQUEST)|\$_(POST|GET|REQUEST|COOKIE)\s*\[[^\]]+\]\s*\(|'
        . 'preg_replace\s*\([^)]*\/e[\'"]|create_function\s*\(|(shell_exec|passthru|proc_open|popen|system|exec)\s*\(\s*\$_(POST|GET|REQUEST|COOKIE)|move_uploaded_file\s*\([^)]*\$_FILES|FilesMan|WSO\s*\d|b374k|r57shell|c99shell)/i';

    /** Kata kunci judi online (sisipan SEO yang paling sering menimpa website .go.id). */
    private const JUDI = '/\b(slot\s*gacor|gacor|maxwin|rtp\s*(live|slot|hari\s*ini)|scatter\s*hitam|situs\s*(slot|judi|toto|togel)|link\s*(slot|alternatif\s*(slot|judi|toto))|'
        . 'sbobet|togel|toto\s*(macau|sgp|hk|4d|slot)|casino\s*online|poker\s*online|judi\s*(online|bola|slot)\s*(terpercaya|resmi|gacor|terbaik)|bandar\s*(slot|togel|judi|bola)|'
        . 'pragmatic\s*play|mahjong\s*ways|zeus\s*slot|gates\s*of\s*olympus|slot\s*(online|thailand|server|demo|88|777|dana|pulsa)|deposit\s*(pulsa|dana)\s*tanpa\s*potongan|'
        . 'kakek\s*merah|bocoran\s*(slot|rtp)|akun\s*(pro|vip)\s*(slot|thailand)|wd\s*cepat|anti\s*rungkad|jackpot\s*(slot|progresif))\b/i';

    /** Tanda sisipan teknis (Purifier membuang hal ini saat disimpan lewat panel, jadi kemunculannya mencurigakan). */
    private const INJECT = '/(<script\b|javascript:|\son(load|error|click|mouseover|focus)\s*=|<iframe\b(?![^>]*(youtube(-nocookie)?\.com|google\.com|drive\.google|docs\.google|maps\.google))|'
        . '(display\s*:\s*none|visibility\s*:\s*hidden|font-size\s*:\s*0(px)?\b|left\s*:\s*-\d{3,}px|text-indent\s*:\s*-\d{3,})[^>]*>[^<]{0,200}<a\s)/i';

    // ------------------------------------------------------------------ hasil tersimpan

    public static function last(): ?array
    {
        $r = cache(self::CACHE_RESULT);

        return is_array($r) ? $r : null;
    }

    /** Perlu diperiksa ulang? (belum pernah / lebih dari 24 jam) */
    public static function due(): bool
    {
        $r = self::last();

        return ! $r || (time() - (int) ($r['at'] ?? 0)) > self::MAX_AGE;
    }

    /** Jumlah temuan aktif (untuk tanda di menu). */
    public static function alerts(): int
    {
        $r = self::last();

        return $r ? (int) ($r['summary']['danger'] ?? 0) + (int) ($r['summary']['warn'] ?? 0) : 0;
    }

    // ------------------------------------------------------------------ jalankan

    public function run(): array
    {
        @set_time_limit(300);
        $t0      = microtime(true);
        $ref     = $this->reference();
        $files   = $this->scanFiles($ref);
        $content = $this->scanContent();
        $ignore  = $this->ignored();

        $files   = array_values(array_filter($files, static fn ($f) => ! isset($ignore[$f['key']])));
        $content = array_values(array_filter($content, static fn ($f) => ! isset($ignore[$f['key']])));
        $all     = array_merge($files, $content);
        $sum     = ['danger' => 0, 'warn' => 0];
        foreach ($all as $f) {
            $sum[$f['level']] = ($sum[$f['level']] ?? 0) + 1;
        }
        $res = [
            'at' => time(), 'seconds' => round(microtime(true) - $t0, 1),
            'ref' => array_diff_key($ref, ['files' => 1]), 'checked' => count($ref['files'] ?? []),
            'files' => $files, 'content' => $content, 'summary' => $sum, 'ignored' => count($ignore),
        ];
        cache()->save(self::CACHE_RESULT, $res, 60 * 86400);

        return $res;
    }

    /**
     * Daftar sidik pembanding untuk versi terpasang.
     * Utama: dari GitHub pada tag versi (tidak bisa diubah penyusup di hosting). Cadangan: berkas lokal.
     * @return array{ok:bool,source:string,build:int,name:string,files:array<string,string>,error:?string}
     */
    public function reference(): array
    {
        $local = (new VersionCheck())->local();
        $build = (int) ($local['build'] ?? 0);
        $name  = (string) ($local['name'] ?? '');
        $keys  = config('Release')->trustedKeys;
        $base  = ['ok' => false, 'source' => '', 'build' => $build, 'name' => $name, 'files' => [], 'error' => null];

        // 1. GitHub (disimpan 24 jam)
        $repo = (string) ($local['repo'] ?? '');
        if ($repo !== '' && $name !== '') {
            $cached = cache(self::CACHE_REF);
            if (is_array($cached) && ($cached['tag'] ?? '') === 'v' . $name && ($cached['at'] ?? 0) > time() - self::MAX_AGE) {
                return $cached['ref'];
            }
            try {
                $raw = 'https://raw.githubusercontent.com/' . $repo . '/v' . rawurlencode($name) . '/';
                $vj  = $this->get($raw . 'version.json', 512 * 1024);
                $ij  = $this->get($raw . 'integrity.json', 4 * 1024 * 1024);
                $sig = $this->get($raw . 'version.sig', 8192);
                $v   = Integrity::verified($vj, $ij, $sig, $keys);
                if ($v['build'] !== $build) {
                    throw new RuntimeException('versi di GitHub berbeda');
                }
                $ref = ['ok' => true, 'source' => 'github', 'build' => $build, 'name' => $name, 'files' => $v['files'], 'error' => null];
                // website yang diperbarui dari versi lama belum punya salinan sidik lokal: simpan yang sudah terverifikasi
                if (@file_get_contents(ROOTPATH . 'version.json') === $vj && @file_get_contents(ROOTPATH . 'integrity.json') !== $ij) {
                    @file_put_contents(ROOTPATH . 'integrity.json', $ij, LOCK_EX);
                    @file_put_contents(ROOTPATH . 'version.sig', $sig, LOCK_EX);
                }
                cache()->save(self::CACHE_REF, ['tag' => 'v' . $name, 'at' => time(), 'ref' => $ref], self::MAX_AGE);

                return $ref;
            } catch (\Throwable $e) {
                $base['error'] = 'GitHub: ' . $e->getMessage();
            }
        }
        // 2. lokal (tetap diperiksa tanda tangannya)
        $vj  = @file_get_contents(ROOTPATH . 'version.json');
        $ij  = @file_get_contents(ROOTPATH . 'integrity.json');
        $sig = @file_get_contents(ROOTPATH . 'version.sig');
        if ($ij === false || $sig === false || $vj === false) {
            $base['error'] = trim(($base['error'] ? $base['error'] . '; ' : '') . 'berkas sidik rilis (integrity.json) belum ada — tersedia mulai versi 1.8.1');

            return $base;
        }
        try {
            $v = Integrity::verified($vj, $ij, $sig, $keys);

            return ['ok' => true, 'source' => 'lokal', 'build' => $v['build'], 'name' => $v['name'], 'files' => $v['files'], 'error' => $base['error']];
        } catch (\Throwable $e) {
            $base['error'] = trim(($base['error'] ? $base['error'] . '; ' : '') . $e->getMessage());

            return $base;
        }
    }

    // ------------------------------------------------------------------ berkas

    private function scanFiles(array $ref): array
    {
        $out   = [];
        $known = $ref['files'] ?? [];
        $seen  = [];

        $vendor = []; // perbedaan pustaka tanpa ciri webshell: dirangkum jadi satu temuan

        // berkas resmi: diubah / hilang (kecuali di folder yang memang ditulis ulang oleh website)
        foreach ($known as $p => $hash) {
            if ($this->skipped($p)) {
                continue;
            }
            // folder data satker (writable/, unggahan): isinya milik satker; yang diperiksa hanya .htaccess pelindungnya
            if (preg_match('#^(writable|public/uploads)/#', $p) && ! preg_match(self::SERVERCFG, $p)) {
                $seen[$p] = true;
                continue;
            }
            $abs = ROOTPATH . $p;
            if (str_starts_with($p, 'vendor/')) {
                $seen[$p] = true;
                if (! is_file($abs)) {
                    $vendor[] = $p;
                } elseif (Integrity::hash((string) @file_get_contents($abs)) !== $hash) {
                    $head = (string) @file_get_contents($abs, false, null, 0, 262144);
                    if (preg_match(self::SCRIPT, $p) && (preg_match(self::SHELL, $head) || preg_match(self::JUDI, $head))) {
                        $out[] = $this->finding('file', 'changed', 'danger', $p, 'Berkas pustaka diubah dan berciri webshell / sisipan judi.', $this->fileInfo($abs, true));
                    } else {
                        $vendor[] = $p;
                    }
                }
                continue;
            }
            if (! is_file($abs)) {
                // dokumentasi & berkas lisensi/teks boleh tidak ikut diunggah (tidak bisa dijalankan, tidak berbahaya bila hilang)
                if (! preg_match('#^(docs|deploy)/#', $p) && ! preg_match('#(^|/)(LICEN[CS]E|COPYING|NOTICE|AUTHORS|CHANGELOG|README)[^/]*$|\.(md|txt)$#i', $p)) {
                    $out[] = $this->finding('file', 'missing', preg_match(self::SCRIPT, $p) || preg_match(self::SERVERCFG, $p) ? 'warn' : 'warn', $p, 'Berkas resmi tidak ada.');
                }
                continue;
            }
            $seen[$p] = true;
            if (Integrity::hash((string) @file_get_contents($abs)) !== $hash) {
                $risky = preg_match(self::SCRIPT, $p) || preg_match(self::SERVERCFG, $p) || preg_match('/\.(js|html?)$/i', $p);
                $out[] = $this->finding('file', 'changed', $risky ? 'danger' : 'warn', $p,
                    'Isi berbeda dengan versi resmi ' . ($ref['name'] ?: 'build ' . $ref['build']) . '.', $this->fileInfo($abs));
            }
        }

        if ($vendor) {
            $f = $this->finding('file', 'vendor', 'warn', 'vendor/ (' . count($vendor) . ' berkas)',
                count($vendor) . ' berkas pustaka (folder vendor) berbeda dari rilis resmi, tanpa ciri webshell. Biasanya karena pustaka pernah dipasang/diperbarui sendiri lewat Composer. '
                . 'Klik "Pulihkan semua" untuk menyamakan dengan rilis resmi, atau "Aman" bila Anda memang memasangnya sendiri.', ['paths' => array_slice($vendor, 0, 500)]);
            $f['key'] = sha1('file|vendor|' . implode('|', $vendor));
            $out[]    = $f;
        }

        // berkas asing
        foreach ($this->walk() as $p) {
            if (isset($seen[$p]) || isset($known[$p]) || in_array($p, Integrity::META, true) || (! $known && in_array($p, self::BASE_OK, true))) {
                continue;
            }
            $abs = ROOTPATH . $p;
            if (! str_contains($p, '/')) { // akar website
                if (in_array($p, self::ROOT_OK, true) || ! (preg_match(self::SCRIPT, $p) || preg_match(self::SERVERCFG, $p))) {
                    continue;
                }
            }
            if (! $known && ! preg_match('#^(public/uploads/|writable/)#', $p) && str_contains($p, '/')) {
                // tanpa daftar resmi: berkas kode website tidak bisa dibedakan, laporkan hanya yang berciri webshell/judi
                $head = (string) @file_get_contents($abs, false, null, 0, 262144);
                if (preg_match(self::SCRIPT, $p) && preg_match(self::SHELL, $head)) {
                    $out[] = $this->finding('file', 'suspect', 'danger', $p, 'Berkas berciri webshell (pintu belakang).', $this->fileInfo($abs));
                } elseif (preg_match(self::SCRIPT, $p) && preg_match(self::JUDI, $head)) {
                    $out[] = $this->finding('file', 'suspect', 'warn', $p, 'Berkas memuat kata yang biasa dipakai sisipan judi online. Periksa isinya: bila hanya teks wajar, klik Aman.', $this->fileInfo($abs));
                }
                continue;
            }
            if (str_starts_with($p, 'vendor/')) {
                // pustaka yang dipasang lewat Composer bisa membawa berkas contoh/uji: laporkan hanya yang berciri webshell/judi
                $head = (string) @file_get_contents($abs, false, null, 0, 262144);
                if (preg_match(self::SCRIPT, $p) && (preg_match(self::SHELL, $head) || preg_match(self::JUDI, $head))) {
                    $out[] = $this->finding('file', 'unknown', 'danger', $p, 'Berkas asing di folder pustaka yang berciri webshell / sisipan judi.', $this->fileInfo($abs));
                }
                continue;
            }
            if (preg_match(self::SCRIPT, $p)) {
                $info = $this->fileInfo($abs);
                if (in_array($p, self::CI_LEFTOVER, true) && empty($info['code']) && empty($info['judi'])) {
                    $out[] = $this->finding('file', 'unknown', 'warn', $p, 'Berkas contoh bawaan CodeIgniter (tidak dipakai website ini). Aman dikarantina.', $info);
                } elseif (! empty($info['code']) || ! empty($info['judi']) || preg_match('#^public/#', $p)) {
                    $out[] = $this->finding('file', 'unknown', 'danger', $p, 'Berkas skrip yang bukan bagian dari website (kemungkinan sisipan/webshell).', $info);
                } else {
                    $out[] = $this->finding('file', 'unknown', 'warn', $p, 'Berkas PHP tambahan yang bukan bagian dari rilis resmi (tanpa ciri webshell). Bila Anda tidak membuatnya, karantina.', $info);
                }
            } elseif (preg_match(self::SERVERCFG, $p)) {
                $out[] = $this->finding('file', 'unknown', 'warn', $p, 'Berkas pengaturan server tambahan. Wajar bila dibuat panel hosting (mis. MultiPHP INI); periksa isinya.', $this->fileInfo($abs));
            } elseif (preg_match('/\.(js|html?|svg)$/i', $p) && ! str_starts_with($p, 'public/uploads/') && $known) {
                $out[] = $this->finding('file', 'unknown', 'warn', $p, 'Berkas skrip/halaman tambahan di luar folder unggahan.', $this->fileInfo($abs));
            }
        }

        return $out;
    }

    /** Semua berkas di folder yang diperiksa + berkas di akar (jalur relatif, pemisah "/"). */
    private function walk(): array
    {
        $root  = rtrim(str_replace('\\', '/', ROOTPATH), '/') . '/';
        $files = [];
        foreach (@scandir(ROOTPATH) ?: [] as $f) {
            if ($f !== '.' && $f !== '..' && is_file(ROOTPATH . $f)) {
                $files[] = $f;
            }
        }
        foreach (self::DIRS as $d) {
            if (! is_dir(ROOTPATH . $d)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator(ROOTPATH . $d, \FilesystemIterator::SKIP_DOTS),
                static function (\SplFileInfo $f) use ($root): bool {
                    $rel = substr(str_replace('\\', '/', $f->getPathname()), strlen($root));
                    foreach (self::SKIP as $s) {
                        if ($rel === $s || str_starts_with($rel, $s . '/')) {
                            return false;
                        }
                    }

                    return ! $f->isLink();
                }
            ));
            foreach ($it as $f) {
                /** @var \SplFileInfo $f */
                if ($f->isFile()) {
                    $files[] = substr(str_replace('\\', '/', $f->getPathname()), strlen($root));
                }
                if (count($files) > 50000) {
                    break 2;
                }
            }
        }

        return $files;
    }

    private function skipped(string $p): bool
    {
        foreach (self::SKIP as $s) {
            if ($p === $s || str_starts_with($p, $s . '/')) {
                return true;
            }
        }

        return false;
    }

    /** @param bool $strict hanya ciri webshell yang kuat (untuk pustaka yang wajar memakai base64_decode dll.) */
    private function fileInfo(string $abs, bool $strict = false): array
    {
        $info = ['size' => (int) @filesize($abs), 'mtime' => (int) @filemtime($abs)];
        $head = (string) @file_get_contents($abs, false, null, 0, 65536);
        if (preg_match(self::JUDI, $head, $m)) {
            $info['judi'] = $m[0];
        }
        if (preg_match(self::SHELL, $head, $m) || (! $strict && preg_match('/(eval\s*\(|base64_decode\s*\(|gzinflate\s*\(|str_rot13\s*\(|shell_exec|passthru)/i', $head, $m))) {
            $info['code'] = mb_substr($m[0], 0, 60);
        }

        return $info;
    }

    // ------------------------------------------------------------------ isi database

    private function scanContent(): array
    {
        $db      = db_connect();
        $targets = [
            'pages'         => [['title', 'content', 'blocks'], 'halaman/{id}', 'Halaman'],
            'posts'         => [['title', 'excerpt', 'content'], 'data/berita/{id}', 'Berita'],
            'menus'         => [['label', 'url'], 'menu', 'Menu'],
            'home_sections' => [['title', 'settings'], 'beranda', 'Tampilan Beranda'],
            'sliders'       => [['badge', 'title', 'subtitle', 'button_url', 'button2_url'], 'data/slider/{id}', 'Slider'],
            'shortcuts'     => [['title', 'description', 'url'], 'data/pintasan/{id}', 'Pintasan'],
            'greetings'     => [['title', 'link_url'], 'data/ucapan/{id}', 'Ucapan'],
            'achievements'  => [['title', 'issuer', 'description'], 'data/prestasi/{id}', 'Galeri Prestasi'],
            'videos'        => [['title', 'url', 'description'], 'data/video/{id}', 'Galeri Video'],
            'documents'     => [['title', 'external_url'], 'data/dokumen/{id}', 'Arsip Dokumen'],
            'settings'      => [['value'], 'pengaturan', 'Pengaturan'],
        ];
        $out = [];
        foreach ($targets as $table => [$cols, $link, $label]) {
            if (! $db->tableExists($table)) {
                continue;
            }
            $fields = $db->getFieldNames($table);
            $cols   = array_values(array_intersect($cols, $fields));
            $idCol  = in_array('id', $fields, true) ? 'id' : (in_array('key', $fields, true) ? 'key' : null);
            if (! $cols || ! $idCol) {
                continue;
            }
            $q = $db->table($table)->select(array_merge([$idCol], $cols));
            if ($table === 'settings') {
                $q->notLike('key', 'secret')->notLike('key', 'password')->notLike('key', 'api_key');
            }
            foreach ($q->get()->getResultArray() as $row) {
                foreach ($cols as $c) {
                    $v = (string) ($row[$c] ?? '');
                    if ($v === '') {
                        continue;
                    }
                    $hits = [];
                    if (preg_match(self::JUDI, html_entity_decode(strip_tags($v)), $m)) {
                        $hits[] = ['warn', 'Kata kunci judi online: “' . $m[0] . '”', $m[0]];
                    }
                    if (preg_match(self::INJECT, $v, $m)) {
                        $hits[] = ['danger', 'Kode sisipan/tautan tersembunyi: ' . mb_strimwidth(trim($m[0]), 0, 60, '…'), $m[0]];
                    }
                    foreach ($hits as [$level, $msg, $needle]) {
                        $id    = (string) $row[$idCol];
                        $out[] = [
                            'type' => 'content', 'kind' => $table, 'level' => $level,
                            'key' => sha1('content|' . $table . '|' . $id . '|' . $c . '|' . mb_strtolower($needle)),
                            'path' => $label . ' #' . $id . ' (' . $c . ')', 'message' => $msg,
                            'snippet' => $this->snippet($v, $needle),
                            'link' => str_replace('{id}', rawurlencode($id), $link),
                        ];
                    }
                }
            }
        }

        return $out;
    }

    private function snippet(string $html, string $needle): string
    {
        $t   = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html))));
        $pos = mb_stripos($t, strip_tags($needle));
        if ($pos === false) {
            $t   = trim(preg_replace('/\s+/u', ' ', $html));
            $pos = mb_stripos($t, $needle);
        }
        $pos = max(0, (int) $pos - 60);

        return ($pos > 0 ? '…' : '') . mb_substr($t, $pos, 180) . '…';
    }

    // ------------------------------------------------------------------ tindakan

    /** Kembalikan satu berkas resmi dari GitHub (dicocokkan dengan sidik bertanda tangan). */
    public function restore(string $path): void
    {
        $ref = $this->reference();
        if (! $ref['ok'] || ! isset($ref['files'][$path])) {
            throw new RuntimeException('Berkas ini tidak tercantum di daftar resmi, tidak dapat dipulihkan otomatis.');
        }
        if (! Updater::allowedPath($path)) {
            throw new RuntimeException('Berkas ini tidak boleh ditulis otomatis. Pulihkan manual dari GitHub.');
        }
        $local = (new VersionCheck())->local();
        $repo  = (string) ($local['repo'] ?? '');
        $rel   = implode('/', array_map('rawurlencode', explode('/', $path)));
        $body  = '';
        // tag versi dulu; bila tag belum ada, cabang utama (aman: isi tetap harus cocok dengan sidik resmi bertanda tangan)
        foreach (array_unique(['v' . rawurlencode($ref['name']), rawurlencode((string) ($local['branch'] ?? 'main'))]) as $refName) {
            try {
                $body = $this->get('https://raw.githubusercontent.com/' . $repo . '/' . $refName . '/' . $rel, 8 * 1024 * 1024);
            } catch (\Throwable) {
                $body = '';
            }
            if ($body !== '' && Integrity::hash($body) === $ref['files'][$path]) {
                break;
            }
        }
        if (Integrity::hash($body) !== $ref['files'][$path]) {
            throw new RuntimeException('Berkas dari GitHub tidak cocok dengan sidik resmi — dibatalkan.');
        }
        $abs = ROOTPATH . $path;
        if (is_file($abs)) {
            $bak = WRITEPATH . 'backups/pemulihan-' . date('Ymd-His') . '/' . $path;
            @mkdir(dirname($bak), 0755, true);
            @copy($abs, $bak);
            @file_put_contents(WRITEPATH . 'backups/.htaccess', "Require all denied\nDeny from all\n");
        } elseif (! is_dir(dirname($abs))) {
            @mkdir(dirname($abs), 0755, true);
        }
        if (@file_put_contents($abs, $body, LOCK_EX) !== strlen($body)) {
            throw new RuntimeException('Server tidak mengizinkan menulis ' . $path . '.');
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($abs, true);
        }
    }

    /**
     * Pulihkan banyak berkas sekaligus dari temuan terakhir.
     * $which: 'vendor' = baris Pustaka; 'all' = semua berkas Diubah/Hilang + Pustaka.
     * @return array{0:int,1:array} [berhasil, gagal]
     */
    public function restoreVendor(string $which = 'vendor'): array
    {
        @set_time_limit(900);
        $paths = [];
        foreach ((self::last()['files'] ?? []) as $f) {
            if ($f['kind'] === 'vendor') {
                $paths = array_merge($paths, $f['info']['paths'] ?? []);
            } elseif ($which === 'all' && in_array($f['kind'], ['changed', 'missing'], true)) {
                $paths[] = $f['path'];
            }
        }
        $paths = array_values(array_unique($paths));
        $ok = 0;
        $bad = [];
        foreach ($paths as $p) {
            try {
                $this->restore((string) $p);
                $ok++;
            } catch (\Throwable $e) {
                $bad[] = $p . ': ' . $e->getMessage();
            }
        }

        return [$ok, $bad];
    }

    /** Pindahkan berkas asing ke writable/karantina (tidak dihapus, bisa diperiksa/dikembalikan manual). */
    public function quarantine(string $path): string
    {
        if ($path === '' || str_contains($path, '..') || ! is_file(ROOTPATH . $path) || in_array($path, ['.env', 'spark'], true)
            || in_array($path, Integrity::META, true) || isset($this->reference()['files'][$path])) {
            throw new RuntimeException('Berkas ini tidak dapat dikarantina.');
        }
        $dir = WRITEPATH . 'karantina/' . date('Ymd-His') . '/';
        @mkdir($dir . dirname($path), 0755, true);
        @file_put_contents(WRITEPATH . 'karantina/.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents(WRITEPATH . 'karantina/index.html', '');
        $dst = $dir . $path . '.karantina';
        if (! @rename(ROOTPATH . $path, $dst)) {
            throw new RuntimeException('Server tidak mengizinkan memindahkan ' . $path . '. Hapus manual lewat File Manager hosting.');
        }

        return 'writable/karantina/' . basename(rtrim($dir, '/')) . '/' . $path . '.karantina';
    }

    /** Tandai temuan sebagai aman (tidak ditampilkan lagi). */
    public function ignore(string $key): void
    {
        if (! preg_match('/^[0-9a-f]{40}$/', $key)) {
            return;
        }
        $list       = $this->ignored();
        $list[$key] = date('Y-m-d');
        service('siteSettings')->set(['security_ignore' => json_encode($list)]);
    }

    public function unignoreAll(): void
    {
        service('siteSettings')->set(['security_ignore' => '{}']);
    }

    private function ignored(): array
    {
        $l = json_decode((string) setting('security_ignore', '{}'), true);

        return is_array($l) ? $l : [];
    }

    // ------------------------------------------------------------------ bantu

    private function finding(string $type, string $kind, string $level, string $path, string $msg, array $info = []): array
    {
        // kunci "aman" ikut sidik isi: bila berkas berubah lagi, temuan muncul kembali
        $h = is_file(ROOTPATH . $path) ? (string) @md5_file(ROOTPATH . $path) : '';

        return ['type' => $type, 'kind' => $kind, 'level' => $level, 'path' => $path, 'message' => $msg, 'info' => $info,
            'key' => sha1($type . '|' . $kind . '|' . $path . '|' . $h)];
    }

    private function get(string $url, int $max): string
    {
        $ch  = curl_init($url);
        $buf = '';
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'WebsiteSatker-SecurityScan', CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($c, $chunk) use (&$buf, $max) {
                $buf .= $chunk;

                return strlen($buf) > $max ? 0 : strlen($chunk);
            },
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($code !== 200) {
            throw new RuntimeException($code === 404 ? 'tidak ditemukan (404)' : ($err ?: 'HTTP ' . $code));
        }

        return $buf;
    }
}
