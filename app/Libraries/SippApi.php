<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Penerima data dari "SIPP API Kantor" (aplikasi di server kantor yang membaca database SIPP
 * lalu MENGIRIM data ke website — hosting tidak bisa menjangkau jaringan kantor).
 *
 * Kiriman: POST /api/sipp/terima, body JSON {"jenis","sumber","dibuat","jumlah","data"}
 *   X-Api-Key, X-Timestamp (unix), X-Signature = hex(HMAC-SHA256(secret, timestamp + "." + body))
 *
 * Data diterapkan ke modul website yang sudah ada (bila "Integrasi SIPP" aktif):
 *   jadwal_sidang     → halaman Jadwal Sidang & blok Beranda (lewat SippSource)
 *   statistik_perkara → tabel case_stats (Statistik Perkara per bulan)
 *   panggilan_ghaib   → tabel ghaib (baris bertanda source = 'sipp'); jawaban berisi "butuh_relaas"
 *                       = sidik (sha256) relaas yang belum dimiliki website
 *   relaas_ghaib      → satu berkas PDF relaas (base64) untuk satu perkara → ghaib.file_id
 *                       (relaas yang diunggah manual tidak pernah ditimpa)
 *   antrian           → disimpan, tersedia di GET /api/sipp/antrian
 */
class SippApi
{
    /** [apiKey, secret, asal] — .env diutamakan, lalu Pengaturan. */
    public static function credentials(): array
    {
        $cfg = config('Sipp');
        if (($cfg->apiKey ?? '') !== '' && ($cfg->secret ?? '') !== '') {
            return [(string) $cfg->apiKey, (string) $cfg->secret, 'env'];
        }

        return [(string) setting('sipp_api_key', ''), (string) setting('sipp_api_secret', ''), 'pengaturan'];
    }

    public static function enabled(): bool
    {
        return setting('sipp_api_aktif') === '1';
    }

    /**
     * Ambil apiKey & secret dari teks yang ditempel (teks .env dari dashboard kantor,
     * atau baris "api_key"/"secret" dari config.php).
     * @return array{0:string,1:string}|null
     */
    public static function parseKeys(string $text): ?array
    {
        $find = static function (array $names) use ($text): string {
            foreach ($names as $n) {
                if (preg_match('/' . $n . '\s*[=:]\s*(?:=>\s*)?[\'"]?([A-Za-z0-9_\-]{16,200})[\'"]?/i', $text, $m)) {
                    return $m[1];
                }
            }

            return '';
        };
        $key    = $find(['sipp\.apiKey', 'apiKey', 'api_key']);
        $secret = $find(['sipp\.secret', 'secret']);

        return $key !== '' && $secret !== '' ? [$key, $secret] : null;
    }

    /**
     * Periksa kiriman. Mengembalikan paket JSON yang sudah diverifikasi.
     * @throws RuntimeException kode = status HTTP
     */
    public function verify(string $body, string $apiKey, string $timestamp, string $signature): array
    {
        $cfg = config('Sipp');
        if (! db_connect()->tableExists('sipp_data')) {
            throw new RuntimeException('Database website belum diperbarui: buka Panel Admin website → Dashboard → klik "Jalankan pembaruan", lalu kirim ulang.', 503);
        }
        [$key, $secret] = self::credentials();
        if ($key === '' || $secret === '') {
            throw new RuntimeException('Penerima belum dikonfigurasi: tempel kunci di Pengaturan → Integrasi SIPP.', 503);
        }
        $maks = max((int) $cfg->maksUkuran, (int) ($cfg->maksUkuranRelaas ?? 0));
        if ($body === '' || strlen($body) > $maks) {
            throw new RuntimeException('Body kosong atau terlalu besar.', 400);
        }
        if ($apiKey === '' || ! hash_equals($key, $apiKey)) {
            throw new RuntimeException('API key tidak valid.', 401);
        }
        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > (int) $cfg->maksSelisihDetik) {
            throw new RuntimeException('Timestamp tidak valid atau jam server berbeda terlalu jauh.', 401);
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        if ($signature === '' || ! hash_equals($expected, strtolower($signature))) {
            throw new RuntimeException('Tanda tangan tidak valid.', 401);
        }
        $paket = json_decode($body, true);
        if (! is_array($paket) || ! isset($paket['jenis']) || ! is_string($paket['jenis'])) {
            throw new RuntimeException('Format JSON tidak valid.', 422);
        }
        if (! in_array($paket['jenis'], $cfg->jenis, true)) {
            throw new RuntimeException('Jenis data tidak dikenal: ' . mb_substr($paket['jenis'], 0, 50), 422);
        }
        $paket['_ts'] = (int) $timestamp;
        if ($paket['jenis'] === 'relaas_ghaib') {
            // berkas dicocokkan lewat sidik sha256, jadi kiriman ulang tidak mengubah apa pun
            return $paket;
        }
        if (strlen($body) > (int) $cfg->maksUkuran) {
            throw new RuntimeException('Body kosong atau terlalu besar.', 400);
        }
        // kiriman lama yang diputar ulang tidak boleh menimpa data yang lebih baru
        $last = (int) (db_connect()->table('sipp_data')->select('ts_kirim')->where('jenis', $paket['jenis'])->get()->getRow('ts_kirim') ?? 0);
        if ((int) $timestamp < $last) {
            throw new RuntimeException('Kiriman lebih lama dari data yang sudah diterima.', 409);
        }

        return $paket;
    }

    /** Simpan kiriman (menimpa data jenis yang sama), lalu terapkan ke modul website. */
    public function store(array $paket): array
    {
        $db    = db_connect();
        $jenis = $paket['jenis'];
        if ($jenis === 'relaas_ghaib') {
            return ['jenis' => $jenis, 'jumlah' => 1, 'diterapkan' => $this->applyRelaas(is_array($paket['data'] ?? null) ? $paket['data'] : [])];
        }
        $dibuat = isset($paket['dibuat']) && is_string($paket['dibuat']) && strtotime($paket['dibuat']) ? date('Y-m-d H:i:s', strtotime($paket['dibuat'])) : null;
        $row   = [
            'jenis'         => $jenis,
            'sumber'        => mb_substr((string) ($paket['sumber'] ?? ''), 0, 30),
            'jumlah'        => (int) ($paket['jumlah'] ?? 0),
            'payload'       => json_encode($paket['data'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'dibuat_kantor' => $dibuat,
            'ts_kirim'      => (int) ($paket['_ts'] ?? time()),
            'diterima_pada' => date('Y-m-d H:i:s'),
        ];
        $id = $db->table('sipp_data')->select('id')->where('jenis', $jenis)->get()->getRow('id');
        $id ? $db->table('sipp_data')->where('id', $id)->update($row) : $db->table('sipp_data')->insert($row);

        $applied = '';
        $need    = [];
        if (self::enabled() && is_array($paket['data'] ?? null)) {
            if ($jenis === 'statistik_perkara' && setting('sipp_api_statistik', '1') === '1') {
                $applied = $this->applyStats($paket['data']);
            } elseif ($jenis === 'panggilan_ghaib' && setting('sipp_api_ghaib', '1') === '1') {
                [$applied, $need] = $this->applyGhaib($paket['data']);
            }
        }
        cache()->clean(); // halaman publik & Beranda memakai data terbaru

        $r = ['jenis' => $jenis, 'jumlah' => $row['jumlah'], 'diterapkan' => $applied];
        if ($jenis === 'panggilan_ghaib') {
            $r['butuh_relaas'] = $need;
        }

        return $r;
    }

    /** Data terakhir satu jenis: ['data','sumber','jumlah','diperbarui','diterima_pada'] atau null. */
    public function get(string $jenis): ?array
    {
        $r = db_connect()->table('sipp_data')->where('jenis', $jenis)->get()->getRowArray();
        if (! $r) {
            return null;
        }

        return ['jenis' => $jenis, 'sumber' => $r['sumber'], 'jumlah' => (int) $r['jumlah'], 'diperbarui' => $r['dibuat_kantor'],
            'diterima_pada' => $r['diterima_pada'], 'data' => json_decode((string) $r['payload'], true)];
    }

    /** Ringkasan kiriman terakhir per jenis (untuk Pengaturan & Dashboard). */
    public function status(): array
    {
        try {
            $rows = db_connect()->table('sipp_data')->select('jenis, sumber, jumlah, dibuat_kantor, diterima_pada')->get()->getResultArray();
        } catch (\Throwable) {
            return [];
        }

        return array_column($rows, null, 'jenis');
    }

    /**
     * Jadwal sidang satu tanggal dari kiriman terakhir (format tabel hearings),
     * atau null bila kiriman tidak mencakup tanggal itu.
     */
    public function hearingsFor(string $date): ?array
    {
        $d = $this->get('jadwal_sidang');
        $p = $d['data'] ?? null;
        if (! is_array($p) || ! is_array($p['jadwal'] ?? null) || empty($p['dari']) || empty($p['sampai'])
            || $date < (string) $p['dari'] || $date > (string) $p['sampai']) {
            return null;
        }
        $rows = [];
        foreach ($p['jadwal'] as $j) {
            if (! is_array($j) || ($j['tanggal_sidang'] ?? '') !== $date) {
                continue;
            }
            $jam = trim((string) ($j['jam_mulai'] ?? ''));
            if ($jam !== '' && ! empty($j['jam_selesai'])) {
                $jam .= ' – ' . $j['jam_selesai'];
            }
            $agenda = trim((string) ($j['agenda'] ?? ''));
            if (! empty($j['ditunda'])) {
                $agenda .= ($agenda !== '' ? ' ' : '') . '(ditunda)';
            }
            $room = trim((string) ($j['ruangan'] ?? ''));
            if (! empty($j['sidang_keliling'])) {
                $room .= ($room !== '' ? ' · ' : '') . 'Sidang Keliling';
            }
            $rows[] = [
                'hearing_time' => mb_substr($jam, 0, 20),
                'case_number'  => mb_substr(trim((string) ($j['nomor_perkara'] ?? '')), 0, 80),
                'agenda'       => mb_substr($agenda, 0, 255),
                'room'         => mb_substr($room, 0, 100),
                'case_type'    => mb_substr(trim((string) ($j['jenis_perkara'] ?? '')), 0, 100),
            ];
        }
        usort($rows, static fn ($a, $b) => [$a['hearing_time'] === '' ? '99' : $a['hearing_time'], $a['case_number']] <=> [$b['hearing_time'] === '' ? '99' : $b['hearing_time'], $b['case_number']]);

        return $rows;
    }

    /** Statistik → case_stats per bulan (tahun kiriman). */
    private function applyStats(array $d): string
    {
        $year = (int) ($d['tahun'] ?? 0);
        if ($year < 2000 || $year > 2100 || ! is_array($d['per_bulan'] ?? null)) {
            return '';
        }
        $db = db_connect();
        $n  = 0;
        foreach ($d['per_bulan'] as $b) {
            $m = (int) ($b['bulan'] ?? 0);
            if ($m < 1 || $m > 12) {
                continue;
            }
            $row = ['carried_over' => max(0, (int) ($b['sisa_awal'] ?? 0)), 'received' => max(0, (int) ($b['masuk'] ?? 0)),
                'decided' => max(0, (int) ($b['putus'] ?? 0)), 'remaining' => (int) ($b['sisa_akhir'] ?? 0)];
            $id = $db->table('case_stats')->select('id')->where(['year' => $year, 'month' => $m])->get()->getRow('id');
            $id ? $db->table('case_stats')->where('id', $id)->update($row) : $db->table('case_stats')->insert($row + ['year' => $year, 'month' => $m]);
            $n++;
        }

        return $n . ' bulan statistik ' . $year;
    }

    /**
     * Panggilan ghaib → tabel ghaib. Perkara dicocokkan lewat nomor perkara; relaas (PDF) yang
     * diunggah manual tidak diubah. Hasil: [ringkasan, daftar sha256 relaas yang perlu dikirim]. Baris buatan SIPP yang tidak ada lagi di kiriman (sudah putus)
     * disembunyikan.
     */
    private function applyGhaib(array $d): array
    {
        if (! is_array($d['perkara'] ?? null)) {
            return ['', []];
        }
        $db     = db_connect();
        $now    = date('Y-m-d H:i:s');
        $hasSrc = $db->fieldExists('source', 'ghaib');
        $seen   = [];
        $relaas = []; // nomor perkara => sha256 relaas terbaru di SIPP
        $n      = 0;
        foreach ($d['perkara'] as $p) {
            $no = trim((string) ($p['nomor_perkara'] ?? ''));
            if ($no === '' || mb_strlen($no) > 80) {
                continue;
            }
            $seen[] = $no;
            $sha    = strtolower((string) ($p['relaas']['sha256'] ?? ''));
            if (preg_match('/^[0-9a-f]{64}$/', $sha)) {
                $relaas[$no] = $sha;
            }
            $date   = (string) ($p['sidang_berikutnya'] ?? '');
            $date   = preg_match('/^\d{4}-\d{2}-\d{2}/', $date) ? substr($date, 0, 10) : null;
            $name   = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($p['nama_pihak'] ?? ''))));
            $old    = $db->table('ghaib')->where('case_number', $no)->orderBy('id', 'DESC')->get(1)->getRowArray();
            if ($old) {
                $upd = ['updated_at' => $now];
                if ($date) {
                    $upd['hearing_date'] = $date;
                }
                if ($hasSrc && ($old['source'] ?? '') === 'sipp') {
                    $upd['is_active'] = 1;
                    if ($name !== '') {
                        $upd['party_name'] = mb_substr($name, 0, 150);
                    }
                }
                $db->table('ghaib')->where('id', $old['id'])->update($upd);
                $n++;
            } elseif ($date) { // pengumuman ghaib perlu tanggal sidang
                $ins = ['party_name' => mb_substr($name !== '' ? $name : 'Tergugat/Termohon', 0, 150), 'case_number' => $no,
                    'hearing_date' => $date, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
                if ($hasSrc) {
                    $ins['source'] = 'sipp';
                }
                $db->table('ghaib')->insert($ins);
                $n++;
            }
        }
        if ($hasSrc) {
            $q = $db->table('ghaib')->where('source', 'sipp')->where('is_active', 1);
            if ($seen) {
                $q->whereNotIn('case_number', $seen);
            }
            $q->update(['is_active' => 0, 'updated_at' => $now]);
        }

        // relaas yang perlu dikirim kantor: baris belum punya relaas, atau relaas otomatisnya sudah berganti
        $need = [];
        if ($relaas && self::relaasEnabled()) {
            foreach ($relaas as $no => $sha) {
                $row = $db->table('ghaib')->where('case_number', $no)->orderBy('id', 'DESC')->get(1)->getRowArray();
                if ($row && $this->relaasNeeded($row, $sha)) {
                    $need[] = $sha;
                }
            }
        }

        return [$n . ' panggilan ghaib', array_values(array_unique($need))];
    }

