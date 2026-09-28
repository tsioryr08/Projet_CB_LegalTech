<?php

define('ROOTPATH', __DIR__ . '/');
define('FCPATH', __DIR__ . '/public/');
define('APPPATH', __DIR__ . '/app/');
define('SYSCPATH', __DIR__ . '/system/');
define('WRITEPATH', __DIR__ . '/writable/');

require_once __DIR__ . '/vendor/autoload.php';

// Import the service directly
require_once __DIR__ . '/app/Libraries/ContratPdfService.php';

// Now we can use our service
$service = new \App\Libraries\ContratPdfService();

$lignes = [
    'CONTRAT DE BAIL D\'HABITATION',
    'Contrat n° 2024-001',
    '',
    'Ordonnance n°62-100 du 1er octobre 1962',
    '',
    'Entre les soussignés :',
    '',
    'Jean DUPONT, propriétaire',
    'D\'une part,',
    '',
    'Marie MARTIN, locataire',
    'D\'autre part,',
    '',
    'Il a été convenu et arrêté ce qui suit :',
    '',
    'Article 1 — Objet du contrat',
    '',
    'Le présent contrat a pour objet la location d\'un immeuble à usage d\'habitation sis à Antananarivo.',
    'Ce contrat lie les deux parties pour la durée convenue ci-dessous.',
    '',
    'Article 2 — Désignation du bien loué',
    '',
    'La maison louée comprend une cuisine, un séjour, deux chambres et une salle d\'eau.',
    'Cette maison est située au centre-ville d\'Antananarivo et est louée meublée.',
    '',
    'Article 3 — Durée du bail',
    '',
    'La présente location est consentie pour une durée de trois années.',
    'Cette durée pourra être prorogée par accord mutuel des parties.',
];

$pdf = $service->genererPdfTextuel('CONTRAT DE BAIL D\'HABITATION', $lignes, true);

file_put_contents('/tmp/test-real.pdf', $pdf);
echo "✓ PDF généré : /tmp/test-real.pdf (" . strlen($pdf) . " bytes)\n";
