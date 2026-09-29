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
            ->select('contrats.*, maisons.titre AS maison_titre, client.nom AS client_nom, client.prenoms AS client_prenoms')
            ->join('maisons', 'maisons.id_maison = contrats.id_maison')
            ->join('utilisateurs client', 'client.id_utilisateur = contrats.id_client')
            ->join('utilisateurs bailleur', 'bailleur.id_utilisateur = contrats.id_proprietaire')
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
        $contenuHtml = $this->construireContenuHtml($contexte, $typeContrat, $donnees, $montantDroit, $numeroContrat);
        $hash = hash('sha256', $contenuHtml);

        $pdfPath = 'uploads/contrats/' . date('Y') . '/' . $numeroContrat . '.pdf';
        $pdf = $pdfService->genererPdfTextuel('Contrat de bail ' . ($typeContrat['libelle'] ?? ''), $this->lignesDepuisTemplate($contenuHtml));
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
        $fichePdf = $pdfService->genererPdfTextuel('Fiche fiscale - ' . $numeroContrat, $this->lignesDepuisTemplate($this->templateFicheFiscale($contexte, $typeContrat, $donnees, $montantTotalLoyers, $montantDroit, $numeroContrat)));
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

        $this->relancerSignatureContratSiNecessaire($contrat);
        foreach ($contrat['avenants'] ?? [] as $avenant) {
            $this->relancerSignatureAvenantSiNecessaire($avenant);
        }

        return view('proprietaire/contrats/detail', $contrat);
    }

    public function detailClient(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'client');
        if ($contrat === null) {
            return redirect()->to('/client/mes-demandes')->with('erreur', 'Contrat introuvable.');
        }

        $this->relancerSignatureContratSiNecessaire($contrat);
        foreach ($contrat['avenants'] ?? [] as $avenant) {
            $this->relancerSignatureAvenantSiNecessaire($avenant);
        }

        return view('client/contrat', $contrat);
    }

    public function telechargerPdf(int $idContrat)
    {
        $contrat = $this->chargerContratPourTelechargement($idContrat);

        if ($contrat === null) {
            return redirect()->to('/')->with('erreur', 'Contrat introuvable ou accès non autorisé.');
        }

        $pdf = $this->genererPdfContratSigne($idContrat)
            ?? $this->lirePdfStocke($contrat['contenu_pdf_chemin'] ?? null);

        if ($pdf === null) {
            $libelle = $contrat['type_libelle'] ?? ($contrat['usage_autorise'] ?? 'Bail');
            $pdf = (new ContratPdfService())->genererPdfTextuel(
                'Contrat de bail ' . $libelle,
                $this->lignesDepuisTemplate((string) ($contrat['texte_contrat'] ?? ''))
            );
        }

        return $this->reponsePdf($pdf, 'contrat-' . ($contrat['numero_contrat'] ?? $idContrat) . '.pdf');
    }

    public function telechargerFicheFiscale(int $idContrat)
    {
        $contrat = $this->chargerContratPourTelechargement($idContrat);

        if ($contrat === null) {
            return redirect()->to('/')->with('erreur', 'Contrat introuvable ou accès non autorisé.');
        }

        $fiche = $contrat['ficheFiscale'] ?? null;

        if ($fiche === null) {
            return redirect()->back()->with('erreur', 'Aucune fiche fiscale n’est disponible pour ce contrat.');
        }

        $pdf = $this->lirePdfStocke($fiche['chemin_pdf'] ?? null);

        if ($pdf === null) {
            $pdf = (new ContratPdfService())->genererPdfTextuel(
                'Fiche fiscale - ' . ($contrat['numero_contrat'] ?? $idContrat),
                $this->lignesFicheFiscaleDepuisContrat($contrat, $fiche)
            );
        }

        return $this->reponsePdf($pdf, 'fiche-fiscale-' . ($contrat['numero_contrat'] ?? $idContrat) . '.pdf');
    }

    public function consulterAvenant(int $idAvenant)
    {
        $contexte = $this->chargerAvenantAccessible($idAvenant);
        if ($contexte === null) {
            return redirect()->back()->with('erreur', 'Avenant introuvable ou non autorisé.');
        }

        $document = $this->construireDocumentAvenant($contexte['avenant'], $contexte['contrat']);

        return view('avenants/detail', [
            'avenant' => $contexte['avenant'],
            'contrat' => $contexte['contrat'],
            'document' => $document,
        ]);
    }

    public function telechargerAvenantPdf(int $idAvenant)
    {
        $contexte = $this->chargerAvenantAccessible($idAvenant);
        if ($contexte === null) {
            return redirect()->back()->with('erreur', 'Avenant introuvable ou non autorisé.');
        }

        $avenant = $contexte['avenant'];
        $contrat = $contexte['contrat'];

        $document = $this->construireDocumentAvenant($avenant, $contrat);
        $pdf = (new ContratPdfService())->genererPdfTextuel(
            $document['titre_pdf'],
            $document['lignes_pdf'],
            true
        );

        $annee = date('Y');
        $numeroContrat = $contrat['numero_contrat'] ?? ('CTR-' . $contrat['id_contrat']);
        $path = 'uploads/avenants/' . $annee . '/' . $numeroContrat . '-avenant' . ($avenant['numero_avenant'] ?? $idAvenant) . '.pdf';
        if (! is_dir(dirname(FCPATH . $path))) {
            mkdir(dirname(FCPATH . $path), 0775, true);
        }
        file_put_contents(FCPATH . $path, $pdf);

        (new AvenantModel())->update($idAvenant, [
            'contenu_pdf_chemin' => $path,
            'contenu_hash_sha256' => hash('sha256', $pdf),
        ]);

        return $this->reponsePdf($pdf, $this->nomFichierAvenant($avenant, $contrat));
    }

    private function chargerContratPourTelechargement(int $idContrat): ?array
    {
        $role = (string) session('role');

        if (! in_array($role, ['client', 'proprietaire'], true)) {
            return null;
        }

        return $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), $role);
    }

    private function lirePdfStocke(?string $cheminRelatif): ?string
    {
        if (empty($cheminRelatif)) {
            return null;
        }

        $cheminAbsolu = FCPATH . ltrim((string) $cheminRelatif, '/');

        if (! is_file($cheminAbsolu) || filesize($cheminAbsolu) === 0) {
            return null;
        }

        $contenu = file_get_contents($cheminAbsolu);

        return $contenu === false ? null : $contenu;
    }

    private function reponsePdf(string $contenuPdf, string $nomFichier)
    {
        $nomPropre = preg_replace('/[^A-Za-z0-9._-]/', '-', $nomFichier) ?: 'document.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nomPropre . '"')
            ->setHeader('Content-Length', (string) strlen($contenuPdf))
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setBody($contenuPdf);
    }

    private function lignesFicheFiscaleDepuisContrat(array $contrat, array $fiche): array
    {
        $dateLimite = $fiche['date_limite_enreg'] ?? null;
        $dateLimiteAffichee = empty($dateLimite) ? '—' : date('d/m/Y', strtotime((string) $dateLimite));
        $taux = $fiche['taux_applique'] ?? ($contrat['taux_enregistrement_applique'] ?? '—');
        $montantDroit = $fiche['montant_droit'] ?? ($contrat['montant_droit_enregistrement'] ?? 0);

        return [
            'FICHE FISCALE',
            'Contrat n° ' . ($contrat['numero_contrat'] ?? '—'),
            'Maison : ' . ($contrat['maison_titre'] ?? '—'),
            'Usage : ' . ($contrat['type_libelle'] ?? ($contrat['usage_autorise'] ?? '—')),
            'Loyer mensuel : ' . number_format((float) ($contrat['loyer_mensuel'] ?? 0), 0, ',', ' ') . ' Ariary',
            'Base loyers annuels : ' . number_format((float) ($fiche['montant_total_loyers'] ?? 0), 0, ',', ' ') . ' Ariary',
            'Taux appliqué : ' . $taux . '%',
            'Montant du droit d’enregistrement : ' . number_format((float) $montantDroit, 0, ',', ' ') . ' Ariary',
            'Date limite d’enregistrement : ' . $dateLimiteAffichee,
            'Statut d’enregistrement : ' . ($fiche['statut_enregistrement'] ?? 'non_enregistre'),
            'Document généré automatiquement par LegalTech.',
        ];
    }

    public function signerBailleur(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Contrat introuvable.');
        }

        $signatureModel = new SignatureModel();
        $signatureExistante = $signatureModel
            ->where('id_contrat', $idContrat)
            ->where('role_signataire', 'bailleur')
            ->first();

        if ($signatureExistante !== null) {
            return redirect()->back()->with('info', 'Le bailleur a déjà signé ce contrat.');
        }

        $nomBailleur = trim(($contrat['proprietaire_prenoms'] ?? '') . ' ' . ($contrat['proprietaire_nom'] ?? '')) ?: 'Bailleur';

        $signatureModel->insert([
            'id_contrat' => $idContrat,
            'id_avenant' => null,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'bailleur',
            'nom_affiche' => $nomBailleur,
            'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $aDejaSigneLocataire = $signatureModel
            ->where('id_contrat', $idContrat)
            ->where('role_signataire', 'locataire')
            ->first();

        (new ContratModel())->update($idContrat, [
            'statut' => $aDejaSigneLocataire !== null ? 'valide' : 'signe_bailleur',
        ]);

        if ($aDejaSigneLocataire !== null) {
            $this->genererPdfContratSigne($idContrat);
        }

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_client'],
            'type' => 'contrat_a_signer',
            'reference_table' => 'contrats',
            'reference_id' => $idContrat,
            'message' => 'Le bailleur a signé le contrat. Veuillez maintenant valider vos engagements.',
        ]);

        return redirect()->to('/proprietaire/contrats/' . $idContrat)->with('succes', 'Signature du bailleur enregistrée.');
    }

    public function signerLocataire(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'client');
        if ($contrat === null) {
            return redirect()->to('/client/mes-demandes')->with('erreur', 'Contrat introuvable.');
        }

        $signatureModel = new SignatureModel();
        $signatureExistante = $signatureModel
            ->where('id_contrat', $idContrat)
            ->where('role_signataire', 'locataire')
            ->first();

        if ($signatureExistante !== null) {
            return redirect()->back()->with('info', 'Le locataire a déjà signé ce contrat.');
        }

        $signatureBailleur = $signatureModel
            ->where('id_contrat', $idContrat)
            ->where('role_signataire', 'bailleur')
            ->first();

        if ($signatureBailleur === null) {
            return redirect()->back()->with('erreur', 'Le bailleur doit signer avant le locataire.');
        }

        $nomLocataire = trim(($contrat['client_prenoms'] ?? '') . ' ' . ($contrat['client_nom'] ?? '')) ?: 'Locataire';

        $signatureModel->insert([
            'id_contrat' => $idContrat,
            'id_avenant' => null,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'locataire',
            'nom_affiche' => $nomLocataire,
            'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        (new ContratModel())->update($idContrat, ['statut' => 'valide']);

        $this->genererPdfContratSigne($idContrat);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_proprietaire'],
            'type' => 'contrat_signe',
            'reference_table' => 'contrats',
            'reference_id' => $idContrat,
            'message' => 'Le locataire a signé le contrat. Le bail est maintenant validé.',
        ]);

        return redirect()->to('/client/contrats/' . $idContrat)->with('succes', 'Signature du locataire enregistrée.');
    }

    public function signerAvenantBailleur(int $idAvenant)
    {
        $avenant = (new AvenantModel())->find($idAvenant);
        if ($avenant === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Avenant introuvable.');
        }

        $contrat = $this->chargerContratAccessible((int) $avenant['id_contrat'], (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Avenant non autorisé.');
        }

        $signatureModel = new SignatureModel();
        if ($signatureModel->where('id_avenant', $idAvenant)->where('role_signataire', 'bailleur')->first() !== null) {
            return redirect()->back()->with('info', 'Le bailleur a déjà signé cet avenant.');
        }

        $nomBailleur = trim(($contrat['proprietaire_prenoms'] ?? '') . ' ' . ($contrat['proprietaire_nom'] ?? '')) ?: 'Bailleur';

        $signatureModel->insert([
            'id_avenant' => $idAvenant,
            'id_contrat' => null,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'bailleur',
            'nom_affiche' => $nomBailleur,
            'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $aDejaSigneLocataire = $signatureModel->where('id_avenant', $idAvenant)->where('role_signataire', 'locataire')->first();
        (new AvenantModel())->update($idAvenant, [
            'statut' => $aDejaSigneLocataire !== null ? 'valide' : 'signe_bailleur',
        ]);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_client'],
            'type' => 'avenant_a_signer',
            'reference_table' => 'avenants',
            'reference_id' => $idAvenant,
            'message' => 'Le bailleur a signé l’avenant. Veuillez continuer la validation.',
        ]);

        return redirect()->to('/proprietaire/contrats/' . $avenant['id_contrat'])->with('succes', 'Signature de l’avenant enregistrée.');
    }

    public function signerAvenantLocataire(int $idAvenant)
    {
        $avenant = (new AvenantModel())->find($idAvenant);
        if ($avenant === null) {
            return redirect()->to('/client/mes-demandes')->with('erreur', 'Avenant introuvable.');
        }

        $contrat = $this->chargerContratAccessible((int) $avenant['id_contrat'], (int) session('id_utilisateur'), 'client');
        if ($contrat === null) {
            return redirect()->to('/client/mes-demandes')->with('erreur', 'Avenant non autorisé.');
        }

        $signatureModel = new SignatureModel();
        if ($signatureModel->where('id_avenant', $idAvenant)->where('role_signataire', 'locataire')->first() !== null) {
            return redirect()->back()->with('info', 'Le locataire a déjà signé cet avenant.');
        }

        $signatureBailleur = $signatureModel->where('id_avenant', $idAvenant)->where('role_signataire', 'bailleur')->first();
        if ($signatureBailleur === null) {
            return redirect()->back()->with('erreur', 'Le bailleur doit signer avant le locataire.');
        }

        $nomLocataire = trim(($contrat['client_prenoms'] ?? '') . ' ' . ($contrat['client_nom'] ?? '')) ?: 'Locataire';

        $signatureModel->insert([
            'id_avenant' => $idAvenant,
            'id_contrat' => null,
            'id_utilisateur' => (int) session('id_utilisateur'),
            'role_signataire' => 'locataire',
            'nom_affiche' => $nomLocataire,
            'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        (new AvenantModel())->update($idAvenant, ['statut' => 'valide']);

        (new NotificationModel())->insert([
            'id_utilisateur' => (int) $contrat['id_proprietaire'],
            'type' => 'avenant_signe',
            'reference_table' => 'avenants',
            'reference_id' => $idAvenant,
            'message' => 'Le locataire a signé l’avenant. La modification est validée.',
        ]);

        return redirect()->to('/client/contrats/' . $avenant['id_contrat'])->with('succes', 'Signature de l’avenant enregistrée.');
    }

    /**
     * Régénère le PDF du contrat avec le nom et la signature (prénom) du bailleur
     * et du preneur, uniquement si les deux ont signé. Retourne null sinon.
     */
    private function genererPdfContratSigne(int $idContrat): ?string
    {
        $roles = array_column(
            (new SignatureModel())->where('id_contrat', $idContrat)->findAll(),
            'role_signataire'
        );

        if (! in_array('bailleur', $roles, true) || ! in_array('locataire', $roles, true)) {
            return null;
        }

        $contrat = (new ContratModel())->find($idContrat);
        if ($contrat === null) {
            return null;
        }

        $maison = (new MaisonModel())->find((int) $contrat['id_maison']);
        $client = (new UtilisateurModel())->find((int) $contrat['id_client']);
        $proprietaire = (new UtilisateurModel())->find((int) $contrat['id_proprietaire']);
        $typeContrat = (new TypeContratModel())->where('id_type_contrat', (int) $contrat['id_type_contrat'])->first();

        if ($maison === null || $client === null || $proprietaire === null || $typeContrat === null) {
            return null;
        }

        $dossier = db_connect()->table('dossiers_location')
            ->where('id_dossier', (int) $contrat['id_dossier'])
            ->get()
            ->getRowArray() ?? [];

        $donnees = [
            'usage_declare' => $dossier['usage_declare'] ?? ($typeContrat['code'] ?? 'habitation'),
            'nb_occupants' => (int) ($dossier['nb_occupants'] ?? 1),
            'activite_declaree' => (string) ($dossier['activite_declaree'] ?? ''),
            'depot_garantie' => (float) ($contrat['depot_garantie'] ?? 0),
            'date_debut' => $contrat['date_debut'] ?? date('Y-m-d'),
            'date_fin' => $contrat['date_fin'] ?? null,
        ];

        $contexte = ['maison' => $maison, 'client' => $client, 'proprietaire' => $proprietaire];
        $dateSignature = date('d/m/Y', strtotime((string) ($contrat['cree_le'] ?? 'now')));

        $texte = $this->construireContenuHtml(
            $contexte,
            $typeContrat,
            $donnees,
            (float) ($contrat['montant_droit_enregistrement'] ?? 0),
            (string) $contrat['numero_contrat'],
            $dateSignature
        );

        $texte = $this->insererSignatures(
            $texte,
            trim(($proprietaire['prenoms'] ?? '') . ' ' . ($proprietaire['nom'] ?? '')),
            $this->premierPrenom($proprietaire['prenoms'] ?? ''),
            trim(($client['prenoms'] ?? '') . ' ' . ($client['nom'] ?? '')),
            $this->premierPrenom($client['prenoms'] ?? '')
        );

        $pdf = (new ContratPdfService())->genererPdfTextuel(
            'Contrat de bail ' . ($typeContrat['libelle'] ?? ''),
            $this->lignesDepuisTemplate($texte)
        );

        $chemin = $contrat['contenu_pdf_chemin'] ?? null;
        if (! empty($chemin)) {
            if (! is_dir(dirname(FCPATH . $chemin))) {
                mkdir(dirname(FCPATH . $chemin), 0775, true);
            }
            file_put_contents(FCPATH . $chemin, $pdf);
        }

        return $pdf;
    }

    private function insererSignatures(string $texte, string $nomBailleur, string $prenomBailleur, string $nomPreneur, string $prenomPreneur): string
    {
        $esc = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES);

        $blocBailleur = 'Nom : ' . $esc($nomBailleur) . "\nSignature : " . $esc($prenomBailleur);
        $blocPreneur = 'Nom : ' . $esc($nomPreneur) . "\nSignature : " . $esc($prenomPreneur);
        $marque = '(nom et signature)';

        if (substr_count($texte, $marque) >= 2) {
            $texte = substr_replace($texte, $blocBailleur, strpos($texte, $marque), strlen($marque));

            return substr_replace($texte, $blocPreneur, strpos($texte, $marque), strlen($marque));
        }

        // Modèle sans bloc de signatures (ex. bail mixte) : on l'ajoute à la fin
        return rtrim($texte) . "\n\nLe Propriétaire\n" . $blocBailleur . "\nLe Locataire\n" . $blocPreneur;
    }

    private function premierPrenom(string $prenoms): string
    {
        $parts = preg_split('/[\s,]+/u', trim($prenoms), -1, PREG_SPLIT_NO_EMPTY);

        return $parts[0] ?? '';
    }

    private function relancerSignatureContratSiNecessaire(array $contrat): void
    {
        $dateCreation = $contrat['cree_le'] ?? date('Y-m-d H:i:s');
        $dateLimite = strtotime($dateCreation . ' +7 days');
        $now = time();

        if ($now < $dateLimite) {
            return;
        }

        $signatureBailleur = (new SignatureModel())
            ->where('id_contrat', (int) $contrat['id_contrat'])
            ->where('role_signataire', 'bailleur')
            ->first();

        if ($signatureBailleur === null) {
            $this->insererRelanceSiAbsente((int) $contrat['id_proprietaire'], 'contrats', (int) $contrat['id_contrat'], 'Le bailleur n’a pas encore signé le contrat. Relance automatique après 7 jours.');
        }

        $signatureLocataire = (new SignatureModel())
            ->where('id_contrat', (int) $contrat['id_contrat'])
            ->where('role_signataire', 'locataire')
            ->first();

        if ($signatureLocataire === null && ($contrat['statut'] ?? null) === 'signe_bailleur') {
            $this->insererRelanceSiAbsente((int) $contrat['id_client'], 'contrats', (int) $contrat['id_contrat'], 'Le locataire n’a pas encore signé le contrat. Relance automatique après 7 jours.');
        }
    }

    private function relancerSignatureAvenantSiNecessaire(array $avenant): void
    {
        $dateCreation = $avenant['cree_le'] ?? date('Y-m-d H:i:s');
        $dateLimite = strtotime($dateCreation . ' +7 days');
        if (time() < $dateLimite) {
            return;
        }

        $signatureBailleur = (new SignatureModel())
            ->where('id_avenant', (int) $avenant['id_avenant'])
            ->where('role_signataire', 'bailleur')
            ->first();

        if ($signatureBailleur === null && ($avenant['statut'] ?? null) === 'propose') {
            $this->insererRelanceSiAbsente((int) $this->chargerProprietaireDepuisAvenant((int) $avenant['id_contrat'])['id_proprietaire'], 'avenants', (int) $avenant['id_avenant'], 'Relance : le bailleur n’a pas encore signé cet avenant depuis 7 jours.');
        }

        $signatureLocataire = (new SignatureModel())
            ->where('id_avenant', (int) $avenant['id_avenant'])
            ->where('role_signataire', 'locataire')
            ->first();

        if ($signatureLocataire === null && ($avenant['statut'] ?? null) === 'signe_bailleur') {
            $this->insererRelanceSiAbsente((int) $this->chargerClientDepuisAvenant((int) $avenant['id_contrat'])['id_client'], 'avenants', (int) $avenant['id_avenant'], 'Relance : le locataire n’a pas encore signé cet avenant depuis 7 jours.');
        }
    }

    // Affiche la liste / page des avenants pour un contrat (redirige vers la page de détail existante)
    public function avenants(int $idContrat)
    {
        $role = session('role') ?? 'proprietaire';
        if ($role === 'proprietaire') {
            return redirect()->to('/proprietaire/contrats/' . $idContrat);
        }

        return redirect()->to('/client/contrats/' . $idContrat);
    }

    // Formulaire de création d'un avenant (GET)
    public function formulaireAvenant(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Contrat introuvable ou non autorisé.');
        }

        return view('proprietaire/contrats/avenant_form', ['contrat' => $contrat]);
    }

    // Traitement de création d'un avenant (POST)
    public function creerAvenant(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Contrat introuvable ou non autorisé.');
        }

        $avenantModel = new AvenantModel();
        $data = [
            'id_contrat' => $idContrat,
            'numero_avenant' => $avenantModel->numeroSuivantPourContrat($idContrat),
            'type_avenant' => $this->request->getPost('type_avenant') ?: 'modification',
            'champ_modifie' => $this->request->getPost('champ_modifie'),
            'ancienne_valeur' => $this->request->getPost('ancienne_valeur'),
            'nouvelle_valeur' => $this->request->getPost('nouvelle_valeur'),
            'justification' => $this->request->getPost('justification'),
            'date_effet' => $this->request->getPost('date_effet') ?: date('Y-m-d'),
            'statut' => 'propose',
        ];

        $id = $avenantModel->insert($data);

        if ($id === false) {
            return redirect()->back()->with('erreur', 'Impossible de créer l\'avenant.')->withInput();
        }

        return redirect()->to('/proprietaire/contrats/' . $idContrat)->with('succes', 'Avenant proposé.');
    }

    private function chargerAvenantAccessible(int $idAvenant): ?array
    {
        $avenant = (new AvenantModel())->find($idAvenant);
        if ($avenant === null) {
            return null;
        }

        $role = (string) session('role');
        if (! in_array($role, ['client', 'proprietaire'], true)) {
            return null;
        }

        $contrat = $this->chargerContratAccessible((int) $avenant['id_contrat'], (int) session('id_utilisateur'), $role);
        if ($contrat === null) {
            return null;
        }

        return [
            'avenant' => $avenant,
            'contrat' => $contrat,
        ];
    }

    private function construireDocumentAvenant(array $avenant, array $contrat): array
    {
        $numeroContrat = $contrat['numero_contrat'] ?? ('CTR-' . $contrat['id_contrat']);
        $dateContrat = $this->formaterDate($contrat['cree_le'] ?? null) ?? ($contrat['date_debut'] ?? '—');
        $dateEffet = $this->formaterDate($avenant['date_effet'] ?? null) ?? date('d/m/Y');

        $nomBailleur = trim(($contrat['proprietaire_prenoms'] ?? '') . ' ' . ($contrat['proprietaire_nom'] ?? '')) ?: 'Bailleur';
        $adresseBailleur = trim((string) ($contrat['proprietaire_adresse'] ?? '')) ?: '—';
        $nomLocataire = trim(($contrat['client_prenoms'] ?? '') . ' ' . ($contrat['client_nom'] ?? '')) ?: 'Locataire';
        $adresseLocataire = trim((string) ($contrat['client_adresse'] ?? '')) ?: ($contrat['maison_adresse'] ?? '—');
        $adresseBien = trim((string) ($contrat['maison_adresse'] ?? '')) ?: '—';

        $signaturesContrat = (new SignatureModel())
            ->where('id_contrat', (int) $contrat['id_contrat'])
            ->orderBy('signe_le', 'ASC')
            ->findAll();

        $texteContratInitial = $this->buildTexteContratAffichage($contrat);
        if (! empty($signaturesContrat)) {
            $texteContratInitial = $this->insererSignatures(
                $texteContratInitial,
                $nomBailleur,
                $this->premierPrenom($contrat['proprietaire_prenoms'] ?? ''),
                $nomLocataire,
                $this->premierPrenom($contrat['client_prenoms'] ?? '')
            );
        }
        $lignesContratInitial = $this->lignesDepuisTemplate($texteContratInitial);

        $modifications = $this->listerModificationsAvenant($avenant);
        $lignesAvenant = [
            'AVENANT AU CONTRAT DE BAIL',
            '',
            'Origine du contrat : Contrat initial n° ' . $numeroContrat,
            'Date du contrat initial : ' . $dateContrat,
            'Logement concerné : ' . $adresseBien,
            '',
            'Entre les soussignés :',
            'Le bailleur : ' . $nomBailleur . ', demeurant ' . $adresseBailleur,
            'Le(s) locataire(s) : ' . $nomLocataire . ', demeurant ' . $adresseLocataire,
            '',
            'Article 1 – Objet de l’avenant',
            'Le présent avenant modifie le contrat de bail conclu le ' . $dateContrat . ' pour le logement situé ' . $adresseBien . '.',
            '',
            'Article 2 – Modification(s) apportée(s)',
        ];

        foreach ($modifications as $modification) {
            $lignesAvenant[] = '- ' . $modification['article'];
            $lignesAvenant[] = '  Ancienne valeur : ' . $modification['ancienne'];
            $lignesAvenant[] = '  Nouvelle valeur : ' . $modification['nouvelle'];
        }

        $lignesAvenant[] = 'Le reste est sans changement.';
        $lignesAvenant[] = '';
        $lignesAvenant[] = 'Article 3 – Date d’effet';
        $lignesAvenant[] = 'Le présent avenant prend effet le ' . $dateEffet . '.';
        $lignesAvenant[] = '';
        $lignesAvenant[] = 'Article 4 – Maintien des autres clauses';
        $lignesAvenant[] = 'Toutes les autres clauses et conditions du bail d’origine demeurent inchangées.';
        $lignesAvenant[] = '';
        $lignesAvenant[] = 'Fait à ' . ($contrat['ville'] ?? ($contrat['maison_ville'] ?? '—')) . ', le ' . date('d/m/Y') . ',';
        $lignesAvenant[] = '';
        $lignesAvenant[] = 'Signature du bailleur : ' . $nomBailleur;
        $lignesAvenant[] = 'Signature du (des) locataire(s) : ' . $nomLocataire;

        $texteAvenant = implode("\n", $lignesAvenant);

        return [
            'titre_pdf' => 'Avenant au contrat de bail - ' . $numeroContrat . ' - n°' . ($avenant['numero_avenant'] ?? 'N'),
            'numero_contrat' => $numeroContrat,
            'date_contrat_initial' => $dateContrat,
            'date_effet' => $dateEffet,
            'nom_bailleur' => $nomBailleur,
            'nom_locataire' => $nomLocataire,
            'adresse_bailleur' => $adresseBailleur,
            'adresse_locataire' => $adresseLocataire,
            'adresse_bien' => $adresseBien,
            'contrat_initial_texte' => $texteContratInitial,
            'contrat_initial_lignes' => $lignesContratInitial,
            'contrat_signatures' => $signaturesContrat,
            'avenant_texte' => $texteAvenant,
            'avenant_lignes' => $lignesAvenant,
            'lignes_pdf' => array_merge(
                ['CONTRAT DE BAIL INITIAL'],
                $lignesContratInitial,
                ['[[PAGE_BREAK]]', 'AVENANT AU CONTRAT DE BAIL'],
                $lignesAvenant
            ),
            'modifications' => $modifications,
            'statut' => $avenant['statut'] ?? null,
            'numero_avenant' => $avenant['numero_avenant'] ?? null,
            'type_avenant' => $avenant['type_avenant'] ?? null,
            'justification' => $avenant['justification'] ?? null,
        ];
    }

    private function listerModificationsAvenant(array $avenant): array
    {
        $articles = $this->splitLignesAvenant((string) ($avenant['champ_modifie'] ?? ''));
        if ($articles === []) {
            $articles = ['Article non précisé'];
        }

        $anciennes = $this->splitLignesAvenant((string) ($avenant['ancienne_valeur'] ?? ''));
        $nouvelles = $this->splitLignesAvenant((string) ($avenant['nouvelle_valeur'] ?? ''));

        $resultat = [];
        foreach ($articles as $index => $article) {
            $resultat[] = [
                'article' => $article,
                'ancienne' => $anciennes[$index] ?? ($anciennes[0] ?? '—'),
                'nouvelle' => $nouvelles[$index] ?? ($nouvelles[0] ?? '—'),
            ];
        }

        return $resultat;
    }

    private function splitLignesAvenant(string $contenu): array
    {
        $lignes = preg_split('/\r\n|\r|\n/', trim($contenu), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $lignes)));
    }

    private function formaterDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($date))->format('d/m/Y');
        } catch (\Throwable $e) {
            return $date;
        }
    }

    private function nomFichierAvenant(array $avenant, array $contrat): string
    {
        $numero = $contrat['numero_contrat'] ?? ('CTR-' . ($contrat['id_contrat'] ?? '0'));

        return 'avenant-' . $numero . '-n' . ($avenant['numero_avenant'] ?? '1') . '.pdf';
    }

    private function insererRelanceSiAbsente(int $idUtilisateur, string $referenceTable, int $referenceId, string $message): void
    {
        $existe = (new NotificationModel())
            ->where('id_utilisateur', $idUtilisateur)
            ->where('reference_table', $referenceTable)
            ->where('reference_id', $referenceId)
            ->where('message', $message)
            ->where('cree_le >=', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->first();

        if ($existe !== null) {
            return;
        }

        (new NotificationModel())->insert([
            'id_utilisateur' => $idUtilisateur,
            'type' => $referenceTable === 'avenants' ? 'avenant_a_signer' : 'contrat_a_signer',
            'reference_table' => $referenceTable,
            'reference_id' => $referenceId,
            'message' => $message,
        ]);
    }

    private function chargerProprietaireDepuisAvenant(int $idContrat): array
    {
        $contrat = (new ContratModel())->find($idContrat);
        return $contrat ?? ['id_proprietaire' => 0];
    }

    private function chargerClientDepuisAvenant(int $idContrat): array
    {
        $contrat = (new ContratModel())->find($idContrat);
        return $contrat ?? ['id_client' => 0];
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

    private function construireContenuHtml(array $contexte, array $typeContrat, array $donnees, float $montantDroit, string $numeroContrat, ?string $dateSignature = null): string
    {
        $code = $typeContrat['code'] ?? 'habitation';
        $template = $this->templateContratSelonType($code);

        $dateDebut = $donnees['date_debut'] ?? date('Y-m-d');
        $dateFin = ! empty($donnees['date_fin'])
            ? $donnees['date_fin']
            : date('Y-m-d', strtotime($dateDebut . ' +1 year'));

        $valeurs = [
            '{{numero_contrat}}' => $numeroContrat,
            '{{bailleur_prenoms}}' => htmlspecialchars($contexte['proprietaire']['prenoms'] ?? '', ENT_QUOTES),
            '{{bailleur_cin}}' => htmlspecialchars($contexte['proprietaire']['cin_numero'] ?? 'N/A', ENT_QUOTES),
            '{{bailleur_adresse}}' => htmlspecialchars($contexte['proprietaire']['adresse'] ?? 'Antananarivo', ENT_QUOTES),
            '{{locataire_nom}}' => htmlspecialchars($contexte['client']['nom'] ?? '', ENT_QUOTES),
            '{{locataire_prenoms}}' => htmlspecialchars($contexte['client']['prenoms'] ?? '', ENT_QUOTES),
            '{{locataire_date_naissance}}' => htmlspecialchars($contexte['client']['date_naissance'] ?? '', ENT_QUOTES),
            '{{locataire_cin}}' => htmlspecialchars($contexte['client']['cin_numero'] ?? 'N/A', ENT_QUOTES),
            '{{locataire_cin_date}}' => htmlspecialchars($contexte['client']['cin_date_delivrance'] ?? '', ENT_QUOTES),
            '{{locataire_cin_lieu}}' => htmlspecialchars($contexte['client']['cin_lieu_delivrance'] ?? 'Antananarivo', ENT_QUOTES),
            '{{locataire_profession}}' => htmlspecialchars($contexte['client']['profession'] ?? ($donnees['activite_declaree'] ?? ''), ENT_QUOTES),
            '{{type_bien}}' => htmlspecialchars($contexte['maison']['type_bien'] ?? 'Appartement', ENT_QUOTES),
            '{{titre_maison}}' => htmlspecialchars($contexte['maison']['titre'] ?? 'Maison', ENT_QUOTES),
            '{{adresse_maison}}' => htmlspecialchars($contexte['maison']['adresse'] ?? '', ENT_QUOTES),
            '{{ville}}' => htmlspecialchars($contexte['maison']['ville'] ?? 'Antananarivo', ENT_QUOTES),
            '{{nb_chambres}}' => (int) ($contexte['maison']['nb_chambres'] ?? 1),
            '{{superficie_m2}}' => htmlspecialchars((string) ($contexte['maison']['superficie_m2'] ?? 0), ENT_QUOTES),
            '{{titre_foncier}}' => htmlspecialchars($contexte['maison']['titre_foncier_numero'] ?? 'N/A', ENT_QUOTES),
            '{{date_debut}}' => htmlspecialchars($dateDebut, ENT_QUOTES),
            '{{date_fin}}' => htmlspecialchars($dateFin, ENT_QUOTES),
            '{{date_signature}}' => htmlspecialchars($dateSignature ?? date('d/m/Y'), ENT_QUOTES),
            '{{lieu_signature}}' => htmlspecialchars($contexte['maison']['ville'] ?? 'Antananarivo', ENT_QUOTES),
            '{{loyer_mensuel}}' => number_format((float) ($contexte['maison']['loyer_mensuel'] ?? 0), 0, ',', ' '),
            '{{depot_garantie}}' => number_format((float) ($donnees['depot_garantie'] ?? 0), 0, ',', ' '),
            '{{nb_occupants}}' => (int) ($donnees['nb_occupants'] ?? 1),
            '{{activite_declaree}}' => htmlspecialchars($donnees['activite_declaree'] ?? '', ENT_QUOTES),
            '{{locataire_nif}}' => htmlspecialchars($contexte['client']['nif'] ?? '', ENT_QUOTES),
            '{{locataire_stat}}' => htmlspecialchars($contexte['client']['stat'] ?? '', ENT_QUOTES),
            '{{duree_bail}}' => htmlspecialchars('1 an', ENT_QUOTES),
            '{{pas_de_porte}}' => '0',
            '{{montant_droit_enregistrement}}' => number_format($montantDroit, 0, ',', ' '),
            '{{type_contrat}}' => htmlspecialchars($typeContrat['libelle'] ?? '', ENT_QUOTES),
        ];

        return strtr($template, $valeurs);
    }

    private function templateContratSelonType(string $code): string
    {
        $base = [];

        $base['habitation'] = <<<'TXT'
CONTRAT DE BAIL D'HABITATION
Ordonnance n°62-100 du 1er octobre 1962 — Contrat n° {{numero_contrat}}

Entre les soussignés :
{{bailleur_nom}} {{bailleur_prenoms}}, titulaire de la CIN n° {{bailleur_cin}}, demeurant à {{bailleur_adresse}}, ci-après dénommé « le Propriétaire »,
D'une part,
Et {{locataire_nom}} {{locataire_prenoms}}, né(e) le {{locataire_date_naissance}}, titulaire de la CIN n° {{locataire_cin}} délivrée le {{locataire_cin_date}} à {{locataire_cin_lieu}}, exerçant la profession de {{locataire_profession}}, ci-après dénommé « le Locataire »,
D'autre part,

Il a été convenu et arrêté ce qui suit :

Article 1 — Objet du contrat
Le présent contrat a pour objet de location d'une partie des locaux sis à {{adresse_maison}}, {{ville}}.

Article 2 — Désignation du bien loué
En considération des conditions et des engagements à respecter par Le Locataire, le Propriétaire loue au Locataire une partie des locaux sur une surface de {{superficie_m2}} mètre carré ({{superficie_m2}} m²).

Article 3 — Durée du bail
a)- La durée du contrat de bail :
Le présent contrat prend effet dès sa signature et prend fin 12 mois après sa date de signature. De ce fait, ce présent contrat débute le {{date_debut}} et prend fin le {{date_fin}}.
b)- Date du début des activités :
La date de début signifie la date à laquelle le Locataire commencera à s'installer dans les locaux loués.
c)- Prorogation du contrat :
Les parties peuvent proroger le contrat par accord mutuel entre les deux parties sous forme manuscrit.
d)- Option de renouvellement :
Le propriétaire accorde au Locataire le droit de renouveler le présent contrat de un an. Le Locataire pour exercer l'option de renouvellement, devra adresser une notification écrite au Propriétaire au plus tard 2 mois avant l'expiration du présent contrat de location.

