<?php
/**
 * includes/Router.php
 * ----------------------
 * Un mini-routeur "fait main" qui transforme une URL propre comme
 * /biens/detail/12 en un appel à BienController::detail(12).
 *
 * Comment ça marche, en résumé :
 * 1. On DÉCLARE des routes avec des espaces réservés entre accolades :
 *    $router->get('biens/detail/{id}', 'BienController', 'detail');
 * 2. Au moment de la requête, on convertit ce motif en expression
 *    régulière : {id} devient (\d+) (uniquement des chiffres).
 * 3. On compare l'URL réellement demandée à cette expression régulière.
 *    Si ça correspond, les valeurs capturées (ex: "12") sont passées
 *    en arguments à la méthode du controller.
 */
class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $motif, string $controller, string $methode): void
    {
        $this->routes['GET'][$motif] = [$controller, $methode];
    }

    public function post(string $motif, string $controller, string $methode): void
    {
        $this->routes['POST'][$motif] = [$controller, $methode];
    }

    /**
     * dispatch()
     * Point d'entrée appelé une seule fois depuis public/index.php.
     */
    public function dispatch(string $methodeHttp, string $uriDemandee): void
    {
        // On isole le CHEMIN de l'URL (on ignore le ?query=string éventuel)
        $chemin = trim((string) parse_url($uriDemandee, PHP_URL_PATH), '/');

        // On retire le préfixe de base éventuel (ex: "public/") pour que
        // les routes déclarées ("inscription") matchent, que le site soit
        // servi depuis la racine ou depuis un sous-dossier — voir
        // cheminBase() dans helpers.php pour l'explication complète.
        $base = trim(cheminBase(), '/');
        if ($base !== '' && str_starts_with($chemin, $base)) {
            $chemin = trim(substr($chemin, strlen($base)), '/');
        }

        $routesDisponibles = $this->routes[$methodeHttp] ?? [];

        foreach ($routesDisponibles as $motif => $handler) {
            $regex = $this->motifVersRegex($motif);

            if (preg_match($regex, $chemin, $correspondances)) {
                array_shift($correspondances); // on retire la correspondance globale, on garde les {paramètres}
                $this->executer($handler, $correspondances);
                return;
            }
        }

        // Aucune route ne correspond : page 404
        http_response_code(404);
        require_once __DIR__ . '/../views/erreurs/404.php';
    }

    /**
     * motifVersRegex()
     * Convertit "biens/detail/{id}" en "#^biens/detail/(\d+)$#"
     * et "verification-email/{token}" en "#^verification-email/([a-zA-Z0-9]+)$#"
     *
     * Pourquoi restreindre chaque paramètre à un format précis plutôt
     * qu'accepter n'importe quel texte ? C'est une première ligne de
     * sécurité : une URL comme /biens/detail/../../config n'a aucune
     * chance de matcher {id}, et une tentative d'injection dans {token}
     * (espaces, guillemets, points-virgules...) ne matchera pas non plus.
     * Dans les deux cas, ça tombe en 404 avant même d'atteindre le controller.
     *
     * {id}    -> uniquement des chiffres (\d+)
     * {toutAutreNom} -> lettres/chiffres uniquement ([a-zA-Z0-9]+), pour
     *                   des paramètres comme {token} (64 caractères hexadécimaux)
     */
    private function motifVersRegex(string $motif): string
    {
        $motifRegex = preg_replace_callback('#\{([a-zA-Z_]+)\}#', function (array $correspondance) {
            return $correspondance[1] === 'id' ? '(\d+)' : '([a-zA-Z0-9]+)';
        }, $motif);

        return '#^' . $motifRegex . '$#';
    }

    private function executer(array $handler, array $parametres): void
    {
        [$controllerNom, $methode] = $handler;
        $fichierController = __DIR__ . '/../controllers/' . $controllerNom . '.php';

        if (!file_exists($fichierController)) {
            die("Controller introuvable : $controllerNom");
        }

        require_once $fichierController;
        $controller = new $controllerNom();
        $controller->$methode(...$parametres);
    }
}
