<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Isi halaman berbasis blok:
 *   text  — teks kaya (HTMLPurifier)
 *   table — tabel bebas; kolom bertipe "tautan" tampil sebagai tombol (mis. "Lihat")
 *           — tautan Google Drive/Docs dibuka sebagai pratinjau di popup
 *   image — gambar (diunggah, dikonversi WebP) + keterangan
 *   embed — menyematkan halaman lain (Google Drive, Docs, YouTube, PDF, situs lain) dalam iframe
 *   monthly — arsip bulanan satu tahun: beberapa baris (mis. SERAPAN 01, SERAPAN 04) × 12 bulan, tiap bulan satu tautan
 */
class PageBlocks
{
    public const TYPES = [
        'text'  => ['label' => 'Teks', 'icon' => 'bi-text-paragraph'],
        'table' => ['label' => 'Tabel', 'icon' => 'bi-table'],
        'image' => ['label' => 'Gambar', 'icon' => 'bi-image'],
        'embed' => ['label' => 'Sematkan Halaman / Google Drive', 'icon' => 'bi-window-stack'],
        'monthly' => ['label' => 'Arsip Bulanan', 'icon' => 'bi-calendar3'],
    ];

    public const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    public const MAX_MONTHLY_ROWS = 20;

    public const MAX_BLOCKS = 60;
    public const MAX_COLS   = 12;
    public const MAX_ROWS   = 1000;
    public const SIZES      = ['full' => 'Penuh', 'large' => 'Besar (75%)', 'medium' => 'Sedang (50%)', 'small' => 'Kecil (33%)'];
    public const ALIGNS     = ['center' => 'Tengah', 'start' => 'Kiri', 'end' => 'Kanan'];
    /** Cara membuka tautan pada kolom tombol tabel */
    public const OPEN_MODES = ['popup' => 'Tampilkan di halaman ini', 'tab' => 'Buka di tab baru', 'same' => 'Pindah ke halaman tujuan'];

    /** Blok sebuah halaman; halaman lama (tanpa blok) dibaca sebagai satu blok teks. */
    public function decode(array $page): array
    {
        $raw = $page['blocks'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $b = json_decode($raw, true);
            if (is_array($b)) {
                return array_values(array_filter($b, static fn ($x) => is_array($x) && isset(self::TYPES[$x['type'] ?? ''])));
            }
        }
        $html = (string) ($page['content'] ?? '');

        return trim(strip_tags($html)) === '' ? [] : [['type' => 'text', 'html' => $html]];
    }