Article 4 — Loyer et charges
Pour la première année d'activité du Locataire, les parties se sont convenues que le montant à payer est de {{loyer_mensuel}} Ariary par mois. Les charges locatives (eau, électricité, entretien courant) sont à la charge du Locataire.
Le paiement du loyer doit se faire au plus tard le cinq du mois. à défaut d'omission du paiement, le propriétaire émet un avis verbal au locataire pour régler le paiement. Passé les cinq jours après omission, le locataire n'arrive pas à payer le loyer, le propriétaire est dans son plein droit d'expulser le locataire et de réquisitionner les clés en leur possession.

Article 5 — Dépôt de garantie
Le Locataire a dans l'obligation de payer au propriétaire une caution de garantie afin de préserver l'état du locale ainsi loué, cette somme est fixé à {{depot_garantie}} Ariary. Dès que le locataire quitte la maison, les deux parties doivent évaluer ensemble, l'ensemble des locaux et si les deux parties sont convenues qu'aucune réparation ne doit se faire, le propriétaire est dans l'obligation de rembourser immédiatement au locataire la somme de la caution.

Article 6 — Destination et occupation des lieux
Les locaux loués peuvent être occupés et utilisés par le Locataire exclusivement comme maison d'habitation. Aucune disposition du présent contrat n'accorde au Locataire le droit d'utiliser la propriété à une autre fin que celle décrite ci-haut. Par ailleurs, le Locataire ne peut sous louer ou autrement autoriser autrui d'utiliser les locaux.
Le logement sera occupé par {{nb_occupants}} personne(s) au maximum.

