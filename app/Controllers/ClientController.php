<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;
use App\Models\MaisonModel;
use App\Models\MaisonPhotoModel;
use App\Models\MaisonHistoriqueModel;
use App\Models\VilleModel;
use App\Models\DemandeModel;
use App\Models\ContratModel;
use App\Models\NotificationModel;
use App\Models\DossierLocationModel;
use App\Models\ContratModel;
use App\Models\AvenantModel;
use App\Models\SignatureModel;

class ClientController extends BaseController
{
    protected UtilisateurModel $utilisateurModel;
    protected MaisonModel $maisonModel;
    protected MaisonPhotoModel $maisonPhotoModel;
    protected MaisonHistoriqueModel $maisonHistoriqueModel;
    protected VilleModel $villeModel;
    protected DemandeModel $demandeModel;
    protected NotificationModel $notificationModel;
    protected DossierLocationModel $dossierLocationModel;
    protected ContratModel $contratModel;
    protected AvenantModel $avenantModel;
    protected SignatureModel $signatureModel;

    public function __construct()
    {
        $this->utilisateurModel      = new UtilisateurModel();
        $this->maisonModel           = new MaisonModel();
        $this->maisonPhotoModel      = new MaisonPhotoModel();
        $this->maisonHistoriqueModel = new MaisonHistoriqueModel();
        $this->villeModel            = new VilleModel();
        $this->demandeModel          = new DemandeModel();
        $this->notificationModel     = new NotificationModel();
        $this->dossierLocationModel  = new DossierLocationModel();
        $this->contratModel          = new ContratModel();
        $this->avenantModel          = new AvenantModel();
        $this->signatureModel        = new SignatureModel();
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

        $contratModel = new ContratModel();
        foreach ($demandes as &$demande) {
            $demande['contrat'] = $contratModel
                ->where('id_client', (int) session('id_utilisateur'))
                ->where('id_maison', (int) $demande['id_maison'])
                ->orderBy('cree_le', 'DESC')
                ->first();
        }
        unset($demande);

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
    // C5 : FORMULAIRE DU DOSSIER DE LOCATION
    // -----------------------------------------------------------------

    /**
     * Contrôle basique LOCAL, en attendant le vrai moteur de règles LegalTech
     * (lot P4, côté Olivier). A REMPLACER par l'appel à sa fonction/endpoint
     * dès qu'il aura terminé — voir TODO plus bas.
     *
     * Retourne ['statut' => 'conforme'|'alerte'|'bloque', 'messages' => [...]]
     */
    private function controlerDossierBasique(array $demande, array $donnees): array
    {
        $messages = [];
        $statut   = 'conforme';

        // Suroccupation : plus de 3 occupants par chambre (seuil de conception, à valider)
        if ($donnees['nb_occupants'] > 3 * $demande['nb_chambres']) {
            $statut = 'alerte';
            $messages[] = "Le nombre d'occupants ({$donnees['nb_occupants']}) semble élevé pour {$demande['nb_chambres']} chambre(s).";
        }

        // Cohérence usage déclaré / usage autorisé sur la maison
        if ($donnees['usage_declare'] !== $demande['usage_autorise'] && $demande['usage_autorise'] !== 'mixte') {
            $statut = 'bloque';
            $messages[] = "L'usage déclaré ({$donnees['usage_declare']}) ne correspond pas à l'usage autorisé pour ce bien ({$demande['usage_autorise']}).";
        }

        // NIF/STAT requis si usage commercial ou mixte
        if (in_array($donnees['usage_declare'], ['commercial', 'mixte'], true)) {
            $utilisateur = $this->utilisateurModel->find(session('id_utilisateur'));
            if (empty($utilisateur['nif']) || empty($utilisateur['stat'])) {
                if ($statut === 'conforme') {
                    $statut = 'alerte';
                }
                $messages[] = 'Le NIF et le STAT sont requis pour un usage commercial ou mixte. Complétez votre profil.';
            }
        }

        // TODO P4 : remplacer ce contrôle local par l'appel au moteur de règles
        // complet d'Olivier (format/district/sexe CIN, capacité mineur/condamné,
        // plafonds caution/loyer), et enregistrer le détail dans regles_resultats
        // avec le code de chaque regles_legaltech déclenchée.

        return ['statut' => $statut, 'messages' => $messages];
    }

    public function formulaireDossier(int $idDemande): string
    {
        $idClient = session('id_utilisateur');
        $demande  = $this->demandeModel->trouverPourClient($idDemande, $idClient);

        if (! $demande) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($demande['statut'] !== 'validee') {
            return redirect()->to('/client/mes-demandes')
                ->with('erreur', 'Le propriétaire doit d\'abord valider votre demande avant de remplir le dossier.');
        }

        $dossierExistant = $this->dossierLocationModel->pourDemande($idDemande);

        return view('client/dossier_location', [
            'demande'          => $demande,
            'dossierExistant'  => $dossierExistant,
        ]);
    }

    public function enregistrerDossier(int $idDemande)
    {
        $idClient = session('id_utilisateur');
        $demande  = $this->demandeModel->trouverPourClient($idDemande, $idClient);

        if (! $demande || $demande['statut'] !== 'validee') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $regles = [
            'nb_occupants'      => 'required|is_natural_no_zero|less_than_equal_to[50]',
            'usage_declare'     => 'required|in_list[habitation,commercial,mixte]',
            'activite_declaree' => 'permit_empty|max_length[150]',
        ];

        if (! $this->validate($regles)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $donnees = [
            'nb_occupants'      => (int) $this->request->getPost('nb_occupants'),
            'usage_declare'     => $this->request->getPost('usage_declare'),
            'activite_declaree' => $this->request->getPost('activite_declaree') ?: null,
        ];

        $controle = $this->controlerDossierBasique($demande, $donnees);

        $dossierExistant = $this->dossierLocationModel->pourDemande($idDemande);

        $donneesAEnregistrer = array_merge($donnees, [
            'id_demande'               => $idDemande,
            'statut_validation_legale' => $controle['statut'],
        ]);

        if ($dossierExistant) {
            $this->dossierLocationModel->update($dossierExistant['id_dossier'], $donneesAEnregistrer);
        } else {
            $this->dossierLocationModel->insert($donneesAEnregistrer);
        }

        if ($controle['statut'] === 'bloque') {
            return redirect()->to('/client/dossier/' . $idDemande)
                ->with('erreur', 'Dossier bloqué : ' . implode(' ', $controle['messages']));
        }

        $messageSucces = 'Dossier enregistré avec succès.';
        if (! empty($controle['messages'])) {
            $messageSucces .= ' Alerte(s) : ' . implode(' ', $controle['messages']);
        }

        return redirect()->to('/client/mes-demandes')->with('succes', $messageSucces);
    }

    // -----------------------------------------------------------------
    // C6 : MON CONTRAT — lecture, signature, avenants
    // -----------------------------------------------------------------

    public function mesContrats(): string
    {
        $contrats = $this->contratModel->pourClient(session('id_utilisateur'));

        return view('client/mes_contrats', ['contrats' => $contrats]);
    }

    public function monContrat(int $idContrat): string
    {
        $idClient = session('id_utilisateur');
        $contrat  = $this->contratModel->trouverPourClient($idContrat, $idClient);

        if (! $contrat) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $avenants = $this->avenantModel->pourContrat($idContrat);

        // Le bouton de signature du locataire n'apparaît que si le bailleur
        // a déjà signé (flux décidé : bailleur signe en premier, locataire ensuite).
        $peutSigner = $contrat['statut'] === 'signe_bailleur'
            && ! $this->signatureModel->existeDeja($idContrat, null, 'locataire');

        return view('client/mon_contrat', [
            'contrat'    => $contrat,
            'avenants'   => $avenants,
            'peutSigner' => $peutSigner,
        ]);
    }

    public function signerContrat(int $idContrat)
    {
        $idClient = session('id_utilisateur');
        $contrat  = $this->contratModel->trouverPourClient($idContrat, $idClient);

        if (! $contrat || $contrat['statut'] !== 'signe_bailleur') {
            return redirect()->back()->with('erreur', 'Ce contrat ne peut pas encore être signé.');
        }

        if ($this->signatureModel->existeDeja($idContrat, null, 'locataire')) {
            return redirect()->back()->with('erreur', 'Vous avez déjà signé ce contrat.');
        }

        $utilisateur = $this->utilisateurModel->find($idClient);
        $nomAffiche  = trim($utilisateur['nom'] . ' ' . ($utilisateur['prenoms'] ?? ''));

        $this->signatureModel->insert([
            'id_contrat'      => $idContrat,
            'id_utilisateur'  => $idClient,
            'role_signataire' => 'locataire',
            'nom_affiche'     => $nomAffiche,
            'signe_le'        => date('Y-m-d H:i:s'),
            'adresse_ip'      => $this->request->getIPAddress(),
        ]);

        // Une fois les deux parties signataires, le contrat devient actif.
        $this->contratModel->update($idContrat, ['statut' => 'actif']);

        return redirect()->to('/client/contrat/' . $idContrat)
            ->with('succes', 'Contrat signé avec succès. Il est maintenant actif.');
    }

    public function signerAvenant(int $idAvenant)
    {
        $idClient = session('id_utilisateur');
        $avenant  = $this->avenantModel->trouver($idAvenant);

        if (! $avenant) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Vérifie que l'avenant appartient bien à un contrat du client connecté
        $contrat = $this->contratModel->trouverPourClient($avenant['id_contrat'], $idClient);
        if (! $contrat) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($avenant['statut'] !== 'signe_bailleur') {
            return redirect()->back()->with('erreur', 'Cet avenant ne peut pas encore être signé.');
        }

        if ($this->signatureModel->existeDeja(null, $idAvenant, 'locataire')) {
            return redirect()->back()->with('erreur', 'Vous avez déjà signé cet avenant.');
        }

        $utilisateur = $this->utilisateurModel->find($idClient);
        $nomAffiche  = trim($utilisateur['nom'] . ' ' . ($utilisateur['prenoms'] ?? ''));

        $this->signatureModel->insert([
            'id_avenant'      => $idAvenant,
            'id_utilisateur'  => $idClient,
            'role_signataire' => 'locataire',
            'nom_affiche'     => $nomAffiche,
            'signe_le'        => date('Y-m-d H:i:s'),
            'adresse_ip'      => $this->request->getIPAddress(),
        ]);

        $this->avenantModel->update($idAvenant, ['statut' => 'actif']);

        return redirect()->to('/client/contrat/' . $avenant['id_contrat'])
            ->with('succes', 'Avenant signé avec succès.');
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