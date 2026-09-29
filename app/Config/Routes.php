<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// -----------------------------------------------------------------
// AUTHENTIFICATION (client / proprietaire)
// -----------------------------------------------------------------
$routes->get('auth/connexion/(:segment)', 'Auth::formulaireConnexion/$1');
$routes->post('auth/connexion/(:segment)', 'Auth::connexion/$1');

$routes->get('auth/inscription/(:segment)', 'Auth::formulaireInscription/$1');
$routes->post('auth/inscription/(:segment)', 'Auth::inscription/$1');

$routes->get('auth/deconnexion', 'Auth::deconnexion');

  
// -----------------------------------------------------------------
// ESPACE CLIENT (locataire) — protégé par le filtre auth:client
// -----------------------------------------------------------------
$routes->get('client/catalogue', 'ClientController::catalogue', ['filter' => 'auth:client']);
$routes->get('client/maison/(:num)', 'ClientController::ficheMaison/$1', ['filter' => 'auth:client']);
$routes->post('client/demander/(:num)', 'ClientController::demanderLocation/$1', ['filter' => 'auth:client']);
$routes->get('client/mes-demandes', 'ClientController::mesDemandes', ['filter' => 'auth:client']);
 
$routes->get('client/notifications', 'ClientController::notifications', ['filter' => 'auth:client']);
$routes->post('client/notifications/(:num)/lu', 'ClientController::marquerNotificationLue/$1', ['filter' => 'auth:client']);
$routes->post('client/notifications/tout-marquer-lu', 'ClientController::marquerToutesNotificationsLues', ['filter' => 'auth:client']);
 
$routes->get('client/dossier/(:num)', 'ClientController::formulaireDossier/$1', ['filter' => 'auth:client']);
$routes->post('client/dossier/(:num)', 'ClientController::enregistrerDossier/$1', ['filter' => 'auth:client']);
 
// $routes->get('client/mes-contrats', 'ClientController::mesContrats', ['filter' => 'auth:client']);
// $routes->get('client/contrat/(:num)', 'ClientController::monContrat/$1', ['filter' => 'auth:client']);
// $routes->post('client/contrat/(:num)/signer', 'ClientController::signerContrat/$1', ['filter' => 'auth:client']);
// $routes->post('client/avenant/(:num)/signer', 'ClientController::signerAvenant/$1', ['filter' => 'auth:client']);
 
$routes->get('client/profil', 'ClientController::profil', ['filter' => 'auth:client']);
$routes->get('client/avenants', 'ContratController::indexAvenants', ['filter' => 'auth:client']);
$routes->post('client/profil', 'ClientController::mettreAJourProfil', ['filter' => 'auth:client']);
 
// -----------------------------------------------------------------
// ESPACE PROPRIETAIRE — protégé par le filtre auth:proprietaire
// -----------------------------------------------------------------
$routes->get('proprietaire/tableau-de-bord', 'ProprietaireController::tableauDeBord', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/contrats', 'ContratController::indexProprietaire', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/avenants', 'ContratController::indexAvenants', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/demandes/(:num)/contrat', 'ContratController::formulaireGenerer/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/demandes/(:num)/contrat', 'ContratController::generer/$1', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/contrats/(:num)', 'ContratController::detailProprietaire/$1', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/contrats/(:num)/avenants', 'ContratController::avenants/$1', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/contrats/(:num)/avenants/ajouter', 'ContratController::formulaireAvenant/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/contrats/(:num)/avenants', 'ContratController::creerAvenant/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/avenants/(:num)/signer', 'ContratController::signerAvenantBailleur/$1', ['filter' => 'auth:proprietaire']);
$routes->post('contrats/(:num)/signer-bailleur', 'ContratController::signerBailleur/$1', ['filter' => 'auth:proprietaire']);
 
$routes->get('proprietaire/maisons', 'MaisonController::index', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/maisons/ajouter', 'MaisonController::formulaireAjout', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/maisons/ajouter', 'MaisonController::ajouter', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/maisons/(:num)/modifier', 'MaisonController::formulaireModifier/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/maisons/(:num)/modifier', 'MaisonController::modifier/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/maisons/(:num)/supprimer', 'MaisonController::supprimer/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/maisons/(:num)/statut', 'MaisonController::changerStatut/$1', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/maisons/(:num)/photos', 'MaisonController::gererPhotos/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/maisons/(:num)/photos', 'MaisonController::ajouterPhoto/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/maisons/(:num)/photos/(:num)/supprimer', 'MaisonController::supprimerPhoto/$1/$2', ['filter' => 'auth:proprietaire']);
$routes->get('proprietaire/maisons/(:num)/historique', 'MaisonController::historique/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/maisons/(:num)/historique', 'MaisonController::mettreAJourHistorique/$1', ['filter' => 'auth:proprietaire']);

$routes->get('proprietaire/demandes', 'DemandeController::index', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/demandes/(:num)/valider', 'DemandeController::valider/$1', ['filter' => 'auth:proprietaire']);
$routes->post('proprietaire/demandes/(:num)/refuser', 'DemandeController::refuser/$1', ['filter' => 'auth:proprietaire']);
$routes->get('contrats/(:num)/pdf', 'ContratController::telechargerPdf/$1', ['filter' => 'auth']);
$routes->get('avenants/(:num)', 'ContratController::consulterAvenant/$1', ['filter' => 'auth']);
$routes->get('avenants/(:num)/pdf', 'ContratController::telechargerAvenantPdf/$1', ['filter' => 'auth']);
$routes->get('contrats/(:num)/fiche-fiscale', 'ContratController::telechargerFicheFiscale/$1', ['filter' => 'auth']);
$routes->get('client/contrats/(:num)', 'ContratController::detailClient/$1', ['filter' => 'auth:client']);
$routes->get('client/contrats/(:num)/avenants', 'ContratController::avenants/$1', ['filter' => 'auth:client']);
$routes->post('client/avenants/(:num)/signer', 'ContratController::signerAvenantLocataire/$1', ['filter' => 'auth:client']);
$routes->post('contrats/(:num)/signer-locataire', 'ContratController::signerLocataire/$1', ['filter' => 'auth:client']);
$routes->post('client/dossier/(:num)/verifier-regles', 'ReglesController::verifierLive/$1', ['filter' => 'auth:client']);