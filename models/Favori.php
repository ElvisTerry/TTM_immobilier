<?php
/**
 * models/Favori.php
 * --------------------
 */
class Favori
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function estFavori(int $utilisateurId, int $bienId): bool
    {
        $requete = $this->pdo->prepare('SELECT id FROM favoris WHERE utilisateur_id = ? AND bien_id = ?');
        $requete->execute([$utilisateurId, $bienId]);
        return $requete->fetch() !== false;
    }

    /**
     * basculer()
     * Ajoute le favori s'il n'existe pas, le retire sinon (comportement
     * "toggle" — un clic sur le cœur, comme sur Airbnb). Renvoie le
     * nouvel état pour que l'appelant sache comment mettre à jour l'icône.
     */
    public function basculer(int $utilisateurId, int $bienId): bool
    {
        if ($this->estFavori($utilisateurId, $bienId)) {
            $requete = $this->pdo->prepare('DELETE FROM favoris WHERE utilisateur_id = ? AND bien_id = ?');
            $requete->execute([$utilisateurId, $bienId]);
            return false;
        }

        $requete = $this->pdo->prepare('INSERT INTO favoris (utilisateur_id, bien_id, date_ajout) VALUES (?, ?, NOW())');
        $requete->execute([$utilisateurId, $bienId]);
        return true;
    }

    public function listerPourUtilisateur(int $utilisateurId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT b.id, b.titre, b.prix, b.ville, f.date_ajout,
                    (SELECT chemin_fichier FROM photos_biens p WHERE p.bien_id = b.id ORDER BY p.ordre_affichage LIMIT 1) AS photo_principale
             FROM favoris f
             INNER JOIN biens b ON b.id = f.bien_id
             WHERE f.utilisateur_id = ?
             ORDER BY f.date_ajout DESC'
        );
        $requete->execute([$utilisateurId]);
        return $requete->fetchAll();
    }
}
