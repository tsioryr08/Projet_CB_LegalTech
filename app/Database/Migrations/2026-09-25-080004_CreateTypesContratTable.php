<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTypesContratTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_type_contrat' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'ENUM',
                'constraint' => ['habitation', 'commercial', 'mixte'],
            ],
            'libelle' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'texte_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'taux_enregistrement' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'preavis_mois' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
            ],
            'modele_html' => [
                'type' => 'MEDIUMTEXT',
            ],
        ]);

        $this->forge->addKey('id_type_contrat', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('types_contrat');
    }

    public function down()
    {
        $this->forge->dropTable('types_contrat');
    }
}
