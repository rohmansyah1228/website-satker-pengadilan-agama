<?php

namespace App\Controllers\Admin;

use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;

/** CRUD generik untuk modul di app/Config/AdminResources.php */
class Crud extends AdminController
{
    private array $res;
    private string $key;

    private function load(string $key): void
    {
        $all = config('AdminResources')->resources;
        if (! isset($all[$key])) {
            throw PageNotFoundException::forPageNotFound();
        }
        $this->key = $key;
        $this->res = $all[$key];
        $this->authorize($this->res['roles']);
    }

    /** Alamat kembali (mis. dari halaman Ubah Halaman) — hanya alamat panel admin sendiri. */
    private function returnUrl(): ?string
    {
        $u = $this->request->getGetPost('kembali');

        return is_string($u) && str_starts_with($u, admin_url('halaman/')) && ! preg_match('/[\s<>"\\\\]/', $u) ? $u : null;
    }

    /** Jenis dokumen yang boleh dikelola pengguna (berdasarkan bagian). */
    private function allowedDocTypeIds(): ?array
    {
        if (empty($this->res['scopeDocTypes']) || in_array($this->user['role'], ['superadmin', 'admin'], true)) {
            return null;
        }
        $sections = [];
        foreach (config('SiteStructure')->sections as $k => $sec) {
            if (in_array($this->user['role'], $sec['roles'], true)) {
                $sections[] = $k;
            }
        }

        return $sections ? array_map('intval', array_column($this->db->table('doc_types')->select('id')->whereIn('section', $sections)->get()->getResultArray(), 'id')) : [0];
    }

    public function index(string $key)
    {
        $this->load($key);
        $r  = $this->res;
        $b  = $this->db->table($r['table']);
        $q  = trim((string) $this->request->getGet('q'));
        $fv = [];

        if ($q !== '' && ! empty($r['search'])) {
            $b->groupStart();
            foreach ($r['search'] as $i => $col) {
                $i === 0 ? $b->like($col, $q) : $b->orLike($col, $q);
            }
            $b->groupEnd();
        }
        foreach (($r['filter'] ?? []) as $col => $opts) {
            $v = $this->request->getGet($col);
            if ($v !== null && $v !== '') {
                $b->where($col, $v);
                $fv[$col] = $v;
            }
        }
        if (($ids = $this->allowedDocTypeIds()) !== null) {
            $b->whereIn('doc_type_id', $ids);
        }
        $order = $r['order'] ?? ['id', 'DESC'];
        for ($i = 0; $i < count($order); $i += 2) {
            $b->orderBy($order[$i], $order[$i + 1]);
        }
        if (! empty($r['orderRaw']) && is_callable($r['orderRaw'])) {
            $b->orderBy(($r['orderRaw'])(), '', false); // mis. urutan jabatan LHKPN
            $b->orderBy('id', 'ASC');
        }
        $b->orderBy('id', 'DESC');

        $page  = max(1, (int) $this->request->getGet('page'));
        $per   = 25;
        $total = (clone $b)->countAllResults(false);
        $rows  = $b->get($per, ($page - 1) * $per)->getResultArray();

        return $this->view('crud/index', [
            'key' => $this->key, 'res' => $r, 'rows' => $rows, 'q' => $q, 'filterValues' => $fv,
            'lookups' => $this->lookups(), 'total' => $total,
            'pager' => service('pager')->makeLinks($page, $per, $total, 'default_full'),
            'activeCount' => isset($r['max']) ? $this->db->table($r['table'])->where('is_active', 1)->countAllResults() : null,
        ]);
    }

    public function create(string $key)
    {
        $this->load($key);
        $row = ['is_active' => 1];
        if ($this->res['table'] === 'posts') {
            $row += ['published_at' => date('Y-m-d H:i'), 'author' => $this->user['name'], 'status' => 'published'];
        }
        if (in_array($this->res['table'], ['documents', 'lhkpn', 'complaints', 'case_stats', 'info_requests'], true)) {
            $row['year'] = date('Y');
        }
        if ($this->res['table'] === 'case_stats' || $this->res['table'] === 'info_requests') {
            $lm           = strtotime('first day of last month');
            $row['year']  = date('Y', $lm);
            $row['month'] = date('n', $lm);
            if ($this->res['table'] === 'case_stats') {
                $prev                = $this->db->table('case_stats')->orderBy('year', 'DESC')->orderBy('month', 'DESC')->get(1)->getRowArray();
                $row['carried_over'] = $prev['remaining'] ?? 0;
            }
        }
        if ($this->res['table'] === 'documents' && ($t = $this->request->getGet('jenis'))) {
            $row['doc_type_id'] = (int) $t;
        }

        return $this->form($row, null);
    }

    public function edit(string $key, int $id)
    {
        $this->load($key);

        return $this->form($this->find($id), $id);
    }

