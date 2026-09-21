<?php
/**
 * config/environnement.php
 * ----------------------------
 * Un seul endroit à changer au moment du déploiement (Jour 21) :
 * passer 'developpement' à 'production'.
 *
 * En développement, on affiche les erreurs PHP en détail pour déboguer.
 * En production, les afficher à un visiteur serait dangereux (fuite de
 * chemins de fichiers, de structure de code...) — elles partent dans un
 * fichier de log à la place.
 */

define('ENVIRONNEMENT', 'production'); // à changer en 'production' au Jour 21

if (ENVIRONNEMENT === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/erreurs.log');
} else {
    ini_set('display_errors', '1');
}

error_reporting(E_ALL);
