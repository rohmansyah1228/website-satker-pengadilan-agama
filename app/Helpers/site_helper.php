<?php

use App\Libraries\SiteSettings;

if (! function_exists('setting')) {
    /** Ambil nilai pengaturan situs. */
    function setting(string $key, string $default = ''): string
    {
        $v = service('siteSettings')->get($key);

        return ($v === null || $v === '') ? $default : $v;
    }
}

if (! function_exists('is_pta')) {
    function is_pta(): bool
    {
        return setting('court_level', 'pa') === 'pta';
    }
}

if (! function_exists('asset')) {
    /** URL aset publik dengan versi berdasarkan waktu ubah berkas (cache busting). */
    function asset(string $path): string
    {
        $file = FCPATH . ltrim($path, '/');
        $v    = is_file($file) ? '?v=' . filemtime($file) : '';

        return base_url(ltrim($path, '/')) . $v;
    }
}

if (! function_exists('media_url')) {
    function media_url(?string $path): string
    {
        return $path ? base_url(ltrim($path, '/')) : '';
    }
}

if (! function_exists('file_url')) {
    /** URL unduh berkas tersimpan (PDF) berdasarkan id tabel files. */
    function file_url(?int $fileId): string
    {
        if (! $fileId) {
            return '';
        }
        $f = service('uploader')->find($fileId);

        return $f ? site_url('berkas/' . $f['uuid']) : '';
    }
}

if (! function_exists('link_url')) {
    /** Ubah URL relatif (/profil/visi-misi) menjadi absolut; URL luar dibiarkan. */
    function link_url(?string $url): string
    {
        // Token pengaturan, mis. {sipp_url}, {ecourt_url}, {direktori_url}, {siwas_url}, {survey_url}
        $url = preg_replace_callback('/\{([a-z_]+)\}/', static fn ($m) => setting($m[1]), trim((string) $url));
        if ($url === '' || $url === null) {
            return '#';
        }
        if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        return site_url(ltrim($url, '/'));
    }
}

if (! function_exists('is_external')) {
    function is_external(?string $url): bool
    {
        return (bool) preg_match('#^(https?:)?//#i', trim((string) $url))
            && ! str_starts_with(trim((string) $url), rtrim(base_url(), '/'));
    }
}

if (! function_exists('bulan_id')) {
    function bulan_id(int $m): string
    {
        return [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$m] ?? '';
    }
}

if (! function_exists('tanggal_id')) {
    /** 2026-11-19 → 19 November 2026 (opsional dengan nama hari). */
    function tanggal_id(?string $date, bool $withDay = false): string
    {
        if (! $date) {
            return '';
        }
        $t = strtotime($date);
        if ($t === false) {
            return '';
        }
        $s = date('j', $t) . ' ' . bulan_id((int) date('n', $t)) . ' ' . date('Y', $t);
        if ($withDay) {
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $s    = $days[(int) date('w', $t)] . ', ' . $s;
        }

        return $s;
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(int $b): string
    {
        return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format($b / 1024, 0, ',', '.') . ' KB';
    }
}

if (! function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return site_url(trim(config('App')->adminPath ?? 'panel-satker', '/') . '/' . ltrim($path, '/'));
    }
}

if (! function_exists('admin_user')) {
    function admin_user(): ?array
    {
        return service('adminAuth')->user();
    }
}

if (! function_exists('can')) {
    /** Cek apakah pengguna admin aktif memiliki salah satu peran. superadmin & admin selalu boleh kecuali dibatasi. */
    function can(array $roles, bool $superOnly = false): bool
    {
        $u = admin_user();
        if (! $u) {
            return false;
        }
        if ($u['role'] === 'superadmin') {
            return true;
        }
        if ($superOnly) {
            return false;
        }

        return $u['role'] === 'admin' || in_array($u['role'], $roles, true);
    }
}

if (! function_exists('plain_excerpt')) {
    function plain_excerpt(?string $html, int $len = 160): string
    {
        $html = preg_replace('#<(/(p|li|h[1-6]|div|blockquote|tr)|br\s*/?)>#i', ' $0', (string) $html);
        $t    = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));

        return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len)) . '…' : $t;
    }
}
