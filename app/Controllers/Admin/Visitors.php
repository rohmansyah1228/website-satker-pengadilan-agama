<?php

namespace App\Controllers\Admin;

/** Statistik pengunjung rinci (termasuk asal provinsi & kota) — hanya di panel admin. */
class Visitors extends AdminController
{
    public function index()
    {
        $this->authorize([]);
        $vs = service('visitorStats');

        return $this->view('visitors', [
            'title'   => 'Statistik Pengunjung',
            'summary' => $vs->summary(),
            'monthly' => $vs->monthly(12),
            'details' => $vs->details(),
        ]);
    }
}
