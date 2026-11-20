<?php

namespace App\Controllers\Admin;

use App\Libraries\PageBlocks;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;

/** Halaman (konten statis + modul). Halaman wajib SK: slug, bagian & modul dikunci. */
class Pages extends AdminController
{
    private function sections(): array
    {
        $out = [];
        foreach (config('SiteStructure')->sections as $k => $s) {
            if (can($s['roles'])) {
                $out[$k] = $s['label'];
            }
        }
        if (can([])) {
            $out['informasi'] = 'Informasi Lainnya';
        }

        return $out;
    }

    private function find(int $id): array
    {
        $p = $this->db->table('pages')->where('id', $id)->get()->getRowArray();
        if (! $p || ! array_key_exists($p['section'], $this->sections())) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $p;
    }

    public function index()
    {
        $sections = $this->sections();
        if (! $sections) {
            $this->authorize(['__none__']);
        }
        $rows = $this->db->table('pages')->whereIn('section', array_keys($sections))->orderBy('section')->orderBy('id')->get()->getResultArray();
        $by   = [];
        foreach ($rows as $r) {
            $by[$r['section']][] = $r;
        }
        $criteria = [];
        foreach (config('SiteStructure')->criteria as $c) {
            if ($c[3]) {
                $criteria[$c[3]][] = $c[0];
            }
        }

        return $this->view('pages/index', ['title' => 'Halaman', 'sections' => $sections, 'grouped' => $by, 'criteria' => $criteria]);
    }

    public function create()
    {
        if (! $this->sections()) {
            $this->authorize(['__none__']);
        }

        return $this->form(['section' => array_key_first($this->sections()), 'status' => 'published', 'module' => '', 'is_required' => 0], null);
    }

