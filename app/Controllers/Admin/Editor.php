<?php

namespace App\Controllers\Admin;

use RuntimeException;

/** Layanan untuk editor teks: unggah gambar/PDF dari editor dan daftar tautan internal. */
class Editor extends AdminController
{
    private function json(array $data, int $code = 200)
    {
        // token CSRF diperbarui tiap POST → kirim token baru agar formulir tetap bisa disimpan
        $data['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];

        return $this->response->setStatusCode($code)->setJSON($data);
    }

    public function image()
    {
        $f = $this->request->getFile('file');
        if (! $f || $f->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->json(['error' => 'Tidak ada berkas.'], 400);
        }
        try {
            $path = service('uploader')->storeImage($f, 1600);
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }
        $this->log('unggah', 'Gambar editor: ' . $path);

        return $this->json(['location' => media_url($path)]);
    }

    public function pdf()
    {
        $f = $this->request->getFile('file');
        if (! $f || $f->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->json(['error' => 'Tidak ada berkas.'], 400);
        }
        try {
            $id = service('uploader')->storePdf($f);
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }
        $name = preg_replace('/\.pdf$/i', '', (string) $f->getClientName());
        $this->log('unggah', 'PDF editor: ' . $name);

        return $this->json(['url' => file_url($id), 'name' => $name ?: 'Dokumen']);
    }

    /** Daftar halaman & berita untuk menu "Tautan Internal". */
    public function links()
    {
        $sections = config('SiteStructure')->sections;
        $out      = [];
        foreach ($this->db->table('pages')->where('status', 'published')->orderBy('section')->orderBy('id')->get()->getResultArray() as $p) {
            $out[] = ['group' => 'Halaman — ' . ($sections[$p['section']]['label'] ?? $p['section']), 'title' => $p['title'], 'url' => site_url("{$p['section']}/{$p['slug']}")];
        }
        foreach ($this->db->table('posts')->where('status', 'published')->orderBy('published_at', 'DESC')->limit(40)->get()->getResultArray() as $p) {
            $out[] = ['group' => 'Berita terbaru', 'title' => $p['title'], 'url' => site_url('berita/' . $p['slug'])];
        }
        foreach ($this->db->table('documents')->orderBy('id', 'DESC')->limit(60)->get()->getResultArray() as $d) {
            if (! empty($d['file_id']) && ($u = file_url((int) $d['file_id']))) {
                $out[] = ['group' => 'Arsip dokumen', 'title' => $d['title'] ?? ('Dokumen #' . $d['id']), 'url' => $u];
            }
        }

        return $this->response->setJSON($out);
    }
}
