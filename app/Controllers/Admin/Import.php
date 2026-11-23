<?php

namespace App\Controllers\Admin;

use App\Libraries\LegacyImporter;

/** Impor berita dari website lama (Joomla atau WordPress) — Super Admin. */
class Import extends AdminController
{
    public function index()
    {
        return $this->page('joomla');
    }

    /** Halaman impor khusus website lama berbasis WordPress. */
    public function wordpress()
    {
        return $this->page('wordpress');
    }

    private function page(string $platform)
    {
        $this->authorize([], true);
        $wp = $platform === 'wordpress';

        return $this->view('import', [
            'title'      => $wp ? 'Impor Berita dari WordPress' : 'Impor Berita dari Website Lama',
            'platform'   => $platform,
            'categories' => config('SiteStructure')->postCategories,
            'siteUrl'    => setting($wp ? 'import_wp_url' : 'import_site_url', ''),
            'imported'   => $this->ready() ? $this->db->table('posts')->like('source_ref', $wp ? 'wp:' : 'joomla:', 'after')->countAllResults() : 0,
            'ready'      => $this->ready(),
        ]);
    }

    /** Daftar kategori website WordPress lama. */
    public function wpCategories()
    {
        $this->authorize([], true);
        $site = (string) $this->request->getPost('site');
        try {
            $cats = $this->importer('wordpress')->wpCategories();
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
        service('siteSettings')->set(['import_wp_url' => trim($site)]);

        return $this->json(['categories' => $cats]);
    }

    private function platform(?string $force = null): string
    {
        $p = $force ?? (string) $this->request->getPost('platform');

        return in_array($p, ['joomla', 'wordpress'], true) ? $p : 'auto';
    }

    private function importer(?string $force = null): LegacyImporter
    {
        return new LegacyImporter((string) $this->request->getPost('site'), (bool) $this->request->getPost('insecure'), $this->platform($force));
    }

    /** Ambil daftar berita di satu kategori situs lama. */
    public function list()
    {
        $this->authorize([], true);
        if (! $this->ready()) {
            return $this->json(['error' => 'Jalankan pembaruan database di Dashboard terlebih dahulu.']);
        }
        @set_time_limit(300);
        $site = (string) $this->request->getPost('site');
        try {
            $imp   = $this->importer();
            $items = $imp->listArticles((string) $this->request->getPost('category_url'));
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
        service('siteSettings')->set([$imp->platform() === 'wordpress' ? 'import_wp_url' : 'import_site_url' => trim($site)]);
        $ref  = static fn ($i) => $i['ref'] ?? ('joomla:' . $i['id']);
        $refs = array_map($ref, $items);
        $have = $refs ? array_column($this->db->table('posts')->select('source_ref')->whereIn('source_ref', $refs)->get()->getResultArray(), 'source_ref') : [];
        $have = array_flip($have);

        return $this->json([
            'items'    => array_map(static fn ($i) => $i + ['done' => isset($have[$ref($i)])], $items),
            'total'    => count($items),
            'existing' => count($have),
        ]);
    }

    /** Impor beberapa berita (dipanggil bertahap oleh halaman). */
    public function run()
    {
        $this->authorize([], true);
        @set_time_limit(300);
        $cats = config('SiteStructure')->postCategories;
        $cat  = (string) $this->request->getPost('category');
        if (! isset($cats[$cat])) {
            return $this->json(['error' => 'Kategori tujuan tidak valid.']);
        }
        $urls = array_slice((array) $this->request->getPost('urls'), 0, 5);
        try {
            $imp = $this->importer();
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
        $out = [];
        $ok  = 0;
        foreach ($urls as $u) {
            try {
                $r = $imp->import((string) $u, [
                    'category'  => $cat,
                    'since'     => (int) $this->request->getPost('since') ?: null,
                    'images'    => (bool) $this->request->getPost('images'),
                    'status'    => (string) $this->request->getPost('status'),
                    'overwrite' => (bool) $this->request->getPost('overwrite'),
                ]);
            } catch (\Throwable $e) {
                $r = ['status' => 'error', 'message' => $e->getMessage()];
            }
            if ($r['status'] === 'ok') {
                $ok++;
                $r['view'] = site_url('berita/' . $r['slug']);
                $r['edit'] = admin_url('data/berita/' . $r['id']);
            }
            $out[] = ['url' => (string) $u] + $r;
        }
        if ($ok) {
            $this->log('import', $ok . ' berita dari ' . (string) $this->request->getPost('site'));
        }

        return $this->json(['results' => $out]);
    }

    /** Impor satu (atau beberapa) berita dari alamat beritanya langsung, tanpa lewat halaman kategori. */
    public function one()
    {
        $this->authorize([], true);
        @set_time_limit(300);
        $cats = config('SiteStructure')->postCategories;
        $cat  = (string) $this->request->getPost('category');
        if (! isset($cats[$cat])) {
            return $this->json(['error' => 'Kategori tujuan tidak valid.']);
        }
        $urls = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', (string) $this->request->getPost('urls'))), static fn ($u) => $u !== ''));
        $urls = array_slice($urls, 0, 20);
        if (! $urls) {
            return $this->json(['error' => 'Tempel minimal satu alamat berita.']);
        }
        try {
            $imp = $this->importer();
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
        $out = [];
        $ok  = 0;
        foreach ($urls as $u) {
            try {
                $r = $imp->import($u, [
                    'category'  => $cat,
                    'images'    => (bool) $this->request->getPost('images'),
                    'status'    => (string) $this->request->getPost('status'),
                    'overwrite' => (bool) $this->request->getPost('overwrite'),
                ]);
            } catch (\Throwable $e) {
                $r = ['status' => 'error', 'message' => $e->getMessage()];
            }
            if ($r['status'] === 'ok') {
                $ok++;
                $r['view'] = site_url('berita/' . $r['slug']);
                $r['edit'] = admin_url('data/berita/' . $r['id']);
            }
            $out[] = ['url' => $u] + $r;
        }
        if ($ok) {
            $this->log('import', $ok . ' berita (satuan) dari ' . (string) $this->request->getPost('site'));
        }

        return $this->json(['results' => $out]);
    }

    private function ready(): bool
    {
        return $this->db->fieldExists('source_ref', 'posts');
    }

    private function json(array $data)
    {
        return $this->response->setJSON($data + ['csrf' => csrf_hash()]);
    }
}
