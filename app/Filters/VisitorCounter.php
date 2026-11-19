<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class VisitorCounter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if ($response->getStatusCode() === 200 && strtolower($request->getMethod()) === 'get') {
            try {
                service('visitorStats')->hit();
            } catch (\Throwable $e) {
                log_message('error', 'VisitorCounter: ' . $e->getMessage());
            }
        }
    }
}
