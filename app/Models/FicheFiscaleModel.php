<?php

namespace App\Models;

use CodeIgniter\Model;

class FicheFiscaleModel extends Model
{
    protected $table            = 'fiches_fiscales';
    protected $primaryKey       = 'id_fiche';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_contrat', 'montant_total_loyers', 'taux_applique', 'montant_droit',
        'date_limite_enreg', 'statut_enregistrement', 'chemin_pdf',
    ];

    public function pourContrat(int $idContrat): ?array
    {
        return $this->where('id_contrat', $idContrat)->first();
    }
}
