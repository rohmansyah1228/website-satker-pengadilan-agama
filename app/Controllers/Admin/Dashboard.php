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
            'lastUpdate' => can([], true) ? $this->undoableUpdate() : null,
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

    /** Pembaruan otomatis dari GitHub (tombol "Update Website"). Hanya Super Admin. */
    public function selfUpdate()
    {
        $this->authorize([], true);
        try {
            $r = (new \App\Libraries\Updater())->run();
        } catch (\Throwable $e) {
            log_message('error', 'Pembaruan otomatis: ' . $e->getMessage());
            cache()->delete('version_check');

            return redirect()->to(admin_url())->with('error', 'Update Website tidak dijalankan: ' . $e->getMessage());
        }
        $this->log('pembaruan', 'Pembaruan otomatis build ' . $r['from'] . ' → ' . $r['to'] . ' (' . count($r['written']) . ' berkas, cadangan ' . $r['backup'] . ')');
        $msg = 'Website berhasil diperbarui ke build ' . $r['to'] . ': ' . count($r['written']) . ' berkas diperbarui'
            . ($r['removed'] ? ', ' . count($r['removed']) . ' berkas lama dihapus' : '')
            . ($r['migrations'] ? ', ' . $r['migrations'] . ' pembaruan database dijalankan' : '')
            . '. Cadangan berkas lama: writable/backups/' . $r['backup'] . '.';
        if ($r['migrationError']) {
            return redirect()->to(admin_url())->with('error', $msg . ' Namun pembaruan database gagal: ' . $r['migrationError'] . ' — klik "Jalankan pembaruan" untuk mencoba lagi.');
        }

        return redirect()->to(admin_url())->with('success', $msg);
    }

    /** Batalkan pembaruan otomatis terakhir (kembalikan berkas dari cadangan). Hanya Super Admin. */
    public function rollbackUpdate()
    {
        $this->authorize([], true);
        try {
            $m = (new \App\Libraries\Updater())->rollbackLast();
        } catch (\Throwable $e) {
            return redirect()->to(admin_url())->with('error', $e->getMessage());
        }
        cache()->delete('version_check');
        $this->log('pembaruan', 'Membatalkan pembaruan otomatis build ' . $m['from'] . ' → ' . $m['to']);

        return redirect()->to(admin_url())->with('success', 'Pembaruan dibatalkan: berkas dikembalikan ke build ' . $m['from'] . '. Perubahan database (bila ada) tetap dan tidak memengaruhi data.');
    }

    /** Pembaruan otomatis terakhir yang masih bisa dibatalkan (versi terpasang = hasil pembaruan itu). */
    private function undoableUpdate(): ?array
    {
        $b = \App\Libraries\Updater::lastBackup();
        if (! $b) {
            return null;
        }
        $local = (new \App\Libraries\VersionCheck())->local();

        return (int) ($local['build'] ?? 0) === (int) ($b['manifest']['to'] ?? -1) ? ['from' => (int) $b['manifest']['from'], 'to' => (int) $b['manifest']['to'], 'at' => (string) $b['manifest']['at']] : null;
    }

    public function clearCache()
    {
        cache()->clean();
        $this->log('cache', 'Membersihkan cache');

        return redirect()->back()->with('success', 'Cache dibersihkan.');
    }
}
