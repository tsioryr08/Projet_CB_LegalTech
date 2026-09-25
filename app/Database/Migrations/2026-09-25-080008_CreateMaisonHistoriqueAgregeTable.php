<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateMaisonHistoriqueAgregeTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_maison' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nb_anciens_locataires' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'nb_litiges_declares' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'nb_impayes_declares' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'derniere_maj' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addPrimaryKey('id_maison');
        $this->forge->addForeignKey('id_maison', 'maisons', 'id_maison', 'CASCADE', 'CASCADE', 'fk_histo_maison');
        $this->forge->createTable('maison_historique_agrege');
    }

    public function down()
    {
        $this->forge->dropTable('maison_historique_agrege');
    }
}
