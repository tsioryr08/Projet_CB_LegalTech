<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;
use App\Models\MaisonModel;
use App\Models\MaisonPhotoModel;
use App\Models\MaisonHistoriqueModel;
use App\Models\VilleModel;
use App\Models\DemandeModel;
use App\Models\NotificationModel;

class ClientController extends BaseController
{
    protected UtilisateurModel $utilisateurModel;
    protected MaisonModel $maisonModel;
    protected MaisonPhotoModel $maisonPhotoModel;
    protected MaisonHistoriqueModel $maisonHistoriqueModel;
    protected VilleModel $villeModel;
    protected DemandeModel $demandeModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->utilisateurModel      = new UtilisateurModel();
        $this->maisonModel           = new MaisonModel();
        $this->maisonPhotoModel      = new MaisonPhotoModel();
        $this->maisonHistoriqueModel = new MaisonHistoriqueModel();
        $this->villeModel            = new VilleModel();
        $this->demandeModel          = new DemandeModel();
        $this->notificationModel     = new NotificationModel();
    }

    // -----------------------------------------------------------------
    // C2 : CATALOGUE PUBLIC
    // -----------------------------------------------------------------

    public function catalogue(): string
    {
        $idVille = $this->request->getGet('ville') ? (int) $this->request->getGet('ville') : null;

        $maisons = $this->maisonModel->listerDisponibles($idVille);
        $villes  = $this->villeModel->orderBy('nom', 'ASC')->findAll();

        return view('client/catalogue', [
            'nom'              => session('prenoms') ?? session('nom'),
            'maisons'          => $maisons,
            'villes'           => $villes,
            'idVilleActif'     => $idVille,
            'nbNotifNonLues'   => $this->notificationModel->compterNonLues(session('id_utilisateur')),
        ]);
    }

    public function ficheMaison(int $idMaison): string
    {
        $maison = $this->maisonModel->trouverFicheDetail($idMaison);

        if (! $maison || $maison['statut'] !== 'disponible') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $photos     = $this->maisonPhotoModel->pourMaison($idMaison);
        $historique = $this->maisonHistoriqueModel->pourMaison($idMaison);

        $demandeEnCours = $this->demandeModel->aDemandeEnCours($idMaison, session('id_utilisateur'));

        return view('client/fiche_maison', [
            'maison'         => $maison,
            'photos'         => $photos,
            'historique'     => $historique,
            'demandeEnCours' => $demandeEnCours,
        ]);
    }

    // -----------------------------------------------------------------
    // C3 : DEMANDE DE LOCATION + SUIVI
    // -----------------------------------------------------------------

    /**
     * Vérifie que le profil contient le minimum requis pour louer
     * (CIN, sexe, date de naissance). Utilisé avant toute demande.
     */
    private function profilEstComplet(array $utilisateur): bool
    {
        return ! empty($utilisateur['cin_numero'])
            && ! empty($utilisateur['sexe'])
            && ! empty($utilisateur['date_naissance']);
    }

    public function demanderLocation(int $idMaison)
    {
        $idClient    = session('id_utilisateur');
        $utilisateur = $this->utilisateurModel->find($idClient);

        // Le profil n'est demandé qu'au moment où il devient réellement nécessaire.
        if (! $this->profilEstComplet($utilisateur)) {
            session()->set('retour_apres_profil', site_url('client/maison/' . $idMaison));

            return redirect()->to('/client/profil')
                ->with('info', 'Merci de compléter votre CIN, votre sexe et votre date de naissance avant de faire une demande de location.');
        }

        $maison = $this->maisonModel->trouverFicheDetail($idMaison);
        if (! $maison || $maison['statut'] !== 'disponible') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $resultat = $this->demandeModel->creerDemande($idMaison, $idClient);

        if ($resultat === false) {
            return redirect()->to('/client/maison/' . $idMaison)
                ->with('erreur', 'Vous avez déjà une demande en cours pour cette maison.');
        }

        return redirect()->to('/client/mes-demandes')
            ->with('succes', 'Votre demande a été envoyée au propriétaire.');
    }

    public function mesDemandes(): string
    {
        $demandes = $this->demandeModel->pourClient(session('id_utilisateur'));

        return view('client/mes_demandes', ['demandes' => $demandes]);
    }

    // -----------------------------------------------------------------
    // C4 : NOTIFICATIONS
    // -----------------------------------------------------------------

    public function notifications(): string
    {
        $idUtilisateur = session('id_utilisateur');
        $notifications = $this->notificationModel->pourUtilisateur($idUtilisateur);

        return view('client/notifications', ['notifications' => $notifications]);
    }

    public function marquerNotificationLue(int $idNotification)
    {
        $this->notificationModel->marquerCommeLue($idNotification, session('id_utilisateur'));

        return redirect()->back();
    }

    public function marquerToutesNotificationsLues()
    {
        $this->notificationModel->marquerToutesCommeLues(session('id_utilisateur'));

        return redirect()->back()->with('succes', 'Toutes les notifications ont été marquées comme lues.');
    }

    // -----------------------------------------------------------------
    // C1 (fin) : PROFIL CLIENT — CIN, sexe, date de naissance, etc.
    // -----------------------------------------------------------------

    public function profil(): string
    {
        $utilisateur = $this->utilisateurModel->find(session('id_utilisateur'));

        return view('client/profil', [
            'utilisateur' => $utilisateur,
        ]);
    }

    public function mettreAJourProfil()
    {
        $idUtilisateur = session('id_utilisateur');

        $regles = [
            'nom'                 => 'required|min_length[2]|max_length[100]',
            'prenoms'             => 'permit_empty|max_length[150]',
            'telephone'           => 'permit_empty|max_length[30]',
            'cin_numero'          => 'permit_empty|regex_match[/^\d{12}$/]',
            'cin_date_delivrance' => 'permit_empty|valid_date',
            'cin_lieu_delivrance' => 'permit_empty|max_length[100]',
            'sexe'                => 'permit_empty|in_list[M,F]',
            'date_naissance'      => 'permit_empty|valid_date',
            'profession'          => 'permit_empty|max_length[150]',
            'nif'                 => 'permit_empty|max_length[30]',
            'stat'                => 'permit_empty|max_length[30]',
        ];

        $messages = [
            'cin_numero' => [
                'regex_match' => 'Le numéro de CIN doit comporter exactement 12 chiffres.',
            ],
        ];

        if (! $this->validate($regles, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $donnees = [
            'nom'                 => $this->request->getPost('nom'),
            'prenoms'             => $this->request->getPost('prenoms'),
            'telephone'           => $this->request->getPost('telephone'),
            'cin_numero'          => $this->request->getPost('cin_numero') ?: null,
            'cin_date_delivrance' => $this->request->getPost('cin_date_delivrance') ?: null,
            'cin_lieu_delivrance' => $this->request->getPost('cin_lieu_delivrance') ?: null,
            'sexe'                => $this->request->getPost('sexe') ?: null,
            'date_naissance'      => $this->request->getPost('date_naissance') ?: null,
            'profession'          => $this->request->getPost('profession') ?: null,
            'nif'                 => $this->request->getPost('nif') ?: null,
            'stat'                => $this->request->getPost('stat') ?: null,
        ];

        $this->utilisateurModel->update($idUtilisateur, $donnees);

        session()->set([
            'nom'     => $donnees['nom'],
            'prenoms' => $donnees['prenoms'],
        ]);

        // Si l'utilisateur venait d'une tentative de demande de location,
        // on le ramène directement là où il voulait aller, sans détour inutile.
        $retour = session('retour_apres_profil');
        if ($retour) {
            session()->remove('retour_apres_profil');

            return redirect()->to($retour)
                ->with('succes', 'Profil complété. Vous pouvez maintenant envoyer votre demande.');
        }

        return redirect()->to('/client/profil')
            ->with('succes', 'Profil mis à jour avec succès.');
    }
}