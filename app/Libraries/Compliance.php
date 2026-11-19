<?php

namespace App\Libraries;

use Config\SiteStructure;

/**
 * Dashboard Kepatuhan SINOVA: memeriksa setiap kriteria Lampiran II
 * dan menghasilkan status ok / kurang / kosong beserta URL bukti.
 */
class Compliance
{
    public function report(): array
    {
        $cfg   = config(SiteStructure::class);
        $level = setting('court_level', 'pa');
        $db    = db_connect();
        $rows  = [];

        foreach ($cfg->criteria as [$code, $group, $label, $page, $check, $lvl]) {
            if ($lvl !== 'all' && $lvl !== $level) {
                continue;
            }
            [$status, $note] = $this->evaluate($check, $page, $db);
            $pageRow = null;
            if ($page) {
                [$sec, $slug] = explode('/', $page);
                $pageRow      = $db->table('pages')->select('id, status')->where(['section' => $sec, 'slug' => $slug])->get()->getRowArray();
                if ($pageRow && $pageRow['status'] !== 'published') {
                    [$status, $note] = ['kosong', 'Halaman belum diterbitkan (draft).'];
                }
            }
            $rows[] = [
                'code' => $code, 'group' => $group, 'label' => $label, 'status' => $status, 'note' => $note,
                'url'  => $page ? site_url($page) : ($check === 'setting:sipp_url' ? setting('sipp_url') : ''),
                'page_id' => $pageRow['id'] ?? null, 'check' => $check,
            ];
        }

        return $rows;
    }

    public function summary(array $rows): array
    {
        $s = ['ok' => 0, 'kurang' => 0, 'kosong' => 0, 'total' => count($rows)];
        foreach ($rows as $r) {
            $s[$r['status']]++;
        }
        $s['percent'] = $s['total'] ? (int) round(($s['ok'] + $s['kurang'] * 0.5) / $s['total'] * 100) : 0;

        return $s;
    }

    private function evaluate(string $check, string $page, $db): array
    {
        [$type, $arg] = array_pad(explode(':', $check, 2), 2, '');

        switch ($type) {
            case 'content':
                [$sec, $slug] = explode('/', $page);
                $p            = $db->table('pages')->where(['section' => $sec, 'slug' => $slug])->get()->getRowArray();
                if (! $p) {
                    return ['kosong', 'Halaman tidak ditemukan.'];
                }
                $text = trim(strip_tags((string) $p['content']));
                if ($text === '' && ! $p['image']) {
                    return ['kosong', 'Konten halaman masih kosong.'];
                }
                if (str_contains((string) $p['content'], '[LENGKAPI]')) {
                    return ['kurang', 'Masih ada teks bertanda [LENGKAPI].'];
                }

                return ['ok', ''];

            case 'setting':
                $missing = [];
                foreach (explode(',', $arg) as $k) {
                    if (trim(setting($k)) === '') {
                        $missing[] = $k;
                    }
                }
                if (! $missing) {
                    return ['ok', ''];
                }

                return [count($missing) === count(explode(',', $arg)) ? 'kosong' : 'kurang', 'Pengaturan belum diisi: ' . implode(', ', $missing)];

            case 'table':
                [$table, $kind] = array_pad(explode(':', $arg, 2), 2, null);
                $b              = $db->table($table);
                if ($kind) {
                    $b->where('kind', $kind);
                }
                $n = $b->countAllResults();
                if ($table === 'case_stats' && $n) {
                    $lm  = strtotime('first day of last month');
                    $has = $db->table('case_stats')->where(['year' => date('Y', $lm), 'month' => date('n', $lm)])->countAllResults();

                    return $has ? ['ok', ''] : ['kurang', 'Statistik bulan ' . bulan_id((int) date('n', $lm)) . ' ' . date('Y', $lm) . ' belum diisi.'];
                }
                if ($table === 'hearings' && setting('sipp_sync') === '1') {
                    return ['ok', 'Diambil dari database SIPP.'];
                }
                if ($table === 'lhkpn' && $n) {
                    $years = $db->table('lhkpn')->select('year')->distinct()->where('year >=', (int) date('Y') - 2)->countAllResults();

                    return $years >= 2 ? ['ok', ''] : ['kurang', 'Tampilkan LHKPN minimal 2 tahun terakhir.'];
                }
                if ($n) {
                    return ['ok', ''];
                }
                if ($table === 'complaints') {
                    return ['kurang', 'Belum ada data. Jika memang nihil, tulis "Nihil" pada konten halaman.'];
                }

                return ['kosong', 'Belum ada data.'];

            case 'docs':
                return $this->checkDocs(explode(',', $arg), $db);
        }

        return ['kosong', 'Pemeriksaan tidak dikenal.'];
    }

    private function checkDocs(array $codes, $db): array
    {
        $notes = [];
        $worst = 'ok';
        $rank  = ['ok' => 0, 'kurang' => 1, 'kosong' => 2];

        foreach ($codes as $code) {
            $t = $db->table('doc_types')->where('code', trim($code))->get()->getRowArray();
            if (! $t) {
                continue;
            }
            $docs = $db->table('documents')->where('doc_type_id', $t['id']);
            $st   = 'ok';
            if ($t['period'] === 'bulanan') {
                $lm  = strtotime('first day of last month');
                $has = (clone $docs)->where(['year' => date('Y', $lm), 'month' => date('n', $lm)])->countAllResults();
                if (! $docs->countAllResults()) {
                    [$st, $notes[]] = ['kosong', "{$t['name']}: belum ada dokumen."];
                } elseif (! $has) {
                    [$st, $notes[]] = ['kurang', "{$t['name']}: laporan " . bulan_id((int) date('n', $lm)) . ' ' . date('Y', $lm) . ' belum diunggah.'];
                }
            } elseif ($t['period'] === 'tahunan') {
                $years = array_column($docs->select('year')->distinct()->get()->getResultArray(), 'year');
                $need  = max(1, (int) $t['min_years']);
                $cur   = (int) date('Y');
                // dokumen laporan (LKjIP, Laporan Tahunan, LRA, CALK) terbit setelah tahun berjalan
                $lagged = in_array($t['code'], ['lkjip', 'laporan-tahunan'], true);
                $end    = $lagged ? $cur - 1 : $cur;
                $range  = range($end - $need + 1, $end);
                $miss   = array_diff($range, array_map('intval', $years));
                if (! $years) {
                    [$st, $notes[]] = ['kosong', "{$t['name']}: belum ada dokumen."];
                } elseif ($miss) {
                    [$st, $notes[]] = ['kurang', "{$t['name']}: tahun " . implode(', ', $miss) . ' belum ada.'];
                }
            } else {
                if (! $docs->countAllResults()) {
                    [$st, $notes[]] = ['kosong', "{$t['name']}: belum ada dokumen."];
                }
            }
            if ($rank[$st] > $rank[$worst]) {
                $worst = $st;
            }
        }

        return [$worst, implode(' ', $notes)];
    }
}
