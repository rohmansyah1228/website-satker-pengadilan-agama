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
        $data['alert']   = $this->db->table('posts')->where(['category' => 'serta-merta', 'status' => 'published'])
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->where('published_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->orderBy('published_at', 'DESC')->get(1)->getRowArray();

        return view($view, $data);
    }
}
