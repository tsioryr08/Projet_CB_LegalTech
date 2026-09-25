<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateReglesLegaltechTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_regle' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'libelle' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'niveau' => [
                'type'       => 'ENUM',
                'constraint' => ['information', 'alerte', 'bloquant'],
            ],
            'article_loi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'message_affiche' => [
                'type' => 'TEXT',
            ],
            'actif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'cree_le' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id_regle', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('regles_legaltech');
    }

    public function down()
    {
        $this->forge->dropTable('regles_legaltech');
    }
}
