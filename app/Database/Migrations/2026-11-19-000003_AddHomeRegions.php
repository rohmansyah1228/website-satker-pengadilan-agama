<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Area tata letak beranda:
 *   top    = lebar penuh di atas
 *   main   = kolom utama
 *   side   = sidebar (kiri/kanan, diatur di Tampilan Beranda)
 *   bottom = lebar penuh di bawah
 */
class AddHomeRegions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('home_sections', [
            'region' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'top', 'after' => 'parent_id'],
        ]);
        $this->db->table('home_sections')->update(['region' => 'top']);

        foreach (['home_sidebar_pos' => 'right', 'home_sidebar_width' => '4'] as $k => $v) {
            if (! $this->db->table('settings')->where('key', $k)->countAllResults()) {
                $this->db->table('settings')->insert(['key' => $k, 'value' => $v]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('home_sections', 'region');
        $this->db->table('settings')->whereIn('key', ['home_sidebar_pos', 'home_sidebar_width'])->delete();
    }
}
