<?php

namespace App\Models;

use CodeIgniter\Model;

class UtilisateurModel extends Model
{
    protected $table            = 'utilisateurs';
    protected $primaryKey       = 'id_utilisateur';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'role',
        'nom',
        'prenoms',
        'email',
        'mot_de_passe_hash',
        'telephone',
        'cin_numero',
        'cin_date_delivrance',
        'cin_lieu_delivrance',
        'sexe',
        'date_naissance',
        'profession',
        'nif',
        'stat',
        'statut_compte',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'cree_le';
    protected $updatedField  = 'modifie_le';
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'role'               => 'required|in_list[client,proprietaire,admin]',
        'nom'                => 'required|min_length[2]|max_length[100]',
        'email'              => 'required|valid_email|is_unique[utilisateurs.email,id_utilisateur,{id_utilisateur}]',
        'mot_de_passe_hash'  => 'required',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Cette adresse e-mail est déjà utilisée.',
        ],
    ];

    /**
     * Récupère un utilisateur par son e-mail, éventuellement filtré par rôle.
     */
    public function trouverParEmail(string $email, ?string $role = null): ?array
    {
        $builder = $this->where('email', $email);

        if ($role !== null) {
            $builder = $builder->where('role', $role);
        }

        return $builder->first();
    }
}