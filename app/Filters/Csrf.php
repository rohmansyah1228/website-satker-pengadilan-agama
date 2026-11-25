<?php

namespace App\Filters;

use CodeIgniter\Filters\CSRF as BaseCsrf;
use CodeIgniter\HTTP\RequestInterface;

/**
 * CSRF hanya untuk permintaan yang mengubah data (POST/PUT/PATCH/DELETE).
 *
 * Filter bawaan memulai sesi pada SETIAP permintaan (termasuk GET halaman publik),
 * sehingga setiap pengunjung mendapat cookie sesi + berkas sesi di server dan halaman
 * publik tidak bisa di-cache (Cloudflare/peramban). GET tidak pernah diperiksa CSRF,
 * jadi dilewati tanpa membuka sesi. Formulir admin tetap mendapat token lewat csrf_field().
 */
class Csrf extends BaseCsrf
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (in_array(strtoupper($request->getMethod()), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        return parent::before($request, $arguments);
    }
}
