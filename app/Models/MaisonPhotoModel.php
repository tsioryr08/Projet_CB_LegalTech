<?php

namespace App\Models;

use CodeIgniter\Model;

class MaisonPhotoModel extends Model
{
    protected $table         = 'maison_photos';
    protected $primaryKey    = 'id_photo';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_maison', 'chemin', 'ordre'];

    public function pourMaison(int $idMaison): array
    {
        return $this->where('id_maison', $idMaison)->orderBy('ordre', 'ASC')->findAll();
    }
}