    public function edit(int $id)
    {
        return $this->form($this->find($id), $id);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    public function delete(int $id)
    {
        $p = $this->find($id);
        if ($p['is_required']) {
            return redirect()->back()->with('error', 'Halaman wajib SK tidak dapat dihapus.');
        }
        $this->db->table('menus')->where(['type' => 'page', 'page_id' => $id])->delete();
        service('uploader')->deleteImage($p['image']);
        foreach ((new PageBlocks())->images((new PageBlocks())->decode($p)) as $img) {
            service('uploader')->deleteImage($img);
        }
        $this->db->table('pages')->where('id', $id)->delete();
        $this->log('hapus', 'Halaman: ' . $p['title']);
        $this->flushPublic();

        return redirect()->to(admin_url('halaman'))->with('success', 'Halaman dihapus beserta tautan menunya.');
    }

    /** Modul halaman → data di menu samping (app/Config/AdminResources.php) */
    public const MODULE_RESOURCES = [
        'documents' => 'dokumen', 'survei' => 'dokumen', 'pegawai' => 'pegawai', 'yurisdiksi' => 'wilayah',
        'jadwal' => 'jadwal-sidang', 'ghaib' => 'panggilan-ghaib', 'statistik' => 'statistik-perkara', 'lhkpn' => 'lhkpn',
        'akses_informasi' => 'akses-informasi', 'pengaduan' => 'pengaduan', 'hukdis' => 'pengaduan',
    ];

    /**
     * Data modul yang tampil di halaman ini — sama persis dengan data di menu samping,
     * sehingga menambah/mengubah dari Ubah Halaman otomatis tampil di menu tersebut.
     */
    private function moduleData(array $page, int $id): ?array
    {
        $module = (string) ($page['module'] ?? '');
        if ($module === 'kontak' || $module === 'ecourt') {
            return can([]) ? ['settings' => true, 'title' => $module === 'kontak' ? 'Alamat & Kontak' : 'Tautan e-Court'] : null;
        }
        $key = self::MODULE_RESOURCES[$module] ?? null;
        $res = $key ? (config('AdminResources')->resources[$key] ?? null) : null;
        if (! $res || ! can($res['roles'])) {
            return null;
        }
        $back = admin_url('halaman/' . $id) . '#data-modul';
        $b    = $this->db->table($res['table']);
        $adds = [];
        if ($res['table'] === 'documents') {
            $codes = array_filter(explode(',', (string) $page['module_param']));
            $types = $codes ? $this->db->table('doc_types')->whereIn('code', $codes)->orderBy('sort')->get()->getResultArray() : [];
            $b->whereIn('doc_type_id', $types ? array_column($types, 'id') : [0]);
            foreach ($types as $t) {
                $adds[] = ['label' => $t['name'], 'url' => admin_url('data/dokumen/baru') . '?' . http_build_query(['jenis' => $t['id'], 'kembali' => $back])];
            }
        } else {
            $adds[] = ['label' => $res['singular'], 'url' => admin_url("data/{$key}/baru") . '?' . http_build_query(['kembali' => $back])];
        }
        $order = $res['order'] ?? ['id', 'DESC'];
        for ($i = 0; $i < count($order); $i += 2) {
            $b->orderBy($order[$i], $order[$i + 1]);
        }
        $total = (clone $b)->countAllResults(false);
        $rows  = $b->orderBy('id', 'DESC')->get(100)->getResultArray();

        $lookups = [];
        foreach ($res['fields'] as $name => $f) {
            if (isset($f['source'])) {
                [$t, $k, $l]    = $f['source'];
                $lookups[$name] = array_column($this->db->table($t)->select("{$k}, {$l}")->get()->getResultArray(), $l, $k);
            }
        }

        return ['key' => $key, 'res' => $res, 'rows' => $rows, 'total' => $total, 'lookups' => $lookups, 'back' => $back, 'adds' => $adds,
            'manage' => admin_url("data/{$key}")];
    }

    private function form(array $page, ?int $id, array $errors = [])
    {
        $crit = [];
        if ($id) {
            foreach (config('SiteStructure')->criteria as $c) {
                if ($c[3] === $page['section'] . '/' . $page['slug']) {
                    $crit[] = $c;
                }
            }
        }

        $pb     = new PageBlocks();
        $blocks = isset($page['blocks_json']) ? (json_decode((string) $page['blocks_json'], true) ?: []) : $pb->decode($page);

        return $this->view('pages/form', [
            'blocks' => $blocks, 'moduleData' => $id ? $this->moduleData($page, $id) : null, 'blockTypes' => PageBlocks::TYPES, 'sizes' => PageBlocks::SIZES, 'aligns' => PageBlocks::ALIGNS,
            'pageBuilder' => true,
            'title' => $id ? 'Ubah Halaman' : 'Halaman Baru', 'page' => $page, 'id' => $id, 'errors' => $errors,
            'sections' => $this->sections(), 'modules' => config('SiteStructure')->modules, 'criteria' => $crit,
            'docTypes' => $this->db->table('doc_types')->orderBy('sort')->get()->getResultArray(),
            'richtext' => true,
        ]);
    }

    private function save(?int $id)
    {
        $old      = $id ? $this->find($id) : ['is_required' => 0, 'image' => null];
        $required = (bool) $old['is_required'];
        $post     = $this->request->getPost();

        $rules = [
            'title'            => ['label' => 'Judul', 'rules' => 'required|max_length[255]'],
            'status'           => ['label' => 'Status', 'rules' => 'required|in_list[published,draft]'],
            'meta_description' => ['label' => 'Deskripsi', 'rules' => 'permit_empty|max_length[255]'],
        ];
        if (! $required) {
            $rules['section'] = ['label' => 'Bagian', 'rules' => 'required|in_list[' . implode(',', array_keys($this->sections())) . ']'];
            $rules['slug']    = ['label' => 'Alamat (slug)', 'rules' => 'permit_empty|alpha_dash|max_length[120]'];
            $rules['module']  = ['label' => 'Modul', 'rules' => 'permit_empty|in_list[' . implode(',', array_filter(array_keys(config('SiteStructure')->modules))) . ']'];
        }
        if (! $this->validateData($post, $rules)) {
            return $this->form(array_merge($old, $post), $id, $this->validator->getErrors());
        }

        $data = [
            'title'            => trim($post['title']),
            'status'           => $required ? 'published' : $post['status'],
            'meta_description' => trim((string) ($post['meta_description'] ?? '')),
            'updated_by'       => $this->user['id'],
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
        if (! $required) {
            $data['section']      = $post['section'];
            $data['slug']         = strtolower($post['slug'] ?: url_title($post['title'], '-', true)) ?: 'halaman';
            $data['module']       = $post['module'] ?? '';
            $data['module_param'] = $data['module'] === 'documents' || $data['module'] === 'survei' ? implode(',', (array) ($post['doc_codes'] ?? []))
                : ($data['module'] === 'pegawai' && ($post['employee_view'] ?? '') === 'tabel' ? 'tabel' : '');

            $dup = $this->db->table('pages')->where(['section' => $data['section'], 'slug' => $data['slug']]);
            if ($id) {
                $dup->where('id !=', $id);
            }
            if ($dup->countAllResults() || in_array($data['slug'], ['baru'], true)) {
                return $this->form(array_merge($old, $post), $id, ['slug' => 'Alamat halaman sudah dipakai pada bagian ini.']);
            }
        } elseif ($old['module'] === 'pegawai') {
            $data['module_param'] = ($post['employee_view'] ?? '') === 'tabel' ? 'tabel' : '';
        } elseif ($old['module'] === 'documents' && isset($post['doc_codes'])) {
            // halaman wajib: jenis dokumen bawaan tetap, boleh ditambah jenis lain
            $base                 = array_filter(explode(',', (string) $old['module_param']));
            $data['module_param'] = implode(',', array_unique(array_merge($base, (array) $post['doc_codes'])));
        }

        // ---- isi halaman (blok) ----
        $pb       = new PageBlocks();
        $raw      = (string) ($post['blocks_json'] ?? '[]');
        $decoded  = strlen($raw) <= 4_000_000 ? json_decode($raw, true) : null;
        if (! is_array($decoded)) {
            return $this->form(array_merge($old, $post), $id, ['blocks' => 'Isi halaman tidak terbaca. Muat ulang halaman lalu coba lagi.']);
        }
        $oldImages = $id ? $pb->images($pb->decode($old)) : [];
        $newFiles  = [];
        [$blocks, $blockErrors] = $pb->sanitize($decoded, $oldImages, function (string $key) use (&$newFiles): ?string {
            $f = $this->request->getFile('block_file.' . $key);
            if (! $f || $f->getError() === UPLOAD_ERR_NO_FILE) {
                return null;
            }

            return $newFiles[] = service('uploader')->storeImage($f, 1600);
        });
        $dropNew = static function () use (&$newFiles): void {
            foreach ($newFiles as $f) {
                service('uploader')->deleteImage($f);
            }
        };
        if ($blockErrors) {
            $dropNew();

            return $this->form(array_merge($old, $post), $id, $blockErrors);
        }
        $data['blocks']  = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['content'] = $pb->searchText($blocks);

        $file = $this->request->getFile('image');
        try {
            if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                $data['image'] = service('uploader')->storeImage($file, 1600);
                service('uploader')->deleteImage($old['image']);
            } elseif (! empty($post['image__remove'])) {
                service('uploader')->deleteImage($old['image']);
                $data['image'] = null;
            }
        } catch (RuntimeException $e) {
            $dropNew();

            return $this->form(array_merge($old, $post), $id, ['image' => $e->getMessage()]);
        }
        foreach (array_diff($oldImages, $pb->images($blocks)) as $unused) {
            service('uploader')->deleteImage($unused);
        }

        if ($id) {
            // URL berubah → simpan redirect 301 agar tautan lama (mis. yang sudah diinput ke SINOVA) tetap berfungsi
            if (! $required && ($old['section'] !== $data['section'] || $old['slug'] !== $data['slug'])) {
                $oldPath = "/{$old['section']}/{$old['slug']}";
                $newPath = "/{$data['section']}/{$data['slug']}";
                $this->db->table('redirects')->where('old_path', $newPath)->delete();
                $this->db->table('redirects')->where('new_path', $oldPath)->update(['new_path' => $newPath]);
                $this->db->table('redirects')->where('old_path', $oldPath)->delete();
                $this->db->table('redirects')->insert(['old_path' => $oldPath, 'new_path' => $newPath, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $this->db->table('pages')->where('id', $id)->update($data);
            $this->log('ubah', 'Halaman: ' . $data['title']);
        } else {
            $data['created_at']  = $data['updated_at'];
            $data['is_required'] = 0;
            $this->db->table('pages')->insert($data);
            $id = (int) $this->db->insertID();
            $this->log('tambah', 'Halaman: ' . $data['title']);
            if (! empty($post['add_to_menu'])) {
                $parent = $this->db->table('menus')->where(['type' => 'section', 'section' => $data['section']])->get()->getRowArray();
                $sort   = (int) ($this->db->table('menus')->selectMax('sort')->where('parent_id', $parent['id'] ?? null)->get()->getRow()->sort ?? 0);
                $this->db->table('menus')->insert(['parent_id' => $parent['id'] ?? null, 'label' => $data['title'], 'type' => 'page', 'page_id' => $id, 'sort' => $sort + 1, 'is_active' => 1]);
            }
        }
        // Nilai survei (IKM/IPKP/IPAK) diisi langsung dari halaman Survei → disimpan ke Pengaturan
        $survey = $post['survey'] ?? null;
        if (is_array($survey) && ($data['module'] ?? $old['module'] ?? '') === 'survei' && can([])) {
            $vals = [];
            foreach (['ikm' => 40, 'ipkp' => 40, 'ipak' => 40, 'survey_period' => 80] as $k => $max) {
                if (array_key_exists($k, $survey)) {
                    $vals[$k] = mb_substr(trim((string) $survey[$k]), 0, $max);
                }
            }
            if ($vals && array_diff_assoc($vals, array_map(static fn ($k) => setting($k), array_combine(array_keys($vals), array_keys($vals))))) {
                service('siteSettings')->set($vals);
                $this->log('ubah', 'Nilai survei (dari halaman ' . $data['title'] . ')');
            }
        }
        $this->flushPublic();

        if ($this->request->getPost('_after') === 'view') {
            $p = $this->db->table('pages')->where('id', $id)->get()->getRowArray();

            return redirect()->to(site_url("{$p['section']}/{$p['slug']}"));
        }

        return redirect()->to(admin_url('halaman/' . $id))->with('success', 'Halaman tersimpan.');
    }
}
