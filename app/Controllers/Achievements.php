<?php

namespace App\Controllers;

/** Halaman publik Galeri Prestasi (prestasi terbaru lebih dulu). */
class Achievements extends BaseController
{
    public function index()
    {
        $perPage = 12;
        $rows    = [];
        $total   = 0;
        $page    = max(1, (int) $this->request->getGet('page'));
        if ($this->db->tableExists('achievements')) {
            $b     = $this->db->table('achievements')->where('is_active', 1);
            $total = (clone $b)->countAllResults();
            $page  = min($page, max(1, (int) ceil($total / $perPage)));
            $rows  = $b->orderBy('awarded_at', 'DESC')->orderBy('sort')->orderBy('id', 'DESC')->get($perPage, ($page - 1) * $perPage)->getResultArray();
        }

        return $this->render('site/achievements', [
            'title'  => 'Galeri Prestasi',
            'items'  => $rows,
            'pager'  => service('pager')->makeLinks($page, $perPage, $total, 'default_full'),
            'metaDescription' => 'Galeri prestasi dan penghargaan ' . setting('site_name'),
        ]);
    }
}
