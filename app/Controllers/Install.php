<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Database;
use Throwable;

/**
 * Pemasangan lewat browser (untuk hosting tanpa terminal): /pasang
 *
 *  1. Memeriksa syarat server (versi PHP, ekstensi, folder dapat ditulis)
 *  2. Membuat berkas .env (hanya bila BELUM ada — .env yang sudah ada tidak pernah ditimpa)
 *  3. Membuat tabel (migrasi) dan data awal (halaman & menu wajib SK)
 *  4. Membuat akun Super Admin pertama
 *
 * Halaman ini otomatis tertutup (404) setelah instalasi selesai (writable/installed.lock)
 * atau bila sudah ada akun admin.
 */
class Install extends Controller
{
    private const LOCK = WRITEPATH . 'installed.lock';

    public function index()
    {
        helper(['url', 'form']);
        $state = $this->state();
        if ($state['installed']) {
            throw PageNotFoundException::forPageNotFound();
        }

        $errors = [];
        if ($this->request->getMethod() === 'POST' && $state['canInstall']) {
            [$ok, $errors, $path] = $this->install($state) + [2 => null];
            if ($ok) {
                return view('install', ['done' => true, 'adminUrl' => rtrim(base_url(), '/') . '/' . $path . '/login', 'state' => $state]);
            }
        }

        return view('install', ['state' => $state, 'errors' => $errors, 'old' => $this->request->getPost() ?? [], 'done' => false]);
    }

    /** Kondisi server & apakah instalasi masih diperbolehkan. */
    private function state(): array
    {
        $envFile = ROOTPATH . '.env';
        $hasEnv  = is_file($envFile);
        $s       = ['hasEnv' => $hasEnv, 'installed' => is_file(self::LOCK), 'dbOk' => false, 'dbError' => '', 'hasTables' => false, 'hasUsers' => false];

        if (! $s['installed'] && $hasEnv) {
            try {
                $db = Database::connect();
                $db->initialize();
                $s['dbOk']      = true;
                $s['hasTables'] = $db->tableExists('users');
                $s['hasUsers']  = $s['hasTables'] && $db->table('users')->countAllResults() > 0;
                $s['installed'] = $s['hasUsers']; // sudah ada admin → instalasi tidak dibuka lagi
            } catch (Throwable $e) {
                $s['dbError'] = 'Tidak dapat terhubung ke database dengan pengaturan di berkas .env.';
            }
        }

        $gd     = function_exists('gd_info') ? gd_info() : [];
        $checks = [
            ['PHP versi 8.2 atau lebih baru (terpasang ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.2.0', '>=')],
            ['Ekstensi intl', extension_loaded('intl')],
            ['Ekstensi mbstring', extension_loaded('mbstring')],
            ['Ekstensi mysqli (database MySQL/MariaDB)', extension_loaded('mysqli')],
            ['Ekstensi gd (olah gambar)', extension_loaded('gd') && function_exists('imagecreatetruecolor') && ! empty($gd['JPEG Support']) && ! empty($gd['PNG Support'])],
            ['Dukungan WebP pada gd (disarankan, tidak wajib — tanpa WebP gambar disimpan sebagai JPG/PNG)', \App\Libraries\Uploader::webpSupported(), true],
            ['Ekstensi fileinfo (cek jenis berkas unggahan)', extension_loaded('fileinfo')],
            ['Folder writable/ dapat ditulis', is_writable(WRITEPATH)],
            ['Folder public/uploads/ dapat ditulis', is_writable(FCPATH . 'uploads')],
        ];
        if (! $hasEnv) {
            $checks[] = ['Folder utama website dapat ditulis (untuk membuat .env)', is_writable(ROOTPATH)];
        }
        $s['checks']     = $checks;
        $s['checksOk']   = ! in_array(false, array_column(array_filter($checks, static fn ($c) => empty($c[2])), 1), true); // butir ke-3 = tidak wajib
        $s['needDbForm'] = ! $hasEnv;                       // .env belum ada → isi koneksi database
        $s['canInstall'] = $s['checksOk'] && (! $hasEnv || $s['dbOk']);
        $s['baseURL']    = config('App')->baseURL;
        $s['https']      = str_starts_with($s['baseURL'], 'https://');

