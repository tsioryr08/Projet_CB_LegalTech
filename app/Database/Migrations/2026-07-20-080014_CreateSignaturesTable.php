<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSignaturesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_signature' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_contrat' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_avenant' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_utilisateur' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'role_signataire' => [
                'type'       => 'ENUM',
                'constraint' => ['bailleur', 'locataire'],
            ],
            'nom_affiche' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'signe_le' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'adresse_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('id_signature', true);
        $this->forge->addForeignKey('id_contrat', 'contrats', 'id_contrat', 'CASCADE', 'CASCADE', 'fk_signature_contrat');
        $this->forge->addForeignKey('id_avenant', 'avenants', 'id_avenant', 'CASCADE', 'CASCADE', 'fk_signature_avenant');
        $this->forge->addForeignKey('id_utilisateur', 'utilisateurs', 'id_utilisateur', 'CASCADE', 'RESTRICT', 'fk_signature_utilisateur');
        $this->forge->createTable('signatures');

        // CHECK constraint (non porté par Forge) : une signature cible soit un contrat, soit un avenant, jamais les deux / ni aucun.
        $this->db->query(
            'ALTER TABLE signatures ADD CONSTRAINT chk_signature_cible CHECK (' .
            '(id_contrat IS NOT NULL AND id_avenant IS NULL) OR ' .
            '(id_contrat IS NULL AND id_avenant IS NOT NULL))'
        );
    }

    public function down()
    {
        $this->forge->dropTable('signatures');
    }
}
