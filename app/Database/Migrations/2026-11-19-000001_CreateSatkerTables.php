<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSatkerTables extends Migration
{
    private function id(): array
    {
        return ['id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true]];
    }

    private function ts(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function str(int $len = 255, bool $null = true, ?string $default = null): array
    {
        $f = ['type' => 'VARCHAR', 'constraint' => $len, 'null' => $null];
        if ($default !== null) {
            $f['default'] = $default;
        }

        return $f;
    }

    private function int(bool $null = false, int $default = 0): array
    {
        return $null ? ['type' => 'INT', 'null' => true] : ['type' => 'INT', 'default' => $default];
    }

    private function bool(int $default = 0): array
    {
        return ['type' => 'TINYINT', 'constraint' => 1, 'default' => $default];
    }

    private function text(bool $medium = false): array
    {
        return ['type' => $medium ? 'MEDIUMTEXT' : 'TEXT', 'null' => true];
    }

    private function make(string $table, array $fields, array $keys = [], array $unique = []): void
    {
        $this->forge->addField($fields);
        if (isset($fields['id'])) {
            $this->forge->addPrimaryKey('id');
        }
        foreach ($keys as $k) {
            $this->forge->addKey($k);
        }
        foreach ($unique as $u) {
            $this->forge->addUniqueKey($u);
        }
        $this->forge->createTable($table, true, $this->db->DBDriver === 'MySQLi' ? ['ENGINE' => 'InnoDB'] : []);
    }

    public function up()
    {
        $this->forge->addField(['key' => $this->str(100, false), 'value' => $this->text(true)]);
        $this->forge->addPrimaryKey('key');
        $this->forge->createTable('settings', true);

        $this->make('users', $this->id() + [
            'username'      => $this->str(60, false),
            'name'          => $this->str(150, false),
            'email'         => $this->str(150),
            'password_hash' => $this->str(255, false),
            'role'          => $this->str(30, false, 'admin'),
            'totp_secret'   => $this->str(64),
            'totp_enabled'  => $this->bool(),
            'is_active'     => $this->bool(1),
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->ts(), [], ['username']);

        $this->make('login_attempts', $this->id() + [
            'username'   => $this->str(60),
            'ip'         => $this->str(45),
            'success'    => $this->bool(),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['ip', 'created_at']);

        $this->make('activity_logs', $this->id() + [
            'user_id'    => $this->int(true),
            'username'   => $this->str(60),
            'action'     => $this->str(30),
            'subject'    => $this->str(255),
            'ip'         => $this->str(45),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['created_at']);

        $this->make('files', $this->id() + [
            'uuid'          => $this->str(40, false),
            'path'          => $this->str(255, false),
            'original_name' => $this->str(255),
            'mime'          => $this->str(100),
            'size'          => $this->int(),
            'downloads'     => $this->int(),
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ], [], ['uuid']);

        $this->make('pages', $this->id() + [
            'section'          => $this->str(60, false),
            'slug'             => $this->str(120, false),
            'title'            => $this->str(255, false),
            'content'          => $this->text(true),
            'image'            => $this->str(255),
            'module'           => $this->str(40),
            'module_param'     => $this->str(255),
            'meta_description' => $this->str(255),
            'is_required'      => $this->bool(),
            'status'           => $this->str(20, false, 'published'),
            'updated_by'       => $this->int(true),
        ] + $this->ts(), [], [['section', 'slug']]);

        $this->make('menus', $this->id() + [
            'parent_id'   => $this->int(true),
            'label'       => $this->str(120, false),
            'type'        => $this->str(10, false, 'page'),
            'page_id'     => $this->int(true),
            'url'         => $this->str(255),
            'section'     => $this->str(60),
            'sort'        => $this->int(),
            'is_required' => $this->bool(),
            'is_active'   => $this->bool(1),
            'new_tab'     => $this->bool(),
        ], ['parent_id']);

        $this->make('redirects', $this->id() + [
            'old_path'   => $this->str(255, false),
            'new_path'   => $this->str(255, false),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], [], ['old_path']);

        $this->make('doc_types', $this->id() + [
            'code'        => $this->str(60, false),
            'name'        => $this->str(150, false),
            'section'     => $this->str(60),
            'period'      => $this->str(10, false, 'tahunan'),
            'min_years'   => $this->int(false, 1),
            'description' => $this->text(),
            'is_required' => $this->bool(),
            'sort'        => $this->int(),
        ], [], ['code']);

        $this->make('documents', $this->id() + [
            'doc_type_id' => $this->int(),
            'title'       => $this->str(255, false),
            'year'        => $this->int(),
            'month'       => $this->int(true),
            'file_id'     => $this->int(true),
            'external_url'=> $this->str(255),
        ] + $this->ts(), ['doc_type_id', 'year']);

        $this->make('posts', $this->id() + [
            'category'     => $this->str(30, false, 'berita'),
            'slug'         => $this->str(180, false),
            'title'        => $this->str(255, false),
            'excerpt'      => $this->text(),
            'content'      => $this->text(true),
            'image'        => $this->str(255),
            'image_alt'    => $this->str(255),
            'status'       => $this->str(20, false, 'published'),
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'author'       => $this->str(120),
            'views'        => $this->int(),
        ] + $this->ts(), ['category', 'published_at'], ['slug']);

        $this->make('sliders', $this->id() + [
            'badge'        => $this->str(120),
            'title'        => $this->str(255, false),
            'subtitle'     => $this->text(),
            'image'        => $this->str(255),
            'image_alt'    => $this->str(255),
            'button_text'  => $this->str(60),
            'button_url'   => $this->str(255),
            'button2_text' => $this->str(60),
            'button2_url'  => $this->str(255),
            'sort'         => $this->int(),
            'is_active'    => $this->bool(1),
        ]);

        $this->make('shortcuts', $this->id() + [
            'title'       => $this->str(120, false),
            'description' => $this->str(255),
            'icon'        => $this->str(60),
            'url'         => $this->str(255, false),
            'sort'        => $this->int(),
            'is_active'   => $this->bool(1),
        ]);

        $this->make('employees', $this->id() + [
            'name'      => $this->str(150, false),
            'position'  => $this->str(150),
            'grp'       => $this->str(20, false, 'pegawai'),
            'rank'      => $this->str(100),
            'tmt'       => ['type' => 'DATE', 'null' => true],
            'photo'     => $this->str(255),
            'education' => $this->text(),
            'career'    => $this->text(),
            'sort'      => $this->int(),
            'is_active' => $this->bool(1),
        ]);

        $this->make('lhkpn', $this->id() + [
            'name'     => $this->str(150, false),
            'position' => $this->str(150),
            'year'     => $this->int(),
            'file_id'  => $this->int(true),
        ], ['year']);

        $this->make('ghaib', $this->id() + [
            'party_name'   => $this->str(150, false),
            'case_number'  => $this->str(80, false),
            'hearing_date' => ['type' => 'DATE', 'null' => true],
            'file_id'      => $this->int(true),
            'is_active'    => $this->bool(1),
        ] + $this->ts(), ['hearing_date']);

        $this->make('hearings', $this->id() + [
            'hearing_date' => ['type' => 'DATE', 'null' => false],
            'hearing_time' => $this->str(10),
            'case_number'  => $this->str(80, false),
            'agenda'       => $this->str(255),
            'room'         => $this->str(100),
        ], ['hearing_date']);

        $this->make('case_stats', $this->id() + [
            'year'         => $this->int(),
            'month'        => $this->int(),
            'carried_over' => $this->int(),
            'received'     => $this->int(),
            'decided'      => $this->int(),
            'remaining'    => $this->int(),
        ], [], [['year', 'month']]);

        $this->make('complaints', $this->id() + [
            'kind'        => $this->str(10, false, 'pengaduan'),
            'year'        => $this->int(),
            'initials'    => $this->str(20),
            'violation'   => $this->str(255),
            'description' => $this->text(),
            'status'      => $this->str(255),
            'penalty'     => $this->str(255),
        ], ['kind', 'year']);

        $this->make('info_requests', $this->id() + [
            'year'            => $this->int(),
            'month'           => $this->int(),
            'received'        => $this->int(),
            'avg_days'        => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'granted_full'    => $this->int(),
            'granted_partial' => $this->int(),
            'rejected'        => $this->int(),
            'reject_reasons'  => $this->text(),
        ], [], [['year', 'month']]);

        $this->make('jurisdictions', $this->id() + [
            'name' => $this->str(150, false),
            'sort' => $this->int(),
        ]);

        $this->forge->addField(['date' => ['type' => 'DATE', 'null' => false], 'ip_hash' => $this->str(64, false)]);
        $this->forge->addPrimaryKey(['date', 'ip_hash']);
        $this->forge->createTable('visitor_hits', true);

        $this->forge->addField(['date' => ['type' => 'DATE', 'null' => false], 'visitors' => $this->int(), 'pageviews' => $this->int()]);
        $this->forge->addPrimaryKey('date');
        $this->forge->createTable('visitor_daily', true);
    }

    public function down()
    {
        foreach (['visitor_daily', 'visitor_hits', 'jurisdictions', 'info_requests', 'complaints', 'case_stats', 'hearings', 'ghaib', 'lhkpn',
            'employees', 'shortcuts', 'sliders', 'posts', 'documents', 'doc_types', 'redirects', 'menus', 'pages', 'files',
            'activity_logs', 'login_attempts', 'users', 'settings'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
