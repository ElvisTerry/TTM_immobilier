<?php
/**
 * includes/helpers.php
 * ----------------------
 * Fonctions transverses utilisées dans toute l'application.
 */

/**
 * cheminBase()
 * Détecte automatiquement si l'application est servie depuis la racine
 * du site (ex: http://immo-app.test/) ou depuis un sous-dossier
 * (ex: http://immo-app.test/public/), selon la configuration réelle
 * d'Apache/Laragon au moment de la requête.
 *
 * Pourquoi c'est nécessaire ?
 * Configurer un vhost Laragon pour pointer précisément sur public/ est
 * une manipulation avancée. Beaucoup de setups locaux accèdent au site
 * via .../public/ dans l'URL. Plutôt que d'exiger une configuration
 * parfaite, l'application s'adapte automatiquement aux deux cas grâce
 * à $_SERVER['SCRIPT_NAME'], qui indique toujours le chemin réel du
 * fichier index.php exécuté.
 */
function cheminBase(): string
{
    static $base = null;

    if ($base === null) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base === '.') {
            $base = '';
        }
    }

    return $base;
}

/**
 * url()
 * Construit une URL propre à partir d'un chemin de route et de segments
 * optionnels. C'est LA fonction à utiliser dans toutes les vues pour
 * générer un lien — jamais d'URL écrite en dur.
 *
 * Exemples :
 *   url('biens/recherche')          -> "/biens/recherche" (ou "/public/biens/recherche" selon le setup)
 *   url('biens/detail', [12])       -> ".../biens/detail/12"
 */
function url(string $chemin, array $segments = []): string
{
    $chemin = trim($chemin, '/');
    $url = cheminBase() . '/' . $chemin;

    foreach ($segments as $segment) {
        $url .= '/' . rawurlencode((string) $segment);
    }

    return $url === '' ? '/' : $url;
}

/**
 * urlAbsolue()
 * Comme url(), mais avec le domaine complet (http://immo-app.test/...).
 * Nécessaire dans les emails : un lien relatif ("/connexion") n'a aucun
 * sens en dehors du navigateur, il faut l'adresse complète.
 */
function urlAbsolue(string $chemin, array $segments = []): string
{
    $schema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $hote = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $schema . '://' . $hote . url($chemin, $segments);
}

/**
 * urlWhatsApp()
 * Construit un lien wa.me à partir d'un numéro camerounais saisi sous
 * différents formats (local avec 0, international avec ou sans +) et
 * d'un message pré-rempli. Renvoie null si le numéro est manquant ou
 * manifestement invalide — jamais de lien mort affiché à l'utilisateur.
 */
function urlWhatsApp(?string $telephone, string $message): ?string
{
    if (empty($telephone)) {
        return null;
    }

    // On ne garde que les chiffres — enlève espaces, tirets, +, parenthèses...
    $chiffres = preg_replace('/\D/', '', $telephone);

    if ($chiffres === '') {
        return null;
    }

    // Formats acceptés en entrée :
    //   0XXXXXXXXX  (10 chiffres, format local)      -> 237XXXXXXXXX
    //   237XXXXXXXXX (12 chiffres, déjà international) -> inchangé
    //   XXXXXXXXX  (9 chiffres, sans le 0 initial)    -> 237XXXXXXXXX
    if (strlen($chiffres) === 10 && $chiffres[0] === '0') {
        $chiffres = '237' . substr($chiffres, 1);
    } elseif (strlen($chiffres) === 9) {
        $chiffres = '237' . $chiffres;
    }

    // Un numéro camerounais complet fait 12 chiffres (237 + 9 chiffres) —
    // si on n'arrive pas à ce compte après normalisation, on renonce
    // plutôt que de construire un lien vers un numéro probablement faux.
    if (strlen($chiffres) !== 12) {
        return null;
    }

    return 'https://wa.me/' . $chiffres . '?text=' . rawurlencode($message);
}

/**
 * estPageActive()
 * Détermine si le chemin de route donné correspond à la page actuellement
 * affichée — utilisé pour souligner l'élément actif du menu.
 *
 * $exact = true  : correspondance stricte (utile pour "Accueil", qui ne
 *                  doit s'allumer QUE sur la racine, pas sur toutes les pages).
 * $exact = false : correspondance par préfixe (utile pour "Rechercher",
 *                  qui doit rester actif même sur une page de détail
 *                  atteinte depuis la recherche, par exemple).
 */
function estPageActive(string $route, bool $exact = false): bool
{
    $cheminDemande = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $base = trim(cheminBase(), '/');
    if ($base !== '' && str_starts_with($cheminDemande, $base)) {
        $cheminDemande = trim(substr($cheminDemande, strlen($base)), '/');
    }

    $route = trim($route, '/');

    return $exact ? $cheminDemande === $route : ($cheminDemande === $route || str_starts_with($cheminDemande, $route . '/'));
}