Article 7 — Obligations du Bailleur
Sauf disposition contraire du présent contrat, et mis à part l'entretien et les remplacements résultant des actes ou omissions des Locataires, le Propriétaire devra réparer tous les défauts et déficiences de tout équipements ou matériels du bâtiment. Le Propriétaire devra préserver les locaux de tels défauts ou déficiences durant le présent contrat.
• Délivrer le logement en bon état d'usage et de réparation ;
• Assurer au Locataire la jouissance paisible des lieux ;
• Remettre une quittance de loyer à chaque paiement.

Article 8 — Obligations du Preneur
Le Locataire devra réparer et maintenir une bonne condition, sauf usure, les réparations effectuées par le Propriétaire conformément au présent contrat. Le locataire devra aussi effectuer les réparations ou les remplacements nécessaires suite à des actes, des omissions ou de négligence du Locataire, ses employés, agents ou sous-traitants.
Le Locataire ne permettra pas les gaspillages, les nuisances ou les activités illégales dans les locaux loués.
Tous les biens personnels, fournitures et équipements ainsi que le mobilier installé par ou aux frais du Locataire ainsi que les ajouts installés dans les locaux loués et utilisés dans les cadres des activités du Locataire aux frais du Locataire et pouvant être enlevés des locaux loués sans dommage. Sauf si ces dommages peuvent réparées par le Locataire, resteront la propriété du Locataire et le Locataire peut mais n'est pas obligé, de les retirer entièrement, ou partie à tout moment pendant la durée du contrat, pourvu que le Locataire effectue les réparations des dommages occasionnés à cet effet à ses propres frais.

