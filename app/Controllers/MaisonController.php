<?php

namespace App\Controllers;

use App\Models\MaisonHistoriqueModel;
use App\Models\MaisonModel;
use App\Models\MaisonPhotoModel;
use App\Models\VilleModel;

class MaisonController extends BaseController
{
    // -----------------------------------------------------------------
    // Liste + CRUD maison
    // -----------------------------------------------------------------

    public function index(): string
    {
        $maisonModel = new MaisonModel();

        return view('proprietaire/maisons/liste', [
            'maisons' => $maisonModel->pourProprietaire((int) session('id_utilisateur')),
        ]);
    }

    public function formulaireAjout(): string
    {
        return view('proprietaire/maisons/formulaire', [
            'maison' => null,
            'villes' => (new VilleModel())->findAll(),
        ]);
    }

    public function ajouter()
    {
        $maisonModel = new MaisonModel();

        $donnees                    = $this->donneesMaisonDepuisRequete();
        $donnees['id_proprietaire'] = (int) session('id_utilisateur');

        if (! $maisonModel->insert($donnees)) {
            return view('proprietaire/maisons/formulaire', [
                'maison'     => $donnees,
                'villes'     => (new VilleModel())->findAll(),
                'validation' => $maisonModel->errors(),
            ]);
        }

        session()->setFlashdata('succes', 'Maison ajoutée.');

        return redirect()->to('/proprietaire/maisons');
    }

    public function formulaireModifier(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        return view('proprietaire/maisons/formulaire', [
            'maison' => $maison,
            'villes' => (new VilleModel())->findAll(),
        ]);
    }

    public function modifier(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        $maisonModel = new MaisonModel();
        $donnees     = $this->donneesMaisonDepuisRequete();

        if (! $maisonModel->update($idMaison, $donnees)) {
            return view('proprietaire/maisons/formulaire', [
                'maison'     => array_merge($maison, $donnees),
                'villes'     => (new VilleModel())->findAll(),
                'validation' => $maisonModel->errors(),
            ]);
        }

        session()->setFlashdata('succes', 'Maison modifiée.');

        return redirect()->to('/proprietaire/maisons');
    }

    public function supprimer(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        // Les photos et l'historique agrégé sont supprimés en cascade par la base
        // (ON DELETE CASCADE sur maison_photos et maison_historique_).
        (new MaisonModel())->delete($idMaison);

        session()->setFlashdata('succes', 'Maison supprimée.');

        return redirect()->to('/proprietaire/maisons');
    }

    public function changerStatut(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        $statut = $this->request->getPost('statut');

        if (! in_array($statut, ['disponible', 'en_attente', 'loue', 'archive'], true)) {
            return redirect()->back()->with('erreur', 'Statut invalide.');
        }

        (new MaisonModel())->update($idMaison, ['statut' => $statut]);

        session()->setFlashdata('succes', 'Statut mis à jour.');

        return redirect()->to('/proprietaire/maisons');
    }

    // -----------------------------------------------------------------
    // Photos
    // -----------------------------------------------------------------

    public function gererPhotos(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        return view('proprietaire/maisons/photos', [
            'maison' => $maison,
            'photos' => (new MaisonPhotoModel())->pourMaison($idMaison),
        ]);
    }

