<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDossiersLocationTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_dossier' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_demande' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nb_occupants' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'usage_declare' => [
                'type'       => 'ENUM',
                'constraint' => ['habitation', 'commercial', 'mixte'],
            ],
            'activite_declaree' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'situation_client' => [
                'type'       => 'ENUM',
                'constraint' => ['libre', 'mineur', 'prevenu', 'condamne'],
                'default'    => 'libre',
            ],
            'detail_situation' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'statut_validation_legale' => [
                'type'       => 'ENUM',
                'constraint' => ['en_attente', 'conforme', 'alerte', 'bloque'],
                'default'    => 'en_attente',
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

        $this->forge->addKey('id_dossier', true);
        $this->forge->addUniqueKey('id_demande');
        $this->forge->addForeignKey('id_demande', 'demandes', 'id_demande', 'CASCADE', 'CASCADE', 'fk_dossier_demande');
        $this->forge->createTable('dossiers_location');
    }

    public function down()
    {
        $this->forge->dropTable('dossiers_location');
    }
}
