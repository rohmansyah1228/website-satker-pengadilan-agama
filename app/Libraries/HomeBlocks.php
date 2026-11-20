<?php

namespace App\Libraries;

/** Menyusun tampilan Beranda dari tabel home_sections. */
class HomeBlocks
{
    /** Semua tampilan (urut posisi grid: atas→bawah, kiri→kanan). */
    public function tree(bool $activeOnly = true): array
    {
        $b = db_connect()->table('home_sections')->where('type !=', 'row')
            ->orderBy('grid_y')->orderBy('grid_x')->orderBy('id');
        if ($activeOnly) {
            $b->where('is_active', 1);
        }
        $out = [];
        foreach ($b->get()->getResultArray() as $r) {
            $r['settings'] = json_decode((string) $r['settings'], true) ?: [];
            $r['children'] = [];
            $out[]         = $r;
        }

        return $out;
    }

    /**
     * Pecah grid menjadi bagian untuk dirender:
     *  - 'band' : baris yang hanya berisi satu kotak selebar 12 kolom & tinggi 1 → tampil lebar penuh layar
     *  - 'grid' : kumpulan baris lain → CSS grid 12 kolom (baris kosong dipadatkan)
     */
    public function segments(array $items): array
    {
        $occ = [];
        $max = 0;
        foreach ($items as $i => $it) {
            for ($r = (int) $it['grid_y']; $r < (int) $it['grid_y'] + max(1, (int) $it['grid_h']); $r++) {
                $occ[$r][] = $i;
                $max       = max($max, $r);
            }
        }
        $segments = [];
        $cur      = null;
        $close    = static function () use (&$cur, &$segments) {
            if ($cur && $cur['items']) {
                $segments[] = $cur;
            }
            $cur = null;
        };
        for ($r = 1; $r <= $max; $r++) {
            $list = $occ[$r] ?? [];
            if (! $list) {
                continue;
            }
            if (count($list) === 1) {
                $it = $items[$list[0]];
                if ((int) $it['grid_x'] === 1 && (int) $it['grid_w'] === 12 && (int) $it['grid_h'] === 1) {
                    $close();
                    $segments[] = ['kind' => 'band', 'item' => $it];
                    continue;
                }
            }
            if (! $cur) {
                $cur = ['kind' => 'grid', 'items' => [], 'map' => []];
            }
            $cur['map'][$r] = count($cur['map']) + 1;
            foreach ($list as $i) {
                if ((int) $items[$i]['grid_y'] === $r) {
                    $cur['items'][] = $i;
                }
            }
            // simpan referensi untuk dihitung setelah map lengkap
            $cur['all'] = $items;
        }
        $close();
        // hitung posisi baris yang dipadatkan
        foreach ($segments as &$sg) {
            if ($sg['kind'] !== 'grid') {
                continue;
            }
            $rows = [];
            foreach ($sg['items'] as $i) {
                $it          = $sg['all'][$i];
                $start       = $sg['map'][(int) $it['grid_y']];
                $endRow      = (int) $it['grid_y'] + max(1, (int) $it['grid_h']) - 1;
                $end         = $sg['map'][$endRow] ?? $start;
                $it['row']   = $start;
                $it['rspan'] = $end - $start + 1;
                $rows[]      = $it;
            }
            $sg = ['kind' => 'grid', 'items' => $rows];
        }

        return $segments;
    }

    /** Data yang dibutuhkan satu blok. */
    public function data(array $b): array
    {
        $db  = db_connect();
        $s   = $b['settings'];
        $lim = max(1, min(30, (int) ($s['limit'] ?? 5)));
        $now = date('Y-m-d H:i:s');

        switch ($b['type']) {
            case 'slider':
                return ['sliders' => $db->table('sliders')->where('is_active', 1)->orderBy('sort')->orderBy('id')
                    ->get(config('SiteStructure')->maxSliders)->getResultArray()];

            case 'shortcuts':
                $q = $db->table('shortcuts')->where('is_active', 1);
                if ($db->fieldExists('grp', 'shortcuts')) {
                    $g = trim((string) ($s['group'] ?? ''));
                    $g === '' ? $q->groupStart()->where('grp', null)->orWhere('grp', '')->groupEnd() : $q->where('grp', $g);
                }

                return ['shortcuts' => $q->orderBy('sort')->orderBy('id')->get(30)->getResultArray()];

            case 'videos':
                if (! $db->tableExists('videos')) {
                    return ['videos' => []];
                }

                return ['videos' => $db->table('videos')->where('is_active', 1)->orderBy('published_at', 'DESC')->orderBy('sort')->orderBy('id', 'DESC')
                    ->get($lim)->getResultArray()];

            case 'announcements':
                $ghaib = ! empty($s['show_ghaib'])
                    ? $db->table('ghaib')->where('is_active', 1)->where('hearing_date >=', date('Y-m-d'))->orderBy('hearing_date')->get($lim)->getResultArray()
                    : [];
                $rest    = max(0, $lim - count($ghaib));
                $notices = $rest ? $db->table('posts')->where('status', 'published')->where('published_at <=', $now)
                    ->whereIn('category', ['pengumuman', 'serta-merta'])->orderBy('published_at', 'DESC')
                    ->get($rest)->getResultArray() : [];

                return ['ghaib' => $ghaib, 'notices' => $notices];

            case 'news':
                $q = $db->table('posts')->where('status', 'published')->where('published_at <=', $now);
                if (! empty($s['category'])) {
                    $q->where('category', $s['category']);
                } else {
                    $q->whereNotIn('category', ['serta-merta', 'pengumuman']); // pengumuman tampil di blok Pengumuman
                }

                return ['posts' => $q->orderBy('published_at', 'DESC')->get($lim)->getResultArray()];

            case 'hearings':
                return ['rows' => array_slice(service('sippSource')->hearings(date('Y-m-d')), 0, $lim)];

            case 'documents':
                $q = $db->table('documents d')->select('d.*, t.name AS type_name, f.uuid, f.size')
                    ->join('doc_types t', 't.id = d.doc_type_id')->join('files f', 'f.id = d.file_id', 'left');
                if (! empty($s['doc_type'])) {
                    $q->where('d.doc_type_id', (int) $s['doc_type']);
                }

                return ['docs' => $q->orderBy('d.created_at', 'DESC')->orderBy('d.id', 'DESC')->get($lim)->getResultArray()];

            case 'stats':
                return ['stat' => $db->table('case_stats')->orderBy('year', 'DESC')->orderBy('month', 'DESC')->get(1)->getRowArray()];
        }

        return [];
    }

    /** Lengkapi setiap blok (dan sub-bloknya) dengan data. */
    public function withData(array $tree): array
    {
        foreach ($tree as &$b) {
            $b['data'] = $this->data($b);
        }

        return $tree;
    }
}
