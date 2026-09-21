<?php
/**
 * controllers/SignalementController.php
 * -------------------------------------------
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Signalement.php';

class SignalementController
{
    private const MOTIFS_VALIDES = ['fausse_annonce', 'prix_suspect', 'contenu_inapproprie', 'arnaque_suspectee', 'autre'];

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

        if ((int) $_SESSION['utilisateur_id'] === (int) $bien['proprietaire_id']) {
            header('Location: ' . url('biens/detail', [$bienId]));
            exit;
        }

        $motif = $_POST['motif'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if (!in_array($motif, self::MOTIFS_VALIDES, true)) {
            $_SESSION['erreurs_signalement'] = ["Veuillez choisir un motif valide."];
            header('Location: ' . url('biens/detail', [$bienId]));
            exit;
        }

        $signalementModel = new Signalement();
        $signalementModel->creer($bienId, (int) $_SESSION['utilisateur_id'], $motif, $description);

        $_SESSION['message_succes'] = "Merci, votre signalement a été transmis à notre équipe de modération.";
        header('Location: ' . url('biens/detail', [$bienId]));
        exit;
    }
}
