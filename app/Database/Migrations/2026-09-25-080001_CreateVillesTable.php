<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVillesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_ville' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nom' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'region' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('id_ville', true);
        $this->forge->addUniqueKey('nom');
        $this->forge->createTable('villes');
    }

    public function down()
    {
        $this->forge->dropTable('villes');
    }
}
