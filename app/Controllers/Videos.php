<?php

namespace App\Controllers;

/** Halaman publik Galeri Video. */
class Videos extends BaseController
{
    public function index()
    {
        $perPage = 12;
        $rows    = [];
        $total   = 0;
        $page    = max(1, (int) $this->request->getGet('page'));
        if ($this->db->tableExists('videos')) {
            $b     = $this->db->table('videos')->where('is_active', 1);
            $total = (clone $b)->countAllResults();
            $page  = min($page, max(1, (int) ceil($total / $perPage)));
            $rows  = $b->orderBy('published_at', 'DESC')->orderBy('sort')->orderBy('id', 'DESC')->get($perPage, ($page - 1) * $perPage)->getResultArray();
        }

        return $this->render('site/videos', [
            'title'  => 'Galeri Video',
            'videos' => $rows,
            'pager'  => service('pager')->makeLinks($page, $perPage, $total, 'default_full'),
            'metaDescription' => 'Galeri video kegiatan dan layanan ' . setting('site_name'),
        ]);
    }
}
