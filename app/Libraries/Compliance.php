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
                $ev   = $this->evidence($p);
                $text = trim(strip_tags((string) $p['content']));
                if ($text === '' && ! $p['image'] && ! $ev['any']) {
                    return ['kosong', 'Konten halaman masih kosong.'];
                }
                if (str_contains((string) $p['content'] . $ev['text'], '[LENGKAPI]')) {
                    return ['kurang', 'Masih ada teks bertanda [LENGKAPI].'];
                }

                return ['ok', $ev['any'] ? $this->describe($ev) : ''];

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
                // Belum diisi di Pengaturan → cari di isi halaman kriteria
                $ev = $this->pageEvidence($page, $db);
                $urlHints = ['sipp_url' => ['sipp'], 'ecourt_url' => ['ecourt'], 'direktori_url' => ['putusan', 'direktori']];
                $found    = [];
                foreach ($missing as $k) {
                    if (isset($urlHints[$k])) {
                        foreach ($ev['urls'] as $u) {
                            foreach ($urlHints[$k] as $h) {
                                if (stripos($u, $h) !== false) {
                                    $found[$k] = true;
                                }
                            }
                        }
                    } elseif ($ev['data']) {
                        $found[$k] = true;
                    }
                }
                $missing = array_values(array_diff($missing, array_keys($found)));
                if (! $missing) {
                    return ['ok', $this->describe($ev)];
                }
                $labels = ['sipp_url' => 'tautan SIPP', 'ecourt_url' => 'tautan e-Court', 'direktori_url' => 'tautan Direktori Putusan', 'ikm' => 'nilai IKM/SKM', 'ipak' => 'nilai IPAK/SPAK', 'ipkp' => 'nilai IPKP/SPKP',
                    'address_street' => 'alamat jalan', 'address_village' => 'kelurahan', 'address_district' => 'kecamatan', 'address_province' => 'provinsi', 'postal_code' => 'kode pos', 'phone' => 'telepon', 'email' => 'email', 'building_photo' => 'foto gedung'];
                $names = array_map(static fn ($k) => $labels[$k] ?? $k, $missing);
                $state = count($missing) === count(explode(',', $arg)) && ! $found ? 'kosong' : 'kurang';

                return [$state, trim(($found ? $this->describe($ev) . ' ' : '') . 'Belum diisi (Pengaturan atau isi halaman): ' . implode(', ', $names) . '.')];

            case 'table':
                [$table, $kind] = array_pad(explode(':', $arg, 2), 2, null);
                $b              = $db->table($table);
                if ($kind) {
                    $b->where('kind', $kind);
                }
                $n = $b->countAllResults();
                $ev = $this->pageEvidence($page, $db);
                if ($table === 'case_stats' && ($n || $ev['data'])) {
                    $lm  = strtotime('first day of last month');
                    $has = $db->table('case_stats')->where(['year' => date('Y', $lm), 'month' => date('n', $lm)])->countAllResults()
                        || in_array(date('Y-n', $lm), $ev['data'] ? $ev['months'] : [], true);
                    $cnt = $this->countNote($n, $ev, 'data di menu admin');

                    return $has ? ['ok', $cnt] : ['kurang', trim($cnt . ' Statistik bulan ' . bulan_id((int) date('n', $lm)) . ' ' . date('Y', $lm) . ' belum ada (tulis bulannya, mis. "' . bulan_id((int) date('n', $lm)) . ' ' . date('Y', $lm) . '").')];
                }
                if ($table === 'hearings' && setting('sipp_sync') === '1') {
                    return ['ok', 'Diambil dari database SIPP.'];
                }
                if ($table === 'lhkpn' && ($n || $ev['data'])) {
                    $yrs = array_map('intval', array_column($db->table('lhkpn')->select('year')->distinct()->get()->getResultArray(), 'year'));
                    $yrs = array_unique(array_merge($yrs, $ev['data'] ? $ev['years'] : []));
                    $got = array_filter($yrs, static fn ($y) => $y >= (int) date('Y') - 2);
                    $cnt = $this->countNote($n, $ev, 'data di menu admin');

                    return count($got) >= 2 ? ['ok', $cnt] : ['kurang', trim($cnt . ' Tampilkan LHKPN minimal 2 tahun terakhir (tulis tahunnya, mis. "LHKPN Tahun ' . (date('Y') - 1) . '").')];
                }
                if ($n || $ev['data']) {
                    return ['ok', $this->countNote($n, $ev, 'data di menu admin')];
                }
                if ($table === 'complaints' && preg_match('/\bnihil\b/i', $ev['text'])) {
                    return ['ok', 'Terbaca: isi halaman menyatakan "Nihil".'];
                }
                if ($table === 'complaints') {
                    return ['kurang', 'Belum ada data. Jika memang nihil, tulis "Nihil" pada konten halaman.'];
                }

                return ['kosong', 'Belum ada data.'];

            case 'docs':
                return $this->checkDocs(explode(',', $arg), $db, $this->pageEvidence($page, $db));
        }

        return ['kosong', 'Pemeriksaan tidak dikenal.'];
    }

    private function checkDocs(array $codes, $db, ?array $ev = null): array
    {
        $ev ??= $this->evidence(null);
        $notes = [];
        $worst = 'ok';
        $rank  = ['ok' => 0, 'kurang' => 1, 'kosong' => 2];
        $total = 0;
        $hasEv = $ev['links'] > 0 || $ev['embeds'] > 0;

        foreach ($codes as $code) {
            $t = $db->table('doc_types')->where('code', trim($code))->get()->getRowArray();
            if (! $t) {
                continue;
            }
            $docs  = $db->table('documents')->where('doc_type_id', $t['id']);
            $count = (clone $docs)->countAllResults();
            $total += $count;
            $st    = 'ok';
            if ($t['period'] === 'bulanan') {
                $lm  = strtotime('first day of last month');
                $has = (clone $docs)->where(['year' => date('Y', $lm), 'month' => date('n', $lm)])->countAllResults()
                    || ($hasEv && in_array(date('Y-n', $lm), $ev['months'], true));
                if (! $count && ! $hasEv) {
                    [$st, $notes[]] = ['kosong', "{$t['name']}: belum ada dokumen."];
                } elseif (! $has) {
                    [$st, $notes[]] = ['kurang', "{$t['name']}: laporan " . bulan_id((int) date('n', $lm)) . ' ' . date('Y', $lm) . ' belum diunggah.'];
                }
            } elseif ($t['period'] === 'tahunan') {
                $years = array_column($docs->select('year')->distinct()->get()->getResultArray(), 'year');
                if ($hasEv) {
                    $years = array_merge($years, $ev['years']);
                }
                $need  = max(1, (int) $t['min_years']);
                $cur   = (int) date('Y');
                // dokumen laporan (LKjIP, Laporan Tahunan, LRA, CALK) terbit setelah tahun berjalan
                $lagged = in_array($t['code'], ['lkjip', 'laporan-tahunan'], true);
                $end    = $lagged ? $cur - 1 : $cur;
                $range  = range($end - $need + 1, $end);
                $miss   = array_diff($range, array_map('intval', $years));
                if (! $years && $hasEv) {
                    $st = 'kurang';
                    $notes['tahun'] ??= 'Tahun dokumen tidak terbaca — tulis tahunnya di judul tabel atau di baris, mis. "' . $t['name'] . ' Tahun ' . $end . '".';
                } elseif (! $years) {
                    [$st, $notes[]] = ['kosong', "{$t['name']}: belum ada dokumen."];
                } elseif ($miss) {
                    [$st, $notes[]] = ['kurang', "{$t['name']}: tahun " . implode(', ', $miss) . ' belum ada.'];
                }
            } elseif (! $count && ! $hasEv) {
                [$st, $notes[]] = ['kosong', "{$t['name']}: belum ada dokumen."];
            }
            if ($rank[$st] > $rank[$worst]) {
                $worst = $st;
            }
        }

        $notes = array_values(array_unique($notes));
        array_unshift($notes, $this->countNote($total, $ev));

        return [$worst, trim(implode(' ', array_filter($notes)))];
    }

    // ------------------------------------------------------------------ isi halaman

    /** Bukti dari isi halaman kriteria (blok teks/tabel/gambar/sematan). */
    private function pageEvidence(string $page, $db): array
    {
        if (! str_contains($page, '/')) {
            return $this->evidence(null);
        }
        [$sec, $slug] = explode('/', $page, 2);
        $p            = $db->table('pages')->where(['section' => $sec, 'slug' => $slug])->get()->getRowArray();

        return $this->evidence($p);
    }

    /**
     * Hitung isi halaman: jumlah tabel, baris, tautan dokumen (sel tombol tautan, tautan di teks,
     * gambar bertautan), sematan, serta tahun & bulan yang tertulis (judul tabel, sel, teks).
     */
    public function evidence(?array $page): array
    {
        $ev = ['tables' => 0, 'rows' => 0, 'links' => 0, 'images' => 0, 'embeds' => 0, 'years' => [], 'months' => [], 'text' => '', 'any' => false, 'data' => false, 'urls' => []];
        if (! $page) {
            return $ev;
        }
        $texts = [];
        foreach ((new PageBlocks())->decode($page) as $b) {
            switch ($b['type']) {
                case 'table':
                    $ev['tables']++;
                    $texts[] = (string) ($b['heading'] ?? '');
                    foreach ((array) ($b['cols'] ?? []) as $c) {
                        $texts[] = (string) ($c['label'] ?? '');
                    }
                    foreach ((array) ($b['rows'] ?? []) as $r) {
                        $rowText = [];
                        $rowLink = false;
                        foreach ((array) ($b['cols'] ?? []) as $ci => $c) {
                            $v = trim((string) ($r[$ci] ?? ''));
                            if ($v === '') {
                                continue;
                            }
                            if (($c['kind'] ?? '') === 'link') {
                                $ev['links']++;
                                $ev['urls'][] = $v;
                                $rowLink = true;
                            } else {
                                $rowText[] = $v;
                            }
                        }
                        if ($rowLink || $rowText) {
                            $ev['rows']++;
                        }
                        // tahun di judul tabel berlaku untuk barisnya; tahun di sel juga dibaca
                        $texts[] = implode(' ', $rowText);
                    }
                    break;
                case 'text':
                    $html    = (string) ($b['html'] ?? '');
                    $ev['links'] += preg_match_all('/<a\s[^>]*href=["\'](?!#)([^"\']+)/i', $html, $hm);
                    array_push($ev['urls'], ...$hm[1]);
                    $texts[] = html_entity_decode(strip_tags($html));
                    break;
                case 'image':
                    $ev['images']++;
                    if (! empty($b['link'])) {
                        $ev['links']++;
                        $ev['urls'][] = $b['link'];
                    }
                    $texts[] = ($b['caption'] ?? '') . ' ' . ($b['alt'] ?? '');
                    break;
                case 'embed':
                    $ev['embeds']++;
                    $ev['urls'][] = (string) ($b['url'] ?? '');
                    $texts[] = (string) ($b['heading'] ?? '');
                    break;
            }
        }
        $text       = trim(implode("\n", array_filter($texts)));
        $ev['text'] = $text;
        // 'data' = ada isi berupa tabel/tautan/gambar/sematan (teks pengantar saja tidak dihitung sebagai data)
        $ev['data'] = $ev['links'] > 0 || $ev['embeds'] > 0 || $ev['images'] > 0 || $ev['rows'] > 0;
        $ev['any']  = $ev['data'] || trim(preg_replace('/\s+/', ' ', $text)) !== '';

        $max = (int) date('Y') + 1;
        if (preg_match_all('/\b(19[89]\d|20\d\d)\b/', $text, $m)) {
            $ev['years'] = array_values(array_unique(array_filter(array_map('intval', $m[1]), static fn ($y) => $y <= $max)));
            sort($ev['years']);
        }
        // bulan + tahun, mis. "Oktober 2026", "Okt 2026", "10/2026", "2026-10"
        $names = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7, 'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12];
        if (preg_match_all('/\b(jan|feb|mar|apr|mei|may|jun|jul|agu|aug|sep|okt|oct|nov|des|dec)[a-z]*\.?\s+(20\d\d)\b/i', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $ev['months'][] = $x[2] . '-' . $names[strtolower($x[1])];
            }
        }
        if (preg_match_all('/\b(0?[1-9]|1[0-2])[\/\-.](20\d\d)\b/', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $ev['months'][] = $x[2] . '-' . (int) $x[1];
            }
        }
        if (preg_match_all('/\b(20\d\d)-(0[1-9]|1[0-2])\b/', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $ev['months'][] = $x[1] . '-' . (int) $x[2];
            }
        }
        $ev['months'] = array_values(array_unique($ev['months']));

        return $ev;
    }

    /** Ringkasan jumlah, mis. "Terbaca: 3 dokumen di Arsip · 2 tabel, 4 tautan dokumen (tahun 2025, 2026)." */
    private function countNote(int $archive, array $ev, string $unit = 'dokumen di Arsip Dokumen'): string
    {
        $parts = [];
        if ($archive) {
            $parts[] = $archive . ' ' . $unit;
        }
        if ($ev['data']) {
            $parts[] = $this->describe($ev, false);
        }

        return $parts ? 'Terbaca: ' . implode(' · ', $parts) . '.' : '';
    }

    private function describe(array $ev, bool $sentence = true): string
    {
        $bits = [];
        if ($ev['tables']) {
            $bits[] = $ev['tables'] . ' tabel';
        }
        if ($ev['links']) {
            $bits[] = $ev['links'] . ' tautan';
        } elseif ($ev['rows']) {
            $bits[] = $ev['rows'] . ' baris data';
        }
        if ($ev['images']) {
            $bits[] = $ev['images'] . ' gambar';
        }
        if ($ev['embeds']) {
            $bits[] = $ev['embeds'] . ' sematan';
        }
        $out = $bits ? 'isi halaman: ' . implode(', ', $bits) : 'isi halaman berupa teks';
        if ($ev['years']) {
            $out .= ' (tahun ' . implode(', ', $ev['years']) . ')';
        }

        return $sentence ? 'Terbaca: ' . $out . '.' : $out;
    }
}
