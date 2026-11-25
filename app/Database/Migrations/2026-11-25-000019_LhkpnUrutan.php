<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** LHKPN: kolom Urutan (0 = otomatis menurut jabatan: Ketua, Wakil Ketua, Hakim, …). */
class LhkpnUrutan extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('lhkpn') && ! $this->db->fieldExists('sort', 'lhkpn')) {
            $this->forge->addColumn('lhkpn', ['sort' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0]]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('sort', 'lhkpn')) {
            $this->forge->dropColumn('lhkpn', 'sort');
        }
    }
}
