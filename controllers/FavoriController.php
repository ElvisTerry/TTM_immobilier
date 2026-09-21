<?php
/**
 * controllers/FavoriController.php
 * ------------------------------------
 */
require_once __DIR__ . '/../models/Favori.php';

class FavoriController
{
    /**
     * basculerAjax()
     * Ajoute/retire un favori sans rechargement de page — le cœur change
     * d'état immédiatement au clic.
     */
    public function basculerAjax(int $bienId): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Connectez-vous pour ajouter des favoris.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $favoriModel = new Favori();
        $estFavori = $favoriModel->basculer((int) $_SESSION['utilisateur_id'], $bienId);

        repondreJson(['succes' => true, 'estFavori' => $estFavori]);
    }

    public function mesFavoris(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $titrePage = 'Mes favoris';
        $favoriModel = new Favori();
        $favoris = $favoriModel->listerPourUtilisateur((int) $_SESSION['utilisateur_id']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/favoris/liste.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