/**
 * genererTokenCSRF() / verifierTokenCSRF()
 * Inchangées depuis la v1 — cette protection a fait ses preuves.
 */
function genererTokenCSRF(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifierTokenCSRF(string $tokenRecu): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $tokenRecu);
}

/**
 * nettoyer()
 * Échappement HTML systématique en sortie — protection XSS.
 */
function nettoyer(?string $donnee): string
{
    return htmlspecialchars($donnee ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * repondreJson()
 * Envoie une réponse JSON avec le bon code HTTP et coupe l'exécution.
 * Utilisée par tous les endpoints AJAX pour ne jamais oublier le
 * Content-Type ni le code de statut.
 */
function repondreJson(array $donnees, int $codeHttp = 200): void
{
    http_response_code($codeHttp);
    header('Content-Type: application/json');
    echo json_encode($donnees);
    exit;
}

function estConnecte(): bool
{
    return isset($_SESSION['utilisateur_id']);
}

/**
 * exigerRole()
 * Redirige vers la connexion si l'utilisateur n'a pas le rôle requis.
 * $roles peut être un rôle unique ('admin') ou plusieurs ('proprietaire','admin').
 */
function exigerRole(string ...$roles): void
{
    if (!estConnecte() || !in_array($_SESSION['utilisateur_role'], $roles, true)) {
        // Journalisation ciblée : uniquement quand la page visée exigeait
        // le rôle admin — pas la peine de tracer chaque redirection de
        // connexion classique (propriétaire non connecté, etc.), seulement
        // les tentatives touchant à l'espace d'administration.
        if (in_array('admin', $roles, true)) {
            require_once __DIR__ . '/../models/AccesAdminRefuse.php';
            (new AccesAdminRefuse())->enregistrer(
                estConnecte() ? (int) $_SESSION['utilisateur_id'] : null,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['REQUEST_URI'] ?? ''
            );
        }

        header('Location: ' . url('connexion'));
        exit;
    }
}

/**
 * exigerRoleAjax()
 * Équivalent d'exigerRole() pour un endpoint appelé en JavaScript :
 * répond en JSON plutôt que de rediriger, mais journalise pareillement
 * les tentatives visant une action réservée aux admins.
 */
function exigerRoleAjax(string ...$roles): void
{
    if (!estConnecte() || !in_array($_SESSION['utilisateur_role'], $roles, true)) {
        if (in_array('admin', $roles, true)) {
            require_once __DIR__ . '/../models/AccesAdminRefuse.php';
            (new AccesAdminRefuse())->enregistrer(
                estConnecte() ? (int) $_SESSION['utilisateur_id'] : null,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['REQUEST_URI'] ?? ''
            );
        }

        repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
    }
}

/**
 * compresserImage()
 * Redimensionne (si besoin) et recompresse une image via GD, DIRECTEMENT
 * après un upload — évite qu'une photo de téléphone à 4-5 Mo alourdisse
 * inutilement chaque page qui l'affiche. Écrit le résultat en JPEG
 * (format le plus compact pour une photo), quelle que soit l'extension
 * d'origine (jpg/png/webp) : la conversion est invisible pour
 * l'utilisateur (l'app ne se fie qu'au nom de fichier stocké en base).
 *
 * $cheminSource et $cheminDestination peuvent être identiques : dans ce
 * cas, le fichier est simplement remplacé par sa version compressée.
 */
function compresserImage(string $cheminSource, string $cheminDestination, int $largeurMax = 1600, int $qualite = 80): bool
{
    $infos = @getimagesize($cheminSource);
    if ($infos === false) {
        return false;
    }

    [$largeur, $hauteur, $type] = $infos;

    $image = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($cheminSource),
        IMAGETYPE_PNG => @imagecreatefrompng($cheminSource),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($cheminSource) : false,
        default => false,
    };

    if ($image === false) {
        return false; // format non reconnu par GD : on garde le fichier original tel quel
    }

    // Redimensionnement uniquement si l'image dépasse la largeur max —
    // jamais d'agrandissement, qui ne ferait que dégrader la qualité.
    if ($largeur > $largeurMax) {
        $nouvelleHauteur = (int) round($hauteur * ($largeurMax / $largeur));
        $imageRedimensionnee = imagecreatetruecolor($largeurMax, $nouvelleHauteur);
        imagecopyresampled($imageRedimensionnee, $image, 0, 0, 0, 0, $largeurMax, $nouvelleHauteur, $largeur, $hauteur);
        imagedestroy($image);
        $image = $imageRedimensionnee;
    }

    $reussite = imagejpeg($image, $cheminDestination, $qualite);
    imagedestroy($image);

    return $reussite;
}
