<?php
/**
 * controllers/NotificationController.php
 * -------------------------------------------
 */
require_once __DIR__ . '/../models/Notification.php';

class NotificationController
{
    /**
     * listerAjax()
     * Chargée en AJAX à l'ouverture de la cloche (Jour 13) — pas au
     * chargement de la page, pour ne pas alourdir chaque page du site
     * avec une requête que l'utilisateur ne consultera peut-être jamais.
     */
    public function listerAjax(): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false], 403);
        }

        $decalage = (int) ($_GET['decalage'] ?? 0);
        $notificationModel = new Notification();
        $notifications = $notificationModel->listerRecentes((int) $_SESSION['utilisateur_id'], 15, $decalage);

        repondreJson(['succes' => true, 'notifications' => $notifications, 'aPlus' => count($notifications) === 15]);
    }

    /**
     * marquerToutesLuesAjax()
     * Appelée à l'ouverture du panneau de notifications — comme sur la
     * plupart des applications, ouvrir la liste suffit à la "consommer".
     */
    public function marquerToutesLuesAjax(): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $notificationModel = new Notification();
        $notificationModel->marquerToutesLues((int) $_SESSION['utilisateur_id']);

        repondreJson(['succes' => true]);
    }
}
