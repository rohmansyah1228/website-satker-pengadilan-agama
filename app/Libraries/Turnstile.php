<?php

namespace App\Libraries;

/**
 * Cloudflare Turnstile pada halaman login admin (pengganti CAPTCHA; gratis, tidak perlu domain di Cloudflare).
 *
 * Aktif bila Pengaturan → Keamanan: "Cloudflare Turnstile" = Aktif dan kunci situs + kunci rahasia terisi.
 * Saklar darurat (mis. kunci dihapus di Cloudflare sehingga tidak bisa login):
 *   tambahkan baris  turnstile.nonaktif = true  ke berkas .env website.
 */
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    public const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    /** Turnstile wajib di login? */
    public static function active(): bool
    {
        if (self::killSwitch()) {
            return false;
        }

        return setting('turnstile', '0') === '1' && self::siteKey() !== '' && self::secret() !== '';
    }

    /** Dimatikan lewat .env (turnstile.nonaktif = true). */
    public static function killSwitch(): bool
    {
        return in_array(strtolower(trim((string) env('turnstile.nonaktif', ''))), ['1', 'true', 'ya', 'yes', 'on'], true);
    }

    public static function siteKey(): string
    {
        return trim(setting('turnstile_site_key'));
    }

    public static function secret(): string
    {
        return trim(setting('turnstile_secret'));
    }

    /** Format kunci situs/rahasia Turnstile (0x… untuk kunci asli, 1x…/2x…/3x… untuk kunci uji Cloudflare). */
    public static function keyFormatOk(string $key): bool
    {
        return (bool) preg_match('/^[0-3]x[0-9A-Za-z_-]{14,}$/', $key);
    }

    /**
     * Periksa token dari widget ke Cloudflare.
     * @return array{ok:bool, reachable:bool, error:string}
     *         reachable=false: server tidak bisa menghubungi Cloudflare (bukan kesalahan pengunjung).
     */
    public static function verify(string $token, string $ip, ?string $secret = null): array
    {
        $secret ??= self::secret();
        $token = trim($token);
        if (strlen($token) > 2048) {
            return ['ok' => false, 'reachable' => true, 'error' => 'Verifikasi Cloudflare tidak valid.'];
        }
        // token kosong tetap ditanyakan ke Cloudflare: bila Cloudflare tak terjangkau (widget pun tak bisa tampil),
        // pemanggil bisa membedakannya dari pengunjung yang belum menyelesaikan verifikasi
        if (! function_exists('curl_init')) {
            return ['ok' => false, 'reachable' => false, 'error' => 'Ekstensi cURL PHP tidak aktif.'];
        }
        $ch = curl_init(self::VERIFY_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $ip]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT      => 'WebsiteSatker-Turnstile',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_errno($ch) ? curl_error($ch) : '';
        curl_close($ch);
        if ($body === false || $code === 0 || $code >= 500) {
            return ['ok' => false, 'reachable' => false, 'error' => 'Cloudflare tidak dapat dihubungi' . ($err !== '' ? ' (' . $err . ')' : '') . '.'];
        }
        $j = json_decode((string) $body, true);
        if (! is_array($j)) {
            return ['ok' => false, 'reachable' => false, 'error' => 'Jawaban Cloudflare tidak dikenali.'];
        }
        if (! empty($j['success'])) {
            return ['ok' => true, 'reachable' => true, 'error' => ''];
        }
        $codes = (array) ($j['error-codes'] ?? []);
        $msg   = match (true) {
            in_array('invalid-input-secret', $codes, true), in_array('missing-input-secret', $codes, true) => 'Kunci rahasia (Secret Key) Turnstile salah.',
            in_array('missing-input-response', $codes, true) => 'Tunggu kotak verifikasi Cloudflare sampai bertanda Berhasil, lalu coba lagi.',
            in_array('timeout-or-duplicate', $codes, true) => 'Verifikasi Cloudflare kedaluwarsa. Muat ulang halaman lalu coba lagi.',
            default => 'Verifikasi Cloudflare gagal (' . implode(', ', array_map('strval', $codes)) . ').',
        };

        return ['ok' => false, 'reachable' => true, 'error' => $msg];
    }
}
