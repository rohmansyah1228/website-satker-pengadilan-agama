<?php

namespace App\Libraries;

/**
 * Urutan tampil LHKPN: tahun terbaru dulu, lalu menurut daftar urutan jabatan
 * (Pengaturan → Identitas Satker → "Urutan jabatan LHKPN"; satu jabatan per baris).
 * Baris ke-1 bernilai 10, ke-2 = 20, dst.; jabatan yang tidak ada di daftar tampil paling bawah.
 * Kata kunci terpanjang yang cocok yang dipakai, jadi "Wakil Ketua" tidak terbaca sebagai "Ketua".
 * Kolom "Urutan Tampil" yang diisi (> 0) mengalahkan urutan otomatis.
 */
class LhkpnOrder
{
    public const DEFAULTS = ['Ketua', 'Wakil Ketua', 'Hakim', 'Panitera', 'Wakil Panitera', 'Sekretaris',
        'Panitera Muda', 'Kepala Bagian', 'Kepala Subbagian', 'Kasubbag', 'Panitera Pengganti', 'Jurusita'];

    public const MAX_LINES = 50;

    public static function defaultText(): string
    {
        return implode("\n", self::DEFAULTS);
    }

    /** Bersihkan teks isian: satu jabatan per baris, tanpa duplikat/baris kosong. */
    public static function clean(string $text): array
    {
        $out = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', strip_tags($line)));
            $key  = mb_strtolower($line);
            if ($line === '' || isset($out[$key])) {
                continue;
            }
            $out[$key] = mb_substr($line, 0, 80);
            if (count($out) >= self::MAX_LINES) {
                break;
            }
        }

        return array_values($out);
    }

    /** Daftar jabatan sesuai urutan di Pengaturan (untuk pilihan dropdown). */
    public static function names(): array
    {
        $saved = trim((string) setting('lhkpn_jabatan', ''));

        return self::clean($saved !== '' ? $saved : self::defaultText()) ?: self::DEFAULTS;
    }

    /** [kata kunci huruf kecil => nilai urut], diurutkan dari kata kunci terpanjang. */
    public static function ranks(): array
    {
        $saved = trim((string) setting('lhkpn_jabatan', ''));
        $list  = self::clean($saved !== '' ? $saved : self::defaultText()) ?: self::DEFAULTS;
        $ranks = [];
        foreach ($list as $i => $name) {
            $ranks[mb_strtolower($name)] = ($i + 1) * 10;
        }
        uksort($ranks, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $ranks;
    }

    public static function otherRank(): int
    {
        return (count(self::ranks()) + 1) * 10;
    }

    /** Nilai urut otomatis dari teks jabatan. */
    public static function rank(?string $position): int
    {
        $p = mb_strtolower((string) $position);
        foreach (self::ranks() as $kw => $n) {
            if (str_contains($p, $kw)) {
                return $n;
            }
        }

        return self::otherRank();
    }

    /** Ekspresi SQL urutan (dipakai Query Builder dengan escape=false). $alias mis. "l." */
    public static function sql(string $alias = '', bool $hasSort = true): string
    {
        $db   = db_connect();
        $pos  = 'LOWER(COALESCE(' . $alias . 'position, \'\'))';
        $case = 'CASE';
        foreach (self::ranks() as $kw => $n) {
            $case .= ' WHEN LOCATE(' . $db->escape($kw) . ", {$pos}) > 0 THEN " . (int) $n;
        }
        $case .= ' ELSE ' . self::otherRank() . ' END';

        return $hasSort ? "CASE WHEN {$alias}sort > 0 THEN {$alias}sort ELSE {$case} END" : $case;
    }

    /** Teks keterangan nilai urut, mis. "Ketua = 10, Wakil Ketua = 20, …". */
    public static function legend(): string
    {
        $saved = trim((string) setting('lhkpn_jabatan', ''));
        $parts = [];
        foreach (self::clean($saved !== '' ? $saved : self::defaultText()) as $i => $name) {
            $parts[] = $name . ' = ' . (($i + 1) * 10);
        }

        return implode(', ', $parts) . ', lainnya = ' . self::otherRank();
    }

    public static function hasSortColumn(): bool
    {
        try {
            return db_connect()->fieldExists('sort', 'lhkpn');
        } catch (\Throwable) {
            return false;
        }
    }
}
