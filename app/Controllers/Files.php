<?php

namespace App\Controllers;

/** Menyajikan dokumen PDF dari writable/uploads (tidak dapat diakses langsung). */
class Files extends BaseController
{
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

        $name = str_replace(['"', "\r", "\n"], '', $f['original_name'] ?: 'dokumen.pdf');

        $this->response->setCache(['public', 'max-age' => 86400]);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }
}
