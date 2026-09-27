<?php

namespace App\Models;

use CodeIgniter\Model;

class ContratModel extends Model
{
    protected $table            = 'contrats';
    protected $primaryKey       = 'id_contrat';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_dossier', 'id_type_contrat', 'id_maison', 'id_client', 'id_proprietaire',
        'numero_contrat', 'loyer_mensuel', 'depot_garantie', 'date_debut', 'date_fin',
        'duree_indeterminee', 'taux_enregistrement_applique', 'montant_droit_enregistrement',
        'contenu_pdf_chemin', 'contenu_hash_sha256', 'statut',
    ];

    public function avecRelations(): self
    {
        return $this->select('contrats.*, types_contrat.code AS type_code, types_contrat.libelle AS type_libelle, types_contrat.preavis_mois, types_contrat.modele_html, fiches_fiscales.id_fiche, fiches_fiscales.montant_total_loyers, fiches_fiscales.taux_applique, fiches_fiscales.montant_droit AS fiche_montant_droit, fiches_fiscales.date_limite_enreg, fiches_fiscales.statut_enregistrement, fiches_fiscales.chemin_pdf AS fiche_pdf')
            ->join('types_contrat', 'types_contrat.id_type_contrat = contrats.id_type_contrat', 'left')
            ->join('fiches_fiscales', 'fiches_fiscales.id_contrat = contrats.id_contrat', 'left');
    }
}
