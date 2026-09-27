<?php

namespace App\Models;

use CodeIgniter\Model;

class DistrictCinModel extends Model
{
    protected $table            = 'districts_cin';
    protected $primaryKey       = 'code_district';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'code_district',
        'province',
        'region',
        'district',
    ];
}
