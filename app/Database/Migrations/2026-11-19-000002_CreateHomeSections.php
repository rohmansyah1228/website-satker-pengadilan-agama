<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Tabel tampilan Beranda yang dapat diatur dari panel admin + susunan awal sesuai template Badilag. */
class CreateHomeSections extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id'  => ['type' => 'INT', 'null' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'subtitle'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'settings'   => ['type' => 'TEXT', 'null' => true],
            'width'      => ['type' => 'INT', 'default' => 12],
            'bg'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'white'],
            'accent'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'primary'],
            'sort'       => ['type' => 'INT', 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['parent_id', 'sort']);
        $this->forge->createTable('home_sections', true, $this->db->DBDriver === 'MySQLi' ? ['ENGINE' => 'InnoDB'] : []);

        // Susunan awal = tampilan template Badilag
        $now = date('Y-m-d H:i:s');
        $t   = $this->db->table('home_sections');
        $add = function (array $row) use ($t, $now): int {
            $t->insert($row + ['created_at' => $now, 'updated_at' => $now, 'is_active' => 1]);

            return (int) $this->db->insertID();
        };
        $add(['type' => 'slider', 'sort' => 1, 'bg' => 'white', 'settings' => '{}']);
        $add(['type' => 'shortcuts', 'sort' => 2, 'bg' => 'white', 'title' => 'Pintasan Layanan Utama',
            'subtitle' => 'Akses langsung ke sistem informasi perkara, produk pengadilan, dan saluran pengaduan masyarakat.', 'settings' => '{"columns":"4"}']);
        $row = $add(['type' => 'row', 'sort' => 3, 'bg' => 'light', 'settings' => '{}']);
        $add(['type' => 'announcements', 'parent_id' => $row, 'sort' => 1, 'width' => 5, 'accent' => 'primary',
            'title' => 'Pengumuman & Panggilan Ghaib', 'settings' => '{"limit":4,"show_ghaib":1}']);
        $add(['type' => 'news', 'parent_id' => $row, 'sort' => 2, 'width' => 7, 'accent' => 'secondary',
            'title' => 'Berita Pelayanan & Inovasi Satker', 'settings' => '{"category":"","limit":2,"columns":"2"}']);
        $add(['type' => 'survey', 'sort' => 4, 'bg' => 'primary', 'title' => 'Indeks Kepuasan & Integritas Pelayanan',
            'subtitle' => 'Transparansi hasil survei pelayanan publik sebagai komitmen peningkatan kualitas layanan peradilan.', 'settings' => '{}']);
    }

    public function down()
    {
        $this->forge->dropTable('home_sections', true);
    }
}
