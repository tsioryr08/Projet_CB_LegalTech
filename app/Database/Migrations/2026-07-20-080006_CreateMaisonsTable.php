<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateMaisonsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_maison' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_proprietaire' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_ville' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'titre' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'adresse' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'type_bien' => [
                'type'       => 'ENUM',
                'constraint' => ['villa', 'appartement', 'studio', 'local_commercial', 'autre'],
            ],
            'nb_chambres' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'superficie_m2' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
            ],
            'loyer_mensuel' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'valeur_immeuble' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'null'       => true,
            ],
            'date_construction' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'titre_foncier_numero' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'usage_autorise' => [
                'type'       => 'ENUM',
                'constraint' => ['habitation', 'commercial', 'mixte'],
                'default'    => 'habitation',
            ],
            'statut' => [
                'type'       => 'ENUM',
                'constraint' => ['disponible', 'en_attente', 'loue', 'archive'],
                'default'    => 'disponible',
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

        $this->forge->addKey('id_maison', true);
        $this->forge->addKey(['id_ville', 'statut']);
        $this->forge->addKey('id_proprietaire');
        $this->forge->addForeignKey('id_proprietaire', 'utilisateurs', 'id_utilisateur', 'CASCADE', 'RESTRICT', 'fk_maisons_proprietaire');
        $this->forge->addForeignKey('id_ville', 'villes', 'id_ville', 'CASCADE', 'RESTRICT', 'fk_maisons_ville');
        $this->forge->createTable('maisons');
    }

    public function down()
    {
        $this->forge->dropTable('maisons');
    }
}
