<?php

namespace App\Controllers\Admin;

use App\Libraries\Compliance;

class Dashboard extends AdminController
{
    public function index()
    {
        $c    = new Compliance();
        $rows = $c->report();

        return $this->view('dashboard', [
            'title' => 'Dashboard Kepatuhan SINOVA', 'rows' => $rows, 'summary' => $c->summary($rows),
            'visitors' => service('visitorStats')->summary(),
            'recent'  => $this->db->table('activity_logs')->orderBy('id', 'DESC')->get(8)->getResultArray(),
        ]);
    }

    public function clearCache()
    {
        cache()->clean();
        $this->log('cache', 'Membersihkan cache');

        return redirect()->back()->with('success', 'Cache dibersihkan.');
    }
}
