<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Galeri Prestasi: piagam / penghargaan yang diterima satker.
 * Tampil di Beranda (blok "Galeri Prestasi", prestasi terbaru lebih dulu) dan halaman /galeri-prestasi.
 */
class Achievements extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('achievements')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
                'issuer'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'awarded_at'  => ['type' => 'DATE', 'null' => true],
                'image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'image_alt'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'description' => ['type' => 'TEXT', 'null' => true],
                'sort'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['is_active', 'awarded_at']);
            $this->forge->createTable('achievements', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('achievements', true);
    }
}
