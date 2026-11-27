<?php

namespace App\Controllers\Admin;

class Logs extends AdminController
{
    public function index()
    {
        $this->authorize([], true);
        $page  = max(1, (int) $this->request->getGet('page'));
        $per   = 50;
        $b     = $this->db->table('activity_logs');
        $total = (clone $b)->countAllResults(false);

        return $this->view('logs', [
            'title'  => 'Log Aktivitas',
            'rows'   => $b->orderBy('id', 'DESC')->get($per, ($page - 1) * $per)->getResultArray(),
            'pager'  => service('pager')->makeLinks($page, $per, $total, 'default_full'),
            'total'  => $total,
            'keep'   => (int) setting('log_retention', '24'),
            'logins' => $this->db->table('login_attempts')->where('success', 0)->orderBy('id', 'DESC')->get(20)->getResultArray(),
        ]);
    }
}
