<?php

namespace App\Controllers\Admin;

use App\Libraries\Backup;

/** Cadangan database (khusus Super Admin). */
class Backups extends AdminController
{
    public function index()
    {
        $this->authorize([], true);
        $b = new Backup();

        return $this->view('backups', ['title' => 'Cadangan', 'list' => $b->list(), 'usage' => $b->usage(),
            'keep' => Backup::keep(), 'schedule' => Backup::schedule(), 'lastDownload' => (int) setting('backup_last_download', '0')]);
    }

    public function create()
    {
        $this->authorize([], true);
        try {
            $name = (new Backup())->create('manual');
        } catch (\Throwable $e) {
            return redirect()->to(admin_url('cadangan'))->with('error', 'Cadangan gagal: ' . $e->getMessage());
        }
        $this->log('cadangan', 'Buat cadangan database ' . $name);

        return redirect()->to(admin_url('cadangan'))->with('success', 'Cadangan database dibuat: ' . $name . '. Sebaiknya unduh ke komputer kantor.');
    }

    public function download(string $name = '')
    {
        $this->authorize([], true);
        try {
            $path = (new Backup())->path($name);
        } catch (\Throwable $e) {
            return redirect()->to(admin_url('cadangan'))->with('error', $e->getMessage());
        }
        service('siteSettings')->set(['backup_last_download' => (string) time()]);
        $this->log('cadangan', 'Unduh cadangan database ' . $name);

        return $this->response->download($path, null)->setFileName($name)->setContentType('application/gzip');
    }

    public function delete()
    {
        $this->authorize([], true);
        $name = (string) $this->request->getPost('name');
        try {
            (new Backup())->delete($name);
        } catch (\Throwable $e) {
            return redirect()->to(admin_url('cadangan'))->with('error', $e->getMessage());
        }
        $this->log('cadangan', 'Hapus cadangan database ' . $name);

        return redirect()->to(admin_url('cadangan'))->with('success', 'Cadangan ' . $name . ' dihapus.');
    }

    public function settings()
    {
        $this->authorize([], true);
        $sched = (string) $this->request->getPost('backup_schedule');
        $keep  = (int) $this->request->getPost('backup_keep');
        if (! in_array($sched, ['off', 'daily', 'weekly'], true) || $keep < 1 || $keep > Backup::MAX_KEEP) {
            return redirect()->to(admin_url('cadangan'))->with('error', 'Pilihan tidak valid.');
        }
        service('siteSettings')->set(['backup_schedule' => $sched, 'backup_keep' => (string) $keep]);
        $n = (new Backup())->rotate();
        $this->log('cadangan', 'Pengaturan cadangan: ' . $sched . ', simpan ' . $keep);

        return redirect()->to(admin_url('cadangan'))->with('success', 'Pengaturan cadangan tersimpan.' . ($n ? ' ' . $n . ' cadangan lama dihapus sesuai batas baru.' : ''));
    }
}
