<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ActivityLog;
use App\Libraries\MenuTree;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class AdminController extends BaseController
{
    protected ?array $user = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->user = service('adminAuth')->user();
        // setiap perubahan di panel admin (POST) → kosongkan cache Beranda & pita pengumuman setelah tersimpan
        if ($request->getMethod() === 'POST' || strtolower($request->getMethod()) === 'post') {
            \CodeIgniter\Events\Events::on('post_system', static function () {
                cache()->deleteMatching('home_blocks_*');
                cache()->delete('site_alert');
            });
        }
    }

    protected function view(string $view, array $data = []): string
    {
        $data['user']      = $this->user;
        $data['resources'] = config('AdminResources')->resources;

        return view('admin/' . $view, $data);
    }

    /** Hentikan bila pengguna tidak memiliki peran yang dibutuhkan. */
    protected function authorize(array $roles, bool $superOnly = false): void
    {
        if (! can($roles, $superOnly)) {
            throw PageNotFoundException::forPageNotFound('Anda tidak memiliki akses ke modul ini.');
        }
    }

    protected function log(string $action, string $subject): void
    {
        ActivityLog::add($action, $subject, $this->user);
    }

    /** Bersihkan cache konten publik setelah perubahan. */
    protected function flushPublic(): void
    {
        MenuTree::flush();
        cache()->deleteMatching('home_blocks_*');
        cache()->delete('site_alert');
        service('siteSettings')->flush();
        cache()->delete('visitor_summary');
    }

    /** Peran yang boleh mengelola sebuah bagian (section) halaman. */
    protected function sectionRoles(string $section): array
    {
        return config('SiteStructure')->sections[$section]['roles'] ?? [];
    }
}
