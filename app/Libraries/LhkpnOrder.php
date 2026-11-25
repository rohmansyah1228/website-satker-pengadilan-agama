<?php

namespace App\Libraries;

/**
 * Urutan tampil LHKPN: tahun terbaru dulu, lalu menurut jabatan
 * (Ketua, Wakil Ketua, Hakim, Panitera, Wakil Panitera, Sekretaris, …).
 * Kolom "Urutan" yang diisi (> 0) mengalahkan urutan otomatis.
 */
class LhkpnOrder
{
    /** [kata kunci jabatan (huruf kecil), nilai urut] — dicek dari atas; yang lebih spesifik lebih dulu. */
    public const RANKS = [
        ['wakil ketua', 20],
        ['ketua', 10],
        ['hakim', 30],
        ['wakil panitera', 45],
        ['panitera muda', 60],
        ['panitera pengganti', 80],
        ['panitera', 40],
        ['sekretaris', 50],
        ['kepala bagian', 55],
        ['kabag', 55],
        ['kepala sub', 70],
        ['kasubag', 70],
        ['kasubbag', 70],
        ['jurusita', 85],
        ['juru sita', 85],
    ];

    public const DEFAULT_RANK = 90;

    /** Nilai urut otomatis dari teks jabatan. */
    public static function rank(?string $position): int
    {
        $p = mb_strtolower((string) $position);
        foreach (self::RANKS as [$kw, $n]) {
            if (str_contains($p, $kw)) {
                return $n;
            }
        }

        return self::DEFAULT_RANK;
    }

    /** Ekspresi SQL urutan (dipakai Query Builder dengan escape=false). $alias mis. "l." */
    public static function sql(string $alias = '', bool $hasSort = true): string
    {
        $pos  = 'LOWER(COALESCE(' . $alias . 'position, \'\'))';
        $case = 'CASE';
        foreach (self::RANKS as [$kw, $n]) {
            $case .= " WHEN {$pos} LIKE '%" . $kw . "%' THEN {$n}";
        }
        $case .= ' ELSE ' . self::DEFAULT_RANK . ' END';

        return $hasSort ? "CASE WHEN {$alias}sort > 0 THEN {$alias}sort ELSE {$case} END" : $case;
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
