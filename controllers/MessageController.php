<?php
/**
 * controllers/MessageController.php
 * -------------------------------------
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Message.php';

class MessageController
{
    /**
     * verifierParticipant()
     * Un locataire ne peut écrire qu'AU PROPRIÉTAIRE du bien. Un
     * propriétaire ne peut répondre qu'à quelqu'un avec qui une
     * conversation existe déjà sur ce bien — jamais initier lui-même
     * un échange avec n'importe qui en devinant un id.
     */
    private function verifierParticipant(array $bien, int $utilisateurId, int $autreId, Message $messageModel): bool
    {
        $estProprietaire = $utilisateurId === (int) $bien['proprietaire_id'];

        if (!$estProprietaire) {
            return $autreId === (int) $bien['proprietaire_id'];
        }

        return !empty($messageModel->conversation((int) $bien['id'], $utilisateurId, $autreId));
    }

    public function conversation(int $bienId, int $autreId): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $bienModel = new Bien();
        $bien = $bienModel->trouverParId($bienId);
        if (!$bien) {
            http_response_code(404);
            require_once __DIR__ . '/../views/erreurs/404.php';
            return;
        }

        $utilisateurId = (int) $_SESSION['utilisateur_id'];
        $messageModel = new Message();

        if (!$this->verifierParticipant($bien, $utilisateurId, $autreId, $messageModel)) {
            http_response_code(403);
            die("Conversation introuvable ou accès non autorisé.");
        }

        $messages = $messageModel->conversation($bienId, $utilisateurId, $autreId);
        $messageModel->marquerCommeLus($bienId, $utilisateurId, $autreId);

        $titrePage = 'Conversation - ' . $bien['titre'];

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/messages/conversation.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * envoyerAjax()
     * Envoie un message SANS recharger la page — le formulaire de la
     * conversation utilise cet endpoint via fetch().
     */
    public function envoyerAjax(int $bienId, int $autreId): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        $bien = $bienModel->trouverParId($bienId);
        if (!$bien) {
            repondreJson(['succes' => false, 'erreur' => 'Annonce introuvable.'], 404);
        }

        $utilisateurId = (int) $_SESSION['utilisateur_id'];
        $messageModel = new Message();

        if (!$this->verifierParticipant($bien, $utilisateurId, $autreId, $messageModel)) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $contenu = trim($_POST['contenu'] ?? '');
        if ($contenu === '' || mb_strlen($contenu) > 2000) {
            repondreJson(['succes' => false, 'erreur' => 'Message invalide (1 à 2000 caractères).'], 422);
        }

        $id = $messageModel->envoyer($bienId, $utilisateurId, $autreId, $contenu);

        require_once __DIR__ . '/../models/Notification.php';
        (new Notification())->creer(
            $autreId,
            'message',
            $_SESSION['utilisateur_nom'] . ' vous a envoyé un message à propos de "' . $bien['titre'] . '"',
            url('biens/' . $bienId . '/messages/' . $utilisateurId)
        );

        repondreJson([
            'succes' => true,
            'id' => $id,
            'contenu' => $contenu,
            'dateEnvoi' => date('d/m à H:i'),
        ]);
    }

    /**
     * nouveauxAjax()
     * Interrogée périodiquement (polling) par la page de conversation
     * ouverte, pour afficher les nouveaux messages sans que l'utilisateur
     * ait besoin de recharger la page — un effet "temps réel" simple,
     * sans dépendance à un serveur WebSocket.
     */
    public function nouveauxAjax(int $bienId, int $autreId): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false], 403);
        }

        $utilisateurId = (int) $_SESSION['utilisateur_id'];
        $depuisId = (int) ($_GET['depuis'] ?? 0);

        $messageModel = new Message();
        $messages = $messageModel->messagesDepuis($bienId, $utilisateurId, $autreId, $depuisId);

        if (!empty($messages)) {
            $messageModel->marquerCommeLus($bienId, $utilisateurId, $autreId);
        }

        repondreJson(['succes' => true, 'messages' => $messages, 'utilisateurId' => $utilisateurId]);
    }

    public function boiteReception(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $titrePage = 'Messagerie';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $parPage = 20;
        $messageModel = new Message();
        $conversations = $messageModel->listerConversations((int) $_SESSION['utilisateur_id'], $parPage, ($page - 1) * $parPage);
        $pageSuivanteExiste = count($conversations) === $parPage;

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/messages/boite.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
