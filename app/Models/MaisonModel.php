<?php

namespace App\Models;

use CodeIgniter\Model;

class MaisonModel extends Model
{
    protected $table            = 'maisons';
    protected $primaryKey       = 'id_maison';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'cree_le';
    protected $updatedField     = 'modifie_le';
    protected $dateFormat       = 'datetime';

    protected $allowedFields = [
        'id_proprietaire', 'id_ville', 'titre', 'adresse', 'description',
        'type_bien', 'nb_chambres', 'superficie_m2', 'loyer_mensuel',
        'valeur_immeuble', 'date_construction', 'titre_foncier_numero',
        'usage_autorise', 'statut',
    ];

    /**
     * Catalogue public : maisons disponibles, avec le nom de la ville.
     * $idVille = null pour ne pas filtrer.
     */
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

    /**
     * Fiche détail d'une maison disponible, avec le nom de la ville.
     * Retourne null si la maison n'existe pas ou n'est pas disponible/visible publiquement.
     */
    public function trouverFicheDetail(int $idMaison): ?array
    {
        return $this->select('maisons.*, villes.nom AS nom_ville, villes.region AS region_ville')
            ->join('villes', 'villes.id_ville = maisons.id_ville')
            ->where('maisons.id_maison', $idMaison)
            ->first();
    }
}