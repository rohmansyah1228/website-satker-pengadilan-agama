<?php

namespace App\Libraries;

/**
 * Pengecek versi aplikasi: membandingkan version.json lokal dengan version.json di GitHub.
 * Hanya MEMBERI TAHU bila ada versi baru (tidak mengubah apa pun). Hasil disimpan 6 jam.
 */
class VersionCheck
{
    public const TTL = 6 * 3600;

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
            'version' => (string) ($local['version'] ?? '-'), 'repo' => $repo,
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
        $url    = 'https://raw.githubusercontent.com/' . $repo . '/' . $branch . '/version.json';
        $res    = ['at' => time(), 'data' => null, 'error' => null];
        try {
            UrlGuard::assertPublic($url); // hanya host publik (GitHub)
            if (! function_exists('curl_init')) {
                throw new \RuntimeException('cURL tidak aktif');
            }
            $ch  = curl_init($url);
            $buf = '';
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_MAXREDIRS       => 3,
                CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT  => 4,
                CURLOPT_TIMEOUT         => 6,
                CURLOPT_USERAGENT       => 'WebsiteSatker-VersionCheck',
                CURLOPT_WRITEFUNCTION   => static function ($c, $chunk) use (&$buf) {
                    $buf .= $chunk;

                    return strlen($buf) > 262144 ? 0 : strlen($chunk);
                },
            ]);
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err  = curl_errno($ch) ? curl_error($ch) : '';
            curl_close($ch);
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
}
