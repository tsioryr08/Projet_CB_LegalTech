<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id_notification';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id_utilisateur', 'type', 'reference_table', 'reference_id', 'message', 'lue', 'cree_le',
    ];

    public function pourUtilisateur(int $idUtilisateur): array
    {
        return $this->where('id_utilisateur', $idUtilisateur)
            ->orderBy('cree_le', 'DESC')
            ->findAll();
    }

    public function compterNonLues(int $idUtilisateur): int
    {
        return $this->where('id_utilisateur', $idUtilisateur)
            ->where('lue', 0)
            ->countAllResults();
    }

    public function marquerCommeLue(int $idNotification, int $idUtilisateur): bool
    {
        // On vérifie que la notification appartient bien à l'utilisateur connecté,
        // pour qu'un client ne puisse jamais marquer la notification de quelqu'un d'autre.
        $notification = $this->where('id_notification', $idNotification)
            ->where('id_utilisateur', $idUtilisateur)
            ->first();

        if (! $notification) {
            return false;
        }

        return (bool) $this->update($idNotification, ['lue' => 1]);
    }

    public function marquerToutesCommeLues(int $idUtilisateur): void
    {
        $this->where('id_utilisateur', $idUtilisateur)
            ->where('lue', 0)
            ->set(['lue' => 1])
            ->update();
    }
}