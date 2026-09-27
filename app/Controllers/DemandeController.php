<?php

namespace App\Controllers;

use App\Models\DemandeModel;
use App\Models\NotificationModel;

class DemandeController extends BaseController
{
    public function index(): string
    {
        $demandeModel = new DemandeModel();

        $demandes = $demandeModel
            ->select('demandes.*, maisons.titre AS maison_titre, utilisateurs.nom AS client_nom, utilisateurs.prenoms AS client_prenoms, utilisateurs.telephone AS client_telephone')
            ->join('maisons', 'maisons.id_maison = demandes.id_maison')
            ->join('utilisateurs', 'utilisateurs.id_utilisateur = demandes.id_client')
            ->where('maisons.id_proprietaire', (int) session('id_utilisateur'))
            ->orderBy('demandes.date_demande', 'DESC')
            ->findAll();

        return view('proprietaire/demandes/liste', [
            'demandes' => $demandes,
        ]);
    }

    public function valider(int $idDemande)
    {
        $demande = $this->recupererDemandePourProprietaire($idDemande);

        if ($demande === null) {
            return redirect()->to('/proprietaire/demandes')->with('erreur', 'Demande introuvable.');
        }

        if ($demande['statut'] !== 'envoyee') {
            return redirect()->to('/proprietaire/demandes')->with('erreur', 'Cette demande a déjà été traitée.');
        }

        (new DemandeModel())->update($idDemande, [
            'statut'          => 'validee',
            'date_traitement' => date('Y-m-d H:i:s'),
        ]);

        (new NotificationModel())->insert([
            'id_utilisateur'  => $demande['id_client'],
            'type'            => 'demande_validee',
            'reference_table' => 'demandes',
            'reference_id'    => $idDemande,
            'message'         => 'Votre demande de location a été validée. Vous pouvez maintenant compléter votre dossier de location.',
        ]);

        session()->setFlashdata('succes', 'Demande validée.');

        return redirect()->to('/proprietaire/demandes');
    }

    public function refuser(int $idDemande)
    {
        $demande = $this->recupererDemandePourProprietaire($idDemande);

        if ($demande === null) {
            return redirect()->to('/proprietaire/demandes')->with('erreur', 'Demande introuvable.');
        }

        if ($demande['statut'] !== 'envoyee') {
            return redirect()->to('/proprietaire/demandes')->with('erreur', 'Cette demande a déjà été traitée.');
        }

        $motif = trim((string) $this->request->getPost('motif_refus'));

        if ($motif === '') {
            return redirect()->back()->with('erreur', "Merci d'indiquer un motif de refus.");
        }

        (new DemandeModel())->update($idDemande, [
            'statut'          => 'refusee',
            'motif_refus'     => $motif,
            'date_traitement' => date('Y-m-d H:i:s'),
        ]);

        (new NotificationModel())->insert([
            'id_utilisateur'  => $demande['id_client'],
            'type'            => 'demande_refusee',
            'reference_table' => 'demandes',
            'reference_id'    => $idDemande,
            'message'         => 'Votre demande de location a été refusée. Motif : ' . $motif,
        ]);

        session()->setFlashdata('succes', 'Demande refusée.');

        return redirect()->to('/proprietaire/demandes');
    }

    private function recupererDemandePourProprietaire(int $idDemande): ?array
    {
        $demande = (new DemandeModel())
            ->select('demandes.*, maisons.id_proprietaire AS id_proprietaire')
            ->join('maisons', 'maisons.id_maison = demandes.id_maison')
            ->where('demandes.id_demande', $idDemande)
            ->first();

        if ($demande === null || (int) $demande['id_proprietaire'] !== (int) session('id_utilisateur')) {
            return null;
        }

        return $demande;
    }
}