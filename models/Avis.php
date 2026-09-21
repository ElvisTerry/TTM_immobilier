<?php
/**
 * models/Avis.php
 * ------------------
 */
class Avis
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function creer(int $bienId, int $auteurId, int $note, string $commentaire): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO avis (bien_id, auteur_id, note, commentaire, date_avis) VALUES (?, ?, ?, ?, NOW())'
        );
        $requete->execute([$bienId, $auteurId, $note, $commentaire]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * aDejaNote()
     * La contrainte UNIQUE(bien_id, auteur_id) en base empêcherait de
     * toute façon un doublon, mais on vérifie ici en amont pour pouvoir
     * afficher un message clair plutôt qu'une erreur SQL brute.
     */
    public function aDejaNote(int $bienId, int $auteurId): bool
    {
        $requete = $this->pdo->prepare('SELECT id FROM avis WHERE bien_id = ? AND auteur_id = ?');
        $requete->execute([$bienId, $auteurId]);
        return $requete->fetch() !== false;
    }

    public function listerPourBien(int $bienId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT a.*, u.nom AS auteur_nom, u.photo_profil AS auteur_photo
             FROM avis a
             INNER JOIN utilisateurs u ON u.id = a.auteur_id
             WHERE a.bien_id = ?
             ORDER BY a.date_avis DESC'
        );
        $requete->execute([$bienId]);
        return $requete->fetchAll();
    }

    public function statistiquesPourBien(int $bienId): array
    {
        $requete = $this->pdo->prepare('SELECT COUNT(*) AS total, AVG(note) AS moyenne FROM avis WHERE bien_id = ?');
        $requete->execute([$bienId]);
        $resultat = $requete->fetch();

        return [
            'total' => (int) $resultat['total'],
            'moyenne' => $resultat['moyenne'] !== null ? round((float) $resultat['moyenne'], 1) : null,
        ];
    }

    /**
     * moyennePourProprietaire()
     * Moyenne calculée sur TOUS les biens du propriétaire — affichée sur
     * son profil public, comme la note globale d'un hôte sur Airbnb.
     */
    public function moyennePourProprietaire(int $proprietaireId): ?float
    {
        $requete = $this->pdo->prepare(
            'SELECT AVG(a.note) AS moyenne
             FROM avis a INNER JOIN biens b ON b.id = a.bien_id
             WHERE b.proprietaire_id = ?'
        );
        $requete->execute([$proprietaireId]);
        $moyenne = $requete->fetchColumn();
        return $moyenne !== null ? round((float) $moyenne, 1) : null;
    }

    /**
     * appartientAuProprietaire()
     * Anti-IDOR : seul le propriétaire du BIEN concerné par l'avis peut y
     * répondre — jamais un autre propriétaire en devinant un id d'avis.
     */
    /**
     * recentsPourProprietaire()
     * Les derniers avis reçus, tous biens confondus — utilisée par le
     * tableau de bord (Jour 14) pour une vue d'ensemble rapide.
     */
    public function recentsPourProprietaire(int $proprietaireId, int $limite = 5): array
    {
        $limite = max(1, min(20, $limite));
        $requete = $this->pdo->prepare(
            'SELECT a.*, b.titre AS bien_titre, u.nom AS auteur_nom
             FROM avis a
             INNER JOIN biens b ON b.id = a.bien_id
             INNER JOIN utilisateurs u ON u.id = a.auteur_id
             WHERE b.proprietaire_id = ?
             ORDER BY a.date_avis DESC
             LIMIT ' . $limite
        );
        $requete->execute([$proprietaireId]);
        return $requete->fetchAll();
    }

    public function appartientAuProprietaire(int $avisId, int $proprietaireId): bool
    {
        $requete = $this->pdo->prepare(
            'SELECT a.id FROM avis a INNER JOIN biens b ON b.id = a.bien_id
             WHERE a.id = ? AND b.proprietaire_id = ?'
        );
        $requete->execute([$avisId, $proprietaireId]);
        return $requete->fetch() !== false;
    }

    public function repondre(int $id, string $reponse): void
    {
        $requete = $this->pdo->prepare('UPDATE avis SET reponse_proprietaire = ?, date_reponse = NOW() WHERE id = ?');
        $requete->execute([$reponse, $id]);
    }

    public function compterTous(): int
    {
        $requete = $this->pdo->query('SELECT COUNT(*) FROM avis');
        return (int) $requete->fetchColumn();
    }
}
