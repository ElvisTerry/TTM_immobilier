<?php
/**
 * models/Message.php
 * ---------------------
 * Une conversation est définie par la paire (bien, autre utilisateur) —
 * chaque échange reste rattaché à une annonce précise, comme sur Airbnb.
 */
class Message
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function envoyer(int $bienId, int $expediteurId, int $destinataireId, string $contenu): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO messages (bien_id, expediteur_id, destinataire_id, contenu, date_envoi, lu)
             VALUES (?, ?, ?, ?, NOW(), 0)'
        );
        $requete->execute([$bienId, $expediteurId, $destinataireId, $contenu]);
        return (int) $this->pdo->lastInsertId();
    }

    public function conversation(int $bienId, int $utilisateurId, int $autreId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT * FROM messages
             WHERE bien_id = ?
               AND ((expediteur_id = ? AND destinataire_id = ?) OR (expediteur_id = ? AND destinataire_id = ?))
             ORDER BY date_envoi ASC'
        );
        $requete->execute([$bienId, $utilisateurId, $autreId, $autreId, $utilisateurId]);
        return $requete->fetchAll();
    }

    /**
     * messagesDepuis()
     * Utilisée par le polling AJAX de la conversation : ne renvoie que
     * les messages postérieurs à un id donné, pour ne pas retélécharger
     * tout l'historique à chaque rafraîchissement automatique.
     */
    public function messagesDepuis(int $bienId, int $utilisateurId, int $autreId, int $depuisId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT * FROM messages
             WHERE bien_id = ? AND id > ?
               AND ((expediteur_id = ? AND destinataire_id = ?) OR (expediteur_id = ? AND destinataire_id = ?))
             ORDER BY date_envoi ASC'
        );
        $requete->execute([$bienId, $depuisId, $utilisateurId, $autreId, $autreId, $utilisateurId]);
        return $requete->fetchAll();
    }

    public function marquerCommeLus(int $bienId, int $utilisateurId, int $autreId): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE messages SET lu = 1 WHERE bien_id = ? AND expediteur_id = ? AND destinataire_id = ?'
        );
        $requete->execute([$bienId, $autreId, $utilisateurId]);
    }

    public function listerConversations(int $utilisateurId, int $limite = 20, int $decalage = 0): array
    {
        $limite = max(1, min(50, $limite));
        $decalage = max(0, $decalage);
        $requete = $this->pdo->prepare(
            "SELECT m.bien_id, b.titre AS bien_titre,
                    CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END AS autre_id,
                    u.nom AS autre_nom,
                    MAX(m.date_envoi) AS dernier_message_date,
                    SUM(CASE WHEN m.destinataire_id = ? AND m.lu = 0 THEN 1 ELSE 0 END) AS non_lus
             FROM messages m
             INNER JOIN biens b ON b.id = m.bien_id
             INNER JOIN utilisateurs u ON u.id = CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END
             WHERE m.expediteur_id = ? OR m.destinataire_id = ?
             GROUP BY m.bien_id, autre_id, b.titre, u.nom
             ORDER BY dernier_message_date DESC
             LIMIT $limite OFFSET $decalage"
        );
        $requete->execute([$utilisateurId, $utilisateurId, $utilisateurId, $utilisateurId, $utilisateurId]);
        return $requete->fetchAll();
    }

    public function compterNonLus(int $utilisateurId): int
    {
        $requete = $this->pdo->prepare('SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND lu = 0');
        $requete->execute([$utilisateurId]);
        return (int) $requete->fetchColumn();
    }
    /**
     * tempsReponseMoyenMinutes()
     * Calcule le délai moyen (en minutes) entre un message REÇU par ce
     * propriétaire et sa PROCHAINE réponse dans le même fil (même
     * annonce, même interlocuteur). N'affiche un résultat que s'il y a
     * au moins $minimumEchanges réponses mesurées — sinon une seule
     * réponse rapide isolée donnerait une fausse image de réactivité.
     *
     * Volontairement limité aux 6 derniers mois : le temps de réponse
     * doit refléter le comportement RÉCENT du propriétaire, pas une
     * moyenne figée depuis la création du compte.
     */
    public function tempsReponseMoyenMinutes(int $proprietaireId, int $minimumEchanges = 3): ?float
    {
        $requete = $this->pdo->prepare(
            'SELECT AVG(delai_minutes) AS moyenne, COUNT(*) AS nombre
             FROM (
                 SELECT TIMESTAMPDIFF(MINUTE, m1.date_envoi,
                     (SELECT MIN(m2.date_envoi) FROM messages m2
                      WHERE m2.bien_id = m1.bien_id
                        AND m2.expediteur_id = ?
                        AND m2.destinataire_id = m1.expediteur_id
                        AND m2.date_envoi > m1.date_envoi)
                 ) AS delai_minutes
                 FROM messages m1
                 WHERE m1.destinataire_id = ?
                   AND m1.date_envoi >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             ) AS sous_requete
             WHERE delai_minutes IS NOT NULL'
        );
        $requete->execute([$proprietaireId, $proprietaireId]);
        $resultat = $requete->fetch();

        if (!$resultat || (int) $resultat['nombre'] < $minimumEchanges) {
            return null;
        }

        return (float) $resultat['moyenne'];
    }

    /**
     * libelleTempsReponse()
     * Traduit un délai en minutes en texte lisible, façon Airbnb —
     * plus parlant qu'un nombre brut de minutes pour un visiteur.
     */
    public static function libelleTempsReponse(float $minutes): string
    {
        if ($minutes < 60) {
            return 'Répond généralement en moins d\'une heure';
        }
        if ($minutes < 4 * 60) {
            return 'Répond généralement en quelques heures';
        }
        if ($minutes < 24 * 60) {
            return 'Répond généralement en moins d\'une journée';
        }
        if ($minutes < 3 * 24 * 60) {
            return 'Répond généralement en quelques jours';
        }
        return 'Répond généralement en plus de 3 jours';
    }

}
