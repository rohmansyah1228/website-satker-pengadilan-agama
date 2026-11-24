<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SIPP API Kantor: tabel penampung kiriman (satu baris per jenis data) +
 * penanda baris Panggilan Ghaib yang dibuat otomatis dari SIPP.
 */
class SippApi extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('sipp_data')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'jenis'         => ['type' => 'VARCHAR', 'constraint' => 50],
                'sumber'        => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'jumlah'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'payload'       => ['type' => 'LONGTEXT'],
                'dibuat_kantor' => ['type' => 'DATETIME', 'null' => true],
                'ts_kirim'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'diterima_pada' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('jenis');
            $this->forge->createTable('sipp_data', true);
        }
        if ($this->db->tableExists('ghaib') && ! $this->db->fieldExists('source', 'ghaib')) {
            $this->forge->addColumn('ghaib', ['source' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true]]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('sipp_data', true);
        if ($this->db->fieldExists('source', 'ghaib')) {
            $this->forge->dropColumn('ghaib', 'source');
        }
    }
}
