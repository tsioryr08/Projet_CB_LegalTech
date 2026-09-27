<?php

namespace App\Controllers;

use App\Libraries\ContratPdfService;
use App\Libraries\MoteurLegaltechService;
use App\Models\AvenantModel;
use App\Models\ContratModel;
use App\Models\DemandeModel;
use App\Models\FicheFiscaleModel;
use App\Models\MaisonModel;
use App\Models\NotificationModel;
use App\Models\SignatureModel;
use App\Models\TypeContratModel;
use App\Models\UtilisateurModel;

class ContratController extends BaseController
{
    public function indexProprietaire(): string
    {
        $contrats = (new ContratModel())
            ->avecRelations()
            ->select('contrats.*, maisons.titre AS maison_titre, utilisateurs.nom AS client_nom, utilisateurs.prenoms AS client_prenoms')
            ->join('maisons', 'maisons.id_maison = contrats.id_maison')
            ->join('utilisateurs', 'utilisateurs.id_utilisateur = contrats.id_client', 'left')
            ->where('contrats.id_proprietaire', (int) session('id_utilisateur'))
            ->orderBy('contrats.cree_le', 'DESC')
            ->findAll();

        return view('proprietaire/contrats/liste', ['contrats' => $contrats]);
    }

    public function formulaireGenerer(int $idDemande)
    {
        $data = $this->chargerContexteGenerer($idDemande);

        if ($data === null) {
            return redirect()->to('/proprietaire/demandes')->with('erreur', 'Demande introuvable.');
        }

        return view('proprietaire/contrats/generer', $data);
    }

