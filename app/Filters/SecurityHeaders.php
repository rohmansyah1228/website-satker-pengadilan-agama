<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Header keamanan tambahan; menyembunyikan informasi versi PHP (Bab II A.3). */
class SecurityHeaders implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        if ($request instanceof \CodeIgniter\HTTP\IncomingRequest && $request->isSecure()) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
