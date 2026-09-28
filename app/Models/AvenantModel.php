<?php

namespace App\Models;

use CodeIgniter\Model;

class AvenantModel extends Model
{
    protected $table            = 'avenants';
    protected $primaryKey       = 'id_avenant';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_contrat', 'numero_avenant', 'type_avenant', 'champ_modifie', 'ancienne_valeur',
        'nouvelle_valeur', 'justification', 'date_effet', 'statut', 'contenu_pdf_chemin',
        'contenu_hash_sha256',
    ];

    public function numeroSuivantPourContrat(int $idContrat): int
    {
        $dernier = $this->where('id_contrat', $idContrat)
            ->orderBy('numero_avenant', 'DESC')
            ->first();

        return (int) (($dernier['numero_avenant'] ?? 0) + 1);
    }

    public function pourContrat(int $idContrat): array
    {
        return $this->where('id_contrat', $idContrat)->orderBy('numero_avenant', 'DESC')->findAll();
    }

    public function enAttente(int $idContrat): array
    {
        return $this->where('id_contrat', $idContrat)->whereIn('statut', ['propose', 'signe_bailleur'])->orderBy('numero_avenant', 'DESC')->findAll();
    }
}
