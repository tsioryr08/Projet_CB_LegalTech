<?php

define('ROOTPATH', __DIR__ . '/');
define('FCPATH', __DIR__ . '/public/');
define('APPPATH', __DIR__ . '/app/');
define('SYSCPATH', __DIR__ . '/system/');
define('WRITEPATH', __DIR__ . '/writable/');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Libraries/ContratPdfService.php';

// Now we can use our service
$service = new \App\Libraries\ContratPdfService();

// Test data for habitation contract
$replace = [
    '{numero_contrat}' => '2024-001',
    '{bailleur}' => 'Jean DUPONT',
    '{bailleur_cin}' => '123456789',
    '{bailleur_adresse}' => '123 Rue de la Paix, Antananarivo',
    '{locataire}' => 'Marie MARTIN',
    '{locataire_date_naissance}' => '15/05/1985',
    '{locataire_cin}' => '987654321',
    '{locataire_cin_date}' => '10/10/2010',
    '{locataire_cin_lieu}' => 'Antananarivo',
    '{locataire_profession}' => 'Ingénieur',
    '{type_bien}' => 'maison',
    '{titre_maison}' => 'Villa Fleur de Vie',
    '{adresse_maison}' => '456 Avenue du Plateau, Antananarivo',
    '{nb_chambres}' => '2',
    '{superficie_m2}' => '120',
    '{date_debut}' => '01/01/2025',
    '{loyer_mensuel}' => '500 000 Ariary',
    '{depot_garantie}' => '1 000 000 Ariary',
    '{montant_droit_enregistrement}' => '6 000 Ariary',
    '{lieu_signature}' => 'Antananarivo',
    '{date_signature}' => '28/09/2024',
    '{nb_occupants}' => '2',
];

// Template habitation with NEW NUMBERED FORMAT
$template = "CONTRAT DE BAIL D'HABITATION
Ordonnance n°62-100 du 1er octobre 1962 — Contrat n° {numero_contrat}

Entre les soussignés :
{bailleur}, titulaire de la CIN n° {bailleur_cin}, demeurant à {bailleur_adresse}, ci-après dénommé « le Bailleur »,
D'une part,
Et {locataire}, né(e) le {locataire_date_naissance}, titulaire de la CIN n° {locataire_cin}, délivrée le {locataire_cin_date} à {locataire_cin_lieu}, exerçant la profession de {locataire_profession}, ci-après dénommé « le Preneur »,
D'autre part,

Il a été convenu et arrêté ce qui suit :

1- OBJET DU CONTRAT : Le Bailleur donne à bail au Preneur, qui l'accepte, le logement désigné ci-après, à usage exclusif d'habitation, conformément à l'Ordonnance n°62-100 du 1er octobre 1962.

2- DESIGNATION DU BIEN LOUE : Le bien loué est un(e) {type_bien} « {titre_maison} » situé(e) à {adresse_maison}, comprenant {nb_chambres} chambre(s), d'une superficie de {superficie_m2} m².

3- DUREE DU BAIL : Le bail est conclu pour une durée d'un (1) an à compter du {date_debut}.

4- LOYER ET CHARGES : Le loyer mensuel est fixé à {loyer_mensuel}. Il est payable d'avance, au plus tard le cinq (5) de chaque mois. Les charges locatives (eau, électricité, entretien courant) sont à la charge du Preneur.

5- DEPOT DE GARANTIE : À la signature, le Preneur verse un dépôt de garantie de {depot_garantie}, qui ne peut excéder deux (2) mois de loyer. Il est restitué en fin de bail après état des lieux de sortie.

6- DESTINATION ET OCCUPATION DES LIEUX : Les lieux sont destinés exclusivement à l'habitation. Toute activité commerciale, artisanale ou professionnelle y est interdite sans avenant préalable. Le logement sera occupé par {nb_occupants} personne(s) au maximum.

7- OBLIGATIONS DU BAILLEUR : Le Bailleur s'engage à délivrer le logement en bon état d'usage et de réparation, à assurer au Preneur la jouissance paisible des lieux, à effectuer les grosses réparations et celles qui ne sont pas locatives, et à remettre une quittance de loyer à chaque paiement.

8- OBLIGATIONS DU PRENEUR : Le Preneur s'engage à payer le loyer et les charges aux échéances convenues, à user des lieux en bon père de famille, à effectuer les réparations locatives et l'entretien courant, et à ne pas transformer les lieux sans accord écrit du Bailleur.

9- SOUS-LOCATION ET CESSION : Toute sous-location, totale ou partielle, ainsi que toute cession du bail, sont interdites sans l'accord écrit et préalable du Bailleur.

10- CONGE ET PREAVIS : Chacune des parties peut mettre fin au bail à l'échéance en notifiant son congé par écrit avec un préavis de trois (3) mois. Le préavis court à compter de la réception de la notification.

Fait à {lieu_signature}, le {date_signature}, en deux exemplaires originaux.

Le Bailleur
(nom et signature)
Le Preneur
(nom et signature)";

// Apply replacements
$texte = strtr($template, $replace);

// Split into lines
$lignes = explode("\n", $texte);

// Generate PDF
$pdf = $service->genererPdfTextuel('CONTRAT DE BAIL D\'HABITATION', $lignes, true);

file_put_contents('/tmp/test-numbered.pdf', $pdf);
echo "✓ PDF généré avec format numéroté : /tmp/test-numbered.pdf (" . strlen($pdf) . " bytes)\n";

// Also output first 120 lines to verify format
echo "\n--- Aperçu du contrat avec articles numérotés ---\n";
$count = 0;
foreach ($lignes as $ligne) {
    echo $ligne . "\n";
    $count++;
    if ($count >= 120) break;
}
