<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Website yang baru diunggah (belum ada .env) diarahkan ke halaman pemasangan /pasang. */
class InstallRedirect implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! is_file(ROOTPATH . '.env') && ! is_file(WRITEPATH . 'installed.lock') && ! is_cli()) {
            return redirect()->to(site_url('pasang'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
