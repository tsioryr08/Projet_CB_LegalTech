<?php

namespace App\Controllers;

use App\Libraries\MoteurLegaltechService;
use App\Models\DemandeModel;
use App\Models\MaisonModel;
use App\Models\UtilisateurModel;

class ReglesController extends BaseController
{
    /**
     * Point d'entrée interne appelé en direct (AJAX) par le formulaire du dossier
     * de location (C5) pour afficher les alertes/blocages avant l'enregistrement.
     *
     * Ne persiste rien : c'est un aperçu en direct. La persistance dans
     * regles_resultats se fait via MoteurLegaltechService::evaluerEtPersisterPourDossier()
     * une fois le dossier réellement enregistré.
     *
     * Attend en POST : situation_client, usage_declare, activite_declaree, nb_occupants.
     * Le reste (CIN, sexe, date de naissance, NIF/STAT, infos de la maison) est relu
     * depuis la base à partir de $idDemande.
     */
    public function verifierLive(int $idDemande)
    {
        $demande = (new DemandeModel())->find($idDemande);

        if ($demande === null || (int) $demande['id_client'] !== (int) session('id_utilisateur')) {
            return $this->response->setStatusCode(404)->setJSON(['erreur' => 'Demande introuvable.']);
        }

        $maison = (new MaisonModel())->find($demande['id_maison']);
        $client = (new UtilisateurModel())->find($demande['id_client']);

        $donnees = [
            'cin_numero'        => $client['cin_numero'] ?? null,
            'sexe'              => $client['sexe'] ?? null,
            'date_naissance'    => $client['date_naissance'] ?? null,
            'nif'               => $client['nif'] ?? null,
            'stat'              => $client['stat'] ?? null,
            'situation_client'  => $this->request->getPost('situation_client'),
            'usage_declare'     => $this->request->getPost('usage_declare'),
            'activite_declaree' => $this->request->getPost('activite_declaree'),
            'nb_occupants'      => $this->request->getPost('nb_occupants'),
            'nb_chambres'       => $maison['nb_chambres'] ?? null,
            'loyer_mensuel'     => $maison['loyer_mensuel'] ?? null,
            'valeur_immeuble'   => $maison['valeur_immeuble'] ?? null,
            'date_construction' => $maison['date_construction'] ?? null,
        ];

        $resultat = (new MoteurLegaltechService())->resultats($donnees);

        return $this->response->setJSON($resultat);
    }
}
