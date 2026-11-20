<?php

namespace App\Controllers\Admin;

use App\Libraries\MenuTree;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Menu Builder: label, urutan, sub-menu, tautan. Menu wajib SK hanya dapat diganti label & urutannya. */
class Menus extends AdminController
{
    public function initController(...$args)
    {
        parent::initController(...$args);
        $this->authorize([]);
    }

    private function find(int $id): array
    {
        $m = $this->db->table('menus')->where('id', $id)->get()->getRowArray();
        if (! $m) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $m;
    }

    public function index()
    {
        return $this->view('menus/index', ['title' => 'Menu Navigasi', 'tree' => (new MenuTree())->tree(false)]);
    }

    public function create()
    {
        return $this->form(['type' => 'page', 'is_active' => 1, 'parent_id' => $this->request->getGet('parent')], null);
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

    /** Pindah naik/turun di antara saudara (alternatif keyboard & pembaca layar untuk drag-and-drop). */
    public function move(int $id)
    {
        $m        = $this->find($id);
        $dir      = $this->request->getPost('dir') === 'up' ? 'up' : 'down';
        $siblings = $this->siblings($m['parent_id']);
        $ids      = array_column($siblings, 'id');
        $pos      = array_search($m['id'], $ids, false);
        $swap     = $dir === 'up' ? $pos - 1 : $pos + 1;
        if ($pos !== false && isset($ids[$swap])) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            foreach ($ids as $i => $sid) {
                $this->db->table('menus')->where('id', $sid)->update(['sort' => $i + 1]);
            }
            $this->flushPublic();
            $this->log('ubah', 'Urutan menu: ' . $m['label']);
        }

        return redirect()->to(admin_url('menu') . '#menu-' . $id)->with('moved', $id)->with('movedDir', $dir);
    }

