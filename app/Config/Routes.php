<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$admin = trim(config('App')->adminPath ?: 'panel-satker', '/');

// Pemasangan lewat browser (tertutup otomatis setelah terpasang)
$routes->match(['GET', 'POST'], 'pasang', 'Install::index');

// ===================== PANEL ADMIN =====================
$routes->group($admin, ['namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attempt');
    $routes->get('login/mfa', 'Auth::mfa');
    $routes->post('login/mfa', 'Auth::mfaVerify');
    $routes->post('logout', 'Auth::logout');

    $routes->group('', ['filter' => 'adminauth'], static function ($routes) {
        $routes->get('/', 'Dashboard::index');

        $routes->get('halaman', 'Pages::index');
        $routes->get('halaman/baru', 'Pages::create');
        $routes->post('halaman/baru', 'Pages::store');
        $routes->get('halaman/(:num)', 'Pages::edit/$1');
        $routes->post('halaman/(:num)', 'Pages::update/$1');
        $routes->post('halaman/(:num)/hapus', 'Pages::delete/$1');

        $routes->get('menu', 'Menus::index');
        $routes->get('menu/baru', 'Menus::create');
        $routes->post('menu/baru', 'Menus::store');
        $routes->get('menu/(:num)', 'Menus::edit/$1');
        $routes->post('menu/(:num)', 'Menus::update/$1');
        $routes->post('menu/susun', 'Menus::arrange');
        $routes->post('menu/(:num)/pindah', 'Menus::move/$1');
        $routes->post('menu/(:num)/hapus', 'Menus::delete/$1');

        $routes->get('beranda', 'Home::index');
        $routes->get('beranda/baru', 'Home::create');
        $routes->post('beranda/tata-letak', 'Home::layout');
        $routes->post('beranda/susunan', 'Home::arrange');
        $routes->post('beranda/baru', 'Home::store');
        $routes->get('beranda/(:num)', 'Home::edit/$1');
        $routes->post('beranda/(:num)', 'Home::update/$1');
        $routes->post('beranda/(:num)/pindah', 'Home::move/$1');
        $routes->post('beranda/(:num)/status', 'Home::toggle/$1');
        $routes->post('beranda/(:num)/salin', 'Home::duplicate/$1');
        $routes->post('beranda/(:num)/hapus', 'Home::delete/$1');

        $routes->get('pengaturan', 'Settings::index');
        $routes->post('pengaturan', 'Settings::save');

        $routes->get('pengguna', 'Users::index');
        $routes->get('pengguna/baru', 'Users::create');
        $routes->post('pengguna/baru', 'Users::store');
        $routes->get('pengguna/(:num)', 'Users::edit/$1');
        $routes->post('pengguna/(:num)', 'Users::update/$1');
        $routes->post('pengguna/(:num)/hapus', 'Users::delete/$1');
        $routes->post('pengguna/(:num)/reset-2fa', 'Users::reset2fa/$1');

        $routes->get('akun', 'Account::index');
        $routes->post('akun/password', 'Account::password');
        $routes->get('akun/2fa', 'Account::twofa');
        $routes->post('akun/2fa/aktifkan', 'Account::enable2fa');
        $routes->post('akun/2fa/nonaktifkan', 'Account::disable2fa');

        $routes->get('log', 'Logs::index');
        $routes->post('editor/gambar', 'Editor::image');
        $routes->post('editor/pdf', 'Editor::pdf');
        $routes->get('editor/tautan', 'Editor::links');
        $routes->post('cache/bersihkan', 'Dashboard::clearCache');
        $routes->post('pembaruan-database', 'Dashboard::migrate');

        // Modul data generik (slider, dokumen, pegawai, dst.) — lihat app/Config/AdminResources.php
        $routes->get('data/(:segment)', 'Crud::index/$1');
        $routes->get('data/(:segment)/baru', 'Crud::create/$1');
        $routes->post('data/(:segment)/baru', 'Crud::store/$1');
        $routes->get('data/(:segment)/(:num)', 'Crud::edit/$1/$2');
        $routes->post('data/(:segment)/(:num)', 'Crud::update/$1/$2');
        $routes->post('data/(:segment)/(:num)/hapus', 'Crud::delete/$1/$2');
    });
});

// ===================== SITUS PUBLIK =====================
$routes->get('berkas/(:segment)', 'Files::show/$1');
$routes->get('favicon.ico', 'Files::favicon');

$routes->group('', ['filter' => 'visitor'], static function ($routes) {
    $routes->get('/', 'Site::home');
    $routes->get('cari', 'Search::index');
    $routes->get('berita', 'Posts::index');
    $routes->get('berita/kategori/(:segment)', 'Posts::index/$1');
    $routes->get('berita/(:segment)', 'Posts::show/$1');
    $routes->get('(:segment)', 'Site::section/$1');
    $routes->get('(:segment)/(:segment)', 'Site::page/$1/$2');
    $routes->get('(:segment)/(:segment)/(:num)', 'Site::employee/$1/$2/$3');
});

$routes->set404Override('App\Controllers\Site::notFound');
