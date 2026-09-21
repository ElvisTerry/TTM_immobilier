<?php
/**
 * controllers/PageController.php
 * ---------------------------------
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Avis.php';

class PageController
{
    public function accueil(): void
    {
        $titrePage = 'Accueil';

        // rechercher() sans filtre applique déjà, par construction,
        // statut_moderation = 'approuve' ET statut = 'disponible'
        // (voir construireConditions() dans le modèle Bien) — on récupère
        // donc naturellement les mêmes annonces que la recherche publique,
        // triées par plus récentes, sans avoir besoin d'une méthode dédiée.
        $bienModel = new Bien();
        $dernieresAnnonces = $bienModel->rechercher([], 'recent', 1, 20);

        foreach ($dernieresAnnonces as &$annonce) {
            $annonce['photo_url'] = $annonce['photo_principale']
                ? cheminBase() . '/uploads/biens/' . $annonce['photo_principale']
                : null;
        }
        // unset() indispensable après un foreach par référence (&$annonce) —
        // voir l'explication détaillée laissée dans l'historique du projet :
        // sans ça, la dernière annonce se retrouve dupliquée à l'affichage.
        unset($annonce);

        // Barre de confiance : chiffres réels de la plateforme, pas des
        // valeurs inventées — un site qui ment sur ses chiffres perd toute
        // crédibilité dès que quelqu'un les vérifie.
        $totalAnnonces = $bienModel->compterParStatutModeration('approuve');
        $totalVilles = $bienModel->compterVillesDistinctes();
        $totalAvis = (new Avis())->compterTous();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/pages/accueil.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * enConstruction()
     * Page temporaire pour d'éventuels liens de menu dont la
     * fonctionnalité ne serait pas encore construite.
     */
    public function enConstruction(): void
    {
        $titrePage = 'Bientôt disponible';
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/pages/en-construction.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