    public function generer(int $idDemande)
    {
        $contexte = $this->chargerContexteGenerer($idDemande);
        if ($contexte === null) {
            return redirect()->to('/proprietaire/demandes')->with('erreur', 'Demande introuvable.');
        }

        $usage = $this->request->getPost('usage_contrat') ?: ($contexte['usageParDefaut'] ?? 'habitation');
        $donnees = [
            'usage_declare' => $usage,
            'nb_occupants' => (int) ($this->request->getPost('nb_occupants') ?: 1),
            'situation_client' => $this->request->getPost('situation_client') ?: 'libre',
            'activite_declaree' => trim((string) $this->request->getPost('activite_declaree')),
            'depot_garantie' => (float) ($this->request->getPost('depot_garantie') ?: 0),
            'date_debut' => $this->request->getPost('date_debut') ?: date('Y-m-d'),
            'date_fin' => $this->request->getPost('date_fin') ?: null,
        ];

        $analyse = (new MoteurLegaltechService())->resultats(array_merge($contexte['donneesLegaltech'], $donnees));
        if ($analyse['statut_validation_legale'] === 'bloque') {
            return view('proprietaire/contrats/generer', $contexte + [
                'erreur' => 'Le contrat ne peut pas être généré tant que des règles bloquantes sont présentes.',
                'analyse' => $analyse,
            ]);
        }

        $typeContrat = (new TypeContratModel())->trouverParCode($usage);
        if ($typeContrat === null) {
            return redirect()->back()->with('erreur', 'Type de contrat introuvable pour cet usage.');
        }

        $contratModel = new ContratModel();
        $ficheModel = new FicheFiscaleModel();
        $pdfService = new ContratPdfService();

        $db = db_connect();
        $db->transStart();

        $dossierId = $this->enregistrerDossier($idDemande, $donnees, $analyse['statut_validation_legale']);
        $contratExistant = $contratModel->where('id_dossier', $dossierId)->first();
        if ($contratExistant !== null) {
            $db->transComplete();

            return redirect()->to('/proprietaire/contrats/' . $contratExistant['id_contrat'])
                ->with('info', 'Un contrat existe déjà pour ce dossier.');
        }

        $numeroContrat = sprintf('CTR-%s-%s', date('Y'), strtoupper(bin2hex(random_bytes(3))));
        $taux = (float) $typeContrat['taux_enregistrement'];
        $loyerMensuel = (float) $contexte['maison']['loyer_mensuel'];
        $montantTotalLoyers = $loyerMensuel * 12;
        $montantDroit = round($montantTotalLoyers * ($taux / 100), 2);
        $contenuHtml = $this->construireContenuHtml($contexte, $typeContrat, $donnees, $montantDroit);
        $hash = hash('sha256', $contenuHtml);

        $pdfPath = 'uploads/contrats/' . date('Y') . '/' . $numeroContrat . '.pdf';
        $pdf = $pdfService->genererPdfTextuel('Contrat de bail ' . ($typeContrat['libelle'] ?? ''), $this->lignesContrat($contexte, $typeContrat, $donnees, $montantDroit, $hash));
        if (! is_dir(dirname(FCPATH . $pdfPath))) {
            mkdir(dirname(FCPATH . $pdfPath), 0775, true);
        }
        file_put_contents(FCPATH . $pdfPath, $pdf);

        $idContrat = $contratModel->insert([
            'id_dossier' => $dossierId,
            'id_type_contrat' => (int) $typeContrat['id_type_contrat'],
            'id_maison' => (int) $contexte['maison']['id_maison'],
            'id_client' => (int) $contexte['client']['id_utilisateur'],
            'id_proprietaire' => (int) $contexte['proprietaire']['id_utilisateur'],
            'numero_contrat' => $numeroContrat,
            'loyer_mensuel' => $loyerMensuel,
            'depot_garantie' => (float) $donnees['depot_garantie'],
            'date_debut' => $donnees['date_debut'],
            'date_fin' => $donnees['date_fin'],
            'duree_indeterminee' => empty($donnees['date_fin']) ? 1 : 0,
            'taux_enregistrement_applique' => $taux,
            'montant_droit_enregistrement' => $montantDroit,
            'contenu_pdf_chemin' => $pdfPath,
            'contenu_hash_sha256' => $hash,
            'statut' => 'genere',
        ], true);

        $fichePath = 'uploads/fiches_fiscales/' . date('Y') . '/' . $numeroContrat . '.pdf';
        $fichePdf = $pdfService->genererPdfTextuel('Fiche fiscale - ' . $numeroContrat, $this->lignesFicheFiscale($contexte, $typeContrat, $donnees, $montantTotalLoyers, $montantDroit));
        if (! is_dir(dirname(FCPATH . $fichePath))) {
            mkdir(dirname(FCPATH . $fichePath), 0775, true);
        }
        file_put_contents(FCPATH . $fichePath, $fichePdf);

        $ficheModel->insert([
            'id_contrat' => $idContrat,
            'montant_total_loyers' => $montantTotalLoyers,
            'taux_applique' => $taux,
            'montant_droit' => $montantDroit,
            'date_limite_enreg' => date('Y-m-d', strtotime($donnees['date_debut'] . ' +30 days')),
            'statut_enregistrement' => 'non_enregistre',
            'chemin_pdf' => $fichePath,
        ]);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contexte['client']['id_utilisateur'],
            'type' => 'contrat_a_signer',
            'reference_table' => 'contrats',
            'reference_id' => $idContrat,
            'message' => 'Votre contrat est prêt. Le propriétaire doit d’abord le signer avant votre validation.',
        ]);

        $this->planifierAlerteEcheance((int) $contexte['proprietaire']['id_utilisateur'], $idContrat, $donnees['date_fin']);

        $db->transComplete();

        return redirect()->to('/proprietaire/contrats/' . $idContrat)->with('succes', 'Contrat généré avec succès.');
    }

    public function detailProprietaire(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Contrat introuvable.');
        }

        return view('proprietaire/contrats/detail', $contrat);
    }

    public function detailClient(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'client');
        if ($contrat === null) {
            return redirect()->to('/client/mes-demandes')->with('erreur', 'Contrat introuvable.');
        }

        return view('client/contrat', $contrat);
    }

    public function signerBailleur(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Contrat introuvable.');
        }

        if (! in_array($contrat['statut'], ['genere'], true)) {
            return redirect()->back()->with('erreur', 'Le contrat a déjà été signé ou activé.');
        }

        (new SignatureModel())->insert([
            'id_contrat' => $idContrat,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'bailleur',
            'nom_affiche' => trim((string) session('prenoms') . ' ' . (string) session('nom')),
            'adresse_ip' => $this->request->getIPAddress(),
        ]);

        (new ContratModel())->update($idContrat, ['statut' => 'signe_bailleur']);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_client'],
            'type' => 'contrat_a_signer',
            'reference_table' => 'contrats',
            'reference_id' => $idContrat,
            'message' => 'Le bailleur a signé le contrat. Vous pouvez maintenant le signer.',
        ]);

        return redirect()->back()->with('succes', 'Contrat signé côté bailleur.');
    }

    public function signerLocataire(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'client');
        if ($contrat === null) {
            return redirect()->to('/client/mes-demandes')->with('erreur', 'Contrat introuvable.');
        }

        if ($contrat['statut'] !== 'signe_bailleur') {
            return redirect()->back()->with('erreur', 'La signature du bailleur est requise avant la vôtre.');
        }

        (new SignatureModel())->insert([
            'id_contrat' => $idContrat,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'locataire',
            'nom_affiche' => trim((string) session('prenoms') . ' ' . (string) session('nom')),
            'adresse_ip' => $this->request->getIPAddress(),
        ]);

        (new ContratModel())->update($idContrat, ['statut' => 'actif']);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_proprietaire'],
            'type' => 'contrat_a_signer',
            'reference_table' => 'contrats',
            'reference_id' => $idContrat,
            'message' => 'Le locataire a signé le contrat. Le bail est désormais actif.',
        ]);

        return redirect()->back()->with('succes', 'Contrat signé.');
    }

    public function telechargerPdf(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), (string) session('role'));
        if ($contrat === null || empty($contrat['contenu_pdf_chemin'])) {
            return redirect()->back()->with('erreur', 'PDF introuvable.');
        }

        $chemin = FCPATH . $contrat['contenu_pdf_chemin'];
        if (! is_file($chemin)) {
            return redirect()->back()->with('erreur', 'Le fichier PDF est manquant.');
        }

        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($chemin) . '"')
            ->setBody((string) file_get_contents($chemin));
    }

    public function telechargerFicheFiscale(int $idContrat)
    {
        $fiche = (new FicheFiscaleModel())->where('id_contrat', $idContrat)->first();
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), (string) session('role'));
        if ($fiche === null || $contrat === null || empty($fiche['chemin_pdf'])) {
            return redirect()->back()->with('erreur', 'Fiche fiscale introuvable.');
        }

        $chemin = FCPATH . $fiche['chemin_pdf'];
        if (! is_file($chemin)) {
            return redirect()->back()->with('erreur', 'Le fichier PDF de la fiche fiscale est manquant.');
        }

        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($chemin) . '"')
            ->setBody((string) file_get_contents($chemin));
    }

    public function avenants(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), (string) session('role'));
        if ($contrat === null) {
            return redirect()->back()->with('erreur', 'Contrat introuvable.');
        }

        $avenants = (new AvenantModel())->pourContrat($idContrat);
        return view('proprietaire/contrats/detail', $contrat + ['avenants' => $avenants]);
    }

    public function signerAvenantBailleur(int $idAvenant)
    {
        $avenantModel = new AvenantModel();
        $avenant = $avenantModel->find($idAvenant);

        if ($avenant === null) {
            return redirect()->back()->with('erreur', 'Avenant introuvable.');
        }

        $contrat = $this->chargerContratAccessible((int) $avenant['id_contrat'], (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->back()->with('erreur', 'Avenant introuvable.');
        }

        if ($avenant['statut'] !== 'propose') {
            return redirect()->back()->with('erreur', 'Cet avenant a déjà été traité.');
        }

        (new SignatureModel())->insert([
            'id_avenant' => $idAvenant,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'bailleur',
            'nom_affiche' => trim((string) session('prenoms') . ' ' . (string) session('nom')),
            'adresse_ip' => $this->request->getIPAddress(),
        ]);

        $avenantModel->update($idAvenant, ['statut' => 'signe_bailleur']);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_client'],
            'type' => 'avenant_a_signer',
            'reference_table' => 'avenants',
            'reference_id' => $idAvenant,
            'message' => 'Un avenant a été signé par le bailleur. Vous pouvez maintenant le signer.',
        ]);

        return redirect()->back()->with('succes', 'Avenant signé côté bailleur.');
    }

    public function signerAvenantLocataire(int $idAvenant)
    {
        $avenantModel = new AvenantModel();
        $avenant = $avenantModel->find($idAvenant);

        if ($avenant === null) {
            return redirect()->back()->with('erreur', 'Avenant introuvable.');
        }

        $contrat = $this->chargerContratAccessible((int) $avenant['id_contrat'], (int) session('id_utilisateur'), 'client');
        if ($contrat === null) {
            return redirect()->back()->with('erreur', 'Avenant introuvable.');
        }

        if ($avenant['statut'] !== 'signe_bailleur') {
            return redirect()->back()->with('erreur', 'La signature du bailleur est requise avant la vôtre.');
        }

        (new SignatureModel())->insert([
            'id_avenant' => $idAvenant,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'locataire',
            'nom_affiche' => trim((string) session('prenoms') . ' ' . (string) session('nom')),
            'adresse_ip' => $this->request->getIPAddress(),
        ]);

        $avenantModel->update($idAvenant, ['statut' => 'actif']);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_proprietaire'],
            'type' => 'avenant_a_signer',
            'reference_table' => 'avenants',
            'reference_id' => $idAvenant,
            'message' => 'Le locataire a signé l\'avenant. Il est désormais actif.',
        ]);

        return redirect()->back()->with('succes', 'Avenant signé.');
    }

    private function chargerContexteGenerer(int $idDemande): ?array
    {
        $demande = (new DemandeModel())
            ->select('demandes.*, dossiers_location.id_dossier, dossiers_location.usage_declare, dossiers_location.nb_occupants, dossiers_location.situation_client, dossiers_location.activite_declaree')
            ->join('dossiers_location', 'dossiers_location.id_demande = demandes.id_demande', 'left')
            ->where('demandes.id_demande', $idDemande)
            ->first();

        if ($demande === null) {
            return null;
        }

        $maison = (new MaisonModel())->find((int) $demande['id_maison']);
        $client = (new UtilisateurModel())->find((int) $demande['id_client']);
        if ($maison === null || $client === null) {
            return null;
        }

        $proprietaire = (new UtilisateurModel())->find((int) $maison['id_proprietaire']);

        if ($maison === null || $client === null || $proprietaire === null || (int) $proprietaire['id_utilisateur'] !== (int) session('id_utilisateur')) {
            return null;
        }

        $usage = $demande['usage_declare'] ?: ($maison['usage_autorise'] ?: 'habitation');

        return [
            'demande' => $demande,
            'maison' => $maison,
            'client' => $client,
            'proprietaire' => $proprietaire,
            'usageParDefaut' => $usage,
            'donneesLegaltech' => [
                'cin_numero' => $client['cin_numero'] ?? null,
                'sexe' => $client['sexe'] ?? null,
                'date_naissance' => $client['date_naissance'] ?? null,
                'nif' => $client['nif'] ?? null,
                'stat' => $client['stat'] ?? null,
                'usage_declare' => $usage,
                'activite_declaree' => $demande['activite_declaree'] ?? null,
                'nb_occupants' => $demande['nb_occupants'] ?? 1,
                'nb_chambres' => $maison['nb_chambres'] ?? 1,
                'loyer_mensuel' => $maison['loyer_mensuel'] ?? 0,
                'valeur_immeuble' => $maison['valeur_immeuble'] ?? 0,
                'date_construction' => $maison['date_construction'] ?? null,
                'depot_garantie' => 0,
            ],
        ];
    }

    private function enregistrerDossier(int $idDemande, array $donnees, string $statutValidation): int
    {
        $db = db_connect();
        $dossier = $db->table('dossiers_location')->where('id_demande', $idDemande)->get()->getRowArray();

        $payload = [
            'id_demande' => $idDemande,
            'nb_occupants' => $donnees['nb_occupants'],
            'usage_declare' => $donnees['usage_declare'],
            'activite_declaree' => $donnees['activite_declaree'] ?: null,
            'situation_client' => $donnees['situation_client'],
            'detail_situation' => null,
            'statut_validation_legale' => $statutValidation,
        ];

        if ($dossier !== null) {
            $db->table('dossiers_location')->where('id_dossier', $dossier['id_dossier'])->update($payload);
            return (int) $dossier['id_dossier'];
        }

        $db->table('dossiers_location')->insert($payload);
        return (int) $db->insertID();
    }

    private function construireContenuHtml(array $contexte, array $typeContrat, array $donnees, float $montantDroit): string
    {
        return strtr($typeContrat['modele_html'] ?: '', [
            '{{contenu_a_completer}}' => sprintf(
                '<p>Contrat entre %s et %s pour la maison %s.</p><p>Usage : %s. Loyer mensuel : %s Ar. Dépôt de garantie : %s Ar.</p><p>Durée : %s au %s.</p><p>Droit d\'enregistrement : %s Ar (taux %s%%).</p>',
                htmlspecialchars(trim(($contexte['proprietaire']['prenoms'] ?? '') . ' ' . ($contexte['proprietaire']['nom'] ?? '')), ENT_QUOTES),
                htmlspecialchars(trim(($contexte['client']['prenoms'] ?? '') . ' ' . ($contexte['client']['nom'] ?? '')), ENT_QUOTES),
                htmlspecialchars($contexte['maison']['titre'], ENT_QUOTES),
                htmlspecialchars($donnees['usage_declare'], ENT_QUOTES),
                number_format((float) $contexte['maison']['loyer_mensuel'], 0, ',', ' '),
                number_format((float) $donnees['depot_garantie'], 0, ',', ' '),
                htmlspecialchars($donnees['date_debut'], ENT_QUOTES),
                htmlspecialchars($donnees['date_fin'] ?: 'indéterminée', ENT_QUOTES),
                number_format($montantDroit, 0, ',', ' '),
                htmlspecialchars((string) $typeContrat['taux_enregistrement'], ENT_QUOTES)
            ),
        ]);
    }

    private function lignesContrat(array $contexte, array $typeContrat, array $donnees, float $montantDroit, string $hash): array
    {
        return [
            'Type : ' . ($typeContrat['libelle'] ?? '—'),
            'Proprietaire : ' . trim(($contexte['proprietaire']['prenoms'] ?? '') . ' ' . ($contexte['proprietaire']['nom'] ?? '')),
            'Locataire : ' . trim(($contexte['client']['prenoms'] ?? '') . ' ' . ($contexte['client']['nom'] ?? '')),
            'Maison : ' . ($contexte['maison']['titre'] ?? '—'),
            'Usage : ' . ($donnees['usage_declare'] ?? '—'),
            'Loyer mensuel : ' . number_format((float) $contexte['maison']['loyer_mensuel'], 0, ',', ' ') . ' Ar',
            'Depot de garantie : ' . number_format((float) $donnees['depot_garantie'], 0, ',', ' ') . ' Ar',
            'Date debut : ' . ($donnees['date_debut'] ?? '—'),
            'Date fin : ' . ($donnees['date_fin'] ?: 'indeterminee'),
            'Taux enregistrement : ' . ($typeContrat['taux_enregistrement'] ?? '—') . '%',
            'Droit d enregistrement : ' . number_format($montantDroit, 0, ',', ' ') . ' Ar',
            'Hash SHA-256 : ' . $hash,
        ];
    }

    private function lignesFicheFiscale(array $contexte, array $typeContrat, array $donnees, float $montantTotalLoyers, float $montantDroit): array
    {
        return [
            'Fiche fiscale du contrat',
            'Maison : ' . ($contexte['maison']['titre'] ?? '—'),
            'Usage : ' . ($donnees['usage_declare'] ?? '—'),
            'Base loyers annuels : ' . number_format($montantTotalLoyers, 0, ',', ' ') . ' Ar',
            'Taux applique : ' . ($typeContrat['taux_enregistrement'] ?? '—') . '%',
            'Montant du droit : ' . number_format($montantDroit, 0, ',', ' ') . ' Ar',
            'Date limite d enregistrement : ' . date('d/m/Y', strtotime($donnees['date_debut'] . ' +30 days')),
        ];
    }

    private function chargerContratAccessible(int $idContrat, int $idUtilisateur, ?string $role = null): ?array
    {
        if ($role === null || ! in_array($role, ['proprietaire', 'client'], true)) {
            return null;
        }

        $contrat = (new ContratModel())->avecRelations()
            ->select('contrats.*, maisons.titre AS maison_titre, maisons.adresse AS maison_adresse, maisons.usage_autorise, maisons.loyer_mensuel AS loyer_maison, maisons.valeur_immeuble, maisons.date_construction, maisons.nb_chambres, maisons.id_proprietaire AS maison_proprietaire, client.nom AS client_nom, client.prenoms AS client_prenoms, client.cin_numero, client.nif, client.stat, bailleur.nom AS proprietaire_nom, bailleur.prenoms AS proprietaire_prenoms')
            ->join('maisons', 'maisons.id_maison = contrats.id_maison')
            ->join('utilisateurs client', 'client.id_utilisateur = contrats.id_client')
            ->join('utilisateurs bailleur', 'bailleur.id_utilisateur = contrats.id_proprietaire')
            ->where('contrats.id_contrat', $idContrat)
            ->first();

        if ($contrat === null) {
            return null;
        }

        if ($role === 'proprietaire' && (int) $contrat['id_proprietaire'] !== $idUtilisateur) {
            return null;
        }

        if ($role === 'client' && (int) $contrat['id_client'] !== $idUtilisateur) {
            return null;
        }

        $contrat['avenants'] = (new AvenantModel())->pourContrat($idContrat);
        $contrat['ficheFiscale'] = (new FicheFiscaleModel())->pourContrat($idContrat);
        $contrat['signatures'] = (new SignatureModel())
            ->where('id_contrat', $idContrat)
            ->orderBy('signe_le', 'ASC')
            ->findAll();

        return $contrat;
    }

    private function planifierAlerteEcheance(int $idProprietaire, int $idContrat, ?string $dateFin): void
    {
        if (empty($dateFin)) {
            return;
        }

        $dateAlerte = date('Y-m-d', strtotime($dateFin . ' -30 days'));
        if ($dateAlerte !== date('Y-m-d')) {
            return;
        }

        $notificationModel = new NotificationModel();
        $existe = $notificationModel->where('id_utilisateur', $idProprietaire)
            ->where('type', 'echeance_proche')
            ->where('reference_table', 'contrats')
            ->where('reference_id', $idContrat)
            ->countAllResults();

        if ($existe > 0) {
            return;
        }

        $notificationModel->insert([
            'id_utilisateur' => $idProprietaire,
            'type' => 'echeance_proche',
            'reference_table' => 'contrats',
            'reference_id' => $idContrat,
            'message' => 'Le contrat arrive à échéance dans 30 jours.',
        ]);
    }
}
