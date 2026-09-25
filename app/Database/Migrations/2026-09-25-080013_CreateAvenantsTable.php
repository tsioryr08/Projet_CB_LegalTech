<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateAvenantsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_avenant' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_contrat' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'numero_avenant' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'type_avenant' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'modification_loyer',
                    'prolongation_duree',
                    'autorisation_sous_location',
                    'modification_caution',
                    'ajout_retrait_occupant',
                    'autre',
                ],
            ],
            'champ_modifie' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'ancienne_valeur' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'nouvelle_valeur' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'justification' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'date_effet' => [
                'type' => 'DATE',
            ],
            'statut' => [
                'type'       => 'ENUM',
                'constraint' => ['propose', 'signe_bailleur', 'actif', 'refuse', 'annule'],
                'default'    => 'propose',
            ],
            'contenu_pdf_chemin' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'contenu_hash_sha256' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'cree_le' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'modifie_le' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id_avenant', true);
        $this->forge->addUniqueKey(['id_contrat', 'numero_avenant'], 'uq_avenant_numero');
        $this->forge->addKey(['id_contrat', 'statut']);
        $this->forge->addForeignKey('id_contrat', 'contrats', 'id_contrat', 'CASCADE', 'CASCADE', 'fk_avenant_contrat');
        $this->forge->createTable('avenants');
    }

    public function down()
    {
        $this->forge->dropTable('avenants');
    }
}
