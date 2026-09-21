<?php
/**
 * models/Notification.php
 * ---------------------------
 */
class Notification
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    /**
     * creer()
     * $lien est stocké déjà construit via url() par l'appelant (jamais
     * reconstruit ici) — cohérent avec le reste du projet où url() est
     * la SEULE source de vérité pour les chemins.
     */
    public function creer(int $utilisateurId, string $type, string $contenu, ?string $lien = null): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO notifications (utilisateur_id, type, contenu, lien, lu, date_creation)
             VALUES (?, ?, ?, ?, 0, NOW())'
        );
        $requete->execute([$utilisateurId, $type, $contenu, $lien]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listerRecentes(int $utilisateurId, int $limite = 15, int $decalage = 0): array
    {
        $limite = max(1, min(50, $limite));
        $decalage = max(0, $decalage);
        $requete = $this->pdo->prepare(
            'SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_creation DESC LIMIT ' . $limite . ' OFFSET ' . $decalage
        );
        $requete->execute([$utilisateurId]);
        return $requete->fetchAll();
    }

    public function compterNonLues(int $utilisateurId): int
    {
        $requete = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lu = 0');
        $requete->execute([$utilisateurId]);
        return (int) $requete->fetchColumn();
    }

    public function marquerToutesLues(int $utilisateurId): void
    {
        $requete = $this->pdo->prepare('UPDATE notifications SET lu = 1 WHERE utilisateur_id = ? AND lu = 0');
        $requete->execute([$utilisateurId]);
    }
    /**
     * supprimerPourUtilisateur()
     * Utilisée lors de la suppression de compte (RGPD).
     */
    public function supprimerPourUtilisateur(int $utilisateurId): void
    {
        $requete = $this->pdo->prepare('DELETE FROM recherches_sauvegardees WHERE utilisateur_id = ?');
        $requete->execute([$utilisateurId]);
    }

}


