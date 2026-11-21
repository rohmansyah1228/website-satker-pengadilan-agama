<?php

namespace App\Controllers;

use App\Libraries\AnnouncementFeed;

/** Halaman publik: pengumuman Badilag & Mahkamah Agung (otomatis, menaut ke sumber). */
class ExternalNotices extends BaseController
{
    public function index()
    {
        $feed    = new AnnouncementFeed();
        $sources = $feed->sources();
        $source  = (string) $this->request->getGet('sumber');
        if (! isset($sources[$source])) {
            $source = '';
        }
        $perPage = 20;
        $page    = max(1, (int) $this->request->getGet('page'));
        try {
            $items = $feed->items($perPage, $source, ($page - 1) * $perPage); // juga mengambil yang baru bila sudah lewat 1 jam
            $total = $feed->count($source);
            if ($page > 1 && ! $items && $total) {
                $page  = (int) ceil($total / $perPage);
                $items = $feed->items($perPage, $source, ($page - 1) * $perPage);
            }
        } catch (\Throwable) {
            $items = [];
            $total = 0;
        }

        return $this->render('site/external_notices', [
            'title'   => 'Pengumuman Badilag & Mahkamah Agung',
            'items'   => $items,
            'sources' => $sources,
            'source'  => $source,
            'status'  => $feed->status(),
            'total'   => $total,
            'pager'   => service('pager')->makeLinks($page, $perPage, $total, 'default_full'),
            'metaDescription' => 'Pengumuman terbaru Direktorat Jenderal Badan Peradilan Agama dan Mahkamah Agung RI.',
        ]);
    }
}