    /**
     * Validasi & bersihkan blok kiriman editor.
     *
     * @param array    $raw       hasil json_decode dari editor
     * @param string[] $oldImages gambar yang sudah dimiliki halaman (boleh dipakai ulang)
     * @param callable $upload    fn(string $key): ?string — simpan berkas unggahan blok, kembalikan path
     *
     * @return array{0: array, 1: string[]} [blok bersih, daftar galat]
     */
    public function sanitize(array $raw, array $oldImages, callable $upload): array
    {
        $out    = [];
        $errors = [];
        if (count($raw) > self::MAX_BLOCKS) {
            $errors[] = 'Maksimal ' . self::MAX_BLOCKS . ' blok per halaman.';
            $raw      = array_slice($raw, 0, self::MAX_BLOCKS);
        }
        $purifier = service('purifier');

        foreach (array_values($raw) as $i => $b) {
            $n    = 'Blok #' . ($i + 1);
            $type = is_array($b) ? (string) ($b['type'] ?? '') : '';

            switch ($type) {
                case 'text':
                    $html = $purifier->clean((string) ($b['html'] ?? ''));
                    if (trim(strip_tags($html)) !== '') {
                        $out[] = ['type' => 'text', 'html' => $html];
                    }
                    break;

                case 'table':
                    $cols = [];
                    foreach (array_slice((array) ($b['cols'] ?? []), 0, self::MAX_COLS) as $c) {
                        $kind   = ($c['kind'] ?? '') === 'link' ? 'link' : 'text';
                        $col    = [
                            'label'  => $this->str($c['label'] ?? '', 120),
                            'kind'   => $kind,
                            'button' => $kind === 'link' ? ($this->str($c['button'] ?? '', 30) ?: 'Lihat') : '',
                        ];
                        if ($kind === 'link') {
                            $col['open']   = isset(self::OPEN_MODES[$c['open'] ?? '']) ? $c['open'] : 'popup';
                            $col['newtab'] = ! array_key_exists('newtab', $c) || ! empty($c['newtab']);
                        }
                        $cols[] = $col;
                    }
                    if (! $cols) {
                        break;
                    }
                    $rows = [];
                    foreach (array_slice((array) ($b['rows'] ?? []), 0, self::MAX_ROWS) as $r) {
                        $r   = array_values((array) $r);
                        $row = [];
                        foreach ($cols as $ci => $c) {
                            $v = $this->str($r[$ci] ?? '', $c['kind'] === 'link' ? 2000 : 1000);
                            if ($c['kind'] === 'link' && $v !== '' && self::cleanUrl($v) === null) {
                                $errors[] = "{$n} (tabel), kolom “{$c['label']}”: tautan “" . mb_strimwidth($v, 0, 60, '…') . '” tidak valid. Gunakan alamat lengkap, mis. https://drive.google.com/…';
                            }
                            $row[] = $c['kind'] === 'link' ? (self::cleanUrl($v) ?? '') : $v;
                        }
                        if (implode('', $row) !== '') {
                            $rows[] = $row;
                        }
                    }
                    $out[] = [
                        'type'     => 'table',
                        'heading'  => $this->str($b['heading'] ?? '', 200),
                        'numbered' => ! empty($b['numbered']),
                        'cols'     => $cols,
                        'rows'     => $rows,
                    ];
                    break;

                case 'image':
                    $key = preg_replace('/[^a-z0-9]/i', '', (string) ($b['key'] ?? ''));
                    $src = (string) ($b['src'] ?? '');
                    $failed = false;
                    try {
                        $new = $key !== '' ? $upload($key) : null;
                    } catch (RuntimeException $e) {
                        $errors[] = "{$n} (gambar): " . $e->getMessage();
                        $new      = null;
                        $failed   = true;
                    }
                    if ($new) {
                        $src = $new;
                    } elseif (! in_array($src, $oldImages, true)) {
                        $src = '';
                    }
                    if ($src === '') {
                        if (! $failed) {
                            $errors[] = "{$n} (gambar): pilih berkas gambar atau hapus blok ini.";
                        }
                        break;
                    }
                    $link  = $this->str($b['link'] ?? '', 2000);
                    $out[] = [
                        'type'    => 'image',
                        'src'     => $src,
                        'alt'     => $this->str($b['alt'] ?? '', 200),
                        'caption' => $this->str($b['caption'] ?? '', 300),
                        'link'    => $link !== '' ? (self::cleanUrl($link) ?? '') : '',
                        'size'    => isset(self::SIZES[$b['size'] ?? '']) ? $b['size'] : 'full',
                        'align'   => isset(self::ALIGNS[$b['align'] ?? '']) ? $b['align'] : 'center',
                    ];
                    break;

                case 'embed':
                    $url = self::cleanUrl($this->str($b['url'] ?? '', 2000));
                    if ($url === null) {
                        $errors[] = "{$n} (sematan): alamat tidak valid. Gunakan alamat lengkap yang diawali https://";
                        break;
                    }
                    $out[] = [
                        'type'    => 'embed',
                        'url'     => $url,
                        'heading' => $this->str($b['heading'] ?? '', 200),
                        'height'  => max(200, min(2000, (int) ($b['height'] ?? 0) ?: 640)),
                    ];
                    break;

                case 'monthly':
                    $year = (int) ($b['year'] ?? 0);
                    if ($year < 2000 || $year > 2100) {
                        $year = (int) date('Y');
                    }
                    $rows = [];
                    foreach (array_slice((array) ($b['rows'] ?? []), 0, self::MAX_MONTHLY_ROWS) as $r) {
                        $label = $this->str($r['label'] ?? '', 120);
                        $links = [];
                        $src   = array_values((array) ($r['links'] ?? []));
                        for ($m = 0; $m < 12; $m++) {
                            $v = $this->str($src[$m] ?? '', 2000);
                            if ($v !== '' && self::cleanUrl($v) === null) {
                                $errors[] = "{$n} (arsip bulanan), " . ($label ?: 'baris') . ' ' . self::MONTHS[$m + 1] . ': tautan “' . mb_strimwidth($v, 0, 60, '…') . '” tidak valid. Gunakan alamat lengkap, mis. https://drive.google.com/…';
                            }
                            $links[] = $v !== '' ? (self::cleanUrl($v) ?? '') : '';
                        }
                        if ($label !== '' || implode('', $links) !== '') {
                            $rows[] = ['label' => $label, 'links' => $links];
                        }
                    }
                    if (! $rows) {
                        $errors[] = "{$n} (arsip bulanan): isi minimal satu baris (mis. SERAPAN 01) atau hapus blok ini.";
                        break;
                    }
                    $out[] = [
                        'type'    => 'monthly',
                        'year'    => $year,
                        'heading' => $this->str($b['heading'] ?? '', 200),
                        'open'    => isset(self::OPEN_MODES[$b['open'] ?? '']) ? $b['open'] : 'popup',
                        'rows'    => $rows,
                    ];
                    break;
            }
        }

        return [$out, array_values(array_unique($errors))];
    }