    public function store(string $key)
    {
        $this->load($key);

        return $this->save(null);
    }

    public function update(string $key, int $id)
    {
        $this->load($key);
        $this->find($id);

        return $this->save($id);
    }

    public function delete(string $key, int $id)
    {
        $this->load($key);
        $row = $this->find($id);
        if (! empty($this->res['locked']) && ! empty($row[$this->res['locked']])) {
            return redirect()->back()->with('error', 'Data bawaan SK tidak dapat dihapus.');
        }
        if ($this->res['table'] === 'doc_types' && $this->db->table('documents')->where('doc_type_id', $id)->countAllResults()) {
            return redirect()->back()->with('error', 'Jenis dokumen masih memiliki dokumen. Hapus atau pindahkan dokumennya terlebih dahulu.');
        }
        foreach ($this->res['fields'] as $name => $f) {
            if ($f[1] === 'image') {
                service('uploader')->deleteImage($row[$name] ?? null);
            } elseif ($f[1] === 'pdf') {
                service('uploader')->deleteFile(isset($row[$name]) ? (int) $row[$name] : null);
            }
        }
        $this->db->table($this->res['table'])->where('id', $id)->delete();
        $this->log('hapus', $this->res['singular'] . ': ' . $this->label($row));
        $this->flushPublic();

        return redirect()->to($this->returnUrl() ?? admin_url('data/' . $this->key))->with('success', $this->res['singular'] . ' dihapus.');
    }

    // ------------------------------------------------------------------

