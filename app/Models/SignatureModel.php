<?php

namespace App\Models;

use CodeIgniter\Model;

class SignatureModel extends Model
{
    protected $table            = 'signatures';
    protected $primaryKey       = 'id_signature';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_contrat', 'id_avenant', 'id_utilisateur', 'role_signataire', 'nom_affiche', 'signe_le', 'adresse_ip',
    ];
}
