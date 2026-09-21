<?php
/**
 * config/database.php
 * ---------------------
 * Connexion PDO centralisée et sécurisée (inchangée depuis la v1,
 * elle avait déjà fait ses preuves).
 */

define('DB_HOST', 'sql300.infinityfree.com');
define('DB_NAME', 'if0_42659982_immo_app');
define('DB_USER', 'if0_42659982');
define('DB_PASS', 'lm658pPbKNg');
define('DB_CHARSET', 'utf8mb4');

function getConnexion(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die('Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
        }
    }

    return $pdo;
}