    private function find(int $id): array
    {
        $row = $this->db->table($this->res['table'])->where('id', $id)->get()->getRowArray();
        if (! $row) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (($ids = $this->allowedDocTypeIds()) !== null && ! in_array((int) $row['doc_type_id'], $ids, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $row;
    }

    /** Halaman publik yang menampilkan data menu ini (untuk panel kanan form). */
    private function shownOn(array $row): array
    {
        $table = $this->res['table'];
        if ($table === 'posts') {
            return [['label' => 'Berita & Publikasi', 'url' => site_url('berita')]];
        }
        if (in_array($table, ['sliders', 'shortcuts', 'greetings'], true)) {
            return [['label' => 'Beranda', 'url' => site_url('/')]];
        }
        $modules = array_keys(array_filter(Pages::MODULE_RESOURCES, fn ($k) => $k === $this->key));
        if (! $modules) {
            return [];
        }
        $codes = $table === 'documents' ? array_column($this->db->table('doc_types')->select('id, code')->get()->getResultArray(), 'id', 'code') : [];
        $out   = [];
        foreach ($this->db->table('pages')->whereIn('module', $modules)->where('status', 'published')->get()->getResultArray() as $p) {
            $secRoles = $p['section'] === 'informasi' ? [] : (config('SiteStructure')->sections[$p['section']]['roles'] ?? ['__none__']);
            $item     = ['label' => $p['title'], 'url' => site_url("{$p['section']}/{$p['slug']}"), 'edit' => can($secRoles) ? admin_url('halaman/' . $p['id']) : null];
            if ($table === 'documents') {
                // dokumen: tampil di halaman yang memilih jenis dokumennya (disaring di browser saat jenis dipilih)
                $item['types'] = array_values(array_filter(array_map(static fn ($c) => $codes[$c] ?? null, explode(',', (string) $p['module_param']))));
                if (! $item['types']) {
                    continue;
                }
            }
            $out[] = $item;
        }

        return $out;
    }

    private function form(array $row, ?int $id, array $errors = [])
    {
        return $this->view('crud/form', [
            'shownOn' => $this->shownOn($row),
            'key' => $this->key, 'res' => $this->res, 'row' => $row, 'id' => $id,
            'lookups' => $this->lookups(), 'errors' => $errors, 'kembali' => $this->returnUrl(),
            'richtext' => (bool) array_filter($this->res['fields'], static fn ($f) => $f[1] === 'richtext'),
        ]);
    }

    private function lookups(): array
    {
        $out = [];
        foreach ($this->res['fields'] as $name => $f) {
            if (isset($f['source'])) {
                [$t, $k, $l] = $f['source'];
                $b           = $this->db->table($t)->select("{$k}, {$l}");
                if ($t === 'doc_types') {
                    $b->orderBy('sort');
                    if (($ids = $this->allowedDocTypeIds()) !== null) {
                        $b->whereIn('id', $ids);
                    }
                }
                $out[$name] = array_column($b->get()->getResultArray(), $l, $k);
            }
        }

        return $out;
    }

    private function label(array $row): string
    {
        foreach (['title', 'name', 'party_name', 'case_number', 'initials'] as $c) {
            if (! empty($row[$c])) {
                return (string) $row[$c];
            }
        }

        if (! empty($row['year']) && ! empty($row['month'])) {
            return bulan_id((int) $row['month']) . ' ' . $row['year'];
        }

        return '#' . ($row['id'] ?? '');
    }

    private function save(?int $id)
    {
        $r      = $this->res;
        $table  = $r['table'];
        $post   = $this->request->getPost();
        $old    = $id ? $this->find($id) : [];
        $locked = $id && ! empty($r['locked']) && ! empty($old[$r['locked']]);
        $rules  = [];
        foreach ($r['fields'] as $name => $f) {
            if (! in_array($f[1], ['image', 'pdf', 'checkbox'], true) && ! ($locked && ! empty($f['lockable']))) {
                $rules[$name] = ['label' => $f[0], 'rules' => $f[2]];
            }
        }
        if (! $this->validateData($post, $rules)) {
            return $this->form(array_merge($old, $post), $id, $this->validator->getErrors());
        }

        $data   = [];
        $errors = [];
        foreach ($r['fields'] as $name => $f) {
            $type = $f[1];
            if ($locked && ! empty($f['lockable'])) {
                continue;
            }
            try {
                switch ($type) {
                    case 'checkbox':
                        $data[$name] = $this->request->getPost($name) ? 1 : 0;
                        break;

                    case 'image':
                        $file = $this->request->getFile($name);
                        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                            $data[$name] = service('uploader')->storeImage($file, $f['width'] ?? 1600);
                            service('uploader')->deleteImage($old[$name] ?? null);
                        } elseif ($this->request->getPost($name . '__remove')) {
                            service('uploader')->deleteImage($old[$name] ?? null);
                            $data[$name] = null;
                        }
                        break;

                    case 'pdf':
                        $file = $this->request->getFile($name);
                        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                            $data[$name] = service('uploader')->storePdf($file);
                            service('uploader')->deleteFile(isset($old[$name]) ? (int) $old[$name] : null);
                        } elseif ($this->request->getPost($name . '__remove')) {
                            service('uploader')->deleteFile(isset($old[$name]) ? (int) $old[$name] : null);
                            $data[$name] = null;
                        }
                        break;

                    case 'richtext':
                        $data[$name] = service('purifier')->clean((string) ($post[$name] ?? ''));
                        break;

                    case 'datetime':
                        $raw = trim((string) ($post[$name] ?? ''));
                        if ($raw === '' && str_contains((string) ($f[2] ?? ''), 'permit_empty')) {
                            $data[$name] = null; // opsional & dikosongkan
                            break;
                        }
                        $data[$name] = date('Y-m-d H:i:s', strtotime($raw) ?: time());
                        break;

                    case 'number':
                        $v           = $post[$name] ?? null;
                        $data[$name] = ($v === '' || $v === null) ? ($name === 'remaining' ? null : 0) : str_replace(',', '.', (string) $v);
                        break;

                    case 'year':
                    case 'date':
                    case 'combo':
                        $pick        = trim((string) ($post[$name . '__pilih'] ?? ''));
                        $v           = ($pick !== '' && $pick !== '__lain') ? $pick : trim((string) ($post[$name] ?? ''));
                        $data[$name] = $v === '' ? null : mb_substr($v, 0, 150);
                        break;

                    case 'select':
                        $v           = $post[$name] ?? null;
                        $data[$name] = ($v === '' || $v === null) ? null : $v;
                        break;

                    case 'url':
                        // "youtu.be/…", "www.situs.go.id" → https://…  (jalur internal "/…" dan token "{…}" dibiarkan)
                        $v = trim((string) ($post[$name] ?? ''));
                        if ($v !== '' && ! preg_match('#^([a-z][a-z0-9+.-]*:|/|\{)#i', $v) && preg_match('#^[a-z0-9-]+(\.[a-z0-9-]+)+(/|$|\?)#i', $v)) {
                            $v = 'https://' . $v;
                        }
                        if (! url_scheme_ok($v)) {
                            throw new RuntimeException('Tautan harus diawali https://, http://, atau alamat internal "/…".');
                        }
                        $data[$name] = $v;
                        break;

                    default:
                        $data[$name] = trim((string) ($post[$name] ?? ''));
                }
            } catch (RuntimeException $e) {
                $errors[$name] = $e->getMessage();
            }
        }
        if ($errors) {
            return $this->form(array_merge($old, $post), $id, $errors);
        }

        // ---- aturan khusus per modul ----
        if ($table === 'documents' && empty($data['file_id']) && empty($old['file_id']) && empty($data['external_url'])) {
            return $this->form(array_merge($old, $post), $id, ['file_id' => 'Unggah berkas PDF atau isi tautan luar.']);
        }
        if ($table === 'documents' && ($ids = $this->allowedDocTypeIds()) !== null && ! in_array((int) $data['doc_type_id'], $ids, true)) {
            return $this->form(array_merge($old, $post), $id, ['doc_type_id' => 'Anda tidak berhak mengelola jenis dokumen ini.']);
        }
        if ($table === 'sliders' && empty($data['image']) && empty($old['image'])) {
            return $this->form(array_merge($old, $post), $id, ['image' => 'Gambar latar wajib diunggah.']);
        }
        if ($table === 'greetings') {
            if (empty($data['image']) && empty($old['image'])) {
                return $this->form(array_merge($old, $post), $id, ['image' => 'Banner wajib diunggah.']);
            }
            if (empty($data['published_at'])) {
                $data['published_at'] = date('Y-m-d'); // "terbaru" = hari ini bila dikosongkan
            }
        }
        if (isset($r['max']) && ! empty($data['is_active'])) {
            $active = $this->db->table($table)->where('is_active', 1);
            if ($id) {
                $active->where('id !=', $id);
            }
            if ($active->countAllResults() >= $r['max']) {
                return $this->form(array_merge($old, $post), $id, ['is_active' => "Maksimal {$r['max']} {$r['singular']} aktif sesuai SK (Bab III huruf C). Nonaktifkan salah satu terlebih dahulu."]);
            }
        }
        if ($table === 'case_stats' && ($data['remaining'] === null || $data['remaining'] === '')) {
            $data['remaining'] = (int) $data['carried_over'] + (int) $data['received'] - (int) $data['decided'];
        }
        if ($table === 'posts' && trim(strip_tags((string) $data['excerpt'])) === '') {
            $data['excerpt'] = plain_excerpt($data['content'], 200);
        }
        if (! empty($r['unique'])) {
            $u = $this->db->table($table);
            foreach ($r['unique'] as $c) {
                $u->where($c, $data[$c]);
            }
            if ($id) {
                $u->where('id !=', $id);
            }
            if ($u->countAllResults()) {
                return $this->form(array_merge($old, $post), $id, [$r['unique'][0] => 'Data untuk periode tersebut sudah ada. Ubah data yang sudah ada.']);
            }
        }
        if (isset($r['slug'])) {
            $data[$r['slug']['field']] = $this->uniqueSlug($table, $r['slug']['field'], $id && ! empty($old['slug']) ? $old['slug'] : url_title($data[$r['slug']['from']], '-', true), $id);
        }
        if ($table === 'doc_types' && isset($data['code'])) {
            $data['code'] = strtolower($data['code']);
            $dup          = $this->db->table('doc_types')->where('code', $data['code']);
            if ($id) {
                $dup->where('id !=', $id);
            }
            if ($dup->countAllResults()) {
                return $this->form(array_merge($old, $post), $id, ['code' => 'Kode sudah dipakai.']);
            }
        }

        $now = date('Y-m-d H:i:s');
        if (in_array($table, ['posts', 'documents', 'ghaib'], true) || ! empty($r['timestamps'])) {
            $data['updated_at'] = $now;
            if (! $id) {
                $data['created_at'] = $now;
            }
        }

        if ($id) {
            $this->db->table($table)->where('id', $id)->update($data);
            $this->log('ubah', $r['singular'] . ': ' . $this->label($data + $old));
        } else {
            $this->db->table($table)->insert($data);
            $id = (int) $this->db->insertID();
            $this->log('tambah', $r['singular'] . ': ' . $this->label($data));
        }
        $this->flushPublic();

        if ($this->request->getPost('_after') === 'view' && ! empty($r['view'])) {
            $saved = $this->db->table($table)->where('id', $id)->get()->getRowArray();

            return redirect()->to(site_url(str_replace(['{slug}', '{id}'], [$saved['slug'] ?? '', $id], $r['view'])))->with('success', $r['singular'] . ' tersimpan.');
        }
        $back     = $this->returnUrl();
        $redirect = $this->request->getPost('_after') === 'new'
            ? admin_url("data/{$this->key}/baru") . '?' . http_build_query(array_filter(['kembali' => $back, 'jenis' => $this->res['table'] === 'documents' ? ($data['doc_type_id'] ?? null) : null]))
            : ($back ?? admin_url("data/{$this->key}"));

        return redirect()->to($redirect)->with('success', $r['singular'] . ' tersimpan.');
    }

    private function uniqueSlug(string $table, string $col, string $base, ?int $id): string
    {
        $base = $base !== '' ? mb_substr($base, 0, 150) : 'publikasi';
        $slug = $base;
        $n    = 2;
        while (true) {
            $b = $this->db->table($table)->where($col, $slug);
            if ($id) {
                $b->where('id !=', $id);
            }
            if (! $b->countAllResults()) {
                return $slug;
            }
            $slug = $base . '-' . $n++;
        }
    }
}
