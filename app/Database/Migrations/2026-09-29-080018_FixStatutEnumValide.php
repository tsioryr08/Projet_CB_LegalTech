<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Le code de génération/signature de contrat (ContratController) utilise le
 * statut 'valide' pour un contrat ou un avenant entièrement signé, alors que
 * la migration initiale ne définissait que 'actif' dans l'ENUM. On ajoute
 * 'valide' aux deux tables SANS retirer 'actif', pour ne rien casser si
 * 'actif' est utilisé ailleurs.
 */
class FixStatutEnumValide extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE contrats MODIFY statut
             ENUM('genere','signe_bailleur','actif','valide','resilie','expire','annule')
             NOT NULL DEFAULT 'genere'"
        );

        $this->db->query(
            "ALTER TABLE avenants MODIFY statut
             ENUM('propose','signe_bailleur','actif','valide','refuse','annule')
             NOT NULL DEFAULT 'propose'"
        );
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE contrats MODIFY statut
             ENUM('genere','signe_bailleur','actif','resilie','expire','annule')
             NOT NULL DEFAULT 'genere'"
        );

        $this->db->query(
            "ALTER TABLE avenants MODIFY statut
             ENUM('propose','signe_bailleur','actif','refuse','annule')
             NOT NULL DEFAULT 'propose'"
        );
    }
}