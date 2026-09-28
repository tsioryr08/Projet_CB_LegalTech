<?php

namespace App\Libraries;

use App\Models\DemandeModel;
use App\Models\DistrictCinModel;
use App\Models\MaisonModel;
use App\Models\RegleLegaltechModel;
use App\Models\RegleResultatModel;
use App\Models\UtilisateurModel;
use DateTimeImmutable;
use Exception;

/**
 * Moteur de règles LegalTech.
 *
 * Hypothèses prises faute de précision dans le cahier des charges — à valider
 * avec la recherche juridique avant la soutenance, et faciles à ajuster ici :
 *
 * - 6e chiffre de la CIN : pair = homme, impair = femme (convention non confirmée
 *   officiellement -> self::sexeAttenduDepuisCin()).
 * - Majorité retenue pour "mineur" : 21 ans, conforme au cahier des charges
 *   -> self::AGE_MAJORITE.
 * - Suroccupation : plus de 3 occupants par chambre -> self::MAX_OCCUPANTS_PAR_CHAMBRE.
 * - Plafond de loyer (immeuble > 5 ans) : fixé arbitrairement à 1 % de la valeur
 *   de l'immeuble par mois, faute de formule légale précisée -> self::PLAFOND_LOYER_POURCENT_VALEUR.
 * - La cohérence CIN/sexe déclenche le même code CIN_FORMAT_INVALIDE que le format,
 *   car le catalogue regles_legaltech ne prévoit pas de code dédié. Facile à séparer
 *   plus tard : ajouter une ligne dans regles_legaltech (ex. CIN_SEXE_INCOHERENT) et
 *   changer le code renvoyé ci-dessous.
 * - depot_garantie n'existe pas encore au stade du dossier (C5) : la règle
 *   CAUTION_PLAFOND ne peut donc s'appliquer qu'au stade de la génération du contrat
 *   (P5), en rappelant evaluer()/resultats() avec le montant réel de la caution.
 */
class MoteurLegaltechService
{
    private const AGE_MAJORITE                 = 21;
    private const MAX_OCCUPANTS_PAR_CHAMBRE     = 3;
    private const PLAFOND_LOYER_POURCENT_VALEUR = 1.0; // % de la valeur de l'immeuble, par mois
    private const AGE_IMMEUBLE_PLAFOND_ANS      = 5;

