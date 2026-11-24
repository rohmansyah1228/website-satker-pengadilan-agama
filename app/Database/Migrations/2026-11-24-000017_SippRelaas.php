<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Relaas panggilan ghaib yang dikirim otomatis oleh SIPP API Kantor.
 *   relaas_sha256  : sidik berkas terakhir dari SIPP (agar tidak dikirim ulang)
 *   relaas_file_id : berkas yang dipasang otomatis. Bila file_id ≠ relaas_file_id,
 *                    relaas diunggah manual oleh admin dan TIDAK akan ditimpa.
 */
class SippRelaas extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('ghaib')) {
            return;
        }
        $add = [];
        if (! $this->db->fieldExists('relaas_sha256', 'ghaib')) {
            $add['relaas_sha256'] = ['type' => 'CHAR', 'constraint' => 64, 'null' => true];
        }
        if (! $this->db->fieldExists('relaas_file_id', 'ghaib')) {
            $add['relaas_file_id'] = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true];
        }
        if ($add) {
            $this->forge->addColumn('ghaib', $add);
        }
    }

    public function down()
    {
        foreach (['relaas_sha256', 'relaas_file_id'] as $f) {
            if ($this->db->fieldExists($f, 'ghaib')) {
                $this->forge->dropColumn('ghaib', $f);
            }
        }
    }
}
