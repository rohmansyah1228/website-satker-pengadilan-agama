<?php

namespace App\Libraries;

/**
 * Pengecek versi aplikasi: membandingkan version.json lokal dengan version.json di GitHub.
 * Hanya MEMBERI TAHU bila ada versi baru (tidak mengubah apa pun). Hasil disimpan 6 jam.
 */
class VersionCheck
{
    public const TTL = 6 * 3600;

    /** Nama versi untuk ditampilkan: "Versi 18 (24 November 2026)". */
    public static function label(int $build, string $date = ''): string
    {
        $d = $date !== '' && strtotime($date) ? tanggal_id($date) : '';

        return 'Versi ' . $build . ($d !== '' ? ' (' . $d . ')' : '');
    }

    /** Versi terbaru dari hasil pemeriksaan terakhir (tanpa menghubungi GitHub), 0 bila belum diperiksa. */
    public static function cachedLatest(): int
    {
        $c = cache('version_check');

        return is_array($c) && is_array($c['data'] ?? null) ? (int) ($c['data']['build'] ?? 0) : 0;
    }

    /** version.json yang ikut terpasang bersama kode (versi yang sedang berjalan). */
    public function local(): array
    {
        $f = ROOTPATH . 'version.json';
        $d = is_file($f) ? json_decode((string) @file_get_contents($f), true) : null;

        return is_array($d) ? $d : ['build' => 0, 'version' => '-', 'repo' => '', 'releases' => []];
    }

    /**
     * Status pembaruan untuk Dashboard.
     * @return array{available:bool,local:int,latest:int,version:string,repo:string,url:string,releases:list,files:list,error:?string,checkedAt:?int}
     */
    public function status(): array
    {
        $local = $this->local();
        $repo  = (string) ($local['repo'] ?? '');
        $base  = ['available' => false, 'local' => (int) ($local['build'] ?? 0), 'latest' => (int) ($local['build'] ?? 0),
            'version' => (string) ($local['version'] ?? '-'), 'localVersion' => (string) ($local['version'] ?? ''), 'repo' => $repo,
            'history' => array_slice(array_map(static fn ($x) => ['version' => (string) ($x['version'] ?? ''), 'build' => (int) ($x['build'] ?? 0), 'notes' => (string) ($x['notes'] ?? '')], (array) ($local['releases'] ?? [])), 0, 5),
            'url' => $repo ? 'https://github.com/' . $repo : '', 'releases' => [], 'files' => [], 'error' => null, 'checkedAt' => null];

        if ($repo === '') {
            return $base;
        }
        $remote = $this->remote($local);
        $base['checkedAt'] = $remote['at'] ?? null;
        if (! empty($remote['error'])) {
            $base['error'] = $remote['error'];

            return $base;
        }
        $r = $remote['data'] ?? [];
        $latest = (int) ($r['build'] ?? 0);
        if ($latest <= $base['local']) {
            return $base; // sudah versi terbaru
        }
        // kumpulkan rilis yang lebih baru dari yang terpasang, beserta gabungan berkas yang berubah
        $newer = array_values(array_filter($r['releases'] ?? [], static fn ($x) => (int) ($x['build'] ?? 0) > $base['local']));
        usort($newer, static fn ($a, $b) => (int) $b['build'] - (int) $a['build']);
        $files = [];
        foreach ($newer as $x) {
            foreach ((array) ($x['files'] ?? []) as $f) {
                $files[$f] = true;
            }
        }
        ksort($files);

        return array_merge($base, [
            'available' => true,
            'latest'    => $latest,
            'version'   => (string) ($r['version'] ?? $base['version']),
            'releases'  => array_map(static fn ($x) => ['version' => (string) ($x['version'] ?? ''), 'build' => (int) ($x['build'] ?? 0), 'notes' => (string) ($x['notes'] ?? '')], $newer),
            'files'     => array_keys($files),
        ]);
    }

    /** Ambil version.json dari GitHub (raw), disimpan 6 jam; gagal = diam (tidak mengganggu dashboard). */
    private function remote(array $local): array
    {
        $cached = cache('version_check');
        if (is_array($cached) && ($cached['at'] ?? 0) > time() - self::TTL) {
            return $cached;
        }
        $repo   = (string) ($local['repo'] ?? '');
        $branch = (string) ($local['branch'] ?? 'main');
        $res    = ['at' => time(), 'data' => null, 'error' => null];
        try {
            if (! function_exists('curl_init')) {
                throw new \RuntimeException('cURL tidak aktif');
            }
            // version.json dibaca pada SHA commit terbaru (selalu segar), bukan nama cabang yang bisa
            // tertahan cache CDN GitHub beberapa menit. Bila SHA tak didapat → pakai nama cabang.
            $ref = self::latestSha($repo, $branch) ?? $branch;
            [$code, $buf, $err] = $this->get('https://raw.githubusercontent.com/' . $repo . '/' . $ref . '/version.json', [], 262144);
            if ($code !== 200 || $buf === '') {
                throw new \RuntimeException($err ?: ('HTTP ' . $code));
            }
            $data = json_decode($buf, true);
            if (! is_array($data)) {
                throw new \RuntimeException('format version.json tidak valid');
            }
            $res['data'] = $data;
        } catch (\Throwable $e) {
            $res['error'] = $e->getMessage();
        }
        cache()->save('version_check', $res, self::TTL);

        return $res;
    }

    /**
     * SHA commit terbaru sebuah cabang di GitHub.
     * Utama: info/refs (jalur yang dipakai `git clone`, tidak terkena batas API 60/jam per IP —
     * penting di shared hosting yang IP-nya dipakai bersama). Cadangan: API GitHub.
     */
    public static function latestSha(string $repo, string $branch): ?string
    {
        if (! preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo) || ! preg_match('#^[A-Za-z0-9_./-]+$#', $branch) || ! function_exists('curl_init')) {
            return null;
        }
        $self = new self();
        try {
            [$code, $body] = $self->get('https://github.com/' . $repo . '.git/info/refs?service=git-upload-pack', [], 2 * 1024 * 1024, 'git/2.43.0');
            if ($code === 200 && preg_match('#([0-9a-f]{40}) refs/heads/' . preg_quote($branch, '#') . '\n#', $body, $m)) {
                return $m[1];
            }
        } catch (\Throwable) {
        }
        try {
            [$code, $sha] = $self->get('https://api.github.com/repos/' . $repo . '/commits/' . rawurlencode($branch), ['Accept: application/vnd.github.sha'], 4096);
            if ($code === 200 && preg_match('/^[0-9a-f]{40}$/', trim($sha))) {
                return trim($sha);
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /** GET HTTPS sederhana (host publik saja). @return array{0:int,1:string,2:string} */
    private function get(string $url, array $headers, int $max, string $ua = 'WebsiteSatker-VersionCheck'): array
    {
        UrlGuard::assertPublic($url);
        $ch  = curl_init($url);
        $buf = '';
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 3,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT  => 4,
            CURLOPT_TIMEOUT         => 6,
            CURLOPT_USERAGENT       => $ua,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_WRITEFUNCTION   => static function ($c, $chunk) use (&$buf, $max) {
                $buf .= $chunk;

                return strlen($buf) > $max ? 0 : strlen($chunk);
            },
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_errno($ch) ? curl_error($ch) : '';
        curl_close($ch);

        return [$code, $buf, $err];
    }
}