Article 9 — Sous-location et cession
Toute sous-location, totale ou partielle, ainsi que toute cession du bail, sont interdites sans l'accord écrit et préalable du Propriétaire.

Article 10 — Congé et préavis
Chacune des parties peut mettre fin au bail à l'échéance en notifiant son congé par écrit avec un préavis de trois (3) mois. Le préavis court à compter de la réception de la notification.

Article 11 — Clause résolutoire
À défaut de paiement d'un seul terme de loyer à son échéance, ou en cas d'inexécution des obligations du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée infructueuse.

Article 12 — État des lieux
Un état des lieux contradictoire est dressé à l'entrée et à la sortie du Locataire. À défaut, le logement est présumé remis en bon état de réparations locatives.

Article 13 — Enregistrement fiscal
Conformément à l'article 02.01.14 du Code Général des Impôts, le présent contrat doit être enregistré dans un délai de deux (2) mois à compter de sa signature. Le droit d'enregistrement applicable est de 1 % du montant total des loyers, soit {{montant_droit_enregistrement}} Ariary. Une fiche fiscale est annexée au présent contrat.

Article 14 — Élection de domicile et litiges
Pour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus. Tout litige relève de la compétence des juridictions malgaches.

Article 15 — LOI APPLICABLE - JURIDICTION COMPÉTENTE
Pour tout ce qui n'est pas prévu par le présent contrat, les parties se réfèrent aux dispositions légales applicables en matière de bail aux usages locaux.
Le présent contrat est soumis tant pour sa validité, son interprétation, que pour son exécution, aux lois et règlements en vigueur à Madagascar.
Les parties s'engagent à agir de bonne foi dans le respect des droits et obligations réciproques définis aux termes du présent contrat. Elles s'engagent à adopter toutes les mesures raisonnables pour assurer la réalisation du présent Contrat.
Tout litige entre les parties sera présenté au Tribunal de Commerce compétent d'Antananarivo, après une tentative de conciliation amiable ou par recours à un arbitre consenti par les deux parties.
EN FOI DE QUOI, les parties ont conclu le présent contrat, qui prend effet dès sa signature.

