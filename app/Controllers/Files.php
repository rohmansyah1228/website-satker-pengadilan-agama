<?php

namespace App\Controllers;

/** Menyajikan dokumen PDF dari writable/uploads (tidak dapat diakses langsung). */
class Files extends BaseController
{
    /** /favicon.ico → logo satker yang diunggah (untuk peramban yang meminta favicon.ico langsung). */
    public function favicon()
    {
        $path = setting('logo');
        $url  = $path !== '' && is_file(FCPATH . ltrim($path, '/')) ? media_url($path) : base_url('assets/img/logo-default.svg');

        return redirect()->to($url)->setHeader('Cache-Control', 'public, max-age=86400');
    }

    public function show(string $uuid)
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $uuid)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $f = $this->db->table('files')->where('uuid', $uuid)->get()->getRowArray();
        $path = $f ? realpath(WRITEPATH . $f['path']) : false;
        if (! $f || ! $path || ! str_starts_with($path, realpath(WRITEPATH . 'uploads'))) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $this->db->table('files')->where('id', $f['id'])->set('downloads', 'downloads + 1', false)->update();

        $name = str_replace(['"', "\r", "\n", '\\'], '', $f['original_name'] ?: 'dokumen.pdf');
        $mime = (string) ($f['mime'] ?: 'application/pdf');
        // PDF dibuka di peramban; berkas Office selalu diunduh (tidak pernah ditampilkan sebagai halaman)
        $isPdf = $mime === 'application/pdf';
        if (! $isPdf && ! in_array($mime, array_column(\App\Libraries\Uploader::DOC_TYPES, 0), true)) {
            $mime = 'application/octet-stream';
        }

        $this->response->setCache(['public', 'max-age' => 86400]);

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', ($isPdf ? 'inline' : 'attachment') . '; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($name))
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }
}
