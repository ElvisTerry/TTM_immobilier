<?php
/**
 * models/Signalement.php
 * ---------------------------
 */
class Signalement
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function creer(int $bienId, int $auteurId, string $motif, string $description): int
    {
        $requete = $this->pdo->prepare(
            "INSERT INTO signalements (bien_id, auteur_id, motif, description, statut, date_signalement)
             VALUES (?, ?, ?, ?, 'en_attente', NOW())"
        );
        $requete->execute([$bienId, $auteurId, $motif, $description]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listerEnAttente(): array
    {
        $requete = $this->pdo->prepare(
            "SELECT s.*, b.titre AS bien_titre, u.nom AS auteur_nom
             FROM signalements s
             INNER JOIN biens b ON b.id = s.bien_id
             INNER JOIN utilisateurs u ON u.id = s.auteur_id
             WHERE s.statut = 'en_attente'
             ORDER BY s.date_signalement ASC"
        );
        $requete->execute();
        return $requete->fetchAll();
    }

    public function trouverParId(int $id): ?array
    {
        $requete = $this->pdo->prepare('SELECT * FROM signalements WHERE id = ?');
        $requete->execute([$id]);
        return $requete->fetch() ?: null;
    }

    public function changerStatut(int $id, string $statut): void
    {
        $requete = $this->pdo->prepare('UPDATE signalements SET statut = ? WHERE id = ?');
        $requete->execute([$statut, $id]);
    }

    public function compterEnAttente(): int
    {
        $requete = $this->pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'en_attente'");
        return (int) $requete->fetchColumn();
    }
}
