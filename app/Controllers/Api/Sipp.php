<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\SippApi;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Penerima data dari SIPP API Kantor.
 *   POST /api/sipp/terima   → dipanggil server kantor (API key + tanda tangan HMAC; tanpa CSRF)
 *   GET  /api/sipp/{jenis}  → data terakhir (JSON) untuk tampilan lain
 */
class Sipp extends BaseController
{
    public function terima(): ResponseInterface
    {
        $api = new SippApi();
        if ((int) $this->request->getServer('CONTENT_LENGTH') > 4 * 1048576) {
            $mem = SippApi::iniBytes((string) ini_get('memory_limit'));
            if ($mem > 0 && $mem < 268435456) {
                @ini_set('memory_limit', '256M'); // kiriman relaas besar (body + JSON + PDF di memori)
            }
            @set_time_limit(120);
        }
        // kiriman yang melebihi post_max_size dipotong PHP: beri pesan jelas, bukan "tanda tangan tidak valid"
        $len = (int) $this->request->getServer('CONTENT_LENGTH');
        $max = SippApi::phpPostMax();
        if ($max > 0 && $len > $max) {
            return $this->fail(413, 'Kiriman terlalu besar untuk batas PHP hosting (post_max_size ' . ini_get('post_max_size') . ').');
        }
        try {
            $paket = $api->verify(
                (string) $this->request->getBody(),
                $this->request->getHeaderLine('X-Api-Key'),
                $this->request->getHeaderLine('X-Timestamp'),
                $this->request->getHeaderLine('X-Signature'),
            );
            $r = $api->store($paket);
        } catch (\RuntimeException $e) {
            $code = in_array($e->getCode(), [400, 401, 409, 413, 422, 503], true) ? $e->getCode() : 500;
            if ($code === 401) {
                log_message('warning', 'SIPP API: kiriman ditolak ({msg}) dari {ip}', ['msg' => $e->getMessage(), 'ip' => $this->request->getIPAddress()]);
            }

            return $this->fail($code, $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SIPP API: ' . $e->getMessage());

            return $this->fail(500, 'Gagal menyimpan data.');
        }

        return $this->response->setJSON(['status' => 'ok', 'jenis' => $r['jenis'], 'jumlah' => $r['jumlah'],
            'diterapkan' => $r['diterapkan'], 'waktu' => date('Y-m-d H:i:s')]
            + (isset($r['butuh_relaas']) ? ['butuh_relaas' => $r['butuh_relaas'], 'maks_relaas_byte' => SippApi::maxRelaasBytes()] : []));
    }

    public function ambil(string $jenis = ''): ResponseInterface
    {
        if ($jenis === 'relaas_ghaib' || ! in_array($jenis, config('Sipp')->jenis, true)) {
            return $this->fail(404, 'Data tidak ditemukan.');
        }
        $d = (new SippApi())->get($jenis);
        if ($d === null) {
            return $this->fail(404, 'Data belum pernah dikirim dari SIPP.');
        }
        // data pribadi tidak dibuka lewat API publik (nama pihak di jadwal, alamat pihak ghaib)
        if ($jenis === 'jadwal_sidang' && is_array($d['data']['jadwal'] ?? null)) {
            foreach ($d['data']['jadwal'] as &$j) {
                unset($j['pihak1'], $j['pihak2']);
            }
            unset($j);
        }
        if ($jenis === 'panggilan_ghaib' && is_array($d['data']['perkara'] ?? null)) {
            foreach ($d['data']['perkara'] as &$p) {
                unset($p['alamat_pihak'], $p['relaas']);
            }
            unset($p);
        }

        return $this->response->setHeader('Cache-Control', 'public, max-age=300')->setJSON(['status' => 'ok'] + $d);
    }

    private function fail(int $code, string $msg): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(['status' => 'error', 'pesan' => $msg]);
    }
}
