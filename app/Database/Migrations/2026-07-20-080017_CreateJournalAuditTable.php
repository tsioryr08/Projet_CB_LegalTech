<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateJournalAuditTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_log' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_utilisateur' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'table_cible' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'id_cible' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'details' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'adresse_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'horodatage' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id_log', true);
        $this->forge->addKey(['table_cible', 'id_cible']);
        $this->forge->addKey('horodatage');
        $this->forge->addForeignKey('id_utilisateur', 'utilisateurs', 'id_utilisateur', 'CASCADE', 'SET NULL', 'fk_audit_utilisateur');
        $this->forge->createTable('journal_audit');
    }

    public function down()
    {
        $this->forge->dropTable('journal_audit');
    }
}
