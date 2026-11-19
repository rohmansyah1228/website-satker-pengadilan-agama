<?php

namespace App\Libraries;

/**
 * Autentikasi panel admin: password (bcrypt/argon), throttle brute-force,
 * MFA TOTP, regenerasi session, dan log percobaan login (Bab II huruf B.3).
 */
class AdminAuth
{
    public const MAX_ATTEMPTS   = 5;   // percobaan gagal per IP
    public const LOCK_MINUTES   = 15;  // jendela waktu
    public const IDLE_MINUTES   = 60;  // logout otomatis bila tidak aktif

    private ?array $user = null;
    private bool $loaded = false;

    public function user(): ?array
    {
        if (! $this->loaded) {
            $this->loaded = true;
            $id           = session('admin_id');
            if ($id) {
                $last = (int) session('admin_seen');
                if ($last && time() - $last > self::IDLE_MINUTES * 60) {
                    $this->logout();

                    return null;
                }
                session()->set('admin_seen', time());
                $u = db_connect()->table('users')->where(['id' => $id, 'is_active' => 1])->get()->getRowArray();
                $this->user = $u ?: null;
            }
        }

        return $this->user;
    }

    public function isLocked(string $ip): bool
    {
        return $this->recentFailures($ip) >= self::MAX_ATTEMPTS;
    }

    public function recentFailures(string $ip): int
    {
        return db_connect()->table('login_attempts')
            ->where(['ip' => $ip, 'success' => 0])
            ->where('created_at >=', date('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60))
            ->countAllResults();
    }

    public function recordAttempt(string $username, string $ip, bool $success): void
    {
        db_connect()->table('login_attempts')->insert([
            'username' => mb_substr($username, 0, 60), 'ip' => $ip, 'success' => $success ? 1 : 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($success) {
            db_connect()->table('login_attempts')->where(['ip' => $ip, 'success' => 0])->delete();
        }
    }

    /** @return array|null baris user bila username & password cocok */
    public function checkPassword(string $username, string $password): ?array
    {
        $u = db_connect()->table('users')->where(['username' => $username, 'is_active' => 1])->get()->getRowArray();
        if (! $u) {
            password_verify($password, '$2y$10$usesomesillystringforsalt$'); // samakan waktu respons

            return null;
        }
        if (! password_verify($password, $u['password_hash'])) {
            return null;
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            db_connect()->table('users')->where('id', $u['id'])->update(['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        return $u;
    }

    public function login(array $u): void
    {
        session()->regenerate(true);
        session()->remove(['mfa_pending', 'mfa_tries']);
        session()->set(['admin_id' => (int) $u['id'], 'admin_seen' => time()]);
        db_connect()->table('users')->where('id', $u['id'])->update(['last_login_at' => date('Y-m-d H:i:s')]);
        $this->loaded = false;
    }

    public function logout(): void
    {
        session()->remove(['admin_id', 'admin_seen', 'mfa_pending', 'mfa_tries', 'totp_setup']);
        session()->regenerate(true);
        $this->user   = null;
        $this->loaded = true;
    }

    public static function validatePasswordStrength(string $p): ?string
    {
        if (mb_strlen($p) < 10) {
            return 'Password minimal 10 karakter.';
        }
        if (! preg_match('/[a-z]/', $p) || ! preg_match('/[A-Z]/', $p) || ! preg_match('/\d/', $p)) {
            return 'Password harus memuat huruf kecil, huruf besar, dan angka.';
        }

        return null;
    }
}
