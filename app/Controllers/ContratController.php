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

    public function proposerAvenant(int $idContrat)
    {
        $contrat = $this->chargerContratAccessible($idContrat, (int) session('id_utilisateur'), 'proprietaire');
        if ($contrat === null) {
            return redirect()->to('/proprietaire/contrats')->with('erreur', 'Contrat introuvable.');
        }

        $typeAvenant = $this->request->getPost('type_avenant') ?: 'autre';
        $champModifie = trim((string) $this->request->getPost('champ_modifie') ?: 'autre');
        $ancienneValeur = trim((string) $this->request->getPost('ancienne_valeur') ?? '');
        $nouvelleValeur = trim((string) $this->request->getPost('nouvelle_valeur') ?? '');
        $justification = trim((string) $this->request->getPost('justification') ?? '');
        $dateEffet = $this->request->getPost('date_effet') ?: date('Y-m-d');

        $avenantModel = new AvenantModel();
        $numeroAvenant = $avenantModel->numeroSuivantPourContrat($idContrat);

        $contenuAvenant = [
            'AVENANT N° ' . $numeroAvenant,
            'Contrat n° ' . ($contrat['numero_contrat'] ?? 'N/A'),
            'Type : ' . $typeAvenant,
            'Champ modifié : ' . $champModifie,
            'Ancienne valeur : ' . ($ancienneValeur !== '' ? $ancienneValeur : '—'),
            'Nouvelle valeur : ' . ($nouvelleValeur !== '' ? $nouvelleValeur : '—'),
            'Justification : ' . ($justification !== '' ? $justification : '—'),
            'Date d’effet : ' . $dateEffet,
        ];

        $pdfService = new ContratPdfService();
        $pdfPath = 'uploads/avenants/' . date('Y') . '/' . $contrat['numero_contrat'] . '-A-' . $numeroAvenant . '.pdf';
        $pdf = $pdfService->genererPdfTextuel('Avenant ' . $numeroAvenant, $contenuAvenant);

        if (! is_dir(dirname(FCPATH . $pdfPath))) {
            mkdir(dirname(FCPATH . $pdfPath), 0775, true);
        }
        file_put_contents(FCPATH . $pdfPath, $pdf);

        $idAvenant = $avenantModel->insert([
            'id_contrat' => $idContrat,
            'numero_avenant' => $numeroAvenant,
            'type_avenant' => $typeAvenant,
            'champ_modifie' => $champModifie,
            'ancienne_valeur' => $ancienneValeur !== '' ? $ancienneValeur : null,
            'nouvelle_valeur' => $nouvelleValeur !== '' ? $nouvelleValeur : 'N/A',
            'justification' => $justification !== '' ? $justification : null,
            'date_effet' => $dateEffet,
            'statut' => 'propose',
            'contenu_pdf_chemin' => $pdfPath,
            'contenu_hash_sha256' => hash('sha256', implode("\n", $contenuAvenant)),
        ]);

        if ($idAvenant) {
            (new NotificationModel())->insert([
                'id_utilisateur' => (int) $contrat['id_client'],
                'type' => 'avenant_a_signer',
                'reference_table' => 'avenants',
                'reference_id' => (int) $idAvenant,
                'message' => 'Un avenant a été proposé par le bailleur. Veuillez le signer après validation.',
            ]);
        }

        return redirect()->back()->with('succes', 'Avenant proposé avec numérotation automatique.');
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

    private function construireContenuHtml(array $contexte, array $typeContrat, array $donnees, float $montantDroit, string $numeroContrat): string
    {
        $code = $typeContrat['code'] ?? 'habitation';
        $template = $this->templateContratSelonType($code);

        $valeurs = [
            '{{numero_contrat}}' => $numeroContrat,
            '{{bailleur_nom}}' => htmlspecialchars($contexte['proprietaire']['nom'] ?? '', ENT_QUOTES),
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
            '{{titre_foncier}}' => htmlspecialchars($contexte['maison']['titre_foncier'] ?? 'N/A', ENT_QUOTES),
            '{{date_debut}}' => htmlspecialchars($donnees['date_debut'] ?? date('Y-m-d'), ENT_QUOTES),
            '{{date_signature}}' => htmlspecialchars(date('d/m/Y'), ENT_QUOTES),
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
        $base = [
            'habitation' => "CONTRAT DE BAIL D'HABITATION\nOrdonnance n°62-100 du 1er octobre 1962 — Contrat n° {{numero_contrat}}\n\nEntre les soussignés :\n{{bailleur_nom}} {{bailleur_prenoms}}, titulaire de la CIN n° {{bailleur_cin}}, demeurant à {{bailleur_adresse}}, ci-après dénommé « le Bailleur »,\nD'une part,\nEt {{locataire_nom}} {{locataire_prenoms}}, né(e) le {{locataire_date_naissance}}, titulaire de la CIN n° {{locataire_cin}} délivrée le {{locataire_cin_date}} à {{locataire_cin_lieu}}, exerçant la profession de {{locataire_profession}}, ci-après dénommé « le Preneur »,\nD'autre part,\n\nIl a été convenu et arrêté ce qui suit :\n\nArticle 1 — Objet du contrat\nLe Bailleur donne à bail au Preneur, qui l'accepte, le logement désigné à l'article 2, à usage exclusif d'habitation, conformément à l'Ordonnance n°62-100 du 1er octobre 1962.\n\nArticle 2 — Désignation du bien loué\nLe bien loué est un(e) {{type_bien}} « {{titre_maison}} » situé(e) à {{adresse_maison}}, {{ville}}, comprenant {{nb_chambres}} chambre(s), d'une superficie de {{superficie_m2}} m².\n\nArticle 3 — Durée du bail\nLe bail est conclu pour une durée d'un (1) an à compter du {{date_debut}}, renouvelable par tacite reconduction pour des périodes successives d'un an, sauf congé donné dans les conditions de l'article 10.\n\nArticle 4 — Loyer et charges\nLe loyer mensuel est fixé à {{loyer_mensuel}} Ariary. Il est payable d'avance, au plus tard le cinq (5) de chaque mois. Les charges locatives (eau, électricité, entretien courant) sont à la charge du Preneur.\n\nArticle 5 — Dépôt de garantie\nÀ la signature, le Preneur verse un dépôt de garantie de {{depot_garantie}} Ariary, qui ne peut excéder deux (2) mois de loyer. Il est restitué en fin de bail après état des lieux de sortie.\n\nArticle 6 — Destination et occupation des lieux\nLes lieux sont destinés exclusivement à l'habitation. Toute activité commerciale, artisanale ou professionnelle y est interdite sans avenant préalable. Le logement sera occupé par {{nb_occupants}} personne(s) au maximum.\n\nArticle 7 — Obligations du Bailleur\n• Délivrer le logement en bon état d'usage et de réparation ;\n• Assurer au Preneur la jouissance paisible des lieux ;\n• Effectuer les grosses réparations et celles qui ne sont pas locatives ;\n• Remettre une quittance de loyer à chaque paiement.\n\nArticle 8 — Obligations du Preneur\n• Payer le loyer et les charges aux échéances convenues ;\n• User des lieux en bon père de famille ;\n• Effectuer les réparations locatives et l'entretien courant ;\n• Ne pas transformer les lieux sans accord écrit du Bailleur.\n\nArticle 9 — Sous-location et cession\nToute sous-location, totale ou partielle, ainsi que toute cession du bail, sont interdites sans l'accord écrit et préalable du Bailleur.\n\nArticle 10 — Congé et préavis\nChacune des parties peut mettre fin au bail à l'échéance en notifiant son congé par écrit avec un préavis de trois (3) mois. Le préavis court à compter de la réception de la notification.\n\nArticle 11 — Clause résolutoire\nÀ défaut de paiement d'un seul terme de loyer à son échéance, ou en cas d'inexécution des obligations du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée infructueuse.\n\nArticle 12 — État des lieux\nUn état des lieux contradictoire est dressé à l'entrée et à la sortie du Preneur. À défaut, le logement est présumé remis en bon état de réparations locatives.\n\nArticle 13 — Enregistrement fiscal\nConformément à l'article 02.01.14 du Code Général des Impôts, le présent contrat doit être enregistré dans un délai de deux (2) mois à compter de sa signature. Le droit d'enregistrement applicable est de 1 % du montant total des loyers, soit {{montant_droit_enregistrement}} Ariary. Une fiche fiscale est annexée au présent contrat.\n\nArticle 14 — Élection de domicile et litiges\nPour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus. Tout litige relève de la compétence des juridictions malgaches.\n\nFait à {{lieu_signature}}, le {{date_signature}}, en deux exemplaires originaux.\n\nLe Bailleur\n(nom et signature)\nLe Preneur\n(nom et signature)",
            'commercial' => "CONTRAT DE BAIL COMMERCIAL\nLoi n°2015-037 du 8 décembre 2015 — Contrat n° {{numero_contrat}}\n\nEntre les soussignés :\n{{bailleur_nom}} {{bailleur_prenoms}}, titulaire de la CIN n° {{bailleur_cin}}, demeurant à {{bailleur_adresse}}, ci-après dénommé « le Bailleur », D'une part,\nEt {{locataire_nom}} {{locataire_prenoms}}, titulaire de la CIN n° {{locataire_cin}}, exerçant l'activité de {{activite_declaree}}, immatriculé(e) sous le NIF n° {{locataire_nif}} et le STAT n° {{locataire_stat}}, ci-après dénommé « le Preneur », D'autre part,\n\nIl a été convenu et arrêté ce qui suit :\n\nArticle 1 — Objet du contrat\nLe Bailleur donne à bail commercial au Preneur, qui l'accepte, le local désigné à l'article 2, en vue de l'exploitation d'un fonds de commerce, conformément à la Loi n°2015-037 du 8 décembre 2015.\n\nArticle 2 — Désignation du local\nLe local loué est un(e) {{type_bien}} « {{titre_maison}} » situé(e) à {{adresse_maison}}, {{ville}}, d'une superficie de {{superficie_m2}} m².\n\nArticle 3 — Destination et activité autorisée\nLe local est destiné exclusivement à l'activité suivante : {{activite_declaree}}. Toute modification ou extension d'activité requiert l'accord écrit préalable du Bailleur.\n\nArticle 4 — Durée du bail\nLe bail est conclu pour une durée de {{duree_bail}} à compter du {{date_debut}}. À défaut de terme fixé, il est réputé conclu pour une durée indéterminée, sauf congé donné dans les conditions l'article 10.\n\nArticle 5 — Loyer et charges\nLe loyer mensuel est fixé à {{loyer_mensuel}} Ariary, payable d'avance au plus tard le cinq (5) de chaque mois. Les charges locatives (eau, électricité, taxes liées à l'exploitation) sont à la charge du Preneur.\n\nArticle 6 — Pas-de-porte\nLe cas échéant, un droit d'entrée (pas-de-porte) de {{pas_de_porte}} Ariary est versé par le Preneur au Bailleur à la signature. Son montant ne peut excéder l'équivalent de trois (3) mois de loyer.\n\nArticle 7 — Dépôt de garantie\nLe Preneur verse un dépôt de garantie de {{depot_garantie}} Ariary, restitué en fin de bail après état des lieux de sortie et déduction des sommes dues.\n\nArticle 8 — Obligations du Bailleur\n• Délivrer le local en état de servir à l'usage commercial convenu ;\n• Assurer au Preneur la jouissance paisible du local pendant toute la durée du bail ;\n• Effectuer les grosses réparations ;\n• Remettre une quittance de loyer à chaque paiement.\n\nArticle 9 — Obligations du Preneur\n• Payer le loyer, les charges et les impôts liés à son activité ;\n• Exploiter le fonds de commerce de façon continue ;\n• Maintenir son immatriculation fiscale (NIF/STAT) pendant toute la durée du bail ;\n• Entretenir le local et effectuer les réparations locatives ;\n• Souscrire une assurance couvrant le local et l'activité exercée.\n\nArticle 10 — Préavis et résiliation\nPour un bail à durée indéterminée, le congé est notifié par écrit avec un préavis de six (6) mois.\n\nArticle 11 — Droit au renouvellement\nConformément à l'article 29 de la Loi n°2015-037, le Preneur qui a exploité de manière continue son fonds pendant deux (2) ans bénéficie d'un droit au renouvellement du bail, sauf motif grave et légitime opposé par le Bailleur.\n\nArticle 12 — Cession et sous-location\nLa sous-location est interdite sans l'accord écrit du Bailleur. La cession du bail ne peut intervenir qu'avec la cession du fonds de commerce, après information préalable du Bailleur.\n\nArticle 13 — Condition suspensive d'immatriculation\nSi, à la date de signature, le Preneur n'a pas encore communiqué son NIF et son STAT, le présent bail est conclu sous condition suspensive de leur production dans un délai de trente (30) jours. À défaut, le bail est réputé caduc sans indemnité.\n\nArticle 14 — Clause résolutoire\nÀ défaut de paiement d'un seul terme de loyer, ou en cas d'inexécution des obligations du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée sans effet.\n\nArticle 15 — Enregistrement fiscal\nConformément à l'article 02.01.14 du Code Général des Impôts, le contrat doit être enregistré dans un délai de deux (2) mois à compter de sa signature. Le droit d'enregistrement applicable est de 2 % du montant total des loyers, soit {{montant_droit_enregistrement}} Ariary. Une fiche fiscale est annexée au présent contrat.\n\nArticle 16 — Élection de domicile et juridiction compétente\nPour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus indiquées. Tout litige relatif à l'interprétation ou à l'exécution du présent contrat relève de la compétence des juridictions malgaches.\n\nFait à {{lieu_signature}}, le {{date_signature}}, en deux exemplaires originaux.\n\nLe Bailleur\n(nom et signature)\nLe Preneur\n(nom et signature)",
            'mixte' => "CONTRAT DE BAIL À USAGE MIXTE\n(Habitation et Activité Commerciale)\n\nDocument généré automatiquement par le module LegalTech — conforme à la Loi n°2015-037 du 8 décembre 2015 et à l'Ordonnance n°62-100 du 1er octobre 1962.\n\nEntre les soussignés :\n{{bailleur_nom}} {{bailleur_prenoms}}, propriétaire du bien désigné ci-après, domicilié(e) à {{bailleur_adresse}}, ci-après dénommé « le Bailleur », D'une part,\nEt {{locataire_nom}} {{locataire_prenoms}}, titulaire de la CIN n° {{locataire_cin}}, exerçant la profession de {{locataire_profession}}, ci-après dénommé « le Preneur », D'autre part,\n\nIl a été convenu et arrêté ce qui suit :\n\nArticle 1 — Objet du contrat\nLe présent contrat a pour objet la location d'un immeuble à usage mixte (habitation et activité commerciale), conformément aux dispositions de la Loi n°2015-037 et de l'Ordonnance n°62-100.\n\nArticle 2 — Désignation du bien loué\nLe bien loué est un(e) {{type_bien}} « {{titre_maison}} » situé(e) à {{adresse_maison}}, {{ville}}, comprenant un espace d'habitation et un local destiné à l'exploitation commerciale, tel que décrit dans la fiche descriptive annexée au présent contrat.\n\nArticle 3 — Durée du bail\nLe présent bail est conclu pour une durée d'un (1) an à compter du {{date_debut}}, renouvelable par tacite reconduction.\n\nArticle 4 — Loyer et charges\nLe loyer mensuel est fixé d'un commun accord entre les parties. Il est payable d'avance, au plus tard le cinq (5) de chaque mois. Les charges locatives (eau, électricité) restent à la charge exclusive du Preneur.\n\nArticle 5 — Dépôt de garantie\nÀ la signature du présent contrat, le Preneur verse au Bailleur un dépôt de garantie équivalent à deux (2) mois de loyer. Ce dépôt est restitué en fin de bail, déduction faite des sommes dues.\n\nArticle 6 — Usage des lieux\nLe Preneur déclare affecter les lieux loués à un usage mixte : d'une part à son habitation personnelle et de sa famille, d'autre part à l'exploitation d'un commerce. Le Preneur s'engage à ne pas modifier la destination des lieux sans l'accord écrit préalable du Bailleur.\n\nArticle 7 — Obligations du Bailleur\n• Délivrer le bien loué en bon état d'usage et de réparation ;\n• Assurer au Preneur la jouissance paisible des lieux pendant toute la durée du bail ;\n• Entretenir les locaux de manière à permettre l'usage mixte prévu au contrat ;\n• Procéder aux réparations autres que locatives.\n\nArticle 8 — Obligations du Preneur\n• Payer le loyer et les charges aux termes convenus ;\n• User des lieux loués en bon père de famille et suivant la destination prévue au contrat ;\n• Ne pas sous-louer ni céder le bail sans l'accord écrit du Bailleur ;\n• Régulariser sa situation fiscale (NIF/STAT) pour l'exercice de son activité commerciale ;\n• Souscrire une assurance couvrant les risques locatifs.\n\nArticle 9 — Droit au renouvellement\nConformément à l'article 29 de la Loi n°2015-037, le Preneur ayant exploité le fonds de commerce de manière continue pendant deux années bénéficie d'un droit au renouvellement du bail, sauf motif grave et légitime opposé par le Bailleur.\n\nArticle 10 — Préavis et résiliation\nEn cas de congé, la notification doit être faite par écrit avec un préavis de six (6) mois. Le présent contrat pourra être résilié de plein droit en cas de non-paiement du loyer ou de manquement grave aux obligations ci-dessus, un mois après une mise en demeure restée infructueuse.\n\nArticle 11 — Clause résolutoire\nÀ défaut de paiement à son échéance d'un seul terme de loyer, ou en cas d'inexécution des clauses et conditions du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée sans effet.\n\nArticle 12 — Enregistrement fiscal\nConformément à l'article 02.01.14 du Code Général des Impôts, le présent contrat doit être enregistré auprès du Centre fiscal compétent dans un délai de deux (2) mois à compter de sa signature. En application de l'article 02.02.12 du CGI, le droit d'enregistrement applicable est fixé à 2 % du montant total des loyers, soit {{montant_droit_enregistrement}} Ariary. Une fiche fiscale est annexée au présent contrat.\n\nArticle 13 — Élection de domicile et juridiction compétente\nPour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus indiquées. Tout litige relatif à l'interprétation ou à l'exécution du présent contrat relève de la compétence des juridictions malgaches.\n\nFait à {{lieu_signature}}, le {{date_signature}}, en deux exemplaires originaux.",
        ];

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
        $dateSignature = date('d/m/Y');
        $montantDroit = (float) ($contrat['montant_droit_enregistrement'] ?? 0);

        $replace = [
            '{numero_contrat}' => $contrat['numero_contrat'] ?? 'N/A',
            '{bailleur}' => $nomBailleur ?: 'Bailleur',
            '{locataire}' => $nomLocataire ?: 'Locataire',
            '{bailleur_cin}' => $contrat['cin_numero'] ?? ($contrat['bailleur_cin'] ?? 'N/A'),
            '{bailleur_adresse}' => $contrat['adresse_proprietaire'] ?? ($contrat['bailleur_adresse'] ?? 'Antananarivo'),
            '{locataire_date_naissance}' => $contrat['client_date_naissance'] ?? ($contrat['date_naissance'] ?? 'N/A'),
            '{locataire_cin}' => $contrat['client_cin_numero'] ?? ($contrat['cin_numero_client'] ?? 'N/A'),
            '{locataire_cin_date}' => $contrat['client_cin_date_delivrance'] ?? ($contrat['cin_date_delivrance'] ?? 'N/A'),
            '{locataire_cin_lieu}' => $contrat['client_cin_lieu_delivrance'] ?? ($contrat['cin_lieu_delivrance'] ?? 'Antananarivo'),
            '{locataire_profession}' => $contrat['client_profession'] ?? ($contrat['profession'] ?? ($contrat['activite_declaree'] ?? '—')),
            '{type_bien}' => $contrat['type_bien'] ?? ($contrat['usage_autorise'] ?? 'Appartement'),
            '{titre_maison}' => $titreMaison,
            '{adresse_maison}' => $adresseMaison,
            '{nb_chambres}' => $contrat['nb_chambres'] ?? 1,
            '{superficie_m2}' => $contrat['superficie_m2'] ?? 0,
            '{date_debut}' => $dateDebut,
            '{loyer_mensuel}' => number_format((float) ($contrat['loyer_maison'] ?? ($contrat['loyer_mensuel'] ?? 0)), 0, ',', ' ') . ' Ariary',
            '{depot_garantie}' => number_format((float) ($contrat['depot_garantie'] ?? 0), 0, ',', ' ') . ' Ariary',
            '{montant_droit_enregistrement}' => number_format($montantDroit, 0, ',', ' ') . ' Ariary',
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

        $templates = [
            'habitation' => "CONTRAT DE BAIL D'HABITATION\nOrdonnance n°62-100 du 1er octobre 1962 — Contrat n° {numero_contrat}\n\nEntre les soussignés :\n{bailleur}, titulaire de la CIN n° {bailleur_cin}, demeurant à {bailleur_adresse}, ci-après dénommé « le Bailleur »,\nD'une part,\nEt {locataire}, né(e) le {locataire_date_naissance}, titulaire de la CIN n° {locataire_cin}, délivrée le {locataire_cin_date} à {locataire_cin_lieu}, exerçant la profession de {locataire_profession}, ci-après dénommé « le Preneur »,\nD'autre part,\n\nIl a été convenu et arrêté ce qui suit :\n\nArticle 1 — Objet du contrat\nLe Bailleur donne à bail au Preneur, qui l'accepte, le logement désigné à l'article 2, à usage exclusif d'habitation, conformément à l'Ordonnance n°62-100 du 1er octobre 1962.\n\nArticle 2 — Désignation du bien loué\nLe bien loué est un(e) {type_bien} « {titre_maison} » situé(e) à {adresse_maison}, comprenant {nb_chambres} chambre(s), d'une superficie de {superficie_m2} m².\n\nArticle 3 — Durée du bail\nLe bail est conclu pour une durée d'un (1) an à compter du {date_debut}.\n\nArticle 4 — Loyer et charges\nLe loyer mensuel est fixé à {loyer_mensuel} Ariary. Il est payable d'avance, au plus tard le cinq (5) de chaque mois. Les charges locatives (eau, électricité, entretien courant) sont à la charge du Preneur.\n\nArticle 5 — Dépôt de garantie\nÀ la signature, le Preneur verse un dépôt de garantie de {depot_garantie} Ariary, qui ne peut excéder deux (2) mois de loyer. Il est restitué en fin de bail après état des lieux de sortie.\n\nArticle 6 — Destination et occupation des lieux\nLes lieux sont destinés exclusivement à l'habitation. Toute activité commerciale, artisanale ou professionnelle y est interdite sans avenant préalable. Le logement sera occupé par {nb_occupants} personne(s) au maximum.\n\nArticle 7 — Obligations du Bailleur\n• Délivrer le logement en bon état d'usage et de réparation ;\n• Assurer au Preneur la jouissance paisible des lieux ;\n• Effectuer les grosses réparations et celles qui ne sont pas locatives ;\n• Remettre une quittance de loyer à chaque paiement.\n\nArticle 8 — Obligations du Preneur\n• Payer le loyer et les charges aux échéances convenues ;\n• User des lieux en bon père de famille ;\n• Effectuer les réparations locatives et l'entretien courant ;\n• Ne pas transformer les lieux sans accord écrit du Bailleur.\n\nArticle 9 — Sous-location et cession\nToute sous-location, totale ou partielle, ainsi que toute cession du bail, sont interdites sans l'accord écrit et préalable du Bailleur.\n\nArticle 10 — Congé et préavis\nChacune des parties peut mettre fin au bail à l'échéance en notifiant son congé par écrit avec un préavis de trois (3) mois. Le préavis court à compter de la réception de la notification.\n\nArticle 11 — Clause résolutoire\nÀ défaut de paiement d'un seul terme de loyer à son échéance, ou en cas d'inexécution des obligations du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée infructueuse.\n\nArticle 12 — État des lieux\nUn état des lieux contradictoire est dressé à l'entrée et à la sortie du Preneur. À défaut, le logement est présumé remis en bon état de réparations locatives.\n\nArticle 13 — Enregistrement fiscal\nConformément à l'article 02.01.14 du Code Général des Impôts, le présent contrat doit être enregistré dans un délai de deux (2) mois à compter de sa signature. Le droit d'enregistrement applicable est de 1 % du montant total des loyers, soit {montant_droit_enregistrement} Ariary. Une fiche fiscale est annexée au présent contrat.\n\nArticle 14 — Élection de domicile et litiges\nPour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus. Tout litige relève de la compétence des juridictions malgaches.\n\nFait à {lieu_signature}, le {date_signature}, en deux exemplaires originaux.\n\nLe Bailleur\n(nom et signature)\nLe Preneur\n(nom et signature)",
            'commercial' => "CONTRAT DE BAIL COMMERCIAL\nLoi n°2015-037 du 8 décembre 2015 — Contrat n° {numero_contrat}\n\nEntre les soussignés :\n{bailleur}, titulaire de la CIN n° {bailleur_cin}, demeurant à {bailleur_adresse}, ci-après dénommé « le Bailleur », D'une part,\nEt {locataire}, titulaire de la CIN n° {locataire_cin}, exerçant l'activité de {activite_declaree}, immatriculé(e) sous le NIF n° {locataire_nif} et le STAT n° {locataire_stat}, ci-après dénommé « le Preneur », D'autre part,\n\nIl a été convenu et arrêté ce qui suit :\n\nArticle 1 — Objet du contrat\nLe Bailleur donne à bail commercial au Preneur, qui l'accepte, le local désigné à l'article 2, en vue de l'exploitation d'un fonds de commerce, conformément à la Loi n°2015-037 du 8 décembre 2015.\n\nArticle 2 — Désignation du local\nLe local loué est un(e) {type_bien} « {titre_maison} » situé(e) à {adresse_maison}, d'une superficie de {superficie_m2} m².\n\nArticle 3 — Destination et activité autorisée\nLe local est destiné exclusivement à l'activité suivante : {activite_declaree}. Toute modification ou extension d'activité requiert l'accord écrit préalable du Bailleur.\n\nArticle 4 — Durée du bail\nLe bail est conclu pour une durée de {duree_bail} à compter du {date_debut}.\n\nArticle 5 — Loyer et charges\nLe loyer mensuel est fixé à {loyer_mensuel} Ariary, payable d'avance au plus tard le cinq (5) de chaque mois. Les charges locatives (eau, électricité, taxes liées à l'exploitation) sont à la charge du Preneur.\n\nArticle 6 — Pas-de-porte\nLe cas échéant, un droit d'entrée (pas-de-porte) de {pas_de_porte} Ariary est versé par le Preneur au Bailleur à la signature. Son montant ne peut excéder l'équivalent de trois (3) mois de loyer.\n\nArticle 7 — Dépôt de garantie\nLe Preneur verse un dépôt de garantie de {depot_garantie} Ariary, restitué en fin de bail après état des lieux de sortie et déduction des sommes dues.\n\nArticle 8 — Obligations du Bailleur\n• Délivrer le local en état de servir à l'usage commercial convenu ;\n• Assurer au Preneur la jouissance paisible du local pendant toute la durée du bail ;\n• Effectuer les grosses réparations ;\n• Remettre une quittance de loyer à chaque paiement.\n\nArticle 9 — Obligations du Preneur\n• Payer le loyer, les charges et les impôts liés à son activité ;\n• Exploiter le fonds de commerce de façon continue ;\n• Maintenir son immatriculation fiscale (NIF/STAT) pendant toute la durée du bail ;\n• Entretenir le local et effectuer les réparations locatives ;\n• Souscrire une assurance couvrant le local et l'activité exercée.\n\nArticle 10 — Préavis et résiliation\nPour un bail à durée indéterminée, le congé est notifié par écrit avec un préavis de six (6) mois.\n\nArticle 11 — Droit au renouvellement\nConformément à l'article 29 de la Loi n°2015-037, le Preneur qui a exploité de manière continue son fonds pendant deux (2) ans bénéficie d'un droit au renouvellement du bail, sauf motif grave et légitime opposé par le Bailleur.\n\nArticle 12 — Cession et sous-location\nLa sous-location est interdite sans l'accord écrit du Bailleur. La cession du bail ne peut intervenir qu'avec la cession du fonds de commerce, après information préalable du Bailleur.\n\nArticle 13 — Condition suspensive d'immatriculation\nSi, à la date de signature, le Preneur n'a pas encore communiqué son NIF et son STAT, le présent bail est conclu sous condition suspensive de leur production dans un délai de trente (30) jours. À défaut, le bail est réputé caduc sans indemnité.\n\nArticle 14 — Clause résolutoire\nÀ défaut de paiement d'un seul terme de loyer, ou en cas d'inexécution des obligations du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée sans effet.\n\nArticle 15 — Enregistrement fiscal\nConformément à l'article 02.01.14 du Code Général des Impôts, le contrat doit être enregistré dans un délai de deux (2) mois à compter de sa signature. Le droit d'enregistrement applicable est de 2 % du montant total des loyers, soit {montant_droit_enregistrement} Ariary. Une fiche fiscale est annexée au présent contrat.\n\nArticle 16 — Élection de domicile et juridiction compétente\nPour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus indiquées. Tout litige relatif à l'interprétation ou à l'exécution du présent contrat relève de la compétence des juridictions malgaches.\n\nFait à {lieu_signature}, le {date_signature}, en deux exemplaires originaux.\n\nLe Bailleur\n(nom et signature)\nLe Preneur\n(nom et signature)",
            'mixte' => "CONTRAT DE BAIL À USAGE MIXTE\n(Habitation et Activité Commerciale)\n\nDocument généré automatiquement par le module LegalTech — conforme à la Loi n°2015-037 du 8 décembre 2015 et à l'Ordonnance n°62-100 du 1er octobre 1962.\n\nEntre les soussignés :\n{bailleur_nom} {bailleur_prenoms}, propriétaire du bien désigné ci-après, domicilié(e) à {bailleur_adresse}, ci-après dénommé « le Bailleur », D'une part,\nEt {locataire_nom} {locataire_prenoms}, titulaire de la CIN n° {locataire_cin}, exerçant la profession de {locataire_profession}, ci-après dénommé « le Preneur », D'autre part,\n\nIl a été convenu et arrêté ce qui suit :\n\nArticle 1 — Objet du contrat\nLe présent contrat a pour objet la location d'un immeuble à usage mixte (habitation et activité commerciale), conformément aux dispositions de la Loi n°2015-037 et de l'Ordonnance n°62-100.\n\nArticle 2 — Désignation du bien loué\nLe bien loué est un(e) {type_bien} « {titre_maison} » situé(e) à {adresse_maison}, {ville}, comprenant un espace d'habitation et un local destiné à l'exploitation commerciale, tel que décrit dans la fiche descriptive annexée au présent contrat.\n\nArticle 3 — Durée du bail\nLe présent bail est conclu pour une durée d'un (1) an à compter du {date_debut}, renouvelable par tacite reconduction.\n\nArticle 4 — Loyer et charges\nLe loyer mensuel est fixé d'un commun accord entre les parties. Il est payable d'avance, au plus tard le cinq (5) de chaque mois. Les charges locatives (eau, électricité) restent à la charge exclusive du Preneur.\n\nArticle 5 — Dépôt de garantie\nÀ la signature du présent contrat, le Preneur verse au Bailleur un dépôt de garantie équivalent à deux (2) mois de loyer. Ce dépôt est restitué en fin de bail, déduction faite des sommes dues.\n\nArticle 6 — Usage des lieux\nLe Preneur déclare affecter les lieux loués à un usage mixte : d'une part à son habitation personnelle et de sa famille, d'autre part à l'exploitation d'un commerce. Le Preneur s'engage à ne pas modifier la destination des lieux sans l'accord écrit préalable du Bailleur.\n\nArticle 7 — Obligations du Bailleur\n• Délivrer le bien loué en bon état d'usage et de réparation ;\n• Assurer au Preneur la jouissance paisible des lieux pendant toute la durée du bail ;\n• Entretenir les locaux de manière à permettre l'usage mixte prévu au contrat ;\n• Procéder aux réparations autres que locatives.\n\nArticle 8 — Obligations du Preneur\n• Payer le loyer et les charges aux termes convenus ;\n• User des lieux loués en bon père de famille et suivant la destination prévue au contrat ;\n• Ne pas sous-louer ni céder le bail sans l'accord écrit du Bailleur ;\n• Régulariser sa situation fiscale (NIF/STAT) pour l'exercice de son activité commerciale ;\n• Souscrire une assurance couvrant les risques locatifs.\n\nArticle 9 — Droit au renouvellement\nConformément à l'article 29 de la Loi n°2015-037, le Preneur ayant exploité le fonds de commerce de manière continue pendant deux années bénéficie d'un droit au renouvellement du bail, sauf motif grave et légitime opposé par le Bailleur.\n\nArticle 10 — Préavis et résiliation\nEn cas de congé, la notification doit être faite par écrit avec un préavis de six (6) mois. Le présent contrat pourra être résilié de plein droit en cas de non-paiement du loyer ou de manquement grave aux obligations ci-dessus, un mois après une mise en demeure restée infructueuse.\n\nArticle 11 — Clause résolutoire\nÀ défaut de paiement à son échéance d'un seul terme de loyer, ou en cas d'inexécution des clauses et conditions du présent contrat, celui-ci sera résilié de plein droit, un mois après une mise en demeure restée sans effet.\n\nArticle 12 — Enregistrement fiscal\nConformément à l'article 02.01.14 du Code Général des Impôts, le présent contrat doit être enregistré auprès du Centre fiscal compétent dans un délai de deux (2) mois à compter de sa signature. En application de l'article 02.02.12 du CGI, le droit d'enregistrement applicable est fixé à 2 % du montant total des loyers, soit {montant_droit_enregistrement} Ariary. Une fiche fiscale est annexée au présent contrat.\n\nArticle 13 — Élection de domicile et juridiction compétente\nPour l'exécution des présentes, les parties font élection de domicile à leurs adresses respectives ci-dessus indiquées. Tout litige relatif à l'interprétation ou à l'exécution du présent contrat relève de la compétence des juridictions malgaches.\n\nFait à {lieu_signature}, le {date_signature}, en deux exemplaires originaux.",
        ];

        $modele = str_replace(['{{', '}}'], ['{', '}'], $templates[$typeCode] ?? $templates['habitation']);

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
