<?php
/**
 * models/AccesAdminRefuse.php
 * ---------------------------------
 * Journal des tentatives d'accès à une page réservée aux admins par
 * quelqu'un qui n'en a pas le droit — utile pour repérer une activité
 * suspecte (quelqu'un qui teste systématiquement les URLs admin).
 */
class AccesAdminRefuse
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function enregistrer(?int $utilisateurId, string $ip, string $route): void
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO acces_admin_refuses (utilisateur_id, ip, route, date_tentative) VALUES (?, ?, ?, NOW())'
        );
        $requete->execute([$utilisateurId, $ip, $route]);
    }

    public function listerRecents(int $limite = 50): array
    {
        $limite = max(1, min(200, $limite));
        $requete = $this->pdo->query(
            'SELECT a.*, u.nom AS utilisateur_nom
             FROM acces_admin_refuses a
             LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
             ORDER BY a.date_tentative DESC
             LIMIT ' . $limite
        );
        return $requete->fetchAll();
    }
}
 
