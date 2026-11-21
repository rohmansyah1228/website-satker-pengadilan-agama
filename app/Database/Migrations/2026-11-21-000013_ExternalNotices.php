<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pengumuman otomatis Badilag & Mahkamah Agung (pengganti "Arsip Berita" website lama):
 * pengaturan sumber + menu Berita & Publikasi → Pengumuman Badilag & MA.
 */
class ExternalNotices extends Migration
{
    public function up()
    {
        foreach (\App\Libraries\AnnouncementFeed::DEFAULT as $k => [, $url]) {
            if (! $this->db->table('settings')->where('key', 'feed_' . $k . '_url')->countAllResults()) {
                $this->db->table('settings')->insert(['key' => 'feed_' . $k . '_url', 'value' => $url]);
            }
        }

        if (! $this->db->table('menus')->where('url', '/pengumuman-badilag-ma')->countAllResults()) {
            $top = $this->db->table('menus')->where('parent_id', null)->where('url', '/berita')->get()->getRowArray();
            if ($top) {
                if (! $this->db->table('menus')->where('parent_id', $top['id'])->countAllResults()) {
                    $this->db->table('menus')->insert(['parent_id' => $top['id'], 'label' => 'Berita & Publikasi', 'type' => 'route', 'url' => '/berita', 'sort' => 1, 'is_active' => 1, 'is_required' => 0, 'new_tab' => 0]);
                }
                $sort = (int) ($this->db->table('menus')->selectMax('sort')->where('parent_id', $top['id'])->get()->getRow()->sort ?? 0);
                $this->db->table('menus')->insert(['parent_id' => $top['id'], 'label' => 'Pengumuman Badilag & MA', 'type' => 'route', 'url' => '/pengumuman-badilag-ma', 'sort' => $sort + 1, 'is_active' => 1, 'is_required' => 0, 'new_tab' => 0]);
            }
        }
        try {
            \App\Libraries\MenuTree::flush();
            cache()->delete('site_settings');
        } catch (\Throwable) {
        }
    }

    public function down()
    {
        $this->db->table('menus')->where('url', '/pengumuman-badilag-ma')->delete();
        $this->db->table('settings')->whereIn('key', ['feed_badilag_url', 'feed_ma_url'])->delete();
    }
}
