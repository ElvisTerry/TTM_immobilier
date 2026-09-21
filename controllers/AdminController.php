<?php
/**
 * controllers/AdminController.php
 * -------------------------------------
 * Toutes les méthodes exigent le rôle 'admin' — vérifié en tout premier,
 * systématiquement, avant même de lire quoi que ce soit d'autre.
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Signalement.php';
require_once __DIR__ . '/../models/AccesAdminRefuse.php';

class AdminController
{
    public function index(): void
    {
        exigerRole('admin');

        $titrePage = 'Espace administrateur';
        $bienModel = new Bien();
        $utilisateurModel = new Utilisateur();
        $signalementModel = new Signalement();

        $enAttente = $bienModel->compterParStatutModeration('en_attente');
        $approuvees = $bienModel->compterParStatutModeration('approuve');
        $rejetees = $bienModel->compterParStatutModeration('rejete');
        $totalUtilisateurs = $utilisateurModel->compterTous();
        $signalementsEnAttente = $signalementModel->compterEnAttente();

        $annoncesEnAttente = $bienModel->listerParStatutModeration('en_attente');

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * approuverAjax()
     * L'annonce devient visible en recherche publique et sur le profil
     * du propriétaire. On notifie immédiatement le propriétaire.
     */
    public function approuverAjax(int $id): void
    {
        $this->traiterDecision($id, 'approuve', 'a été validée et est maintenant visible publiquement.', 'annonce_validee');
    }

    /**
     * rejeterAjax()
     * L'annonce reste invisible du public — seul le propriétaire (et
     * l'admin) peut encore la consulter, avec le motif si fourni.
     */
    public function rejeterAjax(int $id): void
    {
        $this->traiterDecision($id, 'rejete', 'a été rejetée par notre équipe de modération.', 'annonce_rejetee');
    }

    private function traiterDecision(int $id, string $statut, string $messageNotification, string $typeNotification): void
    {
        exigerRoleAjax('admin');
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        $bien = $bienModel->trouverParId($id);
        if (!$bien) {
            repondreJson(['succes' => false, 'erreur' => 'Annonce introuvable.'], 404);
        }

        $bienModel->changerStatutModeration($id, $statut);

        (new Notification())->creer(
            (int) $bien['proprietaire_id'],
            $typeNotification,
            'Votre annonce "' . $bien['titre'] . '" ' . $messageNotification,
            url('biens/detail', [$id])
        );

        // Alertes de recherche sauvegardée : uniquement à l'approbation
        // (pas au rejet, ça n'intéresse évidemment que le propriétaire).
        if ($statut === 'approuve') {
            require_once __DIR__ . '/../models/RechercheSauvegardee.php';
            $notificationModel = new Notification();
            foreach ((new RechercheSauvegardee())->trouverCorrespondantes($bien) as $alerte) {
                // Un propriétaire ne se notifie pas lui-même si jamais il
                // avait sauvegardé une recherche correspondant à son propre bien.
                if ((int) $alerte['utilisateur_id'] === (int) $bien['proprietaire_id']) {
                    continue;
                }
                $notificationModel->creer(
                    (int) $alerte['utilisateur_id'],
                    'alerte',
                    'Nouvelle annonce correspondant à votre alerte "' . $alerte['nom_recherche'] . '" : ' . $bien['titre'],
                    url('biens/detail', [$id])
                );
            }
        }

        repondreJson(['succes' => true, 'statut' => $statut]);
    }

    // ==================== SIGNALEMENTS ====================

    public function signalements(): void
    {
        exigerRole('admin');

        $titrePage = 'Signalements';
        $signalementModel = new Signalement();
        $signalementsEnAttente = $signalementModel->listerEnAttente();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/signalements.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * traiterSignalementAjax()
     * 'traite' = l'équipe a agi (ex: l'annonce liée a été rejetée à part).
     * 'rejete' = le signalement lui-même a été jugé infondé.
     * Dans les deux cas, il disparaît de la file d'attente.
     */
    public function traiterSignalementAjax(int $id): void
    {
        exigerRoleAjax('admin');
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, ['traite', 'rejete'], true)) {
            repondreJson(['succes' => false, 'erreur' => 'Statut invalide.'], 422);
        }

        $signalementModel = new Signalement();
        if (!$signalementModel->trouverParId($id)) {
            repondreJson(['succes' => false, 'erreur' => 'Signalement introuvable.'], 404);
        }

        $signalementModel->changerStatut($id, $statut);
        repondreJson(['succes' => true]);
    }

    // ==================== UTILISATEURS ====================

    public function utilisateurs(): void
    {
        exigerRole('admin');

        $titrePage = 'Utilisateurs';
        $utilisateurModel = new Utilisateur();
        $utilisateurs = $utilisateurModel->listerTous();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/utilisateurs.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    // ==================== JOURNAL DE SÉCURITÉ ====================

    /**
     * journalAcces()
     * Affiche les tentatives d'accès refusées à l'espace admin,
     * journalisées par exigerRole()/exigerRoleAjax().
     */
    public function journalAcces(): void
    {
        exigerRole('admin');

        $titrePage = 'Journal des accès refusés';
        $accesRefuses = (new AccesAdminRefuse())->listerRecents(100);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/journal-acces.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }


    /**
     * changerStatutUtilisateurAjax()
     * Suspend ou réactive un compte. On empêche explicitement qu'un
     * admin se suspende lui-même par erreur — ça bloquerait l'accès à
     * cet écran sans qu'aucun autre admin ne puisse le rétablir ici.
     */
    public function changerStatutUtilisateurAjax(int $id): void
    {
        exigerRoleAjax('admin');
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        if ($id === (int) $_SESSION['utilisateur_id']) {
            repondreJson(['succes' => false, 'erreur' => 'Vous ne pouvez pas modifier votre propre compte.'], 422);
        }

        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, ['actif', 'suspendu'], true)) {
            repondreJson(['succes' => false, 'erreur' => 'Statut invalide.'], 422);
        }

        $utilisateurModel = new Utilisateur();
        if (!$utilisateurModel->trouverParId($id)) {
            repondreJson(['succes' => false, 'erreur' => 'Utilisateur introuvable.'], 404);
        }

        $utilisateurModel->changerStatut($id, $statut);
        repondreJson(['succes' => true, 'statut' => $statut]);
    }

    // ==================== SAUVEGARDE ====================

    /**
     * exporterSauvegarde()
     * Génère un export SQL complet de toutes les tables, téléchargeable
     * directement — utile sur un hébergement mutualisé gratuit qui ne
     * permet ni tâche planifiée (cron) ni accès shell pour lancer un
     * mysqldump classique. Réservé aux admins, exigé en POST + CSRF
     * puisque ça expose l'intégralité du contenu de la base (y compris
     * les mots de passe hachés).
     */
    public function exporterSauvegarde(): void
    {
        exigerRole('admin');
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $pdo = getConnexion();
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $sortie = "-- Sauvegarde ImmoApp — générée le " . date('Y-m-d H:i:s') . "\n\n";
        $sortie .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Structure de la table
            $creation = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $sortie .= "DROP TABLE IF EXISTS `$table`;\n" . $creation['Create Table'] . ";\n\n";

            // Contenu de la table, ligne par ligne
            $lignes = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lignes as $ligne) {
                $colonnes = array_map(fn($c) => "`$c`", array_keys($ligne));
                $valeurs = array_map(function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, array_values($ligne));
                $sortie .= "INSERT INTO `$table` (" . implode(', ', $colonnes) . ") VALUES (" . implode(', ', $valeurs) . ");\n";
            }
            $sortie .= "\n";
        }

        $sortie .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $nomFichier = 'sauvegarde-immoapp-' . date('Y-m-d_His') . '.sql';
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
        header('Content-Length: ' . strlen($sortie));
        echo $sortie;
        exit;
    }
}

