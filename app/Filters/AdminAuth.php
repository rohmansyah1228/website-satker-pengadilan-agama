<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Wajib login untuk panel admin; paksa aktivasi 2FA bila diwajibkan. */
class AdminAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = service('adminAuth')->user();
        if (! $user) {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401);
            }
            session()->set('admin_intended', current_url());

            return redirect()->to(admin_url('login'));
        }

        $path    = trim($request->getPath(), '/');
        $allowed = str_ends_with($path, '/akun/2fa') || str_contains($path, '/akun/2fa/') || str_ends_with($path, '/logout');
        if (setting('require_2fa', '1') === '1' && ! (int) $user['totp_enabled'] && ! $allowed) {
            return redirect()->to(admin_url('akun/2fa'))->with('warning', 'Demi keamanan (SK 2722/2026 Bab II B.3), aktifkan autentikasi dua faktor (MFA) terlebih dahulu.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'no-store, private');
        $response->setHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
