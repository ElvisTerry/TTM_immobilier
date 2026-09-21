<?php
/**
 * controllers/ReservationController.php
 * -------------------------------------------
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Disponibilite.php';
require_once __DIR__ . '/../models/ReservationVisite.php';

class ReservationController
{
    /** Créneaux horaires proposés — volontairement une liste fermée plutôt
     * qu'un champ libre, pour garder des rendez-vous à heures rondes. */
    private const CRENEAUX_DISPONIBLES = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

    public function formulaire(int $bienId): void
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

        if ((int) $_SESSION['utilisateur_id'] === (int) $bien['proprietaire_id']) {
            header('Location: ' . url('biens/detail', [$bienId]));
            exit;
        }

        $titrePage = 'Réserver une visite';
        $erreurs = $_SESSION['erreurs_visite'] ?? [];
        unset($_SESSION['erreurs_visite']);

        $dispoModel = new Disponibilite();
        $periodesBloquees = $dispoModel->listerPourBien($bienId);
        $creneaux = self::CRENEAUX_DISPONIBLES;

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/visites/formulaire.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

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

        $dateVisite = $_POST['date_visite'] ?? '';
        $heureVisite = $_POST['heure_visite'] ?? '';
        $message = trim($_POST['message'] ?? '');

        $erreurs = [];

        $dateValide = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVisite) && strtotime($dateVisite) !== false;
        if (!$dateValide || $dateVisite < date('Y-m-d')) {
            $erreurs[] = "Veuillez choisir une date valide, à partir d'aujourd'hui.";
        }
        if (!in_array($heureVisite, self::CRENEAUX_DISPONIBLES, true)) {
            $erreurs[] = "Veuillez choisir un créneau horaire valide.";
        }

        $dispoModel = new Disponibilite();
        $reservationModel = new ReservationVisite();

        // On revérifie TOUT côté serveur, même si le calendrier affiché
        // grise déjà les dates bloquées — le JavaScript peut toujours
        // être contourné par une requête forgée à la main.
        if ($dateValide && $dispoModel->dateEstBloquee($bienId, $dateVisite)) {
            $erreurs[] = "Cette date n'est pas disponible pour ce bien.";
        }
        if ($dateValide && $reservationModel->dejaDemandee($bienId, (int) $_SESSION['utilisateur_id'], $dateVisite)) {
            $erreurs[] = "Vous avez déjà une demande de visite en cours pour cette date.";
        }

        if (!empty($erreurs)) {
            $_SESSION['erreurs_visite'] = $erreurs;
            header('Location: ' . url('biens/' . $bienId . '/visite'));
            exit;
        }

        $reservationModel->creer($bienId, (int) $_SESSION['utilisateur_id'], $dateVisite, $heureVisite, $message);

        require_once __DIR__ . '/../models/Notification.php';
        (new Notification())->creer(
            (int) $bien['proprietaire_id'],
            'visite',
            $_SESSION['utilisateur_nom'] . ' souhaite visiter "' . $bien['titre'] . '" le ' . date('d/m/Y', strtotime($dateVisite)),
            url('mes-visites')
        );

       $_SESSION['message_succes'] = "Votre demande de visite a été envoyée au propriétaire.";
        if (!empty($bien['proprietaire_telephone'])) {
            $_SESSION['proposer_whatsapp_visite'] = true;
        }
        header('Location: ' . url('biens/detail', [$bienId]));
        exit;

    }

    /**
     * mesVisites()
     * Page unique, mais le contenu s'adapte au rôle : un locataire y voit
     * les demandes qu'il a envoyées, un propriétaire celles qu'il a reçues
     * (tous biens confondus).
     */
    public function mesVisites(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $titrePage = 'Mes visites';
        $reservationModel = new ReservationVisite();
        $vueProprietaire = $_SESSION['utilisateur_role'] === 'proprietaire';

        $visites = $vueProprietaire
            ? $reservationModel->listerPourProprietaire((int) $_SESSION['utilisateur_id'])
            : $reservationModel->listerPourLocataire((int) $_SESSION['utilisateur_id']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/visites/liste.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    /**
     * annulerAjax()
     * Le LOCATAIRE annule sa propre demande de visite (en_attente ou
     * acceptee) — le statut 'annulee' existait déjà en base sans qu'aucun
     * chemin du code ne puisse jamais l'atteindre. On n'autorise pas
     * l'annulation d'une visite déjà refusée ou déjà annulée : ça n'a
     * pas de sens de "réannuler" quelque chose qui n'est plus actif.
     */
    public function annulerAjax(int $id): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $reservationModel = new ReservationVisite();
        if (!$reservationModel->appartientAuLocataire($id, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $reservation = $reservationModel->trouverParId($id);
        if (!$reservation || !in_array($reservation['statut'], ['en_attente', 'acceptee'], true)) {
            repondreJson(['succes' => false, 'erreur' => 'Cette visite ne peut plus être annulée.'], 422);
        }

        // Si la visite avait bloqué une date (cas d'une visite acceptée,
        // voir changerStatutAjax), on la libère : le propriétaire
        // redevient disponible ce jour-là pour d'autres demandes de visite.
        if (!empty($reservation['disponibilite_id'])) {
            (new Disponibilite())->supprimer((int) $reservation['disponibilite_id']);
            $reservationModel->definirDisponibiliteAssociee($id, null);
        }

        $reservationModel->changerStatut($id, 'annulee');

        require_once __DIR__ . '/../models/Notification.php';
        (new Notification())->creer(
            (int) $reservation['proprietaire_id'],
            'visite',
            $_SESSION['utilisateur_nom'] . ' a annulé sa visite pour "' . $reservation['bien_titre'] . '" du ' . date('d/m/Y', strtotime($reservation['date_visite'])),
            url('mes-visites')
        );

        repondreJson(['succes' => true, 'statut' => 'annulee']);
    }



    /**
     * changerStatutAjax()
     * Le propriétaire accepte ou refuse une demande, sans quitter la page.
     */
   public function changerStatutAjax(int $id): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $reservationModel = new ReservationVisite();
        if (!$reservationModel->appartientAuProprietaire($id, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, ['acceptee', 'refusee'], true)) {
            repondreJson(['succes' => false, 'erreur' => 'Statut invalide.'], 422);
        }

        $reservation = $reservationModel->trouverParId($id);
        $dispoModel = new Disponibilite();

        // Si cette visite avait déjà bloqué une date (ex : acceptée une
        // première fois, puis le propriétaire change d'avis), on libère
        // d'abord l'ancien blocage avant d'appliquer le nouveau statut —
        // évite un blocage fantôme qui resterait bloqué indéfiniment.
        if (!empty($reservation['disponibilite_id'])) {
            $dispoModel->supprimer((int) $reservation['disponibilite_id']);
            $reservationModel->definirDisponibiliteAssociee($id, null);
        }

        $reservationModel->changerStatut($id, $statut);

        require_once __DIR__ . '/../models/Notification.php';
        $notificationModel = new Notification();

        if ($statut === 'acceptee') {
            // On bloque automatiquement la date sur le calendrier de
            // disponibilité — sauf si elle l'est déjà (blocage manuel du
            // propriétaire, ou une autre visite acceptée ce jour-là),
            // pour ne jamais créer de doublon.
            if (!$dispoModel->dateEstBloquee((int) $reservation['bien_id'], $reservation['date_visite'])) {
                $disponibiliteId = $dispoModel->ajouter(
                    (int) $reservation['bien_id'],
                    $reservation['date_visite'],
                    $reservation['date_visite'],
                    'Visite acceptée'
                );
                $reservationModel->definirDisponibiliteAssociee($id, $disponibiliteId);
            }

            // Les autres demandes encore en attente ce même jour n'ont
            // plus lieu d'être : le propriétaire ne peut pas recevoir
            // deux visiteurs différents en même temps.
            foreach ($reservationModel->autresEnAttentePourDate((int) $reservation['bien_id'], $reservation['date_visite'], $id) as $autre) {
                $reservationModel->changerStatut((int) $autre['id'], 'refusee');
                $notificationModel->creer(
                    (int) $autre['locataire_id'],
                    'visite',
                    'Votre demande de visite pour "' . $autre['bien_titre'] . '" a été refusée : ce créneau vient d\'être attribué à quelqu\'un d\'autre.',
                    url('mes-visites')
                );
            }
        }

        $notificationModel->creer(
            (int) $reservation['locataire_id'],
            'visite',
            'Votre demande de visite pour "' . $reservation['bien_titre'] . '" a été ' . ($statut === 'acceptee' ? 'acceptée' : 'refusée'),
            url('mes-visites')
        );

        repondreJson(['succes' => true, 'statut' => $statut]);
    }

}