Fait à {{lieu_signature}}, le {{date_signature}}, en deux exemplaires originaux.

Le Propriétaire
(nom et signature)
Le Locataire
(nom et signature)
TXT;

        $base['commercial'] = <<<'TXT'
CONTRAT DE BAIL COMMERCIAL
Loi n°2015-037 du 8 décembre 2015 — Contrat n° {{numero_contrat}}

Entre les soussignés :
{{bailleur_nom}} {{bailleur_prenoms}}, titulaire de la CIN n° {{bailleur_cin}}, demeurant à {{bailleur_adresse}}, ci-après dénommé « le Propriétaire », D'une part,
Et {{locataire_nom}} {{locataire_prenoms}}, titulaire de la CIN n° {{locataire_cin}}, exerçant l'activité de {{activite_declaree}}, immatriculé(e) sous le NIF n° {{locataire_nif}} et le STAT n° {{locataire_stat}}, ci-après dénommé « le Locataire », D'autre part,

Il a été convenu et arrêté ce qui suit :

Article 1 — Objet du contrat
Le présent contrat a pour objet de location d'une partie des locaux sis à {{adresse_maison}}, {{ville}}.

Article 2 — Désignation du local
En considération des conditions et des engagements à respecter par Le Locataire, le Propriétaire loue au Locataire une partie des locaux sur une surface de {{superficie_m2}} mètre carré ({{superficie_m2}} m²).

