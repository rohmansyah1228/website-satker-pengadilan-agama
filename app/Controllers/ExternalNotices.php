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
        try {
            $items = $feed->items(60, $source);
        } catch (\Throwable) {
            $items = [];
        }

        return $this->render('site/external_notices', [
            'title'   => 'Pengumuman Badilag & Mahkamah Agung',
            'items'   => $items,
            'sources' => $sources,
            'source'  => $source,
            'status'  => $feed->status(),
            'metaDescription' => 'Pengumuman terbaru Direktorat Jenderal Badan Peradilan Agama dan Mahkamah Agung RI.',
        ]);
    }
}
