<?php

// Bootstrap CodeIgniter
$_SERVER['REQUEST_METHOD'] = 'GET';
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

require_once FCPATH . '../vendor/autoload.php';
require_once FCPATH . '../app/Config/Constants.php';

// Instantiate the framework
$app = new \CodeIgniter\CodeIgniter();
$app->initialize();

// Now use the service
$service = new \App\Libraries\ContratPdfService();

$lignes = [
    'CONTRAT DE BAIL D\'HABITATION',
    '',
    'Contrat n° 2024-001',
    '',
    'Ordonnance n°62-100 du 1er octobre 1962 — Droit à la propriété et contrat de bail d\'habitation',
    '',
    'Entre les soussignés :',
    '',
    'Jean DUPONT, propriétaire, demeurant à Antananarivo',
    'D\'une part,',
    '',
    'Marie MARTIN, locataire, demeurant à Antananarivo',
    'D\'autre part,',
    '',
    'Il a été convenu et arrêté ce qui suit :',
    '',
    'Article 1 — Objet du contrat',
    '',
    'Le présent contrat a pour objet la location d\'un immeuble à usage d\'habitation sis à Antananarivo. Ce contrat lie les deux parties pour la durée convenue ci-dessous.',
    '',
    'Article 2 — Désignation du bien loué',
    '',
    'La maison louée, comprend : une cuisine, un séjour, deux chambres, une salle d\'eau et des dépendances. Cette maison est située au centre-ville d\'Antananarivo et est loée meublée selon l\'état des lieux établi d\'un commun accord.',
    '',
    'Article 3 — Durée du bail',
    '',
    'La présente location est consentie pour une durée de trois années à compter de la date de signature du présent contrat. Cette durée pourra être prorogée par accord mutuel des parties.',
    '',
    'Article 4 — Loyer',
    '',
    'Le loyer mensuel est fixé à 500 000 Ariary, payable le premier jour de chaque mois. Ce loyer pourra être révisé conformément à la loi.',
    '',
    'Article 5 — Charges et dépenses',
    '',
    'Le locataire reste responsable du paiement des charges communes et des dépenses d\'entretien courant de l\'immeuble loué.',
    '',
    'Article 6 — Obligations du bailleur',
    '',
    'Le bailleur s\'engage à mettre à la disposition du locataire un immeuble en bon état d\'habitabilité et à effectuer tous les travaux nécessaires pour son maintien en bon état.',
    '',
    'Article 7 — Obligations du locataire',
    '',
    'Le locataire s\'engage à user paisiblement du bien loué, à l\'entretenir en bon état et à le restituer au terme du bail dans l\'état où il l\'aura reçu.',
];

$pdf = $service->genererPdfTextuel('CONTRAT DE BAIL D\'HABITATION', $lignes, true);

if ($pdf) {
    file_put_contents('/tmp/test-contrat.pdf', $pdf);
    echo "✓ PDF généré avec succès : /tmp/test-contrat.pdf\n";
    echo "Taille du PDF : " . strlen($pdf) . " bytes\n";
} else {
    echo "✗ Erreur lors de la génération du PDF\n";
}
