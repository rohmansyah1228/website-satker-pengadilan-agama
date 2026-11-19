<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Isi lama yang memuat alamat situs lengkap (mis. http://localhost:8080/uploads/…, alamat IP lokal,
 * atau domain di .env) diubah menjadi penanda {{SITE_URL}}/ agar tetap tampil bila domain berganti.
 */
class PortableContentUrls extends Migration
{
    private array $targets = [
        'posts'         => ['content', 'excerpt'],
        'pages'         => ['content', 'blocks'],
        'home_sections' => ['settings'],
        'employees'     => ['bio', 'education', 'career', 'training'],
    ];

    public function up()
    {
        foreach ($this->targets as $table => $cols) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $cols = array_values(array_filter($cols, fn ($c) => $this->db->fieldExists($c, $table)));
            foreach ($this->db->table($table)->select(array_merge(['id'], $cols))->get()->getResultArray() as $row) {
                $set = [];
                foreach ($cols as $c) {
                    $v = (string) ($row[$c] ?? '');
                    if ($v !== '' && ($n = self::collapse($v)) !== $v) {
                        $set[$c] = $n;
                    }
                }
                if ($set) {
                    $this->db->table($table)->where('id', $row['id'])->update($set);
                }
            }
        }
    }

    public function down()
    {
    }

    public static function collapse(string $s): string
    {
        $slash = '(\\\\?/)';                                      // "/" atau "\/" (di dalam JSON)
        $local = 'localhost|127\.0\.0\.1|\[::1\]|10(?:\.\d{1,3}){3}|192\.168(?:\.\d{1,3}){2}|172\.(?:1[6-9]|2\d|3[01])(?:\.\d{1,3}){2}'
               . '|100\.(?:6[4-9]|[7-9]\d|1[01]\d|12[0-7])(?:\.\d{1,3}){2}';
        $hosts = [$local];
        $cfg   = (string) env('app.baseURL', '');
        if (($h = parse_url($cfg, PHP_URL_HOST)) && ! preg_match('#^(' . $local . ')$#', $h)) {
            $hosts[] = preg_quote($h, '#');
        }
        // awal rute website ini; folder instalasi (nama apa pun) di depannya ikut dibuang
        $routes = ['uploads', 'berkas', 'berita', 'cari', 'assets', 'pasang'];
        if (class_exists(\Config\SiteStructure::class)) {
            $routes = array_merge($routes, array_keys(config(\Config\SiteStructure::class)->sections));
        }
        $routes[] = trim((string) (env('app.adminPath') ?: 'panel-satker'), '/');
        $routes   = implode('|', array_map(static fn ($r) => preg_quote($r, '#'), array_unique(array_filter($routes))));
        $re = '#https?:' . $slash . '{2}(?:' . implode('|', $hosts) . ')(?::\d+)?' . $slash
            . '(?:[A-Za-z0-9._~-]+' . $slash . '(?=(?:public' . $slash . ')?(?:' . $routes . ')\b))?(?:public' . $slash . ')?#i';

        return preg_replace_callback($re, static fn ($m) => str_contains($m[0], '\\/') ? '{{SITE_URL}}\\/' : '{{SITE_URL}}/', $s);
    }
}
