<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Beranda berbasis grid bebas 12 kolom: setiap tampilan punya posisi (x, y) dan ukuran (lebar, tinggi).
 * Susunan lama (area atas/utama/sidebar/bawah + Baris Kolom) dikonversi otomatis.
 */
class HomeGrid extends Migration
{
    public function up()
    {
        $this->forge->addColumn('home_sections', [
            'grid_x' => ['type' => 'INT', 'default' => 1],
            'grid_y' => ['type' => 'INT', 'default' => 1],
            'grid_w' => ['type' => 'INT', 'default' => 12],
            'grid_h' => ['type' => 'INT', 'default' => 1],
        ]);

        $db   = $this->db;
        $all  = $db->table('home_sections')->orderBy('sort')->orderBy('id')->get()->getResultArray();
        $top  = $kids = [];
        foreach ($all as $r) {
            $r['parent_id'] ? $kids[(int) $r['parent_id']][] = $r : $top[$r['region'] ?? 'top'][] = $r;
        }
        $get = static function (string $k, string $d) use ($db) {
            $r = $db->table('settings')->where('key', $k)->get()->getRowArray();

            return $r['value'] ?? $d;
        };
        $sw   = max(3, min(5, (int) $get('home_sidebar_width', '4')));
        $left = $get('home_sidebar_pos', 'right') === 'left';
        $put  = static fn (int $id, int $x, int $y, int $w, int $h) => $db->table('home_sections')->where('id', $id)
            ->update(['grid_x' => $x, 'grid_y' => $y, 'grid_w' => $w, 'grid_h' => $h, 'parent_id' => null]);

        // Menata daftar kotak berurutan ke kiri→kanan dalam lebar $cols mulai kolom $x0; kembali baris berikutnya yang kosong.
        $flow = static function (array $items, int $x0, int $cols, int $y) use ($put): int {
            $cx = 0;
            foreach ($items as [$id, $span]) {
                $span = max(2, min($cols, $span));
                if ($cx + $span > $cols) {
                    $y++;
                    $cx = 0;
                }
                $put($id, $x0 + $cx, $y, $span, 1);
                $cx += $span;
            }

            return $cx > 0 ? $y + 1 : $y;
        };
        $expand = static function (array $list, int $cols) use ($kids): array {
            $out = [];
            foreach ($list as $r) {
                if ($r['type'] === 'row') {
                    foreach ($kids[(int) $r['id']] ?? [] as $c) {
                        $out[] = [(int) $c['id'], (int) round(((int) $c['width'] ?: 12) * $cols / 12)];
                    }
                } else {
                    $out[] = [(int) $r['id'], (int) round(((int) $r['width'] ?: 12) * $cols / 12)];
                }
            }

            return $out;
        };
        $full = static function (array $list, int $y) use ($kids, $flow, $put): int {
            foreach ($list as $r) {
                if ($r['type'] === 'row') {
                    $y = $flow(array_map(static fn ($c) => [(int) $c['id'], (int) $c['width'] ?: 12], $kids[(int) $r['id']] ?? []), 1, 12, $y);
                } else {
                    $put((int) $r['id'], 1, $y++, 12, 1);
                }
            }

            return $y;
        };

        $y     = $full($top['top'] ?? [], 1);
        $main  = $top['main'] ?? [];
        $side  = $top['side'] ?? [];
        $mainW = $side ? 12 - $sw : 12;
        $mainX = $side && $left ? $sw + 1 : 1;
        $endM  = $flow($expand($main, $mainW), $mainX, $mainW, $y);
        $sy    = $y;
        foreach ($side as $r) {
            $put((int) $r['id'], $left ? 1 : 13 - $sw, $sy++, $sw, 1);
        }
        $y = $full($top['bottom'] ?? [], max($endM, $sy));

        // "Baris Kolom" tidak diperlukan lagi pada grid bebas
        $db->table('home_sections')->where('type', 'row')->delete();
    }

    public function down()
    {
        $this->forge->dropColumn('home_sections', ['grid_x', 'grid_y', 'grid_w', 'grid_h']);
    }
}
