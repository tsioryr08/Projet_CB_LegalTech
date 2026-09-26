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
 
$routes->get('client/profil', 'ClientController::profil', ['filter' => 'auth:client']);
$routes->post('client/profil', 'ClientController::mettreAJourProfil', ['filter' => 'auth:client']);
 
// -----------------------------------------------------------------
// ESPACE PROPRIETAIRE — protégé par le filtre auth:proprietaire
// -----------------------------------------------------------------
$routes->get('proprietaire/tableau-de-bord', 'ProprietaireController::tableauDeBord', ['filter' => 'auth:proprietaire']);
 