    public function ajouterPhoto(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        $fichier = $this->request->getFile('photo');

        if ($fichier === null || ! $fichier->isValid() || $fichier->hasMoved()) {
            return redirect()->back()->with('erreur', 'Aucune photo valide reçue.');
        }

        if (! in_array($fichier->getClientMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return redirect()->back()->with('erreur', "Format d'image non supporté (jpeg, png, webp uniquement).");
        }

        $nomFichier = $fichier->getRandomName();
        $dossier    = 'uploads/maisons/' . $idMaison;
        $fichier->move(FCPATH . $dossier, $nomFichier);

        (new MaisonPhotoModel())->insert([
            'id_maison' => $idMaison,
            'chemin'    => $dossier . '/' . $nomFichier,
            'ordre'     => (int) ($this->request->getPost('ordre') ?? 0),
        ]);

        session()->setFlashdata('succes', 'Photo ajoutée.');

        return redirect()->to("/proprietaire/maisons/{$idMaison}/photos");
    }

    public function supprimerPhoto(int $idMaison, int $idPhoto)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        $photoModel = new MaisonPhotoModel();
        $photo      = $photoModel->find($idPhoto);

        if ($photo !== null && (int) $photo['id_maison'] === $idMaison) {
            $chemin = FCPATH . $photo['chemin'];

            if (is_file($chemin)) {
                unlink($chemin);
            }

            $photoModel->delete($idPhoto);
        }

        return redirect()->to("/proprietaire/maisons/{$idMaison}/photos");
    }

    // -----------------------------------------------------------------
    // Historique agrégé (compteurs anonymes)
    // -----------------------------------------------------------------

    public function historique(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        $historique = (new MaisonHistoriqueModel())->pourMaison($idMaison) ?? [
            'nb_anciens_locataires' => 0,
            'nb_litiges_declares'   => 0,
            'nb_impayes_declares'   => 0,
        ];

        return view('proprietaire/maisons/historique', [
            'maison'     => $maison,
            'historique' => $historique,
        ]);
    }

    public function mettreAJourHistorique(int $idMaison)
    {
        $maison = $this->recupererMaisonDuProprietaire($idMaison);

        if ($maison === null) {
            return redirect()->to('/proprietaire/maisons')->with('erreur', 'Maison introuvable.');
        }

        $rules = [
            'nb_anciens_locataires' => 'permit_empty|is_natural',
            'nb_litiges_declares'   => 'permit_empty|is_natural',
            'nb_impayes_declares'   => 'permit_empty|is_natural',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $historiqueModel = new MaisonHistoriqueModel();

        $donnees = [
            'id_maison'             => $idMaison,
            'nb_anciens_locataires' => (int) ($this->request->getPost('nb_anciens_locataires') ?? 0),
            'nb_litiges_declares'   => (int) ($this->request->getPost('nb_litiges_declares') ?? 0),
            'nb_impayes_declares'   => (int) ($this->request->getPost('nb_impayes_declares') ?? 0),
        ];

        if ($historiqueModel->find($idMaison)) {
            $historiqueModel->update($idMaison, $donnees);
        } else {
            $historiqueModel->insert($donnees);
        }

        session()->setFlashdata('succes', 'Historique mis à jour.');

        return redirect()->to("/proprietaire/maisons/{$idMaison}/historique");
    }

    // -----------------------------------------------------------------
    // Utilitaires internes
    // -----------------------------------------------------------------

    private function donneesMaisonDepuisRequete(): array
    {
        return [
            'id_ville'             => $this->request->getPost('id_ville'),
            'titre'                => $this->request->getPost('titre'),
            'adresse'              => $this->request->getPost('adresse'),
            'description'          => $this->request->getPost('description'),
            'type_bien'            => $this->request->getPost('type_bien'),
            'nb_chambres'          => $this->request->getPost('nb_chambres'),
            'superficie_m2'        => $this->request->getPost('superficie_m2'),
            'loyer_mensuel'        => $this->request->getPost('loyer_mensuel'),
            'valeur_immeuble'      => $this->request->getPost('valeur_immeuble'),
            'date_construction'    => $this->request->getPost('date_construction'),
            'titre_foncier_numero' => $this->request->getPost('titre_foncier_numero'),
            'usage_autorise'       => $this->request->getPost('usage_autorise'),
        ];
    }

    private function recupererMaisonDuProprietaire(int $idMaison): ?array
    {
        $maison = (new MaisonModel())->find($idMaison);

        if ($maison === null || (int) $maison['id_proprietaire'] !== (int) session('id_utilisateur')) {
            return null;
        }

        return $maison;
    }
}
