<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_notification' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_utilisateur' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'demande_recue', 'demande_validee', 'demande_refusee',
                    'alerte_legaltech', 'contrat_a_signer', 'avenant_a_signer',
                    'echeance_proche', 'preavis', 'autre',
                ],
            ],
            'reference_table' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'reference_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'message' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'lue' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'cree_le' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id_notification', true);
        $this->forge->addKey(['id_utilisateur', 'lue']);
        $this->forge->addForeignKey('id_utilisateur', 'utilisateurs', 'id_utilisateur', 'CASCADE', 'CASCADE', 'fk_notif_utilisateur');
        $this->forge->createTable('notifications');
    }

    public function down()
    {
        $this->forge->dropTable('notifications');
    }
}