Article 3 — Destination et activité autorisée
Les locaux loués peuvent être occupés et utilisés par le Locataire exclusivement pour l'activité suivante : {{activite_declaree}}. Aucune disposition du présent contrat n'accorde au Locataire le droit d'utiliser la propriété à une autre fin que celle décrite ci-haut. Par ailleurs, le Locataire ne peut sous louer ou autrement autoriser autrui d'utiliser les locaux.
Toute modification ou extension d'activité requiert l'accord écrit préalable du Propriétaire.

Article 4 — Durée du bail
a)- La durée du contrat de bail :
Le présent contrat prend effet dès sa signature et prend fin 12 mois après sa date de signature. De ce fait, ce présent contrat débute le {{date_debut}} et prend fin le {{date_fin}}.
b)- Date du début des activités :
La date de début signifie la date à laquelle le Locataire commencera à s'installer dans les locaux loués.
c)- Prorogation du contrat :
Les parties peuvent proroger le contrat par accord mutuel entre les deux parties sous forme manuscrit.

Article 5 — Loyer et charges
Pour la première année d'activité du Locataire, les parties se sont convenues que le montant à payer est de {{loyer_mensuel}} Ariary par mois. Les charges locatives (eau, électricité, taxes liées à l'exploitation) sont à la charge du Locataire.
Le paiement du loyer doit se faire au plus tard le cinq du mois. à défaut d'omission du paiement, le propriétaire émet un avis verbal au locataire pour régler le paiement. Passé les cinq jours après omission, le locataire n'arrive pas à payer le loyer, le propriétaire est dans son plein droit d'expulser le locataire et de réquisitionner les clés en leur possession.

Article 6 — Pas-de-porte
Le cas échéant, un droit d'entrée (pas-de-porte) de {{pas_de_porte}} Ariary est versé par le Locataire au Propriétaire à la signature. Son montant ne peut excéder l'équivalent de trois (3) mois de loyer.

Article 7 — Dépôt de garantie
Le Locataire a dans l'obligation de payer au propriétaire une caution de garantie afin de préserver l'état du locale ainsi loué, cette somme est fixé à {{depot_garantie}} Ariary. Dès que le locataire quitte la maison, les deux parties doivent évaluer ensemble, l'ensemble des locaux et si les deux parties sont convenues qu'aucune réparation ne doit se faire, le propriétaire est dans l'obligation de rembourser immédiatement au locataire la somme de la caution.

Article 8 — Obligations du Bailleur
Sauf disposition contraire du présent contrat, et mis à part l'entretien et les remplacements résultant des actes ou omissions des Locataires, le Propriétaire devra réparer tous les défauts et déficiences de tout équipements ou matériels du bâtiment. Le Propriétaire devra préserver les locaux de tels défauts ou déficiences durant le présent contrat.
• Délivrer le local en état de servir à l'usage commercial convenu ;
• Assurer au Locataire la jouissance paisible du local pendant toute la durée du bail ;
• Remettre une quittance de loyer à chaque paiement.

Article 9 — Obligations du Preneur
Le Locataire devra réparer et maintenir une bonne condition, sauf usure, les réparations effectuées par le Propriétaire conformément au présent contrat. Le locataire devra aussi effectuer les réparations ou les remplacements nécessaires suite à des actes, des omissions ou de négligence du Locataire, ses employés, agents ou sous-traitants.
Le Locataire ne permettra pas les gaspillages, les nuisances ou les activités illégales dans les locaux loués.
Tous les biens personnels, fournitures et équipements ainsi que le mobilier installé par ou aux frais du Locataire ainsi que les ajouts installés dans les locaux loués et utilisés dans les cadres des activités du Locataire aux frais du Locataire et pouvant être enlevés des locaux loués sans dommage. Sauf si ces dommages peuvent réparées par le Locataire, resteront la propriété du Locataire et le Locataire peut mais n'est pas obligé, de les retirer entièrement, ou partie à tout moment pendant la durée du contrat, pourvu que le Locataire effectue les réparations des dommages occasionnés à cet effet à ses propres frais.
• Payer les impôts liés à son activité ;
• Exploiter le fonds de commerce de façon continue ;
• Maintenir son immatriculation fiscale (NIF/STAT) pendant toute la durée du bail ;
• Souscrire une assurance couvrant le local et l'activité exercée.

Article 10 — Préavis et résiliation
Pour un bail à durée indéterminée, le congé est notifié par écrit avec un préavis de six (6) mois.

Article 11 — Droit au renouvellement
Le propriétaire accorde au Locataire le droit de renouveler le présent contrat de un an. Le Locataire pour exercer l'option de renouvellement, devra adresser une notification écrite au Propriétaire au plus tard 2 mois avant l'expiration du présent contrat de location.
Conformément à l'article 29 de la Loi n°2015-037, le Locataire qui a exploité de manière continue son fonds pendant deux (2) ans bénéficie d'un droit au renouvellement du bail, sauf motif grave et légitime opposé par le Propriétaire.

Article 12 — Cession et sous-location
La sous-location est interdite sans l'accord écrit du Propriétaire. La cession du bail ne peut intervenir qu'avec la cession du fonds de commerce, après information préalable du Propriétaire.

Article 13 — Condition suspensive d'immatriculation
Si, à la date de signature, le Locataire n'a pas encore communiqué son NIF et son STAT, le présent bail est conclu sous condition suspensive de leur production dans un délai de trente (30) jours. À défaut, le bail est réputé caduc sans indemnité.

Article 14 — Clause résolutoire
À défaut de paiement d'un seul terme de loyer, ou en cas d'inexécution des obligations du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée sans effet.

Article 15 — Enregistrement fiscal
Conformément à l'article 02.01.14 du Code Général des Impôts, le contrat doit être enregistré dans un délai de deux (2) mois à compter de sa signature. Le droit d'enregistrement applicable est de 2 % du montant total des loyers, soit {{montant_droit_enregistrement}} Ariary. Une fiche fiscale est annexée au présent contrat.

Article 16 — Élection de domicile et juridiction compétente
Pour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus indiquées. Tout litige relatif à l'interprétation ou à l'exécution du présent contrat relève de la compétence des juridictions malgaches.

Article 17 — LOI APPLICABLE - JURIDICTION COMPÉTENTE
Pour tout ce qui n'est pas prévu par le présent contrat, les parties se réfèrent aux dispositions légales applicables en matière de bail aux usages locaux.
Le présent contrat est soumis tant pour sa validité, son interprétation, que pour son exécution, aux lois et règlements en vigueur à Madagascar.
Les parties s'engagent à agir de bonne foi dans le respect des droits et obligations réciproques définis aux termes du présent contrat. Elles s'engagent à adopter toutes les mesures raisonnables pour assurer la réalisation du présent Contrat.
Tout litige entre les parties sera présenté au Tribunal de Commerce compétent d'Antananarivo, après une tentative de conciliation amiable ou par recours à un arbitre consenti par les deux parties.
EN FOI DE QUOI, les parties ont conclu le présent contrat, qui prend effet dès sa signature.

Fait à {{lieu_signature}}, le {{date_signature}}, en deux exemplaires originaux.

Le Propriétaire
(nom et signature)
Le Locataire
(nom et signature)
TXT;

        $base['mixte'] = <<<'TXT'
CONTRAT DE BAIL À USAGE MIXTE
(Habitation et Activité Commerciale)

Document généré automatiquement par le module LegalTech — conforme à la Loi n°2015-037 du 8 décembre 2015 et à l'Ordonnance n°62-100 du 1er octobre 1962.

Entre les soussignés :
{{bailleur_nom}} {{bailleur_prenoms}}, propriétaire du bien désigné ci-après, domicilié(e) à {{bailleur_adresse}}, ci-après dénommé « le Propriétaire », D'une part,
Et {{locataire_nom}} {{locataire_prenoms}}, titulaire de la CIN n° {{locataire_cin}}, exerçant la profession de {{locataire_profession}}, ci-après dénommé « le Locataire », D'autre part,

Il a été convenu et arrêté ce qui suit :

Article 1 — Objet du contrat
Le présent contrat a pour objet de location d'une partie des locaux sis à {{adresse_maison}}, {{ville}}.

Article 2 — Désignation du bien loué
En considération des conditions et des engagements à respecter par Le Locataire, le Propriétaire loue au Locataire une partie des locaux sur une surface de {{superficie_m2}} mètre carré ({{superficie_m2}} m²).

Article 3 — Durée du bail
a)- La durée du contrat de bail :
Le présent contrat prend effet dès sa signature et prend fin 12 mois après sa date de signature. De ce fait, ce présent contrat débute le {{date_debut}} et prend fin le {{date_fin}}.
b)- Date du début des activités :
La date de début signifie la date à laquelle le Locataire commencera à s'installer dans les locaux loués.
c)- Prorogation du contrat :
Les parties peuvent proroger le contrat par accord mutuel entre les deux parties sous forme manuscrit.

