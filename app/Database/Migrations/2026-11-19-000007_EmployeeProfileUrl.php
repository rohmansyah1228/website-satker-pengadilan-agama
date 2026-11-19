<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Tautan profil pegawai di luar (mis. halaman share SIMTEPA). */
class EmployeeProfileUrl extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employees', [
            'profile_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employees', 'profile_url');
    }
}
