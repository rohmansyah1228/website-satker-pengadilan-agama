<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Penjaga URL untuk pengambilan dari luar (impor berita & pengumuman Badilag/MA).
 * Mencegah SSRF: hanya http/https ke alamat publik. Alamat loopback, jaringan lokal,
 * link-local, dan metadata cloud (169.254.169.254) ditolak — IP publik tetap diizinkan.
 */
class UrlGuard
{
    /** Periksa sebelum permintaan: skema http/https dan host menunjuk ke IP publik. */
    public static function assertPublic(string $url): void
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Hanya alamat http/https yang diperbolehkan.');
        }
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            throw new RuntimeException('Alamat tidak memiliki nama host yang sah.');
        }
        $ips = self::resolve($host);
        if (! $ips) {
            throw new RuntimeException('Nama host tidak dapat diterjemahkan ke alamat IP.');
        }
        foreach ($ips as $ip) {
            self::assertPublicIp($ip);
        }
    }

    /** Periksa setelah permintaan: IP yang benar-benar dihubungi curl (menangkal redirect / DNS-rebinding). */
    public static function assertConnectedIpPublic($ch): void
    {
        $ip = (string) curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        if ($ip !== '') {
            self::assertPublicIp($ip);
        }
    }

    private static function assertPublicIp(string $ip): void
    {
        // tolak loopback, privat, link-local, dan rentang khusus lainnya
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new RuntimeException('Alamat IP internal/privat tidak diperbolehkan (' . $ip . ').');
        }
        // 169.254.0.0/16 (link-local & metadata cloud) — sebagian versi PHP tidak menandainya reserved
        if (str_starts_with($ip, '169.254.') || strcasecmp($ip, 'fe80::') === 0 || stripos($ip, 'fe80:') === 0) {
            throw new RuntimeException('Alamat link-local tidak diperbolehkan (' . $ip . ').');
        }
    }

    /** @return list<string> daftar IP (IPv4 & IPv6) untuk host, atau host itu sendiri bila sudah berupa IP. */
    private static function resolve(string $host): array
    {
        $host = trim($host, '[]'); // IPv6 literal
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }
        $ips = gethostbynamel($host) ?: [];
        if (function_exists('dns_get_record')) {
            foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $r) {
                if (! empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }
}
