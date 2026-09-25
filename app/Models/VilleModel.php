<?php

namespace App\Models;

use CodeIgniter\Model;

class VilleModel extends Model
{
    protected $table            = 'villes';
    protected $primaryKey       = 'id_ville';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['nom', 'region'];
}