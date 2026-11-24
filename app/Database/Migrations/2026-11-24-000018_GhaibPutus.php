<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Panggilan Ghaib: tanggal putus perkara. Baris dengan decided_at terisi tampil di tabel
 * "Perkara Ghaib yang Sudah Putus" (bukan lagi di daftar panggilan).
 */
class GhaibPutus extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('ghaib') && ! $this->db->fieldExists('decided_at', 'ghaib')) {
            $this->forge->addColumn('ghaib', ['decided_at' => ['type' => 'DATE', 'null' => true]]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('decided_at', 'ghaib')) {
            $this->forge->dropColumn('ghaib', 'decided_at');
        }
    }
}
