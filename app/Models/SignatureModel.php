<?php

namespace App\Models;

use CodeIgniter\Model;

class SignatureModel extends Model
{
    protected $table            = 'signatures';
    protected $primaryKey       = 'id_signature';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'id_contrat', 'id_avenant', 'id_utilisateur', 'role_signataire',
        'nom_affiche', 'signe_le', 'adresse_ip',
    ];

    public function existeDeja(?int $idContrat, ?int $idAvenant, string $role): bool
    {
        $builder = $this->where('role_signataire', $role);

        $builder = $idContrat !== null
            ? $builder->where('id_contrat', $idContrat)
            : $builder->where('id_avenant', $idAvenant);

        return $builder->countAllResults() > 0;
    }
}