    /**
     * Simpan susunan hasil seret-lepas (drag & drop): urutan, induk, dan kedalaman.
     * Body JSON: {"items":[{"id":1,"parent":null,"sort":1}, ...]} berisi SEMUA menu.
     */
    public function arrange()
    {
        $fail = fn (string $msg) => $this->response->setStatusCode(422)->setJSON(['error' => $msg, 'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()]]);

        $items = $this->request->getJSON(true)['items'] ?? null;
        if (! is_array($items)) {
            return $fail('Data susunan tidak valid.');
        }
        $rows = array_column($this->db->table('menus')->get()->getResultArray(), null, 'id');
        $new  = [];
        foreach ($items as $it) {
            $id     = (int) ($it['id'] ?? 0);
            $parent = isset($it['parent']) && $it['parent'] !== '' && $it['parent'] !== null ? (int) $it['parent'] : null;
            if (! isset($rows[$id]) || isset($new[$id]) || ($parent !== null && ! isset($rows[$parent])) || $parent === $id) {
                return $fail('Data susunan tidak valid.');
            }
            $new[$id] = ['parent_id' => $parent, 'sort' => max(1, (int) ($it['sort'] ?? 1))];
        }
        if (count($new) !== count($rows)) {
            return $fail('Susunan menu sudah berubah di tempat lain. Muat ulang halaman ini.');
        }

        // kedalaman & siklus, lalu menu utama (tingkat 1) tiap menu
        $topOf = static function (int $id, array $map) {
            $seen = [];
            $d    = 1;
            while ($map[$id]['parent_id'] !== null) {
                if (isset($seen[$id])) {
                    return [null, 99];
                }
                $seen[$id] = true;
                $id        = $map[$id]['parent_id'];
                $d++;
            }

            return [$id, $d];
        };
        $oldMap = array_map(static fn ($r) => ['parent_id' => $r['parent_id'] !== null ? (int) $r['parent_id'] : null], $rows);
        foreach ($new as $id => $n) {
            [$top, $depth] = $topOf($id, $new);
            if ($top === null) {
                return $fail('Menu tidak boleh dimasukkan ke dalam sub-menunya sendiri.');
            }
            if ($depth > MenuTree::MAX_DEPTH) {
                return $fail('Menu paling banyak ' . MenuTree::MAX_DEPTH . ' tingkat. “' . $rows[$id]['label'] . '” terlalu dalam.');
            }
            if ($rows[$id]['is_required']) {
                [$oldTop] = $topOf($id, $oldMap);
                if ($oldTop !== $top || ($rows[$id]['parent_id'] === null) !== ($n['parent_id'] === null)) {
                    return $fail('“' . $rows[$id]['label'] . '” adalah menu wajib SK: boleh diurutkan, tetapi harus tetap berada di bawah menu “' . $rows[$oldTop]['label'] . '”.');
                }
            }
        }

        $changed = 0;
        $this->db->transStart();
        foreach ($new as $id => $n) {
            $old = $rows[$id];
            if ((int) $old['sort'] !== $n['sort'] || ($old['parent_id'] === null ? null : (int) $old['parent_id']) !== $n['parent_id']) {
                $this->db->table('menus')->where('id', $id)->update($n);
                $changed++;
            }
        }
        $this->db->transComplete();
        if ($changed) {
            $this->flushPublic();
            $this->log('ubah', 'Susunan menu (seret & lepas): ' . $changed . ' menu');
        }

        return $this->response->setJSON(['ok' => true, 'changed' => $changed, 'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()]]);
    }

    public function delete(int $id)
    {
        $m = $this->find($id);
        if ($m['is_required']) {
            return redirect()->back()->with('error', 'Menu wajib SK tidak dapat dihapus.');
        }
        if ($this->db->table('menus')->where('parent_id', $id)->countAllResults()) {
            return redirect()->back()->with('error', 'Hapus atau pindahkan sub-menu terlebih dahulu.');
        }
        $this->db->table('menus')->where('id', $id)->delete();
        $this->log('hapus', 'Menu: ' . $m['label']);
        $this->flushPublic();

        return redirect()->to(admin_url('menu'))->with('success', 'Menu dihapus.');
    }

    private function siblings($parentId): array
    {
        $b = $this->db->table('menus')->orderBy('sort')->orderBy('id');
        $parentId ? $b->where('parent_id', $parentId) : $b->where('parent_id', null);

        return $b->get()->getResultArray();
    }

    /** Pilihan induk bertingkat (tanpa menu ini & turunannya, tanpa tingkat terdalam). */
    private function parents(?int $exclude): array
    {
        $by = [];
        foreach ($this->db->table('menus')->orderBy('sort')->orderBy('id')->get()->getResultArray() as $r) {
            $by[(int) $r['parent_id']][] = $r;
        }
        $out  = [];
        $walk = function (int $pid, int $depth) use (&$walk, &$out, $by, $exclude) {
            foreach ($by[$pid] ?? [] as $r) {
                if ((int) $r['id'] === $exclude) {
                    continue; // menu ini beserta turunannya
                }
                if ($depth < MenuTree::MAX_DEPTH) {
                    $out[$r['id']] = str_repeat('— ', $depth - 1) . $r['label'];
                    $walk((int) $r['id'], $depth + 1);
                }
            }
        };
        $walk(0, 1);

        return $out;
    }

    /** Tinggi sub-pohon (1 = tanpa sub-menu). */
    private function height(int $id): int
    {
        $h = 1;
        foreach ($this->db->table('menus')->select('id')->where('parent_id', $id)->get()->getResultArray() as $c) {
            $h = max($h, 1 + $this->height((int) $c['id']));
        }

        return $h;
    }

    private function depthOf(?int $id): int
    {
        $d = 0;
        while ($id && $d < 20) {
            $d++;
            $id = (int) ($this->db->table('menus')->select('parent_id')->where('id', $id)->get()->getRow()->parent_id ?? 0);
        }

        return $d;
    }

    private function form(array $m, ?int $id, array $errors = [])
    {
        return $this->view('menus/form', [
            'title' => $id ? 'Ubah Menu' : 'Menu Baru', 'm' => $m, 'id' => $id, 'errors' => $errors,
            'parents' => $this->parents($id),
            'pages' => $this->db->table('pages')->orderBy('section')->orderBy('title')->get()->getResultArray(),
            'sections' => config('SiteStructure')->sections,
        ]);
    }

    private function save(?int $id)
    {
        $old  = $id ? $this->find($id) : ['is_required' => 0];
        $post = $this->request->getPost();
        $req  = (bool) $old['is_required'];

        $rules = ['label' => ['label' => 'Label', 'rules' => 'required|max_length[120]']];
        if (! $req) {
            $rules['type']      = ['label' => 'Jenis', 'rules' => 'required|in_list[page,route,url,none]'];
            $rules['parent_id'] = ['label' => 'Induk', 'rules' => 'permit_empty|is_natural_no_zero'];
            if (($post['type'] ?? '') === 'page') {
                $rules['page_id'] = ['label' => 'Halaman', 'rules' => 'required|is_natural_no_zero'];
            } elseif (($post['type'] ?? '') === 'none') {
                // judul grup: tanpa tautan
            } elseif (($post['type'] ?? '') === 'url') {
                $rules['url'] = ['label' => 'Tautan', 'rules' => 'required|valid_url_strict|max_length[255]'];
            } else {
                $rules['url'] = ['label' => 'Alamat internal', 'rules' => 'required|regex_match[/^\/[a-z0-9\/\-_]*$/]|max_length[255]'];
            }
        }
        if (! $this->validateData($post, $rules)) {
            return $this->form(array_merge($old, $post), $id, $this->validator->getErrors());
        }

        $data = ['label' => trim($post['label'])];
        if (! $req) {
            $parent = ($post['parent_id'] ?? '') !== '' ? (int) $post['parent_id'] : null;
            if ($parent && $id) {
                // cegah siklus: induk tidak boleh turunan menu ini
                $p = $parent;
                while ($p) {
                    if ($p === $id) {
                        return $this->form(array_merge($old, $post), $id, ['parent_id' => 'Induk tidak valid.']);
                    }
                    $p = (int) ($this->db->table('menus')->select('parent_id')->where('id', $p)->get()->getRow()->parent_id ?? 0);
                }
            }
            if ($this->depthOf($parent) + ($id ? $this->height($id) : 1) > MenuTree::MAX_DEPTH) {
                return $this->form(array_merge($old, $post), $id, ['parent_id' => 'Menu paling banyak ' . MenuTree::MAX_DEPTH . ' tingkat. Pilih induk yang lebih tinggi.']);
            }
            $data += [
                'parent_id' => $parent,
                'type'      => $post['type'],
                'page_id'   => $post['type'] === 'page' ? (int) $post['page_id'] : null,
                'url'       => in_array($post['type'], ['route', 'url'], true) ? trim((string) $post['url']) : null,
                'new_tab'   => ! empty($post['new_tab']) ? 1 : 0,
                'is_active' => ! empty($post['is_active']) ? 1 : 0,
            ];
        }

        if ($id) {
            $this->db->table('menus')->where('id', $id)->update($data);
            $this->log('ubah', 'Menu: ' . $data['label']);
        } else {
            $data['sort'] = count($this->siblings($data['parent_id'])) + 1;
            $this->db->table('menus')->insert($data);
            $this->log('tambah', 'Menu: ' . $data['label']);
        }
        $this->flushPublic();

        return redirect()->to(admin_url('menu'))->with('success', 'Menu tersimpan.');
    }
}
