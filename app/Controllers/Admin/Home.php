<?php

namespace App\Controllers\Admin;

use App\Libraries\HomeBlocks;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;

/**
 * Tampilan Beranda — grid bebas 12 kolom.
 * Setiap tampilan memiliki posisi (grid_x, grid_y) dan ukuran (grid_w kolom, grid_h baris).
 */
class Home extends AdminController
{
    private array $types;

    public function initController(...$args)
    {
        parent::initController(...$args);
        $this->authorize(['humas']);
        $this->types = array_filter(config('HomeBlocks')->types, static fn ($t) => empty($t['hidden']));
    }

    private function find(int $id): array
    {
        $r = $this->db->table('home_sections')->where('id', $id)->where('type !=', 'row')->get()->getRowArray();
        if (! $r) {
            throw PageNotFoundException::forPageNotFound();
        }
        $r['settings'] = json_decode((string) $r['settings'], true) ?: [];

        return $r;
    }

    private function label(array $r): string
    {
        return ($this->types[$r['type']][0] ?? $r['type']) . ($r['title'] ? ' “' . $r['title'] . '”' : '');
    }

    /** Baris kosong pertama di bawah semua tampilan. */
    private function nextRow(): int
    {
        $r = $this->db->table('home_sections')->select('MAX(grid_y + grid_h) AS n', false)->where('type !=', 'row')->get()->getRow();

        return max(1, (int) ($r->n ?? 1));
    }

    public function index()
    {
        return $this->view('home/index', [
            'title' => 'Tampilan Beranda', 'items' => (new HomeBlocks())->tree(false),
            'types' => $this->types, 'cfg' => config('HomeBlocks'), 'homeBuilder' => true,
        ]);
    }

    /**
     * Simpan susunan dari editor grid.
     * POST layout = JSON [{id, x, y, w, h}]
     */
    public function arrange()
    {
        $items = json_decode((string) $this->request->getPost('layout'), true);
        if (! is_array($items)) {
            return redirect()->to(admin_url('beranda'))->with('error', 'Data susunan tidak valid.');
        }
        $ids   = array_column($this->db->table('home_sections')->select('id')->where('type !=', 'row')->get()->getResultArray(), 'id');
        $clean = [];
        foreach ($items as $it) {
            $id = (int) ($it['id'] ?? 0);
            $x  = (int) ($it['x'] ?? 0);
            $y  = (int) ($it['y'] ?? 0);
            $w  = (int) ($it['w'] ?? 0);
            $h  = (int) ($it['h'] ?? 0);
            if (! in_array($id, array_map('intval', $ids), true) || $x < 1 || $w < 1 || $x + $w - 1 > 12 || $y < 1 || $y > 200 || $h < 1 || $h > 12) {
                return redirect()->to(admin_url('beranda'))->with('error', 'Posisi atau ukuran tampilan tidak valid.');
            }
            $clean[$id] = compact('x', 'y', 'w', 'h');
        }
        // tolak bila ada tumpang tindih
        $list = array_values($clean);
        for ($i = 0; $i < count($list); $i++) {
            for ($j = $i + 1; $j < count($list); $j++) {
                $a = $list[$i];
                $b = $list[$j];
                if ($a['x'] < $b['x'] + $b['w'] && $b['x'] < $a['x'] + $a['w'] && $a['y'] < $b['y'] + $b['h'] && $b['y'] < $a['y'] + $a['h']) {
                    return redirect()->to(admin_url('beranda'))->with('error', 'Ada tampilan yang saling bertumpuk. Susunan tidak disimpan.');
                }
            }
        }
        $this->db->transStart();
        foreach ($clean as $id => $c) {
            $this->db->table('home_sections')->where('id', $id)->update([
                'grid_x' => $c['x'], 'grid_y' => $c['y'], 'grid_w' => $c['w'], 'grid_h' => $c['h'], 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->db->transComplete();
        $this->log('ubah', 'Menyusun ulang tampilan beranda (grid)');

        return redirect()->to(admin_url('beranda'))->with('success', 'Susunan beranda disimpan.');
    }

    /** Tanpa ?type → pilih jenis; dengan ?type → form. */
    public function create()
    {
        $type = (string) $this->request->getGet('type');
        if (! isset($this->types[$type])) {
            return $this->view('home/pick', ['title' => 'Pilih Jenis Tampilan', 'types' => $this->types]);
        }
        $defaults = [];
        foreach ($this->types[$type]['fields'] as $k => $f) {
            $defaults[$k] = $f['default'] ?? '';
        }
        [$w, $h] = config('HomeBlocks')->sizes[$type] ?? [12, 1];

        return $this->form([
            'type' => $type, 'title' => $this->types[$type][0], 'subtitle' => '', 'settings' => $defaults,
            'grid_x' => 1, 'grid_y' => $this->nextRow(), 'grid_w' => $w, 'grid_h' => $h,
            'bg' => 'white', 'accent' => 'primary', 'is_active' => 1,
        ], null);
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

    public function toggle(int $id)
    {
        $r = $this->find($id);
        $this->db->table('home_sections')->where('id', $id)->update(['is_active' => $r['is_active'] ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->log('ubah', ($r['is_active'] ? 'Menyembunyikan ' : 'Menampilkan ') . $this->label($r));

        return redirect()->to(admin_url('beranda'))->with('success', $this->label($r) . ($r['is_active'] ? ' disembunyikan.' : ' ditampilkan.'));
    }

    public function duplicate(int $id)
    {
        $r    = $this->find($id);
        $now  = date('Y-m-d H:i:s');
        $s    = $r['settings'];
        if (isset($s['image'])) {
            $s['image'] = ''; // gambar tidak disalin agar penghapusan salinan tidak menghapus berkas asli
        }
        $this->db->table('home_sections')->insert([
            'type' => $r['type'], 'title' => $r['title'], 'subtitle' => $r['subtitle'], 'settings' => json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'bg' => $r['bg'], 'accent' => $r['accent'], 'is_active' => $r['is_active'], 'region' => 'top', 'sort' => 0, 'width' => 12,
            'grid_x' => 1, 'grid_y' => $this->nextRow(), 'grid_w' => $r['grid_w'], 'grid_h' => $r['grid_h'],
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $copy = (int) $this->db->insertID();
        $this->log('tambah', 'Salinan ' . $this->label($r));

        return redirect()->to(admin_url('beranda/' . $copy))->with('success', 'Salinan dibuat di baris paling bawah.');
    }

    public function delete(int $id)
    {
        $r = $this->find($id);
        if (! empty($r['settings']['image'])) {
            service('uploader')->deleteImage($r['settings']['image']);
        }
        $this->db->table('home_sections')->where('id', $id)->delete();
        $this->log('hapus', 'Tampilan beranda: ' . $this->label($r));

        return redirect()->to(admin_url('beranda'))->with('success', $this->label($r) . ' dihapus.');
    }

    // Rute lama (tidak dipakai pada grid bebas) dialihkan ke halaman utama editor
    public function move(int $id)
    {
        return redirect()->to(admin_url('beranda'));
    }

    public function layout()
    {
        return redirect()->to(admin_url('beranda'));
    }

    // ------------------------------------------------------------------

    private function lookups(string $type): array
    {
        $out = [];
        foreach ($this->types[$type]['fields'] as $k => $f) {
            if (($f['source'] ?? '') === 'shortcut_groups') {
                $groups = [];
                try {
                    $groups = array_filter(array_column($this->db->table('shortcuts')->select('grp')->distinct()->where('grp IS NOT NULL')->orderBy('grp')->get()->getResultArray(), 'grp'));
                } catch (\Throwable $e) {
                    // kolom grp belum ada (pembaruan database belum dijalankan)
                }
                $out[$k] = ['' => 'Pintasan Layanan Utama (tanpa kelompok)'] + array_combine($groups, $groups);
            }
            if (($f['source'] ?? '') === 'doc_types') {
                $out[$k] = ['' => 'Semua jenis'] + array_column($this->db->table('doc_types')->select('id, name')->orderBy('sort')->get()->getResultArray(), 'name', 'id');
            }
        }

        return $out;
    }

    private function form(array $r, ?int $id, array $errors = [])
    {
        $type = $this->types[$r['type']];

        return $this->view('home/form', [
            'title' => ($id ? 'Ubah ' : 'Tambah ') . $type[0], 'r' => $r, 'id' => $id, 'type' => $type,
            'cfg' => config('HomeBlocks'), 'errors' => $errors, 'lookups' => $this->lookups($r['type']),
            'richtext' => (bool) array_filter($type['fields'], static fn ($f) => $f[1] === 'richtext'),
        ]);
    }

    /** Geser ke bawah tampilan lain yang bertumpuk dengan kotak $id (dipakai saat posisi diubah lewat form). */
    private function pushDown(int $id): void
    {
        $all  = $this->db->table('home_sections')->select('id, grid_x AS x, grid_y AS y, grid_w AS w, grid_h AS h')->where('type !=', 'row')
            ->orderBy('grid_y')->orderBy('grid_x')->get()->getResultArray();
        $by   = array_column($all, null, 'id');
        $hit  = static fn ($a, $b) => $a['x'] < $b['x'] + $b['w'] && $b['x'] < $a['x'] + $a['w'] && $a['y'] < $b['y'] + $b['h'] && $b['y'] < $a['y'] + $a['h'];
        $done = [$by[$id]];
        foreach ($all as $o) {
            if ((int) $o['id'] === $id) {
                continue;
            }
            $moved = false;
            while (array_filter($done, static fn ($d) => $hit($o, $d))) {
                $o['y']++;
                $moved = true;
            }
            if ($moved) {
                $this->db->table('home_sections')->where('id', $o['id'])->update(['grid_y' => $o['y']]);
            }
            $done[] = $o;
        }
    }

    private function save(?int $id)
    {
        $old  = $id ? $this->find($id) : null;
        $post = $this->request->getPost();
        $type = $old['type'] ?? (string) ($post['type'] ?? '');
        if (! isset($this->types[$type])) {
            throw PageNotFoundException::forPageNotFound();
        }
        $cfg   = config('HomeBlocks');
        $def   = $this->types[$type];
        $rules = [
            'title'    => ['label' => 'Judul', 'rules' => 'permit_empty|max_length[255]'],
            'subtitle' => ['label' => 'Sub-judul', 'rules' => 'permit_empty|max_length[500]'],
            'grid_x'   => ['label' => 'Kolom mulai', 'rules' => 'required|integer|greater_than[0]|less_than[13]'],
            'grid_w'   => ['label' => 'Lebar', 'rules' => 'required|integer|greater_than[0]|less_than[13]'],
            'grid_y'   => ['label' => 'Baris', 'rules' => 'required|integer|greater_than[0]|less_than[201]'],
            'grid_h'   => ['label' => 'Tinggi', 'rules' => 'required|integer|greater_than[0]|less_than[13]'],
        ];
        foreach ($def['fields'] as $k => $f) {
            if (! in_array($f[1], ['image', 'checkbox'], true)) {
                $rules['s_' . $k] = ['label' => $f[0], 'rules' => $f[2]];
            }
        }
        $flat = $post;
        foreach ($def['fields'] as $k => $f) {
            $flat['s_' . $k] = $post['settings'][$k] ?? null;
        }
        $current = array_merge(['title' => '', 'subtitle' => '', 'bg' => 'white', 'accent' => 'primary', 'is_active' => 0, 'grid_x' => 1, 'grid_y' => 1, 'grid_w' => 12, 'grid_h' => 1],
            $old ?? ['type' => $type], $post, ['settings' => array_merge($old['settings'] ?? [], (array) ($post['settings'] ?? []))]);
        if (! $this->validateData($flat, $rules)) {
            $errs = [];
            foreach ($this->validator->getErrors() as $k => $e) {
                $errs[preg_replace('/^s_/', 'settings_', $k)] = $e;
            }

            return $this->form($current, $id, $errs);
        }
        if ((int) $post['grid_x'] + (int) $post['grid_w'] - 1 > 12) {
            return $this->form($current, $id, ['grid_w' => 'Kolom mulai + lebar melebihi 12 kolom. Kurangi lebar atau geser ke kiri.']);
        }

        $settings = $old['settings'] ?? [];
        foreach ($def['fields'] as $k => $f) {
            $v = $post['settings'][$k] ?? null;
            switch ($f[1]) {
                case 'checkbox':
                    $settings[$k] = $v ? 1 : 0;
                    break;

                case 'richtext':
                    $settings[$k] = service('purifier')->clean((string) $v);
                    break;

                case 'number':
                    $settings[$k] = (int) $v;
                    break;

                case 'image':
                    $file = $this->request->getFile('settings_' . $k);
                    try {
                        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                            $settings[$k] = service('uploader')->storeImage($file, $f['width'] ?? 1600);
                            service('uploader')->deleteImage($old['settings'][$k] ?? null);
                        } elseif (! empty($post['settings_' . $k . '__remove'])) {
                            service('uploader')->deleteImage($old['settings'][$k] ?? null);
                            $settings[$k] = '';
                        }
                    } catch (RuntimeException $e) {
                        return $this->form($current, $id, ['settings_' . $k => $e->getMessage()]);
                    }
                    break;

                default:
                    $settings[$k] = trim((string) $v);
                    if ($f[1] === 'url' && ! url_scheme_ok($settings[$k])) {
                        return $this->form($current, $id, ['settings_' . $k => 'Tautan harus diawali https://, http://, atau alamat internal "/…".']);
                    }
            }
        }
        if ($type === 'image' && empty($settings['image'])) {
            return $this->form($current, $id, ['settings_image' => 'Gambar wajib diunggah.']);
        }

        $data = [
            'title'      => trim((string) ($post['title'] ?? '')),
            'subtitle'   => trim((string) ($post['subtitle'] ?? '')),
            'settings'   => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'bg'         => isset($cfg->backgrounds[$post['bg'] ?? '']) ? $post['bg'] : 'white',
            'accent'     => isset($cfg->accents[$post['accent'] ?? '']) ? $post['accent'] : 'primary',
            'grid_x'     => (int) $post['grid_x'],
            'grid_y'     => (int) $post['grid_y'],
            'grid_w'     => (int) $post['grid_w'],
            'grid_h'     => (int) $post['grid_h'],
            'is_active'  => ! empty($post['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            $this->db->table('home_sections')->where('id', $id)->update($data);
            $this->log('ubah', 'Tampilan beranda: ' . $this->label(['type' => $type, 'title' => $data['title']]));
        } else {
            $data += ['type' => $type, 'region' => 'top', 'sort' => 0, 'width' => 12, 'created_at' => $data['updated_at']];
            $this->db->table('home_sections')->insert($data);
            $id = (int) $this->db->insertID();
            $this->log('tambah', 'Tampilan beranda: ' . $this->label(['type' => $type, 'title' => $data['title']]));
        }
        $this->pushDown($id);

        if ($this->request->getPost('_after') === 'view') {
            return redirect()->to(site_url('/') . '#blok-' . $id);
        }

        return redirect()->to(admin_url('beranda') . '#tile-' . $id)->with('success', 'Tampilan tersimpan.');
    }
}
