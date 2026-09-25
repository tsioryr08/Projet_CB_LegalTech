<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDemandesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_demande' => [
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
            'id_client' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'statut' => [
                'type'       => 'ENUM',
                'constraint' => ['envoyee', 'validee', 'refusee', 'annulee'],
                'default'    => 'envoyee',
            ],
            'motif_refus' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'date_demande' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'date_traitement' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // NOTE : pas de contrainte UNIQUE sur (id_maison, id_client, statut) —
        // volontaire, voir le commentaire du script SQL d'origine : la règle
        // "une seule demande 'envoyee' par client/maison à la fois" est
        // contrôlée en couche applicative avant l'insertion.
        $this->forge->addKey('id_demande', true);
        $this->forge->addKey('id_client');
        $this->forge->addKey(['id_maison', 'statut']);
        $this->forge->addForeignKey('id_maison', 'maisons', 'id_maison', 'CASCADE', 'RESTRICT', 'fk_demandes_maison');
        $this->forge->addForeignKey('id_client', 'utilisateurs', 'id_utilisateur', 'CASCADE', 'RESTRICT', 'fk_demandes_client');
        $this->forge->createTable('demandes');
    }

    public function down()
    {
        $this->forge->dropTable('demandes');
    }
}
