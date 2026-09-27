<?php

namespace App\Models;

use CodeIgniter\Model;

class RegleLegaltechModel extends Model
{
    protected $table            = 'regles_legaltech';
    protected $primaryKey       = 'id_regle';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'code',
        'libelle',
        'niveau',
        'article_loi',
        'message_affiche',
        'actif',
    ];
}
