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

    private function parents(?int $exclude): array
    {
        $rows = $this->db->table('menus')->where('type !=', 'url')->orderBy('sort')->get()->getResultArray();
        $by   = [];
        foreach ($rows as $r) {
            $by[(int) $r['parent_id']][] = $r;
        }
        $out = [];
        foreach ($by[0] ?? [] as $top) {
            if ((int) $top['id'] === $exclude) {
                continue;
            }
            $out[$top['id']] = $top['label'];
            foreach ($by[$top['id']] ?? [] as $c) {
                if ((int) $c['id'] !== $exclude && $c['type'] !== 'route') {
                    $out[$c['id']] = '— ' . $c['label'];
                }
            }
        }

        return $out;
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
            $rules['type']      = ['label' => 'Jenis', 'rules' => 'required|in_list[page,route,url]'];
            $rules['parent_id'] = ['label' => 'Induk', 'rules' => 'permit_empty|is_natural_no_zero'];
            if (($post['type'] ?? '') === 'page') {
                $rules['page_id'] = ['label' => 'Halaman', 'rules' => 'required|is_natural_no_zero'];
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
            $data += [
                'parent_id' => $parent,
                'type'      => $post['type'],
                'page_id'   => $post['type'] === 'page' ? (int) $post['page_id'] : null,
                'url'       => $post['type'] !== 'page' ? trim((string) $post['url']) : null,
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
