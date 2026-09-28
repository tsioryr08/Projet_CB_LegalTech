<?php

namespace App\Models;

use CodeIgniter\Model;

class MaisonPhotoModel extends Model
{
    protected $table            = 'maison_photos';
    protected $primaryKey       = 'id_photo';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_maison',
        'chemin',
        'ordre',
    ];

    protected $validationRules = [
        'id_maison' => 'required|is_natural_no_zero',
        'chemin'    => 'required|max_length[255]',
        'ordre'     => 'permit_empty|is_natural',
    ];

    public function pourMaison(int $idMaison): array
    {
        return $this->where('id_maison', $idMaison)
            ->orderBy('ordre', 'ASC')
            ->findAll();
    }
}