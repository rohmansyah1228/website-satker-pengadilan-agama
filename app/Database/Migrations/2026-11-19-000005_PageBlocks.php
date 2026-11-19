<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Isi halaman berbasis blok (teks, tabel, gambar, sematan).
 * Kolom `content` tetap diisi (teks gabungan) untuk pencarian & pemeriksaan SINOVA.
 * Halaman lama (blocks = NULL) otomatis dibaca sebagai satu blok teks.
 */
class PageBlocks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pages', [
            'blocks' => ['type' => 'MEDIUMTEXT', 'null' => true, 'after' => 'content'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pages', 'blocks');
    }
}
