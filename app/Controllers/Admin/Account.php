<?php

namespace App\Controllers\Admin;

use App\Libraries\AdminAuth;
use App\Libraries\Totp;

class Account extends AdminController
{
    public function index()
    {
        return $this->view('account/index', ['title' => 'Akun Saya']);
    }

    public function password()
    {
        $cur = (string) $this->request->getPost('current_password');
        $new = (string) $this->request->getPost('new_password');
        if (! password_verify($cur, $this->user['password_hash'])) {
            return redirect()->back()->with('error', 'Password saat ini salah.');
        }
        if ($err = AdminAuth::validatePasswordStrength($new)) {
            return redirect()->back()->with('error', $err);
        }
        if ($new !== (string) $this->request->getPost('confirm_password')) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak sama.');
        }
        $this->db->table('users')->where('id', $this->user['id'])->update(['password_hash' => password_hash($new, PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->log('ubah', 'Mengganti password');
        session()->regenerate(true);

        return redirect()->back()->with('success', 'Password berhasil diganti.');
    }

    public function twofa()
    {
        $secret = null;
        if (! (int) $this->user['totp_enabled']) {
            $secret = session('totp_setup') ?: Totp::generateSecret();
            session()->set('totp_setup', $secret);
        }

        return $this->view('account/twofa', [
            'title'  => 'Autentikasi Dua Faktor (MFA)', 'secret' => $secret,
            'uri'    => $secret ? Totp::uri($secret, $this->user['username'], setting('site_name', 'Website Satker')) : null,
            'forced' => setting('require_2fa', '1') === '1', 'qrcode' => true,
        ]);
    }

    public function enable2fa()
    {
        $secret = (string) session('totp_setup');
        if (! $secret || ! Totp::verify($secret, (string) $this->request->getPost('code'))) {
            return redirect()->back()->with('error', 'Kode tidak valid. Pastikan jam di ponsel sudah sinkron, lalu coba lagi.');
        }
        $this->db->table('users')->where('id', $this->user['id'])->update(['totp_secret' => $secret, 'totp_enabled' => 1]);
        session()->remove('totp_setup');
        $this->log('ubah', 'Mengaktifkan MFA');

        return redirect()->to(admin_url())->with('success', 'MFA aktif. Mulai login berikutnya, masukkan kode dari aplikasi authenticator.');
    }

    public function disable2fa()
    {
        if (setting('require_2fa', '1') === '1') {
            return redirect()->back()->with('error', 'MFA diwajibkan oleh Super Admin.');
        }
        if (! password_verify((string) $this->request->getPost('current_password'), $this->user['password_hash'])) {
            return redirect()->back()->with('error', 'Password salah.');
        }
        $this->db->table('users')->where('id', $this->user['id'])->update(['totp_secret' => null, 'totp_enabled' => 0]);
        $this->log('ubah', 'Menonaktifkan MFA');

        return redirect()->back()->with('success', 'MFA dinonaktifkan.');
    }
}