        return $s;
    }

    private function install(array $state): array
    {
        $p      = $this->request->getPost();
        $errors = [];

        // ---- validasi akun admin
        $user  = trim((string) ($p['username'] ?? ''));
        $name  = trim((string) ($p['name'] ?? ''));
        $pass  = (string) ($p['password'] ?? '');
        $path  = trim((string) ($p['admin_path'] ?? 'panel-satker'), '/ ');
        if (! preg_match('/^[a-z0-9_.-]{4,60}$/i', $user)) {
            $errors[] = 'Nama pengguna 4–60 karakter (huruf, angka, titik, garis bawah, tanda hubung).';
        }
        if ($name === '') {
            $errors[] = 'Nama lengkap admin wajib diisi.';
        }
        if (strlen($pass) < 10 || ! preg_match('/[A-Za-z]/', $pass) || ! preg_match('/\d/', $pass)) {
            $errors[] = 'Password minimal 10 karakter dan mengandung huruf serta angka.';
        }
        if ($pass !== (string) ($p['password2'] ?? '')) {
            $errors[] = 'Ulangi password tidak sama.';
        }
        if (! preg_match('/^[a-z0-9-]{4,40}$/', $path)) {
            $errors[] = 'Alamat panel admin 4–40 karakter: huruf kecil, angka, tanda hubung.';
        }

        // ---- koneksi database (hanya bila .env belum ada)
        $dbCfg = null;
        if ($state['needDbForm']) {
            $dbCfg = [
                'DSN' => '', 'hostname' => trim((string) ($p['db_host'] ?? 'localhost')), 'port' => (int) ($p['db_port'] ?? 3306) ?: 3306,
                'username' => trim((string) ($p['db_user'] ?? '')), 'password' => (string) ($p['db_pass'] ?? ''),
                'database' => trim((string) ($p['db_name'] ?? '')), 'DBDriver' => 'MySQLi', 'DBPrefix' => '', 'pConnect' => false,
                'DBDebug' => true, 'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_unicode_ci', 'swapPre' => '', 'encrypt' => false,
                'compress' => false, 'strictOn' => false, 'failover' => [],
            ];
            foreach (['hostname' => 'Host database', 'username' => 'Pengguna database', 'database' => 'Nama database'] as $k => $l) {
                if ($dbCfg[$k] === '' || preg_match('/[\'"\r\n\\\\]/', $dbCfg[$k])) {
                    $errors[] = "{$l} wajib diisi (tanpa tanda kutip).";
                }
            }
            if (preg_match('/[\'"\r\n\\\\]/', $dbCfg['password'])) {
                $errors[] = 'Password database tidak boleh mengandung tanda kutip atau garis miring terbalik.';
            }
        }
        if ($errors) {
            return [false, $errors];
        }

        try {
            $db = $dbCfg ? Database::connect($dbCfg, false) : Database::connect();
            $db->initialize();
        } catch (Throwable $e) {
            return [false, ['Gagal terhubung ke database: periksa host, nama database, pengguna dan password. (' . mb_strimwidth($e->getMessage(), 0, 160, '…') . ')']];
        }

        try {
            // ---- tabel & data awal
            $migrations = service('migrations', null, $db, false);
            $migrations->setNamespace('App')->latest();

            if (! $db->table('users')->countAllResults()) {
                $now = date('Y-m-d H:i:s');
                $db->table('users')->insert([
                    'username' => $user, 'name' => $name, 'email' => trim((string) ($p['email'] ?? '')),
                    'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'superadmin', 'is_active' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $seeder = new Seeder(config('Database'), $db);
            $seeder->setSilent(true)->call('App\Database\Seeds\SatkerSeeder');
            if (($site = trim((string) ($p['site_name'] ?? ''))) !== '') {
                $db->table('settings')->where('key', 'site_name')->update(['value' => mb_substr($site, 0, 150)]);
            }
        } catch (Throwable $e) {
            log_message('error', 'Install: ' . $e->getMessage());

            return [false, ['Gagal membuat tabel/data awal: ' . mb_strimwidth($e->getMessage(), 0, 200, '…')]];
        }

        // ---- berkas .env (tidak pernah menimpa yang sudah ada)
        if ($dbCfg && ! is_file(ROOTPATH . '.env')) {
            $https = $state['https'];
            $env   = "#--------------------------------------------------------------------\n"
                . "# Dibuat oleh halaman instalasi pada " . date('Y-m-d H:i') . "\n"
                . "#--------------------------------------------------------------------\n"
                . "CI_ENVIRONMENT = production\n\n"
                . "# Kosong = alamat situs dideteksi otomatis. Di server produksi sebaiknya diisi, mis. 'https://www.pa-contoh.go.id/'\n"
                . "app.baseURL = ''\n"
                . "app.adminPath = '{$path}'\n"
                . 'app.forceGlobalSecureRequests = ' . ($https ? 'true' : 'false') . "\n\n"
                . 'encryption.key = hex2bin:' . bin2hex(random_bytes(32)) . "\n\n"
                . "database.default.hostname = {$dbCfg['hostname']}\n"
                . "database.default.database = {$dbCfg['database']}\n"
                . "database.default.username = {$dbCfg['username']}\n"
                . "database.default.password = '{$dbCfg['password']}'\n"
                . "database.default.DBDriver = MySQLi\n"
                . "database.default.charset  = utf8mb4\n"
                . "database.default.DBCollat = utf8mb4_unicode_ci\n"
                . "database.default.port     = {$dbCfg['port']}\n\n"
                . 'cookie.secure   = ' . ($https ? 'true' : 'false') . "\n"
                . "cookie.httponly = true\n"
                . "cookie.samesite = Lax\n"
                . "session.cookieName = satker_session\n"
                . "session.expiration = 7200\n";
            if (@file_put_contents(ROOTPATH . '.env', $env, LOCK_EX) === false) {
                return [false, ['Tabel sudah dibuat, tetapi berkas .env tidak dapat ditulis. Buat berkas .env secara manual dari .env.example.']];
            }
            @chmod(ROOTPATH . '.env', 0640);
        } elseif ($path !== config('App')->adminPath) {
            // .env sudah ada: alamat panel admin diubah sendiri di .env
            $path = config('App')->adminPath;
        }

        @file_put_contents(self::LOCK, 'Terpasang ' . date('c') . "\n");

        return [true, [], $path];
    }
}
