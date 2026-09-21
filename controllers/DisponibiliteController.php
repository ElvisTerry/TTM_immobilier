<?php
/**
 * controllers/DisponibiliteController.php
 * ---------------------------------------------
 */
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Disponibilite.php';

class DisponibiliteController
{
    /**
     * verifierProprietaire()
     * Utilisée par toutes les méthodes de ce controller : seul le
     * propriétaire du BIEN peut gérer son calendrier de disponibilité.
     */
    private function verifierProprietaire(int $bienId): array
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $bienModel = new Bien();
        $bien = $bienModel->trouverParId($bienId);

        if (!$bien || !$bienModel->appartientA($bienId, (int) $_SESSION['utilisateur_id'])) {
            http_response_code(403);
            die("Vous n'êtes pas autorisé à gérer ce bien.");
        }

        return $bien;
    }

    public function gerer(int $bienId): void
    {
        $bien = $this->verifierProprietaire($bienId);
        $titrePage = 'Disponibilités - ' . $bien['titre'];

        $dispoModel = new Disponibilite();
        $periodes = $dispoModel->listerPourBien($bienId);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/disponibilites/gerer.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * ajouterAjax()
     * Bloque une période (ex: le bien est déjà loué ces dates-là, ou le
     * propriétaire s'absente). Répond en JSON pour une mise à jour
     * instantanée du calendrier affiché, sans rechargement de page.
     */
    public function ajouterAjax(int $bienId): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        if (!$bienModel->appartientA($bienId, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        $motif = trim($_POST['motif'] ?? '') ?: 'Indisponible';

        // Validation stricte du format de date (évite qu'une chaîne
        // malformée ne parte directement dans la requête SQL).
        $formatValide = fn(string $d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        if (!$formatValide($dateDebut) || !$formatValide($dateFin) || $dateFin < $dateDebut) {
            repondreJson(['succes' => false, 'erreur' => 'Dates invalides.'], 422);
        }

        $dispoModel = new Disponibilite();

        if ($dispoModel->chevauche($bienId, $dateDebut, $dateFin)) {
            repondreJson(['succes' => false, 'erreur' => 'Cette période chevauche une période déjà bloquée.'], 422);
        }

        $id = $dispoModel->ajouter($bienId, $dateDebut, $dateFin, $motif);



        repondreJson(['succes' => true, 'id' => $id, 'dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'motif' => $motif]);
    }

    public function supprimerAjax(int $bienId): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        if (!$bienModel->appartientA($bienId, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $dispoModel = new Disponibilite();
        $idPeriode = (int) ($_POST['id'] ?? 0);

        if (!$dispoModel->appartientAuBien($idPeriode, $bienId)) {
            repondreJson(['succes' => false, 'erreur' => 'Période introuvable.'], 404);
        }

        $dispoModel->supprimer($idPeriode);
        repondreJson(['succes' => true]);
    }
}
