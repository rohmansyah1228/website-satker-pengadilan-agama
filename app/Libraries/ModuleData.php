<?php

namespace App\Libraries;

use Config\SiteStructure;

/** Mengambil data untuk modul yang ditempelkan pada halaman publik. */
class ModuleData
{
    public function load(string $module, string $param, array $page): array
    {
        $db = db_connect();
        $rq = service('request');

        switch ($module) {
            case 'documents':
                $codes = array_filter(array_map('trim', explode(',', $param)));
                if (! $codes) {
                    return ['groups' => []];
                }
                $types  = $db->table('doc_types')->whereIn('code', $codes)->orderBy('sort')->get()->getResultArray();
                $groups = [];
                foreach ($types as $t) {
                    $docs = $db->table('documents d')->select('d.*, f.uuid, f.size, f.original_name')
                        ->join('files f', 'f.id = d.file_id', 'left')
                        ->where('d.doc_type_id', $t['id'])
                        ->orderBy('d.year', 'DESC')->orderBy('d.month', 'DESC')->orderBy('d.id', 'DESC')
                        ->get()->getResultArray();
                    $byYear = [];
                    foreach ($docs as $d) {
                        $byYear[$d['year']][] = $d;
                    }
                    $groups[] = ['type' => $t, 'years' => $byYear];
                }

                return ['groups' => $groups];

            case 'pegawai':
                $rows = $db->table('employees')->where('is_active', 1)->orderBy('sort')->orderBy('name')->get()->getResultArray();
                $by   = [];
                foreach ($rows as $r) {
                    $by[$r['grp']][] = $r;
                }

                return ['groups' => $by, 'labels' => config(SiteStructure::class)->employeeGroups];

            case 'yurisdiksi':
                return ['items' => $db->table('jurisdictions')->orderBy('sort')->orderBy('name')->get()->getResultArray()];

            case 'jadwal':
                $date = $rq->getGet('tanggal');
                $date = ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date)) ? $date : date('Y-m-d');
                $rows = service('sippSource')->hearings($date);

                return ['date' => $date, 'rows' => $rows];

            case 'ghaib':
                return ['rows' => $db->table('ghaib g')->select('g.*, f.uuid')->join('files f', 'f.id = g.file_id', 'left')
                    ->where('g.is_active', 1)->where('g.hearing_date >=', date('Y-m-d', strtotime('-7 days')))
                    ->orderBy('g.hearing_date')->get()->getResultArray()];

            case 'statistik':
                $year  = (int) ($rq->getGet('tahun') ?: date('Y'));
                $years = array_column($db->table('case_stats')->select('year')->distinct()->orderBy('year', 'DESC')->get()->getResultArray(), 'year');

                return ['year' => $year, 'years' => $years ?: [(int) date('Y')],
                    'rows' => $db->table('case_stats')->where('year', $year)->orderBy('month')->get()->getResultArray()];

            case 'lhkpn':
                return ['rows' => $db->table('lhkpn l')->select('l.*, f.uuid')->join('files f', 'f.id = l.file_id', 'left')
                    ->orderBy('l.year', 'DESC')->orderBy('l.name')->get()->getResultArray()];

            case 'akses_informasi':
                return ['rows' => $db->table('info_requests')->orderBy('year', 'DESC')->orderBy('month', 'DESC')->limit(24)->get()->getResultArray()];

            case 'pengaduan':
            case 'hukdis':
                return ['rows' => $db->table('complaints')->where('kind', $module === 'hukdis' ? 'hukdis' : 'pengaduan')
                    ->orderBy('year', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray()];

            case 'survei':
                $docs = $param ? $this->load('documents', $param, $page) : ['groups' => []];

                return $docs;

            case 'pengunjung':
                return service('visitorStats')->summary() + ['monthly' => service('visitorStats')->monthly(12)];
        }

        return [];
    }

    /**
     * Apakah data modul kosong? Bila halaman sudah punya isi sendiri, modul yang kosong
     * tidak ditampilkan (tidak muncul tulisan "… belum tersedia").
     * Modul berfilter/berbasis tanggal (jadwal, statistik, pengunjung, kontak, e-Court) selalu tampil.
     */
    public static function isEmpty(string $module, array $data): bool
    {
        switch ($module) {
            case 'documents':
                foreach ($data['groups'] ?? [] as $g) {
                    if (! empty($g['years'])) {
                        return false;
                    }
                }

                return true;

            case 'pegawai':
                return empty($data['groups']);

            case 'yurisdiksi':
                return empty($data['items']);

            case 'ghaib':
            case 'lhkpn':
            case 'akses_informasi':
            case 'pengaduan':
            case 'hukdis':
                return empty($data['rows']);
        }

        return false;
    }
}
