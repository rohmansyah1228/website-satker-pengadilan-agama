<?php

namespace App\Controllers\Admin;

/** Panduan penggunaan (manual book) di dalam panel admin. Dapat dibuka semua pengguna admin. */
class Guide extends AdminController
{
    public function index()
    {
        return $this->view('guide', ['title' => 'Panduan Penggunaan']);
    }
}
