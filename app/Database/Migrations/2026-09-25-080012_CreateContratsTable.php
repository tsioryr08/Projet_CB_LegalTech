<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateContratsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_contrat' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_dossier' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_type_contrat' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
            'id_proprietaire' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'numero_contrat' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'loyer_mensuel' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'depot_garantie' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'date_debut' => [
                'type' => 'DATE',
            ],
            'date_fin' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'duree_indeterminee' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'taux_enregistrement_applique' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'montant_droit_enregistrement' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'contenu_pdf_chemin' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'contenu_hash_sha256' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'statut' => [
                'type'       => 'ENUM',
                'constraint' => ['genere', 'signe_bailleur', 'actif', 'resilie', 'expire', 'annule'],
                'default'    => 'genere',
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

        $this->forge->addKey('id_contrat', true);
        $this->forge->addUniqueKey('id_dossier');
        $this->forge->addUniqueKey('numero_contrat');
        $this->forge->addKey('id_client');
        $this->forge->addKey('id_proprietaire');
        $this->forge->addKey('statut');
        $this->forge->addKey('date_fin');
        $this->forge->addForeignKey('id_dossier', 'dossiers_location', 'id_dossier', 'CASCADE', 'RESTRICT', 'fk_contrat_dossier');
        $this->forge->addForeignKey('id_type_contrat', 'types_contrat', 'id_type_contrat', 'CASCADE', 'RESTRICT', 'fk_contrat_type');
        $this->forge->addForeignKey('id_maison', 'maisons', 'id_maison', 'CASCADE', 'RESTRICT', 'fk_contrat_maison');
        $this->forge->addForeignKey('id_client', 'utilisateurs', 'id_utilisateur', 'CASCADE', 'RESTRICT', 'fk_contrat_client');
        $this->forge->addForeignKey('id_proprietaire', 'utilisateurs', 'id_utilisateur', 'CASCADE', 'RESTRICT', 'fk_contrat_proprietaire');
        $this->forge->createTable('contrats');

        // CHECK constraint (non porté par Forge) : date_fin doit être postérieure à date_debut si renseignée.
        $this->db->query('ALTER TABLE contrats ADD CONSTRAINT chk_dates_contrat CHECK (date_fin IS NULL OR date_fin > date_debut)');
    }

    public function down()
    {
        $this->forge->dropTable('contrats');
    }
}
