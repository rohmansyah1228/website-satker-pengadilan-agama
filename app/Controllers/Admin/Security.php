<?php

namespace App\Controllers\Admin;

use App\Libraries\SecurityScan;

/** Pemeriksaan Keamanan: keutuhan berkas & isi website (khusus Super Admin). */
class Security extends AdminController
{
    public function index()
    {
        $this->authorize([], true);

        return $this->view('security', ['title' => 'Pemeriksaan Keamanan', 'scan' => SecurityScan::last()]);
    }

    public function run()
    {
        $this->authorize([], true);
        try {
            $r = (new SecurityScan())->run();
        } catch (\Throwable $e) {
            log_message('error', 'Pemeriksaan keamanan: ' . $e->getMessage());

            return redirect()->to(admin_url('keamanan'))->with('error', 'Pemeriksaan gagal: ' . $e->getMessage());
        }
        $n = $r['summary']['danger'] + $r['summary']['warn'];
        $this->log('keamanan', 'Pemeriksaan keamanan: ' . ($n ? $n . ' temuan' : 'tidak ada temuan'));

        return redirect()->to(admin_url('keamanan'))->with($n ? 'error' : 'success',
            $n ? 'Pemeriksaan selesai: ada ' . $n . ' temuan yang perlu diperiksa.' : 'Pemeriksaan selesai: tidak ada temuan.');
    }

    public function restore()
    {
        $this->authorize([], true);
        $path = (string) $this->request->getPost('path');
        if ($path === '__vendor__') {
            [$ok, $bad] = (new SecurityScan())->restoreVendor();
            (new SecurityScan())->run();
            $this->log('keamanan', 'Pulihkan berkas pustaka (vendor): ' . $ok . ' berkas');

            return redirect()->to(admin_url('keamanan'))->with($bad ? 'error' : 'success', $ok . ' berkas pustaka dipulihkan.'
                . ($bad ? ' Gagal ' . count($bad) . ': ' . implode('; ', array_slice($bad, 0, 3)) : ''));
        }
        try {
            (new SecurityScan())->restore($path);
            (new SecurityScan())->run();
        } catch (\Throwable $e) {
            return redirect()->to(admin_url('keamanan'))->with('error', 'Gagal memulihkan ' . $path . ': ' . $e->getMessage());
        }
        $this->log('keamanan', 'Pulihkan berkas resmi: ' . $path);

        return redirect()->to(admin_url('keamanan'))->with('success', 'Berkas ' . $path . ' dipulihkan dari versi resmi (salinan lama disimpan di writable/backups/).');
    }

    public function quarantine()
    {
        $this->authorize([], true);
        $path = (string) $this->request->getPost('path');
        try {
            $dst = (new SecurityScan())->quarantine($path);
            (new SecurityScan())->run();
        } catch (\Throwable $e) {
            return redirect()->to(admin_url('keamanan'))->with('error', 'Gagal mengarantina ' . $path . ': ' . $e->getMessage());
        }
        $this->log('keamanan', 'Karantina berkas: ' . $path);

        return redirect()->to(admin_url('keamanan'))->with('success', 'Berkas ' . $path . ' dipindahkan ke ' . $dst . ' (tidak bisa dijalankan dari web).');
    }

    public function ignore()
    {
        $this->authorize([], true);
        $scan = new SecurityScan();
        if ($this->request->getPost('all')) {
            $scan->unignoreAll();
            $this->log('keamanan', 'Tampilkan lagi semua temuan yang ditandai aman');
        } else {
            $scan->ignore((string) $this->request->getPost('key'));
            $this->log('keamanan', 'Tandai aman: ' . mb_substr((string) $this->request->getPost('label'), 0, 200));
        }
        $scan->run();

        return redirect()->to(admin_url('keamanan'))->with('success', 'Daftar temuan diperbarui.');
    }
}
