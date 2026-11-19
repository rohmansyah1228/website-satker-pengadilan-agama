<?php

namespace App\Controllers;

/** Pencarian internal (Lampiran II huruf B – Navigasi & Pencarian). */
class Search extends BaseController
{
    public function index(): string
    {
        $q       = trim((string) $this->request->getGet('q'));
        $q       = mb_substr(preg_replace('/\s+/', ' ', $q), 0, 100);
        $results = [];

        if (mb_strlen($q) >= 3) {
            $db = $this->db;
            foreach ($db->table('pages')->where('status', 'published')->groupStart()->like('title', $q)->orLike('content', $q)->groupEnd()
                ->get(30)->getResultArray() as $p) {
                $results[] = ['type' => 'Halaman', 'title' => $p['title'], 'url' => site_url("{$p['section']}/{$p['slug']}"), 'snippet' => plain_excerpt($p['content'], 180)];
            }
            foreach ($db->table('posts')->where('status', 'published')->where('published_at <=', date('Y-m-d H:i:s'))
                ->groupStart()->like('title', $q)->orLike('content', $q)->orLike('excerpt', $q)->groupEnd()
                ->orderBy('published_at', 'DESC')->get(30)->getResultArray() as $p) {
                $results[] = ['type' => 'Berita', 'title' => $p['title'], 'url' => site_url('berita/' . $p['slug']), 'snippet' => tanggal_id($p['published_at']) . ' — ' . ($p['excerpt'] ?: plain_excerpt($p['content'], 150))];
            }
            // Dokumen: cari judul/jenis → arahkan ke halaman yang menampilkan jenis dokumen tsb.
            $pagesByDoc = [];
            foreach ($db->table('pages')->where(['module' => 'documents', 'status' => 'published'])->get()->getResultArray() as $p) {
                foreach (explode(',', (string) $p['module_param']) as $c) {
                    $pagesByDoc[trim($c)] ??= site_url("{$p['section']}/{$p['slug']}");
                }
            }
            foreach ($db->table('documents d')->select('d.title, d.year, t.code, t.name')->join('doc_types t', 't.id = d.doc_type_id')
                ->groupStart()->like('d.title', $q)->orLike('t.name', $q)->orLike('t.code', $q)->groupEnd()
                ->orderBy('d.year', 'DESC')->get(30)->getResultArray() as $d) {
                if (isset($pagesByDoc[$d['code']])) {
                    $results[] = ['type' => 'Dokumen', 'title' => $d['title'], 'url' => $pagesByDoc[$d['code']], 'snippet' => $d['name'] . ' — Tahun ' . $d['year']];
                }
            }
        }

        return $this->render('site/search', ['title' => 'Pencarian', 'q' => $q, 'results' => $results]);
    }
}
