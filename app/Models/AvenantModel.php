<?php

namespace App\Models;

use CodeIgniter\Model;

class AvenantModel extends Model
{
    protected $table            = 'avenants';
    protected $primaryKey       = 'id_avenant';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'cree_le';
    protected $updatedField     = 'modifie_le';
    protected $dateFormat       = 'datetime';

    protected $allowedFields = [
        'id_contrat', 'numero_avenant', 'type_avenant', 'champ_modifie',
        'ancienne_valeur', 'nouvelle_valeur', 'justification', 'date_effet',
        'statut', 'contenu_pdf_chemin', 'contenu_hash_sha256',
    ];

    public function pourContrat(int $idContrat): array
    {
        return $this->where('id_contrat', $idContrat)
            ->orderBy('numero_avenant', 'ASC')
            ->findAll();
    }

    public function trouver(int $idAvenant): ?array
    {
        return $this->find($idAvenant);
    }
}