<?php

namespace App\Models;

use CodeIgniter\Model;

class MaisonModel extends Model
{
    protected $table            = 'maisons';
    protected $primaryKey       = 'id_maison';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_proprietaire',
        'id_ville',
        'titre',
        'adresse',
        'description',
        'type_bien',
        'nb_chambres',
        'superficie_m2',
        'loyer_mensuel',
        'valeur_immeuble',
        'date_construction',
        'titre_foncier_numero',
        'usage_autorise',
        'statut',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'cree_le';
    protected $updatedField  = 'modifie_le';

    protected $validationRules = [
        'id_proprietaire' => 'required|is_natural_no_zero',
        'id_ville'         => 'required|is_natural_no_zero',
        'titre'            => 'required|max_length[150]',
        'adresse'          => 'required|max_length[255]',
        'type_bien'        => 'required|in_list[villa,appartement,studio,local_commercial,autre]',
        'nb_chambres'      => 'permit_empty|is_natural_no_zero',
        'loyer_mensuel'    => 'required|decimal',
        'usage_autorise'   => 'permit_empty|in_list[habitation,commercial,mixte]',
        'statut'           => 'permit_empty|in_list[disponible,en_attente,loue,archive]',
    ];

    public function pourProprietaire(int $idProprietaire): array
    {
        return $this->where('id_proprietaire', $idProprietaire)
            ->orderBy('cree_le', 'DESC')
            ->findAll();
    }
       public function trouverFicheDetail(int $idMaison): ?array
    {
        return $this->select('maisons.*, villes.nom AS nom_ville, villes.region AS region_ville')
            ->join('villes', 'villes.id_ville = maisons.id_ville')
            ->where('maisons.id_maison', $idMaison)
            ->first();
    }
     public function listerDisponibles(?int $idVille = null): array
    {
        $builder = $this->select('maisons.*, villes.nom AS nom_ville')
            ->join('villes', 'villes.id_ville = maisons.id_ville')
            ->where('maisons.statut', 'disponible');

        if ($idVille !== null) {
            $builder = $builder->where('maisons.id_ville', $idVille);
        }

        return $builder->orderBy('maisons.cree_le', 'DESC')->findAll();
    }

}