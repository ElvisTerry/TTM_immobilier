<?php
/**
 * models/RechercheSauvegardee.php
 * -------------------------------------
 */
class RechercheSauvegardee
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function creer(int $utilisateurId, string $nom, array $filtres): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO recherches_sauvegardees
                (utilisateur_id, nom_recherche, ville, quartier, type_bien, type_transaction, prix_min, prix_max, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $requete->execute([
            $utilisateurId, $nom,
            $filtres['ville'] ?: null, $filtres['quartier'] ?: null,
            $filtres['type_bien'] ?: null, $filtres['type_transaction'] ?: null,
            $filtres['prix_min'] ?: null, $filtres['prix_max'] ?: null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listerPourUtilisateur(int $utilisateurId): array
    {
        $requete = $this->pdo->prepare('SELECT * FROM recherches_sauvegardees WHERE utilisateur_id = ? ORDER BY date_creation DESC');
        $requete->execute([$utilisateurId]);
        return $requete->fetchAll();
    }

    public function appartientA(int $id, int $utilisateurId): bool
    {
        $requete = $this->pdo->prepare('SELECT id FROM recherches_sauvegardees WHERE id = ? AND utilisateur_id = ?');
        $requete->execute([$id, $utilisateurId]);
        return $requete->fetch() !== false;
    }

    public function supprimer(int $id): void
    {
        $requete = $this->pdo->prepare('DELETE FROM recherches_sauvegardees WHERE id = ?');
        $requete->execute([$id]);
    }

    /**
     * trouverCorrespondantes()
     * Renvoie toutes les recherches sauvegardées dont les critères
     * correspondent au bien donné — utilisée quand une annonce vient
     * d'être approuvée, pour savoir qui alerter.
     *
     * Chaque critère n'est appliqué QUE s'il a été renseigné dans la
     * recherche sauvegardée (valeur NULL = "peu importe" pour ce critère).
     */
    public function trouverCorrespondantes(array $bien): array
    {
        $requete = $this->pdo->prepare(
            'SELECT * FROM recherches_sauvegardees
             WHERE (ville IS NULL OR ville = ?)
               AND (quartier IS NULL OR quartier = ?)
               AND (type_bien IS NULL OR type_bien = ?)
               AND (type_transaction IS NULL OR type_transaction = ?)
               AND (prix_min IS NULL OR ? >= prix_min)
               AND (prix_max IS NULL OR ? <= prix_max)'
        );
        $requete->execute([
            $bien['ville'], $bien['quartier'], $bien['type_bien'], $bien['type_transaction'],
            $bien['prix'], $bien['prix'],
        ]);
        return $requete->fetchAll();
    }
    /**
     * supprimerPourUtilisateur()
     * Retire tous les favoris d'un compte — utilisée lors de la
     * suppression de compte (RGPD).
     */
    public function supprimerPourUtilisateur(int $utilisateurId): void
    {
        $requete = $this->pdo->prepare('DELETE FROM favoris WHERE utilisateur_id = ?');
        $requete->execute([$utilisateurId]);
    }
}
 
