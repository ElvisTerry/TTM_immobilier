<?php
/**
 * models/Disponibilite.php
 * ----------------------------
 * On stocke uniquement les périodes BLOQUÉES (voir Jour 1) : toute date
 * qui n'apparaît dans aucune période est considérée disponible par défaut.
 */
class Disponibilite
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function ajouter(int $bienId, string $dateDebut, string $dateFin, string $motif): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO disponibilites (bien_id, date_debut, date_fin, motif) VALUES (?, ?, ?, ?)'
        );
        $requete->execute([$bienId, $dateDebut, $dateFin, $motif]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listerPourBien(int $bienId): array
    {
        $requete = $this->pdo->prepare('SELECT * FROM disponibilites WHERE bien_id = ? ORDER BY date_debut');
        $requete->execute([$bienId]);
        return $requete->fetchAll();
    }

    public function appartientAuBien(int $id, int $bienId): bool
    {
        $requete = $this->pdo->prepare('SELECT id FROM disponibilites WHERE id = ? AND bien_id = ?');
        $requete->execute([$id, $bienId]);
        return $requete->fetch() !== false;
    }

    public function supprimer(int $id): void
    {
        $requete = $this->pdo->prepare('DELETE FROM disponibilites WHERE id = ?');
        $requete->execute([$id]);
    }

    /**
     * dateEstBloquee()
     * Vérifie qu'une date précise ne tombe dans AUCUNE période bloquée
     * du bien — appelée côté serveur au moment de la réservation, jamais
     * uniquement côté JavaScript (qui peut toujours être contourné).
     */
    public function dateEstBloquee(int $bienId, string $date): bool
    {
        $requete = $this->pdo->prepare(
            'SELECT id FROM disponibilites WHERE bien_id = ? AND ? BETWEEN date_debut AND date_fin'
        );
        $requete->execute([$bienId, $date]);
        return $requete->fetch() !== false;
    }
    /**
     * chevauche()
     * Vérifie si la période [dateDebut, dateFin] chevauche une période
     * DÉJÀ enregistrée pour ce bien. Deux périodes se chevauchent dès
     * que l'une commence avant que l'autre ne finisse, dans les deux
     * sens — la condition classique pour un test d'intervalles.
     */
    public function chevauche(int $bienId, string $dateDebut, string $dateFin): bool
    {
        $requete = $this->pdo->prepare(
            'SELECT id FROM disponibilites
             WHERE bien_id = ? AND date_debut <= ? AND date_fin >= ?
             LIMIT 1'
        );
        $requete->execute([$bienId, $dateFin, $dateDebut]);
        return $requete->fetch() !== false;
    }

}
