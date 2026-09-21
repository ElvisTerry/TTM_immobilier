<?php
/**
 * models/ReservationVisite.php
 * ---------------------------------
 */
class ReservationVisite
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function creer(int $bienId, int $locataireId, string $dateVisite, string $heureVisite, string $message): int
    {
        $requete = $this->pdo->prepare(
            "INSERT INTO reservations_visites (bien_id, locataire_id, date_visite, heure_visite, message, statut, date_creation)
             VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())"
        );
        $requete->execute([$bienId, $locataireId, $dateVisite, $heureVisite, $message]);
        return (int) $this->pdo->lastInsertId();
    }

    public function trouverParId(int $id): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT r.*, b.titre AS bien_titre, b.proprietaire_id
             FROM reservations_visites r
             INNER JOIN biens b ON b.id = r.bien_id
             WHERE r.id = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch() ?: null;
    }

    public function listerPourLocataire(int $locataireId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT r.*, b.titre AS bien_titre, b.id AS bien_id
             FROM reservations_visites r
             INNER JOIN biens b ON b.id = r.bien_id
             WHERE r.locataire_id = ?
             ORDER BY r.date_visite DESC, r.heure_visite DESC'
        );
        $requete->execute([$locataireId]);
        return $requete->fetchAll();
    }

    public function listerPourProprietaire(int $proprietaireId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT r.*, b.titre AS bien_titre, u.nom AS locataire_nom, u.telephone AS locataire_telephone
             FROM reservations_visites r
             INNER JOIN biens b ON b.id = r.bien_id
             INNER JOIN utilisateurs u ON u.id = r.locataire_id
             WHERE b.proprietaire_id = ?
             ORDER BY r.date_visite DESC, r.heure_visite DESC'
        );
        $requete->execute([$proprietaireId]);
        return $requete->fetchAll();
    }

    /**
     * appartientAuProprietaire()
     * Vérification anti-IDOR : le propriétaire qui tente d'accepter ou
     * refuser une demande est-il bien celui du BIEN concerné par cette
     * réservation ? Indispensable avant tout changement de statut.
     */
    public function appartientAuProprietaire(int $reservationId, int $proprietaireId): bool
    {
        $requete = $this->pdo->prepare(
            'SELECT r.id FROM reservations_visites r
             INNER JOIN biens b ON b.id = r.bien_id
             WHERE r.id = ? AND b.proprietaire_id = ?'
        );
        $requete->execute([$reservationId, $proprietaireId]);
        return $requete->fetch() !== false;
    }

    public function changerStatut(int $id, string $statut): void
    {
        $requete = $this->pdo->prepare('UPDATE reservations_visites SET statut = ? WHERE id = ?');
        $requete->execute([$statut, $id]);
    }

    /**
     * dejaDemandee()
     * Empêche un même locataire de demander deux fois une visite pour
     * le même bien à la même date tant qu'une demande n'a pas été refusée.
     */
    public function dejaDemandee(int $bienId, int $locataireId, string $dateVisite): bool
    {
        $requete = $this->pdo->prepare(
            "SELECT id FROM reservations_visites
             WHERE bien_id = ? AND locataire_id = ? AND date_visite = ? AND statut != 'refusee'"
        );
        $requete->execute([$bienId, $locataireId, $dateVisite]);
        return $requete->fetch() !== false;
    }

    /**
     * visiteAccepteePour()
     * Un avis ne peut être laissé qu'après une visite ACCEPTÉE par le
     * propriétaire — comme annoncé dans le cahier des charges ("avis
     * après une visite ou une location"). Ça évite les faux avis
     * postés par n'importe qui sans lien réel avec le bien.
     */
    public function visiteAccepteePour(int $bienId, int $locataireId): bool
    {
        $requete = $this->pdo->prepare(
            "SELECT id FROM reservations_visites WHERE bien_id = ? AND locataire_id = ? AND statut = 'acceptee'"
        );
        $requete->execute([$bienId, $locataireId]);
        return $requete->fetch() !== false;
    }
    /**
     * annulerToutesPourLocataire()
     * Utilisée lors de la suppression de compte : les demandes de
     * visite encore actives (en_attente/acceptee) sont annulées
     * automatiquement, puisque le locataire ne pourra plus se
     * connecter pour le faire lui-même après suppression.
     */
    public function annulerToutesPourLocataire(int $locataireId): void
    {
        $requete = $this->pdo->prepare(
            "UPDATE reservations_visites SET statut = 'annulee'
             WHERE locataire_id = ? AND statut IN ('en_attente', 'acceptee')"
        );
        $requete->execute([$locataireId]);
    }
    /**
     * definirDisponibiliteAssociee()
     * Enregistre quelle période de disponibilité a été créée
     * automatiquement pour bloquer la date de CETTE visite acceptée —
     * permet de la retirer proprement si la visite est ensuite annulée
     * ou refusée.
     */
    public function definirDisponibiliteAssociee(int $id, ?int $disponibiliteId): void
    {
        $requete = $this->pdo->prepare('UPDATE reservations_visites SET disponibilite_id = ? WHERE id = ?');
        $requete->execute([$disponibiliteId, $id]);
    }

    /**
     * autresEnAttentePourDate()
     * Les autres demandes encore en attente, pour ce même bien et cette
     * même date, envoyées par d'AUTRES locataires — à refuser
     * automatiquement dès qu'une visite est acceptée sur ce créneau.
     */
    public function autresEnAttentePourDate(int $bienId, string $dateVisite, int $idExclu): array
    {
        $requete = $this->pdo->prepare(
            "SELECT r.*, b.titre AS bien_titre
             FROM reservations_visites r
             INNER JOIN biens b ON b.id = r.bien_id
             WHERE r.bien_id = ? AND r.date_visite = ? AND r.statut = 'en_attente' AND r.id != ?"
        );
        $requete->execute([$bienId, $dateVisite, $idExclu]);
        return $requete->fetchAll();
    }

}
