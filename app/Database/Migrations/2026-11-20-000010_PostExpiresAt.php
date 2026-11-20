<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Batas waktu pita "Informasi Serta Merta" (kosong = 7 hari sejak terbit). */
class PostExpiresAt extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('expires_at', 'posts')) {
            $this->forge->addColumn('posts', ['expires_at' => ['type' => 'DATETIME', 'null' => true]]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('posts', 'expires_at');
    }
}
