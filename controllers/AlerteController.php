<?php
/**
 * controllers/AlerteController.php
 * ---------------------------------------
 */
require_once __DIR__ . '/../models/RechercheSauvegardee.php';

class AlerteController
{
    public function sauvegarderAjax(): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Connectez-vous pour sauvegarder une recherche.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $nom = trim($_POST['nom'] ?? '');
        if ($nom === '' || mb_strlen($nom) > 100) {
            repondreJson(['succes' => false, 'erreur' => 'Donnez un nom à cette alerte (100 caractères max).'], 422);
        }

        $filtres = [
            'ville' => trim($_POST['ville'] ?? ''),
            'quartier' => trim($_POST['quartier'] ?? ''),
            'type_bien' => $_POST['type_bien'] ?? '',
            'type_transaction' => $_POST['type_transaction'] ?? '',
            'prix_min' => $_POST['prix_min'] ?? '',
            'prix_max' => $_POST['prix_max'] ?? '',
        ];

        $modele = new RechercheSauvegardee();
        $modele->creer((int) $_SESSION['utilisateur_id'], $nom, $filtres);

        repondreJson(['succes' => true]);
    }

    public function mesAlertes(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $titrePage = 'Mes alertes';
        $modele = new RechercheSauvegardee();
        $alertes = $modele->listerPourUtilisateur((int) $_SESSION['utilisateur_id']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/alertes/liste.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function supprimerAjax(int $id): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $modele = new RechercheSauvegardee();
        if (!$modele->appartientA($id, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $modele->supprimer($id);
        repondreJson(['succes' => true]);
    }
}
 
