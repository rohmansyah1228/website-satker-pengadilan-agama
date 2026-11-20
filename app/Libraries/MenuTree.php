<?php

namespace App\Libraries;

/** Menu navigasi dinamis (tabel menus) dengan cache. */
class MenuTree
{
    /** Kedalaman menu maksimum (1 = menu utama, 2 = sub-menu, 3–4 = sub-sub-menu). */
    public const MAX_DEPTH = 4;

    /** Semua menu sebagai pohon; $activeOnly untuk situs publik. */
    public function tree(bool $activeOnly = true): array
    {
        // href berisi alamat situs → cache dipisah per alamat (domain/IP/folder yang sedang dipakai)
        $key = 'site_menu_' . ($activeOnly ? 'pub' : 'all') . '_' . substr(md5(base_url()), 0, 10);
        if ($activeOnly && ($cached = cache($key))) {
            return $cached;
        }
        $db = db_connect();
        $b  = $db->table('menus m')
            ->select('m.*, p.section AS p_section, p.slug AS p_slug, p.status AS p_status')
            ->join('pages p', 'p.id = m.page_id', 'left')
            ->orderBy('m.sort')->orderBy('m.id');
        if ($activeOnly) {
            $b->where('m.is_active', 1);
        }
        $rows = $b->get()->getResultArray();

        $byParent = [];
        foreach ($rows as $r) {
            if ($activeOnly && $r['type'] === 'page' && $r['p_status'] !== 'published') {
                continue;
            }
            $r['href']                         = $this->href($r);
            $byParent[(int) $r['parent_id']][] = $r;
        }
        $build = function (int $pid, int $depth) use (&$build, $byParent, $activeOnly): array {
            $out = [];
            foreach ($byParent[$pid] ?? [] as $r) {
                $r['depth']    = $depth;
                $r['children'] = $depth < self::MAX_DEPTH ? $build((int) $r['id'], $depth + 1) : [];
                if ($r['children'] && in_array($r['type'], ['section', 'none'], true)) {
                    $r['href'] = $r['children'][0]['href'];
                }
                if ($activeOnly && $r['type'] === 'none' && ! $r['children']) {
                    continue; // judul grup tanpa isi tidak perlu tampil
                }
                $out[] = $r;
            }

            return $out;
        };
        $tree = $build(0, 1);
        if ($activeOnly) {
            cache()->save($key, $tree, 3600);
        }

        return $tree;
    }

    public function href(array $r): string
    {
        return match ($r['type']) {
            'page'    => $r['p_section'] ? site_url($r['p_section'] . '/' . $r['p_slug']) : '#',
            'route'   => site_url(ltrim((string) $r['url'], '/')),
            'url'     => \App\Libraries\PageBlocks::href((string) $r['url']),
            'section' => site_url((string) $r['section']),
            'none'    => '#',
            default   => '#',
        };
    }

    /** Menu level-1 yang memuat path aktif (untuk menandai menu & sidebar). */
    public function activeTop(string $path): ?array
    {
        $path = '/' . trim($path, '/');
        foreach ($this->tree() as $top) {
            if ($this->contains($top, $path)) {
                return $top;
            }
        }

        return null;
    }

    public function contains(array $item, string $path): bool
    {
        $h = '/' . trim((string) parse_url($item['href'], PHP_URL_PATH), '/');
        $base = '/' . trim((string) parse_url(site_url(), PHP_URL_PATH), '/');
        if ($base !== '/' && str_starts_with($h, $base)) {
            $h = '/' . ltrim(substr($h, strlen($base)), '/');
        }
        if (! in_array($item['type'], ['url', 'none'], true) && ($h === $path || ($h !== '/' && $item['type'] === 'route' && str_starts_with($path, $h . '/')))) {
            return true;
        }
        if ($item['type'] === 'section' && str_starts_with($path, '/' . $item['section'] . '/')) {
            return true;
        }
        foreach ($item['children'] ?? [] as $c) {
            if ($this->contains($c, $path)) {
                return true;
            }
        }

        return false;
    }

    public static function flush(): void
    {
        cache()->deleteMatching('site_menu_*');
    }
}
