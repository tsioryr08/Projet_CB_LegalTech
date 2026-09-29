<?php

define('ROOTPATH', __DIR__ . '/');
define('FCPATH', __DIR__ . '/public/');
define('APPPATH', __DIR__ . '/app/');
define('SYSCPATH', __DIR__ . '/system/');
define('WRITEPATH', __DIR__ . '/writable/');

require_once __DIR__ . '/vendor/autoload.php';

// Import the service and controller
require_once __DIR__ . '/app/Libraries/ContratPdfService.php';
require_once __DIR__ . '/app/Controllers/ContratController.php';

// Now we can use our service and controller
$service = new \App\Libraries\ContratPdfService();
$controller = new \App\Controllers\ContratController();

// Test data matching the template placeholders for habitation contract
$contrat = [
    'type' => 'habitation',
    'numero_contrat' => '2024-001',
    'bailleur' => 'Jean DUPONT',
    'bailleur_cin' => '123456789',
    'bailleur_adresse' => '123 Rue de la Paix, Antananarivo',
    'locataire' => 'Marie MARTIN',
    'locataire_date_naissance' => '15/05/1985',
    'locataire_cin' => '987654321',
    'locataire_cin_date' => '10/10/2010',
    'locataire_cin_lieu' => 'Antananarivo',
    'locataire_profession' => 'Ingénieur',
    'type_bien' => 'maison',
    'titre_maison' => 'Villa Fleur de Vie',
    'adresse_maison' => 456 . ' Avenue du Plateau, Antananarivo',
    'nb_chambres' => '2',
    'superficie_m2' => '120',
    'date_debut' => '01/01/2025',
    'loyer_mensuel' => '500000',
    'depot_garantie' => '1000000',
    'nb_occupants' => '2',
    'montant_droit_enregistrement' => '6000',
    'lieu_signature' => 'Antananarivo',
    'date_signature' => '28/09/2024',
];

// Use the controller method to generate the contract text
$contratText = $controller->buildTexteContratAffichage($contrat);

// Generate PDF
$pdf = $service->genererPdfTextuel('CONTRAT DE BAIL D\'HABITATION', 
    explode("\n", $contratText), true);

file_put_contents('/tmp/test-real-numbered.pdf', $pdf);
echo "✓ PDF généré avec templates reformatés : /tmp/test-real-numbered.pdf (" . strlen($pdf) . " bytes)\n";

// Also output first 80 lines to see the format
echo "\n--- Aperçu du contrat généré ---\n";
$lines = explode("\n", $contratText);
for ($i = 0; $i < min(80, count($lines)); $i++) {
    echo $lines[$i] . "\n";
}