    /**
     * Exécute toutes les règles sur un jeu de données et retourne la liste des codes déclenchés.
     *
     * Clés possibles dans $donnees : cin_numero, sexe, date_naissance, situation_client,
     * usage_declare, activite_declaree, nif, stat, nb_occupants, nb_chambres,
     * depot_garantie, loyer_mensuel, valeur_immeuble, date_construction.
     * Toute clé absente est simplement ignorée par la règle correspondante.
     *
     * @return string[] codes de regles_legaltech déclenchés
     */
    public function evaluer(array $donnees): array
    {
        $codes = [];

        // --- CIN : format (12 chiffres) ---
        $cin       = (string) ($donnees['cin_numero'] ?? '');
        $cinValide = (bool) preg_match('/^\d{12}$/', $cin);

        if (! $cinValide) {
            $codes[] = 'CIN_FORMAT_INVALIDE';
        } else {
            // --- CIN : cohérence sexe (6e chiffre) ---
            $sexeAttendu = $this->sexeAttenduDepuisCin($cin);
            if ($sexeAttendu !== null && ($donnees['sexe'] ?? null) !== null && $sexeAttendu !== $donnees['sexe']) {
                $codes[] = 'CIN_FORMAT_INVALIDE';
            }

            // --- CIN : district connu ---
            $codeDistrict = substr($cin, 0, 3);
            if ((new DistrictCinModel())->find($codeDistrict) === null) {
                $codes[] = 'CIN_DISTRICT_INCONNU';
            }
        }

        // --- Capacité : mineur ---
        $age = $this->ageDepuisDate($donnees['date_naissance'] ?? null);
        if (($age !== null && $age < self::AGE_MAJORITE) || ($donnees['situation_client'] ?? null) === 'mineur') {
            $codes[] = 'CAPACITE_MINEUR';
        }

        // --- Capacité : condamné ---
        if (($donnees['situation_client'] ?? null) === 'condamne') {
            $codes[] = 'CAPACITE_CONDAMNE';
        }

        // --- Cohérence usage déclaré / activité ---
        $usageDeclare     = $donnees['usage_declare'] ?? null;
        $activiteDeclaree = trim((string) ($donnees['activite_declaree'] ?? ''));
        if ($usageDeclare === 'habitation' && $activiteDeclaree !== '') {
            $codes[] = 'USAGE_INCOHERENT';
        }

        // --- NIF / STAT requis si commercial ou mixte ---
        if (in_array($usageDeclare, ['commercial', 'mixte'], true)) {
            $nif  = trim((string) ($donnees['nif'] ?? ''));
            $stat = trim((string) ($donnees['stat'] ?? ''));
            if ($nif === '' || $stat === '') {
                $codes[] = 'NIF_STAT_MANQUANT';
            }
        }

        // --- Suroccupation ---
        $occupants = (int) ($donnees['nb_occupants'] ?? 0);
        $chambres  = (int) ($donnees['nb_chambres'] ?? 0);
        if ($chambres > 0 && $occupants > $chambres * self::MAX_OCCUPANTS_PAR_CHAMBRE) {
            $codes[] = 'SUROCCUPATION';
        }

        // --- Plafond de la caution (2 mois max) ---
        $depotGarantie = $donnees['depot_garantie'] ?? null;
        $loyerMensuel  = (float) ($donnees['loyer_mensuel'] ?? 0);
        if ($depotGarantie !== null && $loyerMensuel > 0 && (float) $depotGarantie > $loyerMensuel * 2) {
            $codes[] = 'CAUTION_PLAFOND';
        }

        // --- Plafond du loyer (immeuble > 5 ans) ---
        $ageImmeuble    = $this->ageDepuisDate($donnees['date_construction'] ?? null);
        $valeurImmeuble = (float) ($donnees['valeur_immeuble'] ?? 0);
        if ($ageImmeuble !== null && $ageImmeuble > self::AGE_IMMEUBLE_PLAFOND_ANS && $valeurImmeuble > 0) {
            $plafond = $valeurImmeuble * (self::PLAFOND_LOYER_POURCENT_VALEUR / 100);
            if ($loyerMensuel > $plafond) {
                $codes[] = 'LOYER_PLAFOND';
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Reprend les codes déclenchés, va chercher leur définition dans regles_legaltech
     * (niveau, message, article de loi) et calcule le statut global correspondant.
     */
    public function resultats(array $donnees): array
    {
        $codes = $this->evaluer($donnees);

        $regles = empty($codes)
            ? []
            : (new RegleLegaltechModel())->whereIn('code', $codes)->where('actif', 1)->findAll();

        $ordreNiveau = ['bloquant' => 3, 'alerte' => 2, 'information' => 1];
        usort($regles, static fn ($a, $b) => ($ordreNiveau[$b['niveau']] ?? 0) <=> ($ordreNiveau[$a['niveau']] ?? 0));

        $statutGlobal = 'conforme';
        foreach ($regles as $regle) {
            if ($regle['niveau'] === 'bloquant') {
                $statutGlobal = 'bloque';
                break;
            }
            if ($regle['niveau'] === 'alerte') {
                $statutGlobal = 'alerte';
            }
        }

        return [
            'statut_validation_legale' => $statutGlobal,
            'regles'                   => $regles,
        ];
    }

    /**
     * Évalue les règles pour un dossier de location déjà enregistré (id_dossier),
     * persiste la traçabilité dans regles_resultats et met à jour le statut du dossier.
     * À appeler juste après la création/modification d'un dossier_location (C5),
     * ou de nouveau au stade P5 en injectant le montant réel de la caution pour
     * activer la règle CAUTION_PLAFOND.
     */
    public function evaluerEtPersisterPourDossier(int $idDossier, array $donneesSupplementaires = []): array
    {
        $donnees = $this->chargerDonneesDossier($idDossier);

        if ($donnees === null) {
            return ['statut_validation_legale' => 'en_attente', 'regles' => []];
        }

        $donnees  = array_merge($donnees, $donneesSupplementaires);
        $resultat = $this->resultats($donnees);

        $regleResultatModel = new RegleResultatModel();
        $regleResultatModel->where('id_dossier', $idDossier)->delete();

        foreach ($resultat['regles'] as $regle) {
            $regleResultatModel->insert([
                'id_dossier'    => $idDossier,
                'id_regle'      => $regle['id_regle'],
                'niveau_obtenu' => $regle['niveau'],
            ]);
        }

        db_connect()->table('dossiers_location')
            ->where('id_dossier', $idDossier)
            ->update(['statut_validation_legale' => $resultat['statut_validation_legale']]);

        return $resultat;
    }

    /**
     * Rassemble les données nécessaires au moteur à partir d'un id_dossier
     * (dossier + demande + maison + profil du client).
     */
    private function chargerDonneesDossier(int $idDossier): ?array
    {
        $dossier = db_connect()->table('dossiers_location')->where('id_dossier', $idDossier)->get()->getRowArray();

        if ($dossier === null) {
            return null;
        }

        $demande = (new DemandeModel())->find($dossier['id_demande']);
        if ($demande === null) {
            return null;
        }

        $maison = (new MaisonModel())->find($demande['id_maison']);
        $client = (new UtilisateurModel())->find($demande['id_client']);

        return [
            'cin_numero'        => $client['cin_numero'] ?? null,
            'sexe'              => $client['sexe'] ?? null,
            'date_naissance'    => $client['date_naissance'] ?? null,
            'situation_client'  => $dossier['situation_client'] ?? null,
            'usage_declare'     => $dossier['usage_declare'] ?? null,
            'activite_declaree' => $dossier['activite_declaree'] ?? null,
            'nif'               => $client['nif'] ?? null,
            'stat'              => $client['stat'] ?? null,
            'nb_occupants'      => $dossier['nb_occupants'] ?? null,
            'nb_chambres'       => $maison['nb_chambres'] ?? null,
            'loyer_mensuel'     => $maison['loyer_mensuel'] ?? null,
            'valeur_immeuble'   => $maison['valeur_immeuble'] ?? null,
            'date_construction' => $maison['date_construction'] ?? null,
            // depot_garantie volontairement absent ici : inconnu au stade du dossier,
            // à fournir via $donneesSupplementaires au moment de la génération du contrat (P5).
        ];
    }

    private function sexeAttenduDepuisCin(string $cin): ?string
    {
        if (strlen($cin) < 6) {
            return null;
        }

        return ((int) $cin[5]) % 2 === 0 ? 'F' : 'M';
    }

    private function ageDepuisDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        try {
            $reference = new DateTimeImmutable($date);
        } catch (Exception $e) {
            return null;
        }

        return $reference->diff(new DateTimeImmutable())->y;
    }
}
