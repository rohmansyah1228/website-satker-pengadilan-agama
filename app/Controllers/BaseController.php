<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /** @var CLIRequest|IncomingRequest */
    protected $request;

    protected $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = db_connect();
    }

    /** Render halaman publik dengan layout template Badilag. */
    protected function render(string $view, array $data = []): string
    {
        $path            = '/' . trim($this->request->getPath(), '/');
        $data['menu']    = service('menuTree')->tree();
        $data['activeTop'] = service('menuTree')->activeTop($path);
        $data['path']    = $path;
        $data['visitors'] = service('visitorStats')->summary();
        // Pita "Informasi Serta Merta": tampil sejak terbit sampai expires_at (bila kosong: 7 hari)
        $now  = date('Y-m-d H:i:s');
        $week = date('Y-m-d H:i:s', strtotime('-7 days'));
        $base = fn () => $this->db->table('posts')->where(['category' => 'serta-merta', 'status' => 'published'])->where('published_at <=', $now);
        try {
            $data['alert'] = $base()->groupStart()
                ->groupStart()->where('expires_at', null)->where('published_at >=', $week)->groupEnd()
                ->orWhere('expires_at >', $now)
                ->groupEnd()->orderBy('published_at', 'DESC')->get(1)->getRowArray();
        } catch (\Throwable $e) {
            // kolom expires_at belum ada (pembaruan database belum dijalankan)
            $data['alert'] = $base()->where('published_at >=', $week)->orderBy('published_at', 'DESC')->get(1)->getRowArray();
        }

        return view($view, $data);
    }
}
