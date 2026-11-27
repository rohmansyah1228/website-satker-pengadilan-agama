<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Statistik kunjungan rinci per hari: jumlah per perangkat, OS, browser, provinsi, kota (disimpan maks. 30 hari). */
class VisitorDetails extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('visitor_details')) {
            return;
        }
        $this->forge->addField([
            'date'     => ['type' => 'DATE'],
            'kind'     => ['type' => 'VARCHAR', 'constraint' => 12],
            'name'     => ['type' => 'VARCHAR', 'constraint' => 120],
            'visitors' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey(['date', 'kind', 'name']);
        $this->forge->createTable('visitor_details', true);
    }

    public function down()
    {
        $this->forge->dropTable('visitor_details', true);
    }
}
