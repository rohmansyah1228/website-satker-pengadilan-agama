<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Cadangan database (murni PHP, tanpa mysqldump) → writable/backups/database/db-{jenis}-{YmdHis}.sql.gz
 *
 * Jenis:
 *  - terjadwal : harian/mingguan (Pengaturan cadangan); disimpan N terakhir, yang lama dihapus otomatis
 *  - pembaruan : otomatis sebelum Update Website; disimpan N terakhir
 *  - manual    : tombol "Buat cadangan sekarang"; tidak dihapus otomatis
 * N = setting backup_keep (1–5, bawaan 3). Folder cadangan berkas pembaruan (pembaruan-*) ikut dibatasi N.
 */
class Backup
{
    public const TYPES    = ['terjadwal' => 'Terjadwal', 'pembaruan' => 'Sebelum Update', 'manual' => 'Manual'];
    public const MAX_KEEP = 5;

    public static function dir(): string
    {
        return WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR;
    }

    public static function keep(): int
    {
        return max(1, min(self::MAX_KEEP, (int) setting('backup_keep', '3')));
    }

    /** off | daily | weekly */
    public static function schedule(): string
    {
        $s = setting('backup_schedule', 'weekly');

        return in_array($s, ['off', 'daily', 'weekly'], true) ? $s : 'weekly';
    }

