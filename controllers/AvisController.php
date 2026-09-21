<?php
/**
 * controllers/AvisController.php
 * ------------------------------------
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/ReservationVisite.php';
require_once __DIR__ . '/../models/Avis.php';

class AvisController
{
    public function traiter(int $bienId): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $bienModel = new Bien();
        $bien = $bienModel->trouverParId($bienId);
        if (!$bien) {
            http_response_code(404);
            require_once __DIR__ . '/../views/erreurs/404.php';
            return;
        }

        $utilisateurId = (int) $_SESSION['utilisateur_id'];

        if ($utilisateurId === (int) $bien['proprietaire_id']) {
            header('Location: ' . url('biens/detail', [$bienId]));
            exit;
        }

        $reservationModel = new ReservationVisite();
        $avisModel = new Avis();

        if (!$reservationModel->visiteAccepteePour($bienId, $utilisateurId)) {
            $_SESSION['erreurs_avis'] = ["Vous devez avoir effectué une visite acceptée par le propriétaire avant de laisser un avis."];
            header('Location: ' . url('biens/detail', [$bienId]));
            exit;
        }

        if ($avisModel->aDejaNote($bienId, $utilisateurId)) {
            $_SESSION['erreurs_avis'] = ["Vous avez déjà laissé un avis pour ce bien."];
            header('Location: ' . url('biens/detail', [$bienId]));
            exit;
        }

        $note = (int) ($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($note < 1 || $note > 5) {
            $_SESSION['erreurs_avis'] = ["Veuillez choisir une note entre 1 et 5 étoiles."];
            header('Location: ' . url('biens/detail', [$bienId]));
            exit;
        }

        $avisModel->creer($bienId, $utilisateurId, $note, $commentaire);

        require_once __DIR__ . '/../models/Notification.php';
        (new Notification())->creer(
            (int) $bien['proprietaire_id'],
            'avis',
            $_SESSION['utilisateur_nom'] . ' a laissé un avis (' . $note . '★) sur "' . $bien['titre'] . '"',
            url('biens/detail', [$bienId])
        );

        $_SESSION['message_succes'] = "Merci, votre avis a été publié.";
        header('Location: ' . url('biens/detail', [$bienId]));
        exit;
    }

    /**
     * repondreAjax()
     * Le propriétaire répond publiquement à un avis laissé sur l'un de
     * ses biens — sans rechargement de page.
     */
    public function repondreAjax(int $id): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $avisModel = new Avis();
        if (!$avisModel->appartientAuProprietaire($id, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $reponse = trim($_POST['reponse'] ?? '');
        if ($reponse === '' || mb_strlen($reponse) > 500) {
            repondreJson(['succes' => false, 'erreur' => 'Réponse invalide (1 à 500 caractères).'], 422);
        }

        $avisModel->repondre($id, $reponse);
        repondreJson(['succes' => true, 'reponse' => $reponse]);
    }
}
