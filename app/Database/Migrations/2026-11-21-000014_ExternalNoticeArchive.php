<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Arsip pengumuman Badilag & MA: setiap pengumuman disimpan sekali (judul, tanggal, tautan ke sumber).
 * Pengambilan berikutnya hanya menambah yang belum ada (dicocokkan lewat url_hash).
 */
class ExternalNoticeArchive extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'source'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 300],
            'url'          => ['type' => 'VARCHAR', 'constraint' => 700],
            'url_hash'     => ['type' => 'CHAR', 'constraint' => 40],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('url_hash');
        $this->forge->addKey(['source', 'published_at']);
        $this->forge->addKey('published_at');
        $this->forge->createTable('external_notices', true);
        cache()->delete('ann_feed');
    }

    public function down()
    {
        $this->forge->dropTable('external_notices', true);
    }
}
