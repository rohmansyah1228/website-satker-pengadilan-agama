<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penanda asal berita hasil impor dari website lama (mis. "joomla:3064").
 * Dipakai agar impor bisa diulang tanpa dobel, dan alamat berita lama dialihkan (301) ke alamat baru.
 */
class PostSourceRef extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('source_ref', 'posts')) {
            $this->forge->addColumn('posts', ['source_ref' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true]]);
            $this->db->query('CREATE INDEX posts_source_ref ON ' . $this->db->prefixTable('posts') . ' (source_ref)');
        }
    }

    public function down()
    {
        $this->forge->dropColumn('posts', 'source_ref');
    }
}
