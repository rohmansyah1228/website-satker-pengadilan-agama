<?php

namespace App\Libraries;

/** TOTP (RFC 6238) untuk MFA — kompatibel Google Authenticator, Microsoft Authenticator, dll. */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $account);

        return "otpauth://totp/{$label}?secret={$secret}&issuer=" . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }

    public static function code(string $secret, ?int $time = null): string
    {
        $counter = intdiv($time ?? time(), 30);
        $bin     = pack('N*', 0) . pack('N*', $counter);
        $hash    = hash_hmac('sha1', $bin, self::base32Decode($secret), true);
        $offset  = ord($hash[19]) & 0x0F;
        $value   = ((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /** Verifikasi kode dengan toleransi ±1 periode (30 detik). */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }
        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $now + $i * 30), $code)) {
                return true;
            }
        }

        return false;
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private static function base32Decode(string $s): string
    {
        $s    = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s));
        $bits = '';
        foreach (str_split($s) as $c) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
