<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ucapan Selamat & Belasungkawa.
 * Banner/poster yang tampil bergantian (carousel) di Beranda, 2 kolom
 * bersebelahan: Ucapan Selamat & Belasungkawa, masing-masing memutar
 * entri terbaru. Dikelola di Publikasi → Ucapan & Belasungkawa.
 */
class Greetings extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('greetings')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'kind'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'selamat'], // selamat | belasungkawa
                'title'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'image'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'image_alt'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'link_url'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'published_at' => ['type' => 'DATE', 'null' => true],
                'sort'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['kind', 'is_active']);
            $this->forge->addKey('published_at');
            $this->forge->createTable('greetings', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('greetings', true);
    }
}
