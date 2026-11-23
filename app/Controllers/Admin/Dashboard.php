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
            'pendingMigrations' => can([], true) ? $this->pendingMigrations() : [],
            'update' => can([], true) ? (new \App\Libraries\VersionCheck())->status() : null,
        ]);
    }

    /** Migrasi yang belum dijalankan (pembaruan kode yang membawa perubahan tabel). */
    private function pendingMigrations(): array
    {
        try {
            $runner = service('migrations');
            $runner->setNamespace('App');
            $done = array_column($runner->getHistory(), 'version');
            $out  = [];
            foreach ($runner->findMigrations() as $m) {
                if (! in_array($m->version, $done, true)) {
                    $out[] = $m->name;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Jalankan pembaruan database dari panel (untuk hosting tanpa terminal). Hanya Super Admin. */
    public function migrate()
    {
        $this->authorize([], true);
        $pending = $this->pendingMigrations();
        if (! $pending) {
            return redirect()->to(admin_url())->with('success', 'Database sudah versi terbaru.');
        }
        try {
            service('migrations')->setNamespace('App')->latest();
        } catch (\Throwable $e) {
            log_message('error', 'Migrasi dari panel: ' . $e->getMessage());

            return redirect()->to(admin_url())->with('error', 'Pembaruan database gagal: ' . $e->getMessage());
        }
        cache()->clean();
        $this->log('pembaruan', 'Menjalankan ' . count($pending) . ' pembaruan database');

        return redirect()->to(admin_url())->with('success', count($pending) . ' pembaruan database berhasil dijalankan.');
    }

    public function clearCache()
    {
        cache()->clean();
        $this->log('cache', 'Membersihkan cache');

        return redirect()->back()->with('success', 'Cache dibersihkan.');
    }
}
