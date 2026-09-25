<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFichesFiscalesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_fiche' => [
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
            'montant_total_loyers' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'taux_applique' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'montant_droit' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'date_limite_enreg' => [
                'type' => 'DATE',
            ],
            'statut_enregistrement' => [
                'type'       => 'ENUM',
                'constraint' => ['non_enregistre', 'enregistre'],
                'default'    => 'non_enregistre',
            ],
            'chemin_pdf' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('id_fiche', true);
        $this->forge->addUniqueKey('id_contrat');
        $this->forge->addForeignKey('id_contrat', 'contrats', 'id_contrat', 'CASCADE', 'CASCADE', 'fk_fiche_contrat');
        $this->forge->createTable('fiches_fiscales');
    }

    public function down()
    {
        $this->forge->dropTable('fiches_fiscales');
    }
}