    /** Buat cadangan database. Mengembalikan nama berkas. */
    public function create(string $type = 'manual'): string
    {
        if (! isset(self::TYPES[$type])) {
            $type = 'manual';
        }
        if (! function_exists('gzopen')) {
            throw new RuntimeException('Ekstensi PHP zlib tidak aktif, cadangan tidak dapat dikompres.');
        }
        @set_time_limit(600);
        $dir = self::dir();
        if (! is_dir($dir) && ! @mkdir($dir, 0750, true) && ! is_dir($dir)) {
            throw new RuntimeException('Folder writable/backups/database tidak dapat dibuat.');
        }
        @file_put_contents($dir . '.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents($dir . 'index.html', '');

        $name = 'db-' . $type . '-' . date('Ymd-His') . '.sql.gz';
        $tmp  = $dir . $name . '.tmp';
        $gz   = @gzopen($tmp, 'wb6');
        if (! $gz) {
            throw new RuntimeException('Berkas cadangan tidak dapat ditulis (ruang disk penuh / izin folder).');
        }

        try {
            $db = db_connect();
            $w  = static function (string $s) use ($gz): void {
                if (gzwrite($gz, $s) === false) {
                    throw new RuntimeException('Gagal menulis cadangan (ruang disk penuh?).');
                }
            };
            $w("-- Cadangan database website " . setting('site_name', '') . "\n-- Dibuat: " . date('Y-m-d H:i:s') . " (jenis: {$type})\n"
                . "-- Versi website: " . ((new VersionCheck())->localName() ?: 'build ' . ((new VersionCheck())->local()['build'] ?? '?')) . "\n"
                . "-- Pulihkan lewat phpMyAdmin → Impor (berkas .sql.gz bisa langsung diimpor).\n\n"
                . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
            $mysqli = $db->connID;
            foreach ($db->listTables() as $table) {
                $q      = $db->escapeIdentifiers($table);
                $create = $db->query('SHOW CREATE TABLE ' . $q)->getRowArray();
                $w("-- --------------------------------------------------------\n-- Tabel {$table}\n\nDROP TABLE IF EXISTS {$q};\n"
                    . (string) ($create['Create Table'] ?? end($create)) . ";\n\n");
                // baris diambil bertahap agar hemat memori pada tabel besar
                $offset = 0;
                do {
                    $rows = $db->query('SELECT * FROM ' . $q . ' LIMIT ' . $offset . ', 500')->getResultArray();
                    if ($rows) {
                        $cols = implode(', ', array_map([$db, 'escapeIdentifiers'], array_keys($rows[0])));
                        $vals = [];
                        foreach ($rows as $r) {
                            $vals[] = '(' . implode(', ', array_map(static fn ($v) => $v === null ? 'NULL'
                                : "'" . ($mysqli instanceof \mysqli ? $mysqli->real_escape_string((string) $v) : addslashes((string) $v)) . "'", $r)) . ')';
                        }
                        $w('INSERT INTO ' . $q . ' (' . $cols . ") VALUES\n" . implode(",\n", $vals) . ";\n");
                    }
                    $offset += 500;
                } while (count($rows) === 500);
                $w("\n");
            }
            $w("SET FOREIGN_KEY_CHECKS=1;\n-- selesai\n");
            gzclose($gz);
            $gz = null;
            if (! @rename($tmp, $dir . $name)) {
                throw new RuntimeException('Gagal menyimpan berkas cadangan.');
            }
        } catch (\Throwable $e) {
            if ($gz) {
                @gzclose($gz);
            }
            @unlink($tmp);

            throw new RuntimeException($e->getMessage());
        }
        if ($type === 'terjadwal') {
            service('siteSettings')->set(['backup_last_auto' => (string) time()]);
        }
        $this->rotate();

        return $name;
    }

    /** @return list<array{name:string,type:string,time:int,size:int}> terbaru lebih dulu */
    public function list(): array
    {
        $out = [];
        foreach (glob(self::dir() . 'db-*.sql.gz') ?: [] as $f) {
            if (preg_match('/^db-(terjadwal|pembaruan|manual)-(\d{8}-\d{6})\.sql\.gz$/', basename($f), $m)) {
                $t     = \DateTime::createFromFormat('Ymd-His', $m[2]);
                $out[] = ['name' => basename($f), 'type' => $m[1], 'time' => $t ? $t->getTimestamp() : (int) filemtime($f), 'size' => (int) filesize($f)];
            }
        }
        usort($out, static fn ($a, $b) => $b['time'] <=> $a['time']);

        return $out;
    }

    /** Hapus cadangan lama melebihi batas (jenis terjadwal & pembaruan, serta folder cadangan berkas pembaruan). */
    public function rotate(): int
    {
        $keep    = self::keep();
        $deleted = 0;
        foreach (['terjadwal', 'pembaruan'] as $type) {
            $items = array_values(array_filter($this->list(), static fn ($b) => $b['type'] === $type));
            foreach (array_slice($items, $keep) as $b) {
                $deleted += (int) @unlink(self::dir() . $b['name']);
            }
        }
        // cadangan berkas dari Update Website & Pemulihan berkas (yang terbaru selalu disimpan)
        foreach (['pembaruan-*', 'pemulihan-*'] as $pattern) {
            $dirs = glob(WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . $pattern, GLOB_ONLYDIR) ?: [];
            rsort($dirs);
            foreach (array_slice($dirs, $keep) as $d) {
                $deleted += (int) $this->rmdir($d);
            }
        }

        return $deleted;
    }

    public function path(string $name): string
    {
        if (! preg_match('/^db-(terjadwal|pembaruan|manual)-\d{8}-\d{6}\.sql\.gz$/', $name) || ! is_file(self::dir() . $name)) {
            throw new RuntimeException('Berkas cadangan tidak ditemukan.');
        }

        return self::dir() . $name;
    }

    public function delete(string $name): void
    {
        if (! @unlink($this->path($name))) {
            throw new RuntimeException('Berkas cadangan tidak dapat dihapus.');
        }
    }

    /** Total ukuran folder writable/backups (database + berkas). */
    public function usage(): int
    {
        $sum  = 0;
        $root = WRITEPATH . 'backups';
        if (! is_dir($root)) {
            return 0;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $f) {
            $sum += $f->isFile() ? $f->getSize() : 0;
        }

        return $sum;
    }

    /** Cadangan terjadwal sudah waktunya dibuat? */
    public static function due(): bool
    {
        $s = self::schedule();
        if ($s === 'off') {
            return false;
        }
        $last = (int) setting('backup_last_auto', '0');

        return time() - $last >= ($s === 'daily' ? 86400 : 7 * 86400) - 600;
    }

    /** Jalankan cadangan terjadwal bila waktunya (dipanggil sekali sehari dari kunjungan pertama / Dashboard). */
    public static function runIfDue(): void
    {
        if (! self::due()) {
            return;
        }
        // cegah dua proses bersamaan
        $lock = @fopen(WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'backup.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return;
        }
        try {
            if (self::due()) {
                (new self())->create('terjadwal');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Cadangan terjadwal gagal: ' . $e->getMessage());
            service('siteSettings')->set(['backup_last_error' => date('Y-m-d H:i') . ' ' . mb_substr($e->getMessage(), 0, 200)]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function rmdir(string $dir): bool
    {
        if (! is_dir($dir) || str_contains($dir, '..')) {
            return false;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }

        return @rmdir($dir);
    }
}
