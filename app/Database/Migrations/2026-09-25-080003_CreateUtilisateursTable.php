<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUtilisateursTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_utilisateur' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['client', 'proprietaire', 'admin'],
            ],
            'nom' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'prenoms' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'mot_de_passe_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'telephone' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'cin_numero' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'cin_date_delivrance' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'cin_lieu_delivrance' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'sexe' => [
                'type'       => 'ENUM',
                'constraint' => ['M', 'F'],
                'null'       => true,
            ],
            'date_naissance' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'profession' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'nif' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'stat' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'statut_compte' => [
                'type'       => 'ENUM',
                'constraint' => ['actif', 'suspendu'],
                'default'    => 'actif',
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

        $this->forge->addKey('id_utilisateur', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('role');
        $this->forge->createTable('utilisateurs');
    }

    public function down()
    {
        $this->forge->dropTable('utilisateurs');
    }
}