    /** Teks gabungan untuk kolom `content` (pencarian, ringkasan, pemeriksaan SINOVA). */
    public function searchText(array $blocks): string
    {
        $parts = [];
        foreach ($blocks as $b) {
            if ($b['type'] === 'text') {
                $parts[] = $b['html'];
            } elseif ($b['type'] === 'table') {
                $lines = [];
                if ($b['heading'] !== '') {
                    $lines[] = '<h2>' . esc($b['heading']) . '</h2>';
                }
                foreach ($b['rows'] as $r) {
                    $cells = [];
                    foreach ($b['cols'] as $ci => $c) {
                        if ($c['kind'] === 'text' && ($r[$ci] ?? '') !== '') {
                            $cells[] = esc($r[$ci]);
                        }
                    }
                    if ($cells) {
                        $lines[] = '<p>' . implode(' — ', $cells) . '</p>';
                    }
                }
                $parts[] = implode("\n", $lines);
            } elseif ($b['type'] === 'image' && ($b['caption'] !== '' || $b['alt'] !== '')) {
                $parts[] = '<p>' . esc($b['caption'] ?: $b['alt']) . '</p>';
            } elseif ($b['type'] === 'embed' && $b['heading'] !== '') {
                $parts[] = '<h2>' . esc($b['heading']) . '</h2>';
            } elseif ($b['type'] === 'monthly') {
                $lines = ['<h2>' . esc(($b['heading'] ?: 'Arsip') . ' ' . $b['year']) . '</h2>'];
                foreach ($b['rows'] as $r) {
                    $done = [];
                    foreach ($r['links'] as $i => $u) {
                        if ($u !== '') {
                            $done[] = self::MONTHS[$i + 1] . ' ' . $b['year'];
                        }
                    }
                    $lines[] = '<p>' . esc($r['label']) . ($done ? ' — ' . esc(implode(', ', $done)) : '') . '</p>';
                }
                $parts[] = implode("\n", $lines);
            }
        }

        return implode("\n", $parts);
    }

    /** Gambar yang dipakai blok (untuk menghapus berkas yang tidak terpakai). */
    public function images(array $blocks): array
    {
        return array_values(array_filter(array_map(static fn ($b) => $b['type'] === 'image' ? $b['src'] : null, $blocks)));
    }

    // ------------------------------------------------------------------ URL

