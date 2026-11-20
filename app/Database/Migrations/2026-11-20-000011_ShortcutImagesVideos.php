<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * - Pintasan: gambar (selain ikon) & kelompok (mis. "Zona Integritas", "Aplikasi Pendukung").
 * - Galeri Video: tabel videos + menu "Galeri Video" di bawah Berita & Publikasi.
 */
class ShortcutImagesVideos extends Migration
{
    public function up()
    {
        $add = [];
        if (! $this->db->fieldExists('image', 'shortcuts')) {
            $add['image'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true];
        }
        if (! $this->db->fieldExists('grp', 'shortcuts')) {
            $add['grp'] = ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true];
        }
        if ($add) {
            $this->forge->addColumn('shortcuts', $add);
        }

        if (! $this->db->tableExists('videos')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'title'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'url'          => ['type' => 'VARCHAR', 'constraint' => 500],
                'description'  => ['type' => 'TEXT', 'null' => true],
                'image'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'published_at' => ['type' => 'DATE', 'null' => true],
                'sort'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('published_at');
            $this->forge->createTable('videos', true);
        }

        // Menu: Berita & Publikasi → [Berita & Publikasi, Galeri Video]
        $menus = $this->db->table('menus');
        if (! $this->db->table('menus')->where('url', '/galeri-video')->countAllResults()) {
            $top = $this->db->table('menus')->where('parent_id', null)->where('url', '/berita')->get()->getRowArray();
            if ($top) {
                if (! $this->db->table('menus')->where('parent_id', $top['id'])->countAllResults()) {
                    $menus->insert(['parent_id' => $top['id'], 'label' => 'Berita & Publikasi', 'type' => 'route', 'url' => '/berita', 'sort' => 1, 'is_active' => 1, 'is_required' => 0, 'new_tab' => 0]);
                }
                $sort = (int) ($this->db->table('menus')->selectMax('sort')->where('parent_id', $top['id'])->get()->getRow()->sort ?? 0);
                $this->db->table('menus')->insert(['parent_id' => $top['id'], 'label' => 'Galeri Video', 'type' => 'route', 'url' => '/galeri-video', 'sort' => $sort + 1, 'is_active' => 1, 'is_required' => 0, 'new_tab' => 0]);
            }
        }
        try {
            \App\Libraries\MenuTree::flush();
        } catch (\Throwable) {
        }
    }

    public function down()
    {
        $this->db->table('menus')->where('url', '/galeri-video')->delete();
        $this->forge->dropTable('videos', true);
        $this->forge->dropColumn('shortcuts', ['image', 'grp']);
    }
}
