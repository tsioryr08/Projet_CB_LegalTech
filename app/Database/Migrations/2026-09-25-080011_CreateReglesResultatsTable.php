<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateReglesResultatsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_resultat' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_dossier' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_regle' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'niveau_obtenu' => [
                'type'       => 'ENUM',
                'constraint' => ['information', 'alerte', 'bloquant'],
            ],
            'declenchee_le' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id_resultat', true);
        $this->forge->addKey('id_dossier');
        $this->forge->addForeignKey('id_dossier', 'dossiers_location', 'id_dossier', 'CASCADE', 'CASCADE', 'fk_resultat_dossier');
        $this->forge->addForeignKey('id_regle', 'regles_legaltech', 'id_regle', 'CASCADE', 'RESTRICT', 'fk_resultat_regle');
        $this->forge->createTable('regles_resultats');
    }

    public function down()
    {
        $this->forge->dropTable('regles_resultats');
    }
}
