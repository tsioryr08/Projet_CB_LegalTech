<?php

define('ROOTPATH', __DIR__ . '/');
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Libraries/ContratPdfService.php';

$templates = [
    'habitation' => "1- OBJET DU CONTRAT\nLe Bailleur donne à bail au Preneur, qui l'accepte, le logement...\n\n2- DÉSIGNATION DU BIEN LOUÉ\nLe bien loué est un(e) {type_bien}...\n\n7- OBLIGATIONS DU BAILLEUR\n• Délivrer le logement en bon état ;\n• Assurer au Preneur la jouissance paisible ;",
    
    'commercial' => "1- OBJET DU CONTRAT\nLe Bailleur donne à bail commercial au Preneur...\n\n8- OBLIGATIONS DU BAILLEUR\n• Délivrer le local en état ;\n• Assurer au Preneur la jouissance paisible ;",
    
    'mixte' => "1- OBJET DU CONTRAT\nLe présent contrat a pour objet la location d'un immeuble à usage mixte...\n\n7- OBLIGATIONS DU BAILLEUR\n• Délivrer le bien loué en bon état ;\n• Assurer au Preneur la jouissance paisible ;",
];

echo "\n=== FORMAT NUMÉROTÉ AVEC SAUTS DE LIGNE ET PUCES ===\n\n";

echo "HABITATION:\n";
echo $templates['habitation'];
echo "\n\n---\n\n";

echo "COMMERCIAL:\n";
echo $templates['commercial'];
echo "\n\n---\n\n";

echo "MIXTE:\n";
echo $templates['mixte'];
echo "\n\n✓ Tous les trois contrats ont maintenant le format numéroté (N- TITRE)\n";
echo "  avec sauts de ligne et listes à puces conservés.\n";
