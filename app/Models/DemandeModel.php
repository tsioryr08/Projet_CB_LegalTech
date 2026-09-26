<?php

namespace App\Models;

use CodeIgniter\Model;

class DemandeModel extends Model
{
    protected $table            = 'demandes';
    protected $primaryKey       = 'id_demande';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_maison', 'id_client', 'statut', 'motif_refus', 'date_demande', 'date_traitement',
    ];

    /**
     * Vérifie si le client a déjà une demande 'envoyee' (en cours) pour cette maison.
     * Voir explication dans le schéma : ce contrôle se fait en PHP, pas via une
     * contrainte SQL, pour permettre à un client refusé de redéposer une demande.
     */
    public function aDemandeEnCours(int $idMaison, int $idClient): bool
    {
        return $this->where('id_maison', $idMaison)
            ->where('id_client', $idClient)
            ->where('statut', 'envoyee')
            ->countAllResults() > 0;
    }

    public function creerDemande(int $idMaison, int $idClient): int|false
    {
        if ($this->aDemandeEnCours($idMaison, $idClient)) {
            return false;
        }

        return $this->insert([
            'id_maison'    => $idMaison,
            'id_client'    => $idClient,
            'statut'       => 'envoyee',
            'date_demande' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Liste des demandes d'un client, avec le titre et la ville de la maison.
     */
    public function pourClient(int $idClient): array
    {
        return $this->select('demandes.*, maisons.titre, maisons.loyer_mensuel, villes.nom AS nom_ville')
            ->join('maisons', 'maisons.id_maison = demandes.id_maison')
            ->join('villes', 'villes.id_ville = maisons.id_ville')
            ->where('demandes.id_client', $idClient)
            ->orderBy('demandes.date_demande', 'DESC')
            ->findAll();
    }
}