Article 4 — Loyer et charges
Pour la première année d'activité du Locataire, les parties se sont convenues que le montant à payer est de {{loyer_mensuel}} Ariary par mois. Les charges locatives (eau, électricité) restent à la charge exclusive du Locataire.
Le paiement du loyer doit se faire au plus tard le cinq du mois. à défaut d'omission du paiement, le propriétaire émet un avis verbal au locataire pour régler le paiement. Passé les cinq jours après omission, le locataire n'arrive pas à payer le loyer, le propriétaire est dans son plein droit d'expulser le locataire et de réquisitionner les clés en leur possession.

Article 5 — Dépôt de garantie
Le Locataire a dans l'obligation de payer au propriétaire une caution de garantie afin de préserver l'état du locale ainsi loué, cette somme est fixé à {{depot_garantie}} Ariary. Dès que le locataire quitte la maison, les deux parties doivent évaluer ensemble, l'ensemble des locaux et si les deux parties sont convenues qu'aucune réparation ne doit se faire, le propriétaire est dans l'obligation de rembourser immédiatement au locataire la somme de la caution.

Article 6 — Usage des lieux
Les locaux loués peuvent être occupés et utilisés par le Locataire à un usage mixte : d'une part comme maison d'habitation, d'autre part à l'exploitation d'un commerce. Aucune disposition du présent contrat n'accorde au Locataire le droit d'utiliser la propriété à une autre fin que celle décrite ci-haut. Par ailleurs, le Locataire ne peut sous louer ou autrement autoriser autrui d'utiliser les locaux.

Article 7 — Obligations du Bailleur
Sauf disposition contraire du présent contrat, et mis à part l'entretien et les remplacements résultant des actes ou omissions des Locataires, le Propriétaire devra réparer tous les défauts et déficiences de tout équipements ou matériels du bâtiment. Le Propriétaire devra préserver les locaux de tels défauts ou déficiences durant le présent contrat.
• Délivrer le bien loué en bon état d'usage et de réparation ;
• Assurer au Locataire la jouissance paisible des lieux pendant toute la durée du bail.

Article 8 — Obligations du Preneur
Le Locataire devra réparer et maintenir une bonne condition, sauf usure, les réparations effectuées par le Propriétaire conformément au présent contrat. Le locataire devra aussi effectuer les réparations ou les remplacements nécessaires suite à des actes, des omissions ou de négligence du Locataire, ses employés, agents ou sous-traitants.
Le Locataire ne permettra pas les gaspillages, les nuisances ou les activités illégales dans les locaux loués.
Tous les biens personnels, fournitures et équipements ainsi que le mobilier installé par ou aux frais du Locataire ainsi que les ajouts installés dans les locaux loués et utilisés dans les cadres des activités du Locataire aux frais du Locataire et pouvant être enlevés des locaux loués sans dommage. Sauf si ces dommages peuvent réparées par le Locataire, resteront la propriété du Locataire et le Locataire peut mais n'est pas obligé, de les retirer entièrement, ou partie à tout moment pendant la durée du contrat, pourvu que le Locataire effectue les réparations des dommages occasionnés à cet effet à ses propres frais.
• Régulariser sa situation fiscale (NIF/STAT) pour l'exercice de son activité commerciale ;
• Souscrire une assurance couvrant les risques locatifs.

Article 9 — Droit au renouvellement
Le propriétaire accorde au Locataire le droit de renouveler le présent contrat de un an. Le Locataire pour exercer l'option de renouvellement, devra adresser une notification écrite au Propriétaire au plus tard 2 mois avant l'expiration du présent contrat de location.
Conformément à l'article 29 de la Loi n°2015-037, le Locataire ayant exploité le fonds de commerce de manière continue pendant deux années bénéficie d'un droit au renouvellement du bail, sauf motif grave et légitime opposé par le Propriétaire.

Article 10 — Préavis et résiliation
En cas de congé, la notification doit être faite par écrit avec un préavis de six (6) mois.

Article 11 — Clause résolutoire
À défaut de paiement à son échéance d'un seul terme de loyer, ou en cas d'inexécution des clauses et conditions du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée sans effet.

Article 12 — Enregistrement fiscal
Conformément à l'article 02.01.14 du Code Général des Impôts, le présent contrat doit être enregistré auprès du Centre fiscal compétent dans un délai de deux (2) mois à compter de sa signature. En application de l'article 02.02.12 du CGI, le droit d'enregistrement applicable est fixé à 2 % du montant total des loyers, soit {{montant_droit_enregistrement}} Ariary. Une fiche fiscale est annexée au présent contrat.

Article 13 — Élection de domicile et juridiction compétente
Pour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus indiquées. Tout litige relatif à l'interprétation ou à l'exécution du présent contrat relève de la compétence des juridictions malgaches.

Article 14 — LOI APPLICABLE - JURIDICTION COMPÉTENTE
Pour tout ce qui n'est pas prévu par le présent contrat, les parties se réfèrent aux dispositions légales applicables en matière de bail aux usages locaux.
Le présent contrat est soumis tant pour sa validité, son interprétation, que pour son exécution, aux lois et règlements en vigueur à Madagascar.
Les parties s'engagent à agir de bonne foi dans le respect des droits et obligations réciproques définis aux termes du présent contrat. Elles s'engagent à adopter toutes les mesures raisonnables pour assurer la réalisation du présent Contrat.
Tout litige entre les parties sera présenté au Tribunal de Commerce compétent d'Antananarivo, après une tentative de conciliation amiable ou par recours à un arbitre consenti par les deux parties.
EN FOI DE QUOI, les parties ont conclu le présent contrat, qui prend effet dès sa signature.

Fait à {{lieu_signature}}, le {{date_signature}}, en deux exemplaires originaux.
TXT;

        return $base[$code] ?? $base['habitation'];
    }

    private function lignesContrat(array $contexte, array $typeContrat, array $donnees, float $montantDroit, string $hash): array
    {
        $code = $typeContrat['code'] ?? 'habitation';
        $base = [
            'habitation' => [
                'CONTRAT DE BAIL D\'HABITATION',
                'Ordonnance n°62-100 du 1er octobre 1962',
                'Contrat n° ' . ($donnees['numero_contrat'] ?? 'N/A'),
                'Entre le Bailleur : ' . trim(($contexte['proprietaire']['prenoms'] ?? '') . ' ' . ($contexte['proprietaire']['nom'] ?? '')),
                'Et le Preneur : ' . trim(($contexte['client']['prenoms'] ?? '') . ' ' . ($contexte['client']['nom'] ?? '')),
                'Objet : logement à usage exclusif d\'habitation',
                'Lieu : ' . ($contexte['maison']['adresse'] ?? '—'),
                'Loyer mensuel : ' . number_format((float) ($contexte['maison']['loyer_mensuel'] ?? 0), 0, ',', ' ') . ' Ariary',
                'Dépôt de garantie : ' . number_format((float) ($donnees['depot_garantie'] ?? 0), 0, ',', ' ') . ' Ariary',
                'Taux d\'enregistrement : 1%',
                'Montant du droit : ' . number_format($montantDroit, 0, ',', ' ') . ' Ariary',
                'Hash SHA-256 : ' . $hash,
            ],
            'commercial' => [
                'CONTRAT DE BAIL COMMERCIAL',
                'Loi n°2015-037 du 8 décembre 2015',
                'Contrat n° ' . ($donnees['numero_contrat'] ?? 'N/A'),
                'Entre le Bailleur : ' . trim(($contexte['proprietaire']['prenoms'] ?? '') . ' ' . ($contexte['proprietaire']['nom'] ?? '')),
                'Et le Preneur : ' . trim(($contexte['client']['prenoms'] ?? '') . ' ' . ($contexte['client']['nom'] ?? '')),
                'Objet : local commercial / fonds de commerce',
                'Activité : ' . ($donnees['activite_declaree'] ?? '—'),
                'Loyer mensuel : ' . number_format((float) ($contexte['maison']['loyer_mensuel'] ?? 0), 0, ',', ' ') . ' Ariary',
                'Dépôt de garantie : ' . number_format((float) ($donnees['depot_garantie'] ?? 0), 0, ',', ' ') . ' Ariary',
                'Taux d\'enregistrement : 2%',
                'Montant du droit : ' . number_format($montantDroit, 0, ',', ' ') . ' Ariary',
                'Hash SHA-256 : ' . $hash,
            ],
            'mixte' => [
                'CONTRAT DE BAIL À USAGE MIXTE',
                'Habitation + Activité commerciale',
                'Contrat n° ' . ($donnees['numero_contrat'] ?? 'N/A'),
                'Entre le Bailleur : ' . trim(($contexte['proprietaire']['prenoms'] ?? '') . ' ' . ($contexte['proprietaire']['nom'] ?? '')),
                'Et le Preneur : ' . trim(($contexte['client']['prenoms'] ?? '') . ' ' . ($contexte['client']['nom'] ?? '')),
                'Objet : usage mixte habitation + commerce',
                'Lieu : ' . ($contexte['maison']['adresse'] ?? '—'),
                'Loyer mensuel : ' . number_format((float) ($contexte['maison']['loyer_mensuel'] ?? 0), 0, ',', ' ') . ' Ariary',
                'Dépôt de garantie : ' . number_format((float) ($donnees['depot_garantie'] ?? 0), 0, ',', ' ') . ' Ariary',
                'Taux d\'enregistrement : 2%',
                'Montant du droit : ' . number_format($montantDroit, 0, ',', ' ') . ' Ariary',
                'Hash SHA-256 : ' . $hash,
            ],
        ];

        return $base[$code] ?? $base['habitation'];
    }

    private function lignesFicheFiscale(array $contexte, array $typeContrat, array $donnees, float $montantTotalLoyers, float $montantDroit): array
    {
        return [
            'Fiche fiscale du contrat',
            'Maison : ' . ($contexte['maison']['titre'] ?? '—'),
            'Usage : ' . ($donnees['usage_declare'] ?? '—'),
            'Base loyers annuels : ' . number_format($montantTotalLoyers, 0, ',', ' ') . ' Ar',
            'Taux appliqué : ' . ($typeContrat['taux_enregistrement'] ?? '—') . '%',
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
            ->select('contrats.*, maisons.titre AS maison_titre, maisons.adresse AS maison_adresse, maisons.usage_autorise, maisons.loyer_mensuel AS loyer_maison, maisons.valeur_immeuble, maisons.date_construction, maisons.nb_chambres, maisons.id_proprietaire AS maison_proprietaire, client.nom AS client_nom, client.prenoms AS client_prenoms, client.cin_numero AS client_cin_numero, client.cin_date_delivrance AS client_cin_date_delivrance, client.cin_lieu_delivrance AS client_cin_lieu_delivrance, client.date_naissance AS client_date_naissance, client.profession AS client_profession, client.nif AS client_nif, client.stat AS client_stat, bailleur.nom AS proprietaire_nom, bailleur.prenoms AS proprietaire_prenoms, bailleur.cin_numero AS proprietaire_cin_numero, bailleur.cin_date_delivrance AS proprietaire_cin_date_delivrance, bailleur.cin_lieu_delivrance AS proprietaire_cin_lieu_delivrance, bailleur.date_naissance AS proprietaire_date_naissance, bailleur.profession AS proprietaire_profession, bailleur.nif AS proprietaire_nif, bailleur.stat AS proprietaire_stat')
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
        $contrat['texte_contrat'] = $this->buildTexteContratAffichage($contrat);

        return $contrat;
    }

    private function buildTexteContratAffichage(array $contrat): string
    {
        $typeCode = $contrat['type_code'] ?? ($contrat['usage_autorise'] ?? 'habitation');
        $typeCode = in_array($typeCode, ['habitation', 'commercial', 'mixte'], true) ? $typeCode : 'habitation';

        $nomBailleur = trim(($contrat['proprietaire_prenoms'] ?? '') . ' ' . ($contrat['proprietaire_nom'] ?? ''));
        $nomLocataire = trim(($contrat['client_prenoms'] ?? '') . ' ' . ($contrat['client_nom'] ?? ''));
        $adresseMaison = $contrat['maison_adresse'] ?? 'Antananarivo';
        $titreMaison = $contrat['maison_titre'] ?? 'Maison';
        $dateDebut = $contrat['date_debut'] ?? date('Y-m-d');
        $dateFin = ! empty($contrat['date_fin'])
            ? $contrat['date_fin']
            : date('Y-m-d', strtotime($dateDebut . ' +1 year'));
        $dateSignature = date('d/m/Y');
        $montantDroit = (float) ($contrat['montant_droit_enregistrement'] ?? 0);

        $replace = [
            '{numero_contrat}' => $contrat['numero_contrat'] ?? '—',
            '{bailleur}' => $nomBailleur ?: 'Bailleur',
            '{locataire}' => $nomLocataire ?: 'Locataire',
            '{bailleur_cin}' => $contrat['proprietaire_cin_numero'] ?? ($contrat['bailleur_cin'] ?? '—'),
            '{bailleur_adresse}' => $contrat['adresse_proprietaire'] ?? ($contrat['bailleur_adresse'] ?? 'Antananarivo'),
            '{locataire_date_naissance}' => $contrat['client_date_naissance'] ?? ($contrat['date_naissance'] ?? '—'),
            '{locataire_cin}' => $contrat['client_cin_numero'] ?? ($contrat['cin_numero_client'] ?? '—'),
            '{locataire_cin_date}' => $contrat['client_cin_date_delivrance'] ?? ($contrat['cin_date_delivrance'] ?? '—'),
            '{locataire_cin_lieu}' => $contrat['client_cin_lieu_delivrance'] ?? ($contrat['cin_lieu_delivrance'] ?? 'Antananarivo'),
            '{locataire_profession}' => $contrat['client_profession'] ?? ($contrat['profession'] ?? ($contrat['activite_declaree'] ?? '—')),
            '{type_bien}' => $contrat['type_bien'] ?? ($contrat['usage_autorise'] ?? 'Appartement'),
            '{titre_maison}' => $titreMaison,
            '{adresse_maison}' => $adresseMaison,
            '{nb_chambres}' => $contrat['nb_chambres'] ?? 1,
            '{superficie_m2}' => $contrat['superficie_m2'] ?? 0,
            '{date_debut}' => $dateDebut,
            '{date_fin}' => $dateFin,
            '{loyer_mensuel}' => number_format((float) ($contrat['loyer_maison'] ?? ($contrat['loyer_mensuel'] ?? 0)), 0, ',', ' '),
            '{depot_garantie}' => number_format((float) ($contrat['depot_garantie'] ?? 0), 0, ',', ' '),
            '{montant_droit_enregistrement}' => number_format($montantDroit, 0, ',', ' '),
            '{lieu_signature}' => $contrat['ville'] ?? ($contrat['maison_ville'] ?? 'Antananarivo'),
            '{date_signature}' => $dateSignature,
            '{nb_occupants}' => $contrat['nb_occupants'] ?? 1,
            '{activite_declaree}' => $contrat['activite_declaree'] ?? '—',
            '{locataire_nif}' => $contrat['client_nif'] ?? ($contrat['nif'] ?? 'N/A'),
            '{locataire_stat}' => $contrat['client_stat'] ?? ($contrat['stat'] ?? 'N/A'),
            '{duree_bail}' => '1 an',
            '{pas_de_porte}' => '0',
            '{bailleur_nom}' => $contrat['proprietaire_nom'] ?? 'Bailleur',
            '{bailleur_prenoms}' => $contrat['proprietaire_prenoms'] ?? '',
            '{locataire_nom}' => $contrat['client_nom'] ?? 'Locataire',
            '{locataire_prenoms}' => $contrat['client_prenoms'] ?? '',
            '{ville}' => $contrat['ville'] ?? ($contrat['maison_ville'] ?? 'Antananarivo'),
            '{type_contrat}' => $contrat['type_libelle'] ?? 'Bail',
        ];

        // Source unique : les mêmes modèles que pour le PDF généré.
        $modele = str_replace(['{{', '}}'], ['{', '}'], $this->templateContratSelonType($typeCode));

        return strtr($modele, $replace);
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

    private function lignesDepuisTemplate(string $contenu): array
    {
        $contenu = preg_replace('/<br\s*\/?>/i', "\n", $contenu);
        $contenu = strip_tags($contenu);
        $contenu = html_entity_decode($contenu, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $contenu = preg_replace('/\r\n|\r/', "\n", $contenu);
        $contenu = preg_replace('/\n{3,}/', "\n\n", $contenu);
        $lignes = preg_split('/\n/', $contenu);

        $resultat = [];
        foreach ($lignes as $ligne) {
            $ligne = trim((string) $ligne);
            if ($ligne === '') {
                continue;
            }
            $resultat[] = $ligne;
        }

        return $resultat;
    }

    private function templateFicheFiscale(array $contexte, array $typeContrat, array $donnees, float $montantTotalLoyers, float $montantDroit, string $numeroContrat): string
    {
        $taux = (float) ($typeContrat['taux_enregistrement'] ?? 0);
        $dateLimite = date('d/m/Y', strtotime(($donnees['date_debut'] ?? date('Y-m-d')) . ' +30 days'));

        return "FICHE FISCALE\nContrat n° {$numeroContrat}\n\nMaison : " . ($contexte['maison']['titre'] ?? '—') . "\nUsage : " . ($donnees['usage_declare'] ?? '—') . "\nLoyer mensuel : " . number_format((float) ($contexte['maison']['loyer_mensuel'] ?? 0), 0, ',', ' ') . " Ariary\nBase loyers annuels : " . number_format($montantTotalLoyers, 0, ',', ' ') . " Ariary\nTaux appliqué : {$taux}%\nMontant du droit d'enregistrement : " . number_format($montantDroit, 0, ',', ' ') . " Ariary\nDate limite d'enregistrement : {$dateLimite}\n\nDocument généré automatiquement par LegalTech.";
    }
}