<?php

namespace App\Libraries;

/**
 * Sidik berkas & tanda tangan rilis.
 *
 *  integrity.json : {"build","name","algo":"sha256-lf","files":{"jalur":"sha256",...}}
 *  version.sig    : base64( RSA-SHA256( message(build, version.json, integrity.json) ) )
 *
 * Sidik teks dihitung setelah CRLF → LF, sehingga berkas yang sama di Windows (XAMPP/Git) dan
 * Linux (hosting) menghasilkan sidik yang sama. Berkas biner (mengandung byte NUL) dihitung apa adanya.
 *
 * Berkas ini juga dipakai skrip penandatangan rilis (deploy/tanda-tangan.php) tanpa CodeIgniter,
 * jadi bagian statis di bawah tidak boleh memakai fungsi CodeIgniter.
 */
class Integrity
{
    /** Berkas metadata rilis (tidak tercantum di integrity.json). */
    public const META = ['version.json', 'integrity.json', 'version.sig'];

    public const ALGO = 'sha256-lf';

    /** Sidik isi berkas. */
    public static function hash(string $content): string
    {
        $binary = str_contains(substr($content, 0, 8000), "\0");

        return hash('sha256', $binary ? $content : str_replace("\r\n", "\n", $content));
    }

    /** Teks yang ditandatangani. */
    public static function message(int $build, string $versionJson, string $integrityJson): string
    {
        return "website-satker-rilis\n" . $build . "\n" . hash('sha256', $versionJson) . "\n" . hash('sha256', $integrityJson) . "\n";
    }

    /**
     * Periksa tanda tangan dengan daftar kunci publik.
     * @param list<string> $publicKeys PEM
     */
    public static function verify(int $build, string $versionJson, string $integrityJson, string $sigB64, array $publicKeys): bool
    {
        $sig = base64_decode(trim($sigB64), true);
        if ($sig === false || $sig === '' || ! function_exists('openssl_verify')) {
            return false;
        }
        $msg = self::message($build, $versionJson, $integrityJson);
        foreach ($publicKeys as $pem) {
            $key = @openssl_pkey_get_public($pem);
            if ($key && openssl_verify($msg, $sig, $key, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Uraikan & periksa satu set metadata rilis. Mengembalikan daftar sidik berkas, atau melempar galat.
     * @return array{build:int,name:string,files:array<string,string>}
     */
    public static function verified(string $versionJson, string $integrityJson, string $sigB64, array $publicKeys): array
    {
        $v = json_decode($versionJson, true);
        $i = json_decode($integrityJson, true);
        if (! is_array($v) || ! is_array($i) || ! is_array($i['files'] ?? null) || ($i['algo'] ?? '') !== self::ALGO) {
            throw new \RuntimeException('Data sidik rilis (integrity.json) tidak valid.');
        }
        $build = (int) ($v['build'] ?? 0);
        if ((int) ($i['build'] ?? -1) !== $build) {
            throw new \RuntimeException('integrity.json bukan milik versi ini.');
        }
        if (! self::verify($build, $versionJson, $integrityJson, $sigB64, $publicKeys)) {
            throw new \RuntimeException('Tanda tangan digital rilis TIDAK VALID — rilis ini bukan dari pengelola resmi atau telah diubah.');
        }

        return ['build' => $build, 'name' => (string) ($v['name'] ?? ''), 'files' => $i['files']];
    }
}
