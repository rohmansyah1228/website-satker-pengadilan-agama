<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Pembuat berkas ZIP sederhana tanpa ekstensi PHP zip (ZipArchive).
 * Banyak XAMPP Windows dengan PHP 8.2+ belum mengaktifkan extension=zip, jadi paket unduhan
 * (mis. aplikasi SIPP API Kantor) dibuat dengan cara ini. Kompresi deflate bila zlib tersedia.
 */
class ZipWriter
{
    private array $central = [];
    private string $data   = '';

    public function add(string $name, string $content): void
    {
        $name  = str_replace('\\', '/', $name);
        $crc   = crc32($content);
        $len   = strlen($content);
        $deflt = function_exists('gzdeflate') ? gzdeflate($content, 6) : false;
        [$method, $body] = $deflt !== false && strlen($deflt) < $len ? [8, $deflt] : [0, $content];
        [$time, $date]   = self::dosTime(time());
        $offset = strlen($this->data);
        // 0x0800 = nama berkas UTF-8
        $head = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $time, $date, $crc, strlen($body), $len, strlen($name), 0);
        $this->data .= $head . $name . $body;
        $this->central[] = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, $method, $time, $date, $crc, strlen($body), $len,
            strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
    }

    public function save(string $path): void
    {
        $cd  = implode('', $this->central);
        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, count($this->central), count($this->central), strlen($cd), strlen($this->data), 0);
        if (@file_put_contents($path, $this->data . $cd . $end) === false) {
            throw new RuntimeException('Gagal membuat berkas zip.');
        }
    }

    /** @return array{0:int,1:int} [waktu DOS, tanggal DOS] */
    private static function dosTime(int $ts): array
    {
        $d = getdate($ts);

        return [($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2),
            (max(0, $d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday']];
    }
}
