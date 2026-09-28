<?php

namespace App\Models;

use CodeIgniter\Model;

class ContratModel extends Model
{
    protected $table            = 'contrats';
    protected $primaryKey       = 'id_contrat';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'cree_le';
    protected $updatedField     = 'modifie_le';
    protected $dateFormat       = 'datetime';

    protected $allowedFields = [
        'id_dossier', 'id_type_contrat', 'id_maison', 'id_client', 'id_proprietaire',
        'numero_contrat', 'loyer_mensuel', 'depot_garantie', 'date_debut', 'date_fin',
        'duree_indeterminee', 'taux_enregistrement_applique', 'montant_droit_enregistrement',
        'contenu_pdf_chemin', 'contenu_hash_sha256', 'statut',
    ];
    /**
     * Tous les contrats d'un client, avec le titre de la maison et le type de bail.
     */
    public function pourClient(int $idClient): array
    {
        return $this->select('contrats.*, maisons.titre, types_contrat.libelle AS type_libelle')
            ->join('maisons', 'maisons.id_maison = contrats.id_maison')
            ->join('types_contrat', 'types_contrat.id_type_contrat = contrats.id_type_contrat')
            ->where('contrats.id_client', $idClient)
            ->orderBy('contrats.cree_le', 'DESC')
            ->findAll();
    }

    /**
     * Un contrat précis, en vérifiant qu'il appartient bien au client connecté.
     */
    public function trouverPourClient(int $idContrat, int $idClient): ?array
    {
        return $this->select('contrats.*, maisons.titre, types_contrat.libelle AS type_libelle')
            ->join('maisons', 'maisons.id_maison = contrats.id_maison')
            ->join('types_contrat', 'types_contrat.id_type_contrat = contrats.id_type_contrat')
            ->where('contrats.id_contrat', $idContrat)
            ->where('contrats.id_client', $idClient)
            ->first();
    }

    public function avecRelations(): self
    {
        return $this->select('contrats.*, types_contrat.code AS type_code, types_contrat.libelle AS type_libelle, types_contrat.preavis_mois, types_contrat.modele_html, fiches_fiscales.id_fiche, fiches_fiscales.montant_total_loyers, fiches_fiscales.taux_applique, fiches_fiscales.montant_droit AS fiche_montant_droit, fiches_fiscales.date_limite_enreg, fiches_fiscales.statut_enregistrement, fiches_fiscales.chemin_pdf AS fiche_pdf')
            ->join('types_contrat', 'types_contrat.id_type_contrat = contrats.id_type_contrat', 'left')
            ->join('fiches_fiscales', 'fiches_fiscales.id_contrat = contrats.id_contrat', 'left');
    }
}
