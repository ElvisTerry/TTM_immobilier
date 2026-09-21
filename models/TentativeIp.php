<?php
/**
 * models/TentativeIp.php
 * -------------------------
 * Anti-brute-force PAR ADRESSE IP, en complément du blocage par compte
 * déjà en place depuis le Jour 3 — celui-ci ne protégeait pas contre un
 * attaquant qui tenterait chaque compte seulement quelques fois chacun,
 * sans jamais déclencher le verrou individuel (limite assumée depuis
 * l'audit du Jour 11).
 */
class TentativeIp
{
    private PDO $pdo;
    private const SEUIL = 20; // volontairement plus haut que le seuil par compte (5) :
    // une IP peut légitimement représenter plusieurs vrais utilisateurs
    // (réseau d'entreprise, cybercafé...), pas seulement un attaquant.
    private const DUREE_BLOCAGE_MINUTES = 15;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function estBloquee(string $ip): bool
    {
        $requete = $this->pdo->prepare('SELECT bloque_jusqu_a FROM tentatives_ip WHERE ip = ?');
        $requete->execute([$ip]);
        $bloqueJusqua = $requete->fetchColumn();
        return $bloqueJusqua && strtotime($bloqueJusqua) > time();
    }

    public function enregistrerEchec(string $ip): void
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO tentatives_ip (ip, tentatives, derniere_tentative)
             VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE tentatives = tentatives + 1, derniere_tentative = NOW()'
        );
        $requete->execute([$ip]);

        $requete = $this->pdo->prepare('SELECT tentatives FROM tentatives_ip WHERE ip = ?');
        $requete->execute([$ip]);

        if ((int) $requete->fetchColumn() >= self::SEUIL) {
            $requete = $this->pdo->prepare(
                'UPDATE tentatives_ip SET bloque_jusqu_a = DATE_ADD(NOW(), INTERVAL ' . self::DUREE_BLOCAGE_MINUTES . ' MINUTE) WHERE ip = ?'
            );
            $requete->execute([$ip]);
        }
    }

    public function reinitialiser(string $ip): void
    {
        $requete = $this->pdo->prepare('DELETE FROM tentatives_ip WHERE ip = ?');
        $requete->execute([$ip]);
    }
}
 
