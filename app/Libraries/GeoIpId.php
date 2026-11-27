<?php

namespace App\Libraries;

/**
 * Perkiraan provinsi & kota dari IP (khusus Indonesia), dari berkas lokal app/ThirdParty/geoip-id/.
 * IP tidak dikirim ke layanan luar. Data: IP Geolocation by DB-IP (CC BY 4.0).
 */
class GeoIpId
{
    private const DIR = APPPATH . 'ThirdParty' . DIRECTORY_SEPARATOR . 'geoip-id' . DIRECTORY_SEPARATOR;

    private static ?array $locations = null;

    /** @return array{provinsi:string,kota:string}|null null = bukan IP Indonesia / tidak diketahui */
    public static function lookup(string $ip): ?array
    {
        $bin = @inet_pton($ip);
        if ($bin === false || $bin === '') {
            return null;
        }
        $w    = strlen($bin); // 4 (IPv4) atau 16 (IPv6)
        $file = self::DIR . ($w === 4 ? 'geoip-id-v4.bin' : 'geoip-id-v6.bin');
        $size = @filesize($file);
        $rec  = $w * 2 + 2;
        if (! $size || $size % $rec !== 0 || ! ($fh = @fopen($file, 'rb'))) {
            return null;
        }
        $lo = 0;
        $hi = intdiv($size, $rec) - 1;
        $id = null;
        while ($lo <= $hi) { // pencarian biner: rentang dengan awal <= ip
            $mid = ($lo + $hi) >> 1;
            fseek($fh, $mid * $rec);
            $row   = (string) fread($fh, $rec);
            $start = substr($row, 0, $w);
            if (strcmp($start, $bin) > 0) {
                $hi = $mid - 1;
                continue;
            }
            if (strcmp(substr($row, $w, $w), $bin) >= 0) {
                $id = unpack('n', substr($row, $w * 2, 2))[1];
                break;
            }
            $lo = $mid + 1;
        }
        fclose($fh);
        if ($id === null) {
            return null;
        }
        $loc = self::locations()[$id] ?? null;

        return $loc ? ['provinsi' => (string) $loc[0], 'kota' => (string) $loc[1]] : null;
    }

    private static function locations(): array
    {
        if (self::$locations === null) {
            $j = json_decode((string) @file_get_contents(self::DIR . 'geoip-id-lokasi.json'), true);
            self::$locations = is_array($j['lokasi'] ?? null) ? $j['lokasi'] : [];
        }

        return self::$locations;
    }
}
