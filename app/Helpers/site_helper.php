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

if (! function_exists('sk_unlocked')) {
    /** True bila kunci halaman/menu wajib SK sedang dibuka (Pengaturan → Keamanan) untuk menyesuaikan SK baru. */
    function sk_unlocked(): bool
    {
        return setting('sk_unlock') === '1';
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

if (! function_exists('content_collapse_urls')) {
    /**
     * Alamat situs di dalam isi (gambar/tautan dari editor) disimpan sebagai penanda {{SITE_URL}}/,
     * lalu diganti dengan alamat terkini saat ditampilkan — isi tetap benar bila domain/folder berubah.
     */
    function content_collapse_urls(string $html): string
    {
        $base = rtrim(base_url(), '/') . '/';
        $alt  = str_starts_with($base, 'https://') ? 'http://' . substr($base, 8) : 'https://' . substr($base, 7);

        return str_replace([$base, $alt], '{{SITE_URL}}/', $html);
    }
}

if (! function_exists('content_expand_urls')) {
    function content_expand_urls(string $html): string
    {
        return str_contains($html, '{{SITE_URL}}/') ? str_replace('{{SITE_URL}}/', rtrim(base_url(), '/') . '/', $html) : $html;
    }
}

if (! function_exists('url_scheme_ok')) {
    /**
     * Tautan isian admin hanya boleh http(s), mailto:, tel:, jalur lokal "/…", "#…", "?…" atau token "{…}".
     * Menolak javascript:, data:, vbscript: dsb. (termasuk yang disamarkan dengan spasi/tab/baris baru).
     */
    function url_scheme_ok(string $u): bool
    {
        $t = preg_replace('/[\x00-\x20\x7f]+/', '', $u);
        if ($t === '' || ! preg_match('#^([a-z][a-z0-9+.\-]*):#i', $t, $m)) {
            return true; // tanpa skema: jalur relatif / domain tanpa https:// / token
        }

        return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true);
    }
}

if (! function_exists('safe_href')) {
    /** Untuk ditampilkan di href: tautan berskema berbahaya diganti "#". */
    function safe_href(?string $u): string
    {
        $u = (string) $u;

        return url_scheme_ok($u) ? $u : '#';
    }
}

if (! function_exists('favicon_tags')) {
    /** Ikon tab browser = logo satker yang diunggah (Pengaturan), sama di situs publik & panel admin. */
    function favicon_tags(): string
    {
        $path = '';
        try {
            $path = (string) setting('logo');
        } catch (\Throwable $e) {
            // basis data belum siap (mis. saat pemasangan)
        }
        if ($path !== '' && is_file(FCPATH . ltrim($path, '/'))) {
            $url = media_url($path) . '?v=' . filemtime(FCPATH . ltrim($path, '/'));
        } else {
            $path = 'assets/img/logo-default.svg';
            $url  = base_url($path);
        }
        $type = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'ico' => 'image/x-icon', default => '',
        };
        $t = $type ? ' type="' . $type . '"' : '';

        return '<link rel="icon" href="' . esc($url) . '"' . $t . '>' . "\n    "
            . '<link rel="apple-touch-icon" href="' . esc($url) . '">' . "\n";
    }
}

if (! function_exists('survey_category')) {
    /**
     * Kategori nilai survei (skala 1,00–4,00):
     *   1,00–2,5996 Tidak Baik · 2,60–3,0640 Kurang Baik · 3,0644–3,532 Baik · 3,5324–4,00 Sangat Baik.
     * Nilai skala 100 (mis. IKM 92,40) dikonversi dulu (÷ 25). IPAK memakai sebutan "Bersih".
     */
    function survey_category(string $value, string $kind = 'ikm'): string
    {
        $v = str_replace([' ', ','], ['', '.'], trim($value));
        if (substr_count($v, '.') > 1) {
            $v = preg_replace('/\.(?=.*\.)/', '', $v); // 1.234,56 → 1234.56
        }
        if ($v === '' || ! is_numeric($v)) {
            return '';
        }
        $n = (float) $v;
        if ($n > 4) {
            $n /= 25; // skala 25–100 → 1–4
        }
        if ($n <= 0 || $n > 4.0001) {
            return '';
        }
        $label = $n < 2.60 ? 'Tidak Baik' : ($n < 3.0644 ? 'Kurang Baik' : ($n < 3.5324 ? 'Baik' : 'Sangat Baik'));

        return strtolower($kind) === 'ipak' ? str_replace('Baik', 'Bersih', $label) : $label;
    }
}

if (! function_exists('footer_links_default')) {
    /** Teks bawaan "Tautan Ekosistem Peradilan" (satu tautan per baris: Nama | URL). */
    function footer_links_default(): string
    {
        return "Mahkamah Agung RI | https://www.mahkamahagung.go.id\n"
            . "Ditjen Badan Peradilan Agama | https://badilag.mahkamahagung.go.id\n"
            . "JDIH Mahkamah Agung RI | https://jdih.mahkamahagung.go.id\n"
            . 'SIWAS Mahkamah Agung RI | ' . (setting('siwas_url') ?: 'https://siwas.mahkamahagung.go.id');
    }
}

if (! function_exists('parse_footer_links')) {
    /**
     * Uraikan teks tautan (satu per baris "Nama | URL"). Baris tidak sah diabaikan, atau dicatat di $errors.
     * @return list<array{0:string,1:string}>
     */
    function parse_footer_links(string $text, ?array &$errors = null): array
    {
        $out = [];
        foreach (preg_split('/\R/', $text) as $i => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $url   = $parts[1] ?? '';
            if ($parts[0] === '' || ! preg_match('#^https?://#i', $url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = 'baris ' . ($i + 1) . ' (' . mb_substr($line, 0, 60) . ') harus berformat Nama | https://alamat';
                continue;
            }
            $out[] = [mb_substr($parts[0], 0, 80), $url];
        }

        return $out;
    }
}

if (! function_exists('footer_links')) {
    /** Tautan Ekosistem Peradilan di kaki halaman (bawaan bila belum pernah diatur). */
    function footer_links(): array
    {
        $t = (string) setting('footer_links', '');

        return parse_footer_links(trim($t) !== '' ? $t : footer_links_default());
    }
}

