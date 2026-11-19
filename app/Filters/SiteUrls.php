<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Mengganti penanda {{SITE_URL}}/ di isi halaman dengan alamat situs yang sedang dipakai. */
class SiteUrls implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $type = $response->getHeaderLine('Content-Type');
        $body = $response->getBody();
        if (is_string($body) && ($type === '' || str_contains($type, 'html') || str_contains($type, 'json')) && str_contains($body, '{{SITE_URL}}/')) {
            $base = rtrim(base_url(), '/') . '/';
            // dalam JSON garis miring bisa ditulis \/
            $response->setBody(str_replace(['{{SITE_URL}}/', '{{SITE_URL}}\\/'], [$base, str_replace('/', '\\/', $base)], $body));
        }

        return $response;
    }
}
