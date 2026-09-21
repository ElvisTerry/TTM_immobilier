<?php
/**
 * public/index.php
 * ------------------
 * Point d'entrée UNIQUE de l'application. Toute URL (grâce au .htaccess)
 * arrive ici, où le Router détermine quel controller appeler.
 */

require_once __DIR__ . '/config/environnement.php';

// --- Durcissement de la session, avant session_start() ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,   // inaccessible en JavaScript, protège contre le vol via XSS
    'samesite' => 'Lax',  // limite l'envoi du cookie depuis un autre site (anti-CSRF)
    'secure' => ENVIRONNEMENT === 'production',
]);
session_start();

// --- En-têtes de sécurité HTTP, sur toute réponse ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Router.php';

$router = new Router();

/**
 * Déclaration des routes.
 * On ajoutera des lignes ici à chaque nouvelle fonctionnalité (Jour 3, 4...).
 * Pour l'instant (Jour 2) : uniquement l'accueil et des pages "à venir"
 * pour que la navigation du menu ne tombe jamais en 404 pendant qu'on
 * construit le reste, jour après jour.
 */
$router->get('', 'PageController', 'accueil');
$router->get('biens/recherche', 'BienController', 'recherche');
$router->get('biens/recherche/ajax', 'BienController', 'rechercheAjax');
$router->get('biens/recherche/villes', 'BienController', 'suggestionsVillesAjax');
$router->get('biens/recherche/quartiers', 'BienController', 'suggestionsQuartiersAjax');
$router->get('biens/comparateur', 'BienController', 'comparateur');

$router->get('inscription', 'AuthController', 'formulaireInscription');
$router->post('inscription', 'AuthController', 'traiterInscription');
$router->get('connexion', 'AuthController', 'formulaireConnexion');
$router->post('connexion', 'AuthController', 'traiterConnexion');
$router->get('deconnexion', 'AuthController', 'deconnexion');

$router->get('profil/{id}', 'ProfilController', 'voir');
$router->get('mon-profil', 'ProfilController', 'modifier');
$router->post('mon-profil', 'ProfilController', 'traiterModifier');
$router->post('mon-profil/avatar', 'ProfilController', 'traiterAvatar');
$router->get('mon-compte/supprimer', 'ProfilController', 'formulaireSuppression');
$router->post('mon-compte/supprimer', 'ProfilController', 'traiterSuppression');

$router->post(
    'mon-profil/email',
    'ProfilController',
    'traiterChangementEmail'
);

$router->get(
    'confirmer-changement-email/{token}',
    'ProfilController',
    'confirmerChangementEmail'
);



$router->get('biens/creer/infos', 'BienController', 'etapeInfos');
$router->post('biens/creer/infos', 'BienController', 'traiterEtapeInfos');
$router->get('biens/creer/equipements', 'BienController', 'etapeEquipements');
$router->post('biens/creer/equipements', 'BienController', 'traiterEtapeEquipements');
$router->get('biens/creer/localisation', 'BienController', 'etapeLocalisation');
$router->post('biens/creer/localisation', 'BienController', 'traiterEtapeLocalisation');
$router->get('biens/creer/photos', 'BienController', 'etapePhotos');
$router->post('biens/creer/photos', 'BienController', 'traiterEtapePhotos');
$router->post('biens/creer/photos/upload', 'BienController', 'uploaderPhotoAjax');
$router->post('biens/creer/photos/supprimer', 'BienController', 'supprimerPhotoAjax');
$router->get('biens/creer/recapitulatif', 'BienController', 'etapeRecapitulatif');
$router->post('biens/creer/finaliser', 'BienController', 'finaliser');
$router->get('biens/detail/{id}', 'BienController', 'detail');

$router->get('biens/{id}/disponibilites', 'DisponibiliteController', 'gerer');
$router->post('biens/{id}/disponibilites/ajouter', 'DisponibiliteController', 'ajouterAjax');
$router->post('biens/{id}/disponibilites/supprimer', 'DisponibiliteController', 'supprimerAjax');

$router->get('biens/{id}/visite', 'ReservationController', 'formulaire');
$router->post('biens/{id}/visite', 'ReservationController', 'traiter');
$router->get('mes-visites', 'ReservationController', 'mesVisites');
$router->post('visites/{id}/statut', 'ReservationController', 'changerStatutAjax');

$router->post('biens/{id}/avis', 'AvisController', 'traiter');
$router->post('avis/{id}/repondre', 'AvisController', 'repondreAjax');

$router->get('mes-favoris', 'FavoriController', 'mesFavoris');
$router->post('biens/{id}/favori', 'FavoriController', 'basculerAjax');

$router->get('messages', 'MessageController', 'boiteReception');
$router->get('biens/{id}/messages/{id}', 'MessageController', 'conversation');
$router->post('biens/{id}/messages/{id}/envoyer', 'MessageController', 'envoyerAjax');
$router->get('biens/{id}/messages/{id}/nouveaux', 'MessageController', 'nouveauxAjax');

$router->get('notifications/liste', 'NotificationController', 'listerAjax');
$router->post('notifications/marquer-lues', 'NotificationController', 'marquerToutesLuesAjax');

$router->get('tableau-bord', 'TableauBordController', 'index');

$router->get('mes-annonces', 'BienController', 'gestion');
$router->get('biens/{id}/modifier', 'BienController', 'formulaireModification');
$router->post('biens/{id}/modifier', 'BienController', 'traiterModification');
$router->post('biens/{id}/photos/ajouter', 'BienController', 'ajouterPhotoExistanteAjax');
$router->post('biens/{id}/photos/{id}/supprimer', 'BienController', 'supprimerPhotoExistanteAjax');
$router->post('biens/{id}/statut-commercial', 'BienController', 'changerStatutCommercialAjax');
$router->post('biens/{id}/supprimer', 'BienController', 'supprimerAjax');

$router->get('admin', 'AdminController', 'index');
$router->post('admin/annonces/{id}/approuver', 'AdminController', 'approuverAjax');
$router->post('admin/annonces/{id}/rejeter', 'AdminController', 'rejeterAjax');
$router->get('admin/signalements', 'AdminController', 'signalements');
$router->post('admin/signalements/{id}/traiter', 'AdminController', 'traiterSignalementAjax');
$router->get('admin/utilisateurs', 'AdminController', 'utilisateurs');
$router->get('admin/journal-acces', 'AdminController', 'journalAcces');
$router->post('admin/utilisateurs/{id}/statut', 'AdminController', 'changerStatutUtilisateurAjax');
$router->post('admin/sauvegarde', 'AdminController', 'exporterSauvegarde');

$router->get('mot-de-passe-oublie', 'AuthController', 'formulaireMotDePasseOublie');
$router->post('mot-de-passe-oublie', 'AuthController', 'traiterMotDePasseOublie');
$router->get('reinitialiser-mot-de-passe/{token}', 'AuthController', 'formulaireReinitialisation');
$router->post('reinitialiser-mot-de-passe/{token}', 'AuthController', 'traiterReinitialisation');

$router->post('alertes/sauvegarder', 'AlerteController', 'sauvegarderAjax');
$router->get('mes-alertes', 'AlerteController', 'mesAlertes');
$router->post('alertes/{id}/supprimer', 'AlerteController', 'supprimerAjax');

$router->post('biens/{id}/signaler', 'SignalementController', 'traiter');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);






