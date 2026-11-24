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
 *   panggilan_ghaib   → tabel ghaib (baris bertanda source = 'sipp'; relaas tetap diunggah manual)
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
        [$key, $secret] = self::credentials();
        if ($key === '' || $secret === '') {
            throw new RuntimeException('Penerima belum dikonfigurasi: tempel kunci di Pengaturan → Integrasi SIPP.', 503);
        }
        if ($body === '' || strlen($body) > (int) $cfg->maksUkuran) {
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
        // kiriman lama yang diputar ulang tidak boleh menimpa data yang lebih baru
        $last = (int) (db_connect()->table('sipp_data')->select('ts_kirim')->where('jenis', $paket['jenis'])->get()->getRow('ts_kirim') ?? 0);
        if ((int) $timestamp < $last) {
            throw new RuntimeException('Kiriman lebih lama dari data yang sudah diterima.', 409);
        }
        $paket['_ts'] = (int) $timestamp;

        return $paket;
    }

    /** Simpan kiriman (menimpa data jenis yang sama), lalu terapkan ke modul website. */
    public function store(array $paket): array
    {
        $db    = db_connect();
        $jenis = $paket['jenis'];
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
        if (self::enabled() && is_array($paket['data'] ?? null)) {
            if ($jenis === 'statistik_perkara' && setting('sipp_api_statistik', '1') === '1') {
                $applied = $this->applyStats($paket['data']);
            } elseif ($jenis === 'panggilan_ghaib' && setting('sipp_api_ghaib', '1') === '1') {
                $applied = $this->applyGhaib($paket['data']);
            }
        }
        cache()->clean(); // halaman publik & Beranda memakai data terbaru

        return ['jenis' => $jenis, 'jumlah' => $row['jumlah'], 'diterapkan' => $applied];
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
     * diunggah manual tidak diubah. Baris buatan SIPP yang tidak ada lagi di kiriman (sudah putus)
     * disembunyikan.
     */
    private function applyGhaib(array $d): string
    {
        if (! is_array($d['perkara'] ?? null)) {
            return '';
        }
        $db     = db_connect();
        $now    = date('Y-m-d H:i:s');
        $hasSrc = $db->fieldExists('source', 'ghaib');
        $seen   = [];
        $n      = 0;
        foreach ($d['perkara'] as $p) {
            $no = trim((string) ($p['nomor_perkara'] ?? ''));
            if ($no === '' || mb_strlen($no) > 80) {
                continue;
            }
            $seen[] = $no;
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

        return $n . ' panggilan ghaib';
    }
}