    /** Batas body POST dari php.ini (byte), 0 = tanpa batas. */
    public static function phpPostMax(): int
    {
        $v = trim((string) ini_get('post_max_size'));
        $n = (int) $v;
        $u = strtolower(substr($v, -1));

        return $u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n));
    }

    /** Ukuran PDF relaas terbesar yang bisa diterima (base64 + amplop JSON harus muat di batas kiriman). */
    public static function maxRelaasBytes(): int
    {
        $body = (int) config('Sipp')->maksUkuranRelaas;
        $php  = self::phpPostMax();
        if ($php > 0) {
            $body = min($body, $php);
        }

        return (int) max(0, min(Uploader::MAX_PDF_MB * 1048576, floor(($body - 8192) * 3 / 4)));
    }

    public static function relaasEnabled(): bool
    {
        return setting('sipp_api_relaas', '1') === '1' && db_connect()->fieldExists('relaas_file_id', 'ghaib');
    }

    /** Perlu berkas ini? Ya bila baris belum punya relaas, atau relaas otomatisnya berbeda. */
    private function relaasNeeded(array $row, string $sha): bool
    {
        if ((int) ($row['file_id'] ?? 0) === 0) {
            return true;
        }

        return $this->relaasSlot($row) && ($row['relaas_sha256'] ?? '') !== $sha;
    }

    /** Baris boleh diisi relaas otomatis: belum ada relaas, atau relaasnya memang dipasang otomatis. */
    private function relaasSlot(array $row): bool
    {
        $file = (int) ($row['file_id'] ?? 0);

        return $file === 0 || $file === (int) ($row['relaas_file_id'] ?? 0);
    }

    /**
     * Satu berkas relaas: {nomor_perkara, nama, tanggal, sha256, pdf (base64)}.
     * Diperiksa sidik & tanda PDF, disimpan lewat Uploader, lalu ditautkan ke baris ghaib.
     * @throws RuntimeException 422 bila berkas tidak valid
     */
    private function applyRelaas(array $d): string
    {
        if (! self::enabled() || setting('sipp_api_ghaib', '1') !== '1' || ! self::relaasEnabled()) {
            return 'relaas diabaikan (dimatikan di Pengaturan website)';
        }
        $no  = trim((string) ($d['nomor_perkara'] ?? ''));
        $sha = strtolower((string) ($d['sha256'] ?? ''));
        if ($no === '' || mb_strlen($no) > 80 || ! preg_match('/^[0-9a-f]{64}$/', $sha) || ! is_string($d['pdf'] ?? null)) {
            throw new RuntimeException('Data relaas tidak lengkap.', 422);
        }
        $db  = db_connect();
        $row = $db->table('ghaib')->where('case_number', $no)->orderBy('id', 'DESC')->get(1)->getRowArray();
        if (! $row) {
            return 'relaas ' . $no . ': perkara tidak ada di Panggilan Ghaib';
        }
        if (! $this->relaasSlot($row)) {
            return 'relaas ' . $no . ': sudah ada relaas unggahan manual (tidak ditimpa)';
        }
        if (! $this->relaasNeeded($row, $sha)) {
            return 'relaas ' . $no . ': sudah ada';
        }
        $pdf = base64_decode($d['pdf'], true);
        if ($pdf === false || $pdf === '' || ! hash_equals($sha, hash('sha256', $pdf))) {
            throw new RuntimeException('Berkas relaas rusak di perjalanan (sidik tidak cocok).', 422);
        }
        if (strlen($pdf) > Uploader::MAX_PDF_MB * 1048576 || ! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Berkas relaas harus PDF (maks. ' . Uploader::MAX_PDF_MB . ' MB).', 422);
        }
        $tmp = tempnam(WRITEPATH . 'cache', 'rls');
        file_put_contents($tmp, $pdf);
        unset($pdf);

        try {
            $name = 'Relaas ' . preg_replace('/[^\w.\-]+/u', '_', $no) . '.pdf';
            $id   = service('uploader')->storePdfPath($tmp, $name, true);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Berkas relaas ditolak: ' . $e->getMessage(), 422);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
        $old = (int) ($row['relaas_file_id'] ?? 0);
        $db->table('ghaib')->where('id', $row['id'])->update([
            'file_id' => $id, 'relaas_file_id' => $id, 'relaas_sha256' => $sha, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($old && $old !== $id) {
            service('uploader')->deleteFile($old); // relaas otomatis lama diganti yang baru
        }

        return 'relaas ' . $no . ' tersimpan';
    }

    // ------------------------------------------------------------------ paket aplikasi kantor

    /** Pastikan website punya kunci (dibuat bila belum ada). @return array{0:string,1:string,2:string} */
    public static function ensureKeys(): array
    {
        [$key, $secret, $from] = self::credentials();
        if (strlen($key) >= 16 && strlen($secret) >= 32) {
            return [$key, $secret, $from];
        }
        $key    = bin2hex(random_bytes(16));
        $secret = bin2hex(random_bytes(32));
        service('siteSettings')->set(['sipp_api_key' => $key, 'sipp_api_secret' => $secret]);

        return [$key, $secret, 'pengaturan'];
    }

    /**
     * Buat zip aplikasi SIPP API Kantor (deploy/sipp-api-kantor) yang config.php-nya sudah berisi
     * alamat penerima & kunci website ini. Mengembalikan path berkas zip sementara.
     */
    public function buildKantorZip(): string
    {
        $src = ROOTPATH . 'deploy' . DIRECTORY_SEPARATOR . 'sipp-api-kantor';
        if (! is_file($src . DIRECTORY_SEPARATOR . 'config.php')) {
            throw new RuntimeException('Berkas aplikasi SIPP API Kantor (deploy/sipp-api-kantor) tidak ditemukan. Perbarui website.');
        }
        if (! class_exists(\ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip belum aktif (php.ini: extension=zip).');
        }
        [$key, $secret] = self::ensureKeys();
        $preset = [
            'website' => ['url' => site_url('api/sipp/terima'), 'api_key' => $key, 'secret' => $secret, 'verify_ssl' => true, 'timeout' => 30],
        ];
        $sipp = rtrim((string) setting('sipp_url', ''), '/');
        if ($sipp !== '' && preg_match('#^https?://#i', $sipp)) {
            $preset['cadangan_api_jadwal'] = $sipp . '/api/jadwal_sidang';
        }
        // format sama dengan "Unduh config.php" di dashboard kantor (dibaca lib/init.php sebagai $CONFIG_DASHBOARD)
        $config = rtrim((string) file_get_contents($src . DIRECTORY_SEPARATOR . 'config.php')) . "\n\n"
            . '/* === PENGATURAN DARI DASHBOARD (dibuat otomatis ' . date('Y-m-d H:i') . ' oleh website ' . parse_url(base_url(), PHP_URL_HOST) . ", jangan diubah manual) === */\n"
            . '$CONFIG_DASHBOARD = ' . var_export($preset, true) . ";\n";

        $tmp = tempnam(WRITEPATH . 'cache', 'sippk');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat berkas zip.');
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            /** @var \SplFileInfo $f */
            $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($f->getPathname(), strlen($src) + 1));
            // jangan ikut mengemas data/log yang mungkin ada di server ini
            if (preg_match('#^(data|logs)/(?!index\.html$|\.htaccess$)#', $rel)) {
                continue;
            }
            $name = 'sipp-api-kantor/' . $rel;
            $rel === 'config.php' ? $zip->addFromString($name, $config) : $zip->addFile($f->getPathname(), $name);
        }
        $zip->close();

        return $tmp;
    }
}
