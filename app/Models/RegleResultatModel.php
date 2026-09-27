<?php

namespace App\Models;

use CodeIgniter\Model;

class RegleResultatModel extends Model
{
    protected $table            = 'regles_resultats';
    protected $primaryKey       = 'id_resultat';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_dossier',
        'id_regle',
        'niveau_obtenu',
    ];

    public function pourDossier(int $idDossier): array
    {
        return $this->where('id_dossier', $idDossier)->findAll();
    }
}
