<?php

namespace App\Models;

use CodeIgniter\Model;

class MaisonHistoriqueModel extends Model
{
    protected $table            = 'maison_historique_agrege';
    protected $primaryKey       = 'id_maison';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_maison',
        'nb_anciens_locataires',
        'nb_litiges_declares',
        'nb_impayes_declares',
    ];

    protected $validationRules = [
        'nb_anciens_locataires' => 'permit_empty|is_natural',
        'nb_litiges_declares'   => 'permit_empty|is_natural',
        'nb_impayes_declares'   => 'permit_empty|is_natural',
    ];

    public function pourMaison(int $idMaison): ?array
    {
        return $this->find($idMaison);
    }
}