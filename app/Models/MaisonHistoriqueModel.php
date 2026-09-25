<?php

namespace App\Models;

use CodeIgniter\Model;

class MaisonHistoriqueModel extends Model
{
    protected $table         = 'maison_historique_agrege';
    protected $primaryKey    = 'id_maison';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_maison', 'nb_anciens_locataires', 'nb_litiges_declares', 'nb_impayes_declares'];

    public function pourMaison(int $idMaison): ?array
    {
        return $this->find($idMaison);
    }
}