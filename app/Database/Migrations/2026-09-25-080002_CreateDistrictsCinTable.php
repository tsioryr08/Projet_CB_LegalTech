<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDistrictsCinTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'code_district' => [
                'type'       => 'CHAR',
                'constraint' => 3,
            ],
            'province' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'region' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'district' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);

        $this->forge->addPrimaryKey('code_district');
        $this->forge->createTable('districts_cin');
    }

    public function down()
    {
        $this->forge->dropTable('districts_cin');
    }
}
