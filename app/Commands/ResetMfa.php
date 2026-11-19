<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/** Pemulihan bila ponsel authenticator hilang:  php spark satker:reset-mfa admin */
class ResetMfa extends BaseCommand
{
    protected $group       = 'Satker';
    protected $name        = 'satker:reset-mfa';
    protected $description = 'Reset MFA (dan opsional password) seorang pengguna panel admin.';
    protected $usage       = 'satker:reset-mfa <username> [--password]';

    public function run(array $params)
    {
        $username = $params[0] ?? CLI::prompt('Username');
        $db       = db_connect();
        $u        = $db->table('users')->where('username', $username)->get()->getRowArray();
        if (! $u) {
            CLI::error('Pengguna tidak ditemukan.');

            return;
        }
        $data = ['totp_enabled' => 0, 'totp_secret' => null];
        if (CLI::getOption('password')) {
            $pw                    = bin2hex(random_bytes(6)) . 'Aa1';
            $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
            CLI::write("Password baru: {$pw}", 'yellow');
        }
        $db->table('users')->where('id', $u['id'])->update($data);
        $db->table('login_attempts')->where('username', $username)->delete();
        $db->table('activity_logs')->insert(['username' => 'cli', 'action' => 'ubah', 'subject' => 'Reset MFA via CLI: ' . $username, 'ip' => 'cli', 'created_at' => date('Y-m-d H:i:s')]);
        CLI::write("MFA untuk {$username} direset. Pengguna wajib mengaktifkan ulang saat login.", 'green');
    }
}
