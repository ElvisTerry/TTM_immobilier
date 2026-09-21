<?php
/**
 * controllers/TableauBordController.php
 * -----------------------------------------
 * Une seule route (/tableau-bord) pour les deux rôles connectés — le
 * contenu diffère entièrement selon qu'on est propriétaire ou locataire,
 * mais l'URL reste la même et cohérente dans toute la navigation.
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Avis.php';
require_once __DIR__ . '/../models/ReservationVisite.php';
require_once __DIR__ . '/../models/Favori.php';
require_once __DIR__ . '/../models/RechercheSauvegardee.php';
require_once __DIR__ . '/../models/Message.php';

class TableauBordController
{
    public function index(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        if ($_SESSION['utilisateur_role'] === 'proprietaire') {
            $this->tableauBordProprietaire();
            return;
        }

        $this->tableauBordLocataire();
    }

    private function tableauBordProprietaire(): void
    {
        $titrePage = 'Mon tableau de bord';
        $proprietaireId = (int) $_SESSION['utilisateur_id'];

        $bienModel = new Bien();
        $mesBiens = $bienModel->listerAvecStatistiquesParProprietaire($proprietaireId);
        $vuesParJour = $bienModel->vuesParJourPourProprietaire($proprietaireId, 30);

        $avisModel = new Avis();
        $avisRecents = $avisModel->recentsPourProprietaire($proprietaireId, 5);
        $noteMoyenne = $avisModel->moyennePourProprietaire($proprietaireId);

        $reservationModel = new ReservationVisite();
        $visitesEnAttente = array_filter(
            $reservationModel->listerPourProprietaire($proprietaireId),
            fn($v) => $v['statut'] === 'en_attente'
        );

        $totalVues = array_sum(array_column($mesBiens, 'nb_vues'));
        $totalFavoris = array_sum(array_column($mesBiens, 'nb_favoris'));

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/tableau-bord/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * tableauBordLocataire()
     * Vue d'ensemble pour un locataire : visites en cours, favoris,
     * alertes actives et messages récents — les 4 choses qu'il revient
     * suivre régulièrement, réunies en un seul endroit plutôt que
     * dispersées sur 4 pages différentes.
     */
    private function tableauBordLocataire(): void
    {
        $titrePage = 'Mon tableau de bord';
        $utilisateurId = (int) $_SESSION['utilisateur_id'];

        $reservationModel = new ReservationVisite();
        $mesVisites = $reservationModel->listerPourLocataire($utilisateurId);
        $visitesActives = array_values(array_filter(
            $mesVisites,
            fn($v) => in_array($v['statut'], ['en_attente', 'acceptee'], true)
        ));

        $favoriModel = new Favori();
        $mesFavoris = $favoriModel->listerPourUtilisateur($utilisateurId);

        $rechercheModel = new RechercheSauvegardee();
        $mesAlertes = $rechercheModel->listerPourUtilisateur($utilisateurId);

        $messageModel = new Message();
        $messagesNonLus = $messageModel->compterNonLus($utilisateurId);
        $conversationsRecentes = $messageModel->listerConversations($utilisateurId, 5);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/tableau-bord/locataire.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}


