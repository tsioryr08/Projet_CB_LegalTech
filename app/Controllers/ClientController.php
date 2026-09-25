<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;
use App\Models\MaisonModel;
use App\Models\MaisonPhotoModel;
use App\Models\MaisonHistoriqueModel;
use App\Models\VilleModel;

class ClientController extends BaseController
{
    protected UtilisateurModel $utilisateurModel;
    protected MaisonModel $maisonModel;
    protected MaisonPhotoModel $maisonPhotoModel;
    protected MaisonHistoriqueModel $maisonHistoriqueModel;
    protected VilleModel $villeModel;

    public function __construct()
    {
        $this->utilisateurModel      = new UtilisateurModel();
        $this->maisonModel           = new MaisonModel();
        $this->maisonPhotoModel      = new MaisonPhotoModel();
        $this->maisonHistoriqueModel = new MaisonHistoriqueModel();
        $this->villeModel            = new VilleModel();
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
            'nom'          => session('prenoms') ?? session('nom'),
            'maisons'      => $maisons,
            'villes'       => $villes,
            'idVilleActif' => $idVille,
        ]);
    }

    public function ficheMaison(int $idMaison): string
    {
        $maison = $this->maisonModel->trouverFicheDetail($idMaison);

        if (! $maison || $maison['statut'] !== 'disponible') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $photos      = $this->maisonPhotoModel->pourMaison($idMaison);
        $historique  = $this->maisonHistoriqueModel->pourMaison($idMaison);

        return view('client/fiche_maison', [
            'maison'     => $maison,
            'photos'     => $photos,
            'historique' => $historique,
        ]);
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

        return redirect()->to('/client/profil')
            ->with('succes', 'Profil mis à jour avec succès.');
    }
}