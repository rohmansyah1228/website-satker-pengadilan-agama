<?php

namespace App\Controllers\Admin;

use App\Libraries\AdminAuth;
use CodeIgniter\Exceptions\PageNotFoundException;

class Users extends AdminController
{
    public function initController(...$args)
    {
        parent::initController(...$args);
        $this->authorize([], true);
    }

    private function find(int $id): array
    {
        $u = $this->db->table('users')->where('id', $id)->get()->getRowArray();
        if (! $u) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $u;
    }

    public function index()
    {
        return $this->view('users/index', ['title' => 'Pengguna', 'rows' => $this->db->table('users')->orderBy('role')->orderBy('name')->get()->getResultArray(), 'roles' => config('SiteStructure')->roles]);
    }

    public function create()
    {
        return $this->form(['role' => 'admin', 'is_active' => 1], null);
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
        $u = $this->find($id);
        if ($id === (int) $this->user['id']) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }
        if ($u['role'] === 'superadmin' && $this->db->table('users')->where('role', 'superadmin')->countAllResults() <= 1) {
            return redirect()->back()->with('error', 'Minimal harus ada satu Super Admin.');
        }
        $this->db->table('users')->where('id', $id)->delete();
        $this->log('hapus', 'Pengguna: ' . $u['username']);

        return redirect()->to(admin_url('pengguna'))->with('success', 'Pengguna dihapus.');
    }

    public function reset2fa(int $id)
    {
        $u = $this->find($id);
        $this->db->table('users')->where('id', $id)->update(['totp_enabled' => 0, 'totp_secret' => null]);
        $this->log('ubah', 'Reset MFA pengguna: ' . $u['username']);

        return redirect()->back()->with('success', 'MFA pengguna direset. Pengguna wajib mengaktifkan ulang saat login berikutnya.');
    }

    private function form(array $u, ?int $id, array $errors = [])
    {
        return $this->view('users/form', ['title' => $id ? 'Ubah Pengguna' : 'Pengguna Baru', 'u' => $u, 'id' => $id, 'errors' => $errors, 'roles' => config('SiteStructure')->roles]);
    }

    private function save(?int $id)
    {
        $old  = $id ? $this->find($id) : [];
        $post = $this->request->getPost();
        $rules = [
            'username' => ['label' => 'Nama pengguna', 'rules' => 'required|alpha_dash|min_length[4]|max_length[60]|is_unique[users.username,id,' . ($id ?? 0) . ']'],
            'name'     => ['label' => 'Nama', 'rules' => 'required|max_length[150]'],
            'email'    => ['label' => 'Email', 'rules' => 'permit_empty|valid_email|max_length[150]'],
            'role'     => ['label' => 'Peran', 'rules' => 'required|in_list[' . implode(',', array_keys(config('SiteStructure')->roles)) . ']'],
            'password' => ['label' => 'Password', 'rules' => ($id ? 'permit_empty' : 'required') . '|max_length[200]'],
        ];
        if (! $this->validateData($post, $rules)) {
            return $this->form(array_merge($old, $post), $id, $this->validator->getErrors());
        }
        if (($post['password'] ?? '') !== '' && ($err = AdminAuth::validatePasswordStrength($post['password']))) {
            return $this->form(array_merge($old, $post), $id, ['password' => $err]);
        }
        $data = [
            'username' => $post['username'], 'name' => trim($post['name']), 'email' => trim((string) $post['email']),
            'role' => $post['role'], 'is_active' => ! empty($post['is_active']) ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id === (int) $this->user['id']) {
            $data['role']      = $old['role'];
            $data['is_active'] = 1;
        }
        if (($post['password'] ?? '') !== '') {
            $data['password_hash'] = password_hash($post['password'], PASSWORD_DEFAULT);
        }
        if ($id) {
            if ($old['role'] === 'superadmin' && $data['role'] !== 'superadmin' && $this->db->table('users')->where('role', 'superadmin')->countAllResults() <= 1) {
                return $this->form(array_merge($old, $post), $id, ['role' => 'Minimal harus ada satu Super Admin.']);
            }
            $this->db->table('users')->where('id', $id)->update($data);
            $this->log('ubah', 'Pengguna: ' . $data['username']);
        } else {
            $data['created_at'] = $data['updated_at'];
            $this->db->table('users')->insert($data);
            $this->log('tambah', 'Pengguna: ' . $data['username']);
        }

        return redirect()->to(admin_url('pengguna'))->with('success', 'Pengguna tersimpan.');
    }
}