    /** Hanya http(s) atau jalur lokal "/…". "www.contoh.go.id" dilengkapi https://. */
    public static function cleanUrl(string $u): ?string
    {
        $u = trim($u);
        if ($u === '') {
            return null;
        }
        if ($u[0] === '/' && ! str_starts_with($u, '//')) {
            return preg_match('#^/[^\s<>"\\\\]*$#', $u) ? $u : null;
        }
        if (! preg_match('#^[a-z][a-z0-9+.-]*:#i', $u)) {
            $u = 'https://' . $u;
        }
        $p = parse_url($u);
        if (! $p || ! in_array(strtolower($p['scheme'] ?? ''), ['http', 'https'], true) || empty($p['host'])
            || ! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$|^localhost$|^[0-9.]+$/i', $p['host']) || preg_match('/[\s<>"]/', $u)) {
            return null;
        }

        return $u;
    }

    /** Tautan "/halaman" diketik admin → lengkapi dengan alamat situs (berlaku juga bila website di subfolder). */
    public static function href(string $u): string
    {
        if ($u !== '' && $u[0] === '/' && ! str_starts_with($u, '//')) {
            $basePath = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');

            return ($basePath !== '' && str_starts_with($u, $basePath . '/')) ? $u : site_url(ltrim($u, '/'));
        }

        return content_expand_urls($u);
    }

    public static function isInternal(string $u): bool
    {
        if ($u !== '' && $u[0] === '/') {
            return true;
        }
        $host = parse_url($u, PHP_URL_HOST);
        $self = parse_url(base_url(), PHP_URL_HOST);

        return $host && $self && strcasecmp($host, $self) === 0;
    }

    /**
     * Alamat yang dapat ditampilkan di dalam popup/iframe untuk layanan yang dikenal
     * (Google Drive/Docs/Sheets/Slides/Form, YouTube, PDF). Null bila tidak dikenali.
     */
    public static function previewUrl(string $u): ?string
    {
        $p = parse_url($u);
        if (! $p) {
            return null;
        }
        $host = strtolower($p['host'] ?? '');
        $path = $p['path'] ?? '';
        parse_str($p['query'] ?? '', $q);

        if ($host === 'drive.google.com') {
            if (preg_match('#/file/d/([\w-]+)#', $path, $m)) {
                return "https://drive.google.com/file/d/{$m[1]}/preview";
            }
            if (preg_match('#/folders/([\w-]+)#', $path, $m)) {
                return "https://drive.google.com/embeddedfolderview?id={$m[1]}#list";
            }
            if (! empty($q['id']) && preg_match('/^[\w-]+$/', $q['id'])) {
                return "https://drive.google.com/file/d/{$q['id']}/preview";
            }

            return null;
        }
        if ($host === 'docs.google.com') {
            if (preg_match('#^/forms/(?:u/\d+/)?d/(e/)?([\w-]+)#', $path, $m)) {
                return 'https://docs.google.com/forms/d/' . $m[1] . $m[2] . '/viewform?embedded=true';
            }
            if (preg_match('#^/(document|spreadsheets|presentation)/(?:u/\d+/)?d/(e/)?([\w-]+)#', $path, $m)) {
                if ($m[2] === 'e/') { // "Publikasikan ke web"
                    return "https://docs.google.com/{$m[1]}/d/e/{$m[3]}/" . ($m[1] === 'presentation' ? 'embed' : 'pubhtml?widget=true');
                }

                return "https://docs.google.com/{$m[1]}/d/{$m[3]}/preview";
            }

            return null;
        }
        if (in_array($host, ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'youtu.be'], true)) {
            $id = $host === 'youtu.be' ? ltrim($path, '/') : ($q['v'] ?? (preg_match('#^/(?:embed|shorts|live)/([\w-]+)#', $path, $m) ? $m[1] : ''));

            return preg_match('/^[\w-]{6,20}$/', (string) $id) ? "https://www.youtube-nocookie.com/embed/{$id}" : null;
        }
        if (str_ends_with(strtolower($path), '.pdf') || (self::isInternal($u) && str_contains($path, '/berkas/'))) {
            return $u;
        }

        return null;
    }

    /** ID video YouTube dari berbagai bentuk tautan (watch, youtu.be, shorts, live, embed). */
    public static function youtubeId(string $u): ?string
    {
        $p = parse_url(trim($u));
        if (! $p) {
            return null;
        }
        $host = strtolower($p['host'] ?? '');
        $path = $p['path'] ?? '';
        parse_str($p['query'] ?? '', $q);
        if (! in_array($host, ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com'], true)) {
            return null;
        }
        $id = $host === 'youtu.be' ? ltrim($path, '/') : ($q['v'] ?? (preg_match('#^/(?:embed|shorts|live)/([\w-]+)#', $path, $m) ? $m[1] : ''));

        return preg_match('/^[\w-]{6,20}$/', (string) $id) ? $id : null;
    }

    /** Gambar sampul video: unggahan sendiri, atau sampul otomatis YouTube. */
    public static function videoThumb(array $v): string
    {
        if (! empty($v['image'])) {
            return media_url($v['image']);
        }
        $id = self::youtubeId((string) ($v['url'] ?? ''));

        return $id ? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg" : '';
    }

    /** Alamat untuk iframe blok sematan. */
    public static function embedUrl(string $u): string
    {
        if (preg_match('#^https?://(www\.)?google\.[a-z.]+/maps/embed#i', $u)) {
            return $u;
        }

        return self::previewUrl($u) ?? $u;
    }

    private function str($v, int $max): string
    {
        $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $v) ?? '');

        return mb_substr($v, 0, $max);
    }
}
