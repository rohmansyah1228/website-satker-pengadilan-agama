<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Profil pegawai lengkap: agama, jenis kelamin, profil singkat, riwayat diklat. */
class EmployeeProfile extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employees', [
            'religion' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'gender'   => ['type' => 'VARCHAR', 'constraint' => 1, 'null' => true],
            'bio'      => ['type' => 'TEXT', 'null' => true],
            'training' => ['type' => 'TEXT', 'null' => true],
        ]);
    }

    public function down()
    {
        foreach (['religion', 'gender', 'bio', 'training'] as $c) {
            $this->forge->dropColumn('employees', $c);
        }
    }
}
