<?php

namespace App\Models;

use CodeIgniter\Model;

class DossierLocationModel extends Model
{
    protected $table            = 'dossiers_location';
    protected $primaryKey       = 'id_dossier';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'cree_le';
    protected $updatedField     = 'modifie_le';
    protected $dateFormat       = 'datetime';

    protected $allowedFields = [
        'id_demande', 'nb_occupants', 'usage_declare', 'activite_declaree',
        'situation_client', 'detail_situation', 'statut_validation_legale',
    ];

    public function pourDemande(int $idDemande): ?array
    {
        return $this->where('id_demande', $idDemande)->first();
    }
}