<?php

namespace App\Models;

use CodeIgniter\Model;

class TypeContratModel extends Model
{
    protected $table            = 'types_contrat';
    protected $primaryKey       = 'id_type_contrat';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'code', 'libelle', 'texte_reference', 'taux_enregistrement', 'preavis_mois', 'modele_html',
    ];

    public function trouverParCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }
}
