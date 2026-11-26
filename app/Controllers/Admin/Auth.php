<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ActivityLog;
use App\Libraries\AdminAuth;
use App\Libraries\Totp;

/** Login panel admin: password + throttle + MFA TOTP (SK 2722/2026 Bab II huruf B.3). */
class Auth extends BaseController
{
    private function auth(): AdminAuth
    {
        return service('adminAuth');
    }

    public function login()
    {
        if ($this->auth()->user()) {
            return redirect()->to(admin_url());
        }
        $ip = $this->request->getIPAddress();

        return view('admin/auth/login', ['locked' => $this->auth()->isLocked($ip)]);
    }

    public function attempt()
    {
        $ip       = $this->request->getIPAddress();
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if ($this->auth()->isLocked($ip)) {
            return redirect()->back()->with('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . AdminAuth::LOCK_MINUTES . ' menit.');
        }
        // Honeypot sederhana untuk bot
        if ((string) $this->request->getPost('website') !== '') {
            return redirect()->back();
        }
        if ($username === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Nama pengguna dan password wajib diisi.');
        }
        if (\App\Libraries\Turnstile::active()) {
            $ts = \App\Libraries\Turnstile::verify((string) $this->request->getPost('cf-turnstile-response'), $ip);
            if (! $ts['ok']) {
                if ($ts['reachable']) {
                    return redirect()->back()->withInput()->with('error', $ts['error']);
                }
                // Cloudflare tak terjangkau dari server: jangan kunci semua admin; password, batas percobaan & MFA tetap berlaku
                log_message('warning', 'Turnstile dilewati saat login: ' . $ts['error']);
            }
        }

        $user = $this->auth()->checkPassword($username, $password);
        if (! $user) {
            $this->auth()->recordAttempt($username, $ip, false);
            $left = AdminAuth::MAX_ATTEMPTS - $this->auth()->recentFailures($ip);

            return redirect()->back()->withInput()->with('error', 'Nama pengguna atau password salah.' . ($left > 0 ? " Sisa percobaan: {$left}." : ''));
        }

        if ((int) $user['totp_enabled'] && $user['totp_secret']) {
            session()->regenerate(true);
            session()->set(['mfa_pending' => (int) $user['id'], 'mfa_tries' => 0, 'mfa_at' => time()]);

            return redirect()->to(admin_url('login/mfa'));
        }

        return $this->finish($user, $ip);
    }

    public function mfa()
    {
        if (! session('mfa_pending') || time() - (int) session('mfa_at') > 300) {
            session()->remove(['mfa_pending', 'mfa_tries', 'mfa_at']);

            return redirect()->to(admin_url('login'));
        }

        return view('admin/auth/mfa');
    }

    public function mfaVerify()
    {
        $id = (int) session('mfa_pending');
        if (! $id || time() - (int) session('mfa_at') > 300) {
            return redirect()->to(admin_url('login'))->with('error', 'Sesi verifikasi berakhir. Silakan login ulang.');
        }
        $ip   = $this->request->getIPAddress();
        $user = $this->db->table('users')->where(['id' => $id, 'is_active' => 1])->get()->getRowArray();
        $code = (string) $this->request->getPost('code');

        if (! $user || ! Totp::verify((string) $user['totp_secret'], $code)) {
            $tries = (int) session('mfa_tries') + 1;
            session()->set('mfa_tries', $tries);
            $this->auth()->recordAttempt($user['username'] ?? '', $ip, false);
            if ($tries >= 5) {
                session()->remove(['mfa_pending', 'mfa_tries', 'mfa_at']);

                return redirect()->to(admin_url('login'))->with('error', 'Kode verifikasi salah terlalu banyak. Silakan login ulang.');
            }

            return redirect()->back()->with('error', 'Kode verifikasi tidak valid.');
        }

        return $this->finish($user, $ip);
    }

    public function logout()
    {
        $u = $this->auth()->user();
        if ($u) {
            ActivityLog::add('logout', 'Keluar', $u);
        }
        $this->auth()->logout();

        return redirect()->to(admin_url('login'))->with('success', 'Anda telah keluar.');
    }

    private function finish(array $user, string $ip)
    {
        $this->auth()->recordAttempt($user['username'], $ip, true);
        $this->auth()->login($user);
        ActivityLog::add('login', 'Masuk panel admin', $user);
        $to = session('admin_intended');
        session()->remove('admin_intended');

        return redirect()->to($to && str_starts_with($to, admin_url()) ? $to : admin_url());
    }
}
