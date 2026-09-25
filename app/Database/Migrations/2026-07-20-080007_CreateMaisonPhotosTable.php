<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMaisonPhotosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_photo' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_maison' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'chemin' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'ordre' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
            ],
        ]);

        $this->forge->addKey('id_photo', true);
        $this->forge->addForeignKey('id_maison', 'maisons', 'id_maison', 'CASCADE', 'CASCADE', 'fk_photos_maison');
        $this->forge->createTable('maison_photos');
    }

    public function down()
    {
        $this->forge->dropTable('maison_photos');
    }
}
