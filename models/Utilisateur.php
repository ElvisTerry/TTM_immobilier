<?php
/**
 * models/Utilisateur.php
 * -------------------------
 */
class Utilisateur
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    public function emailExiste(string $email): bool
    {
        $requete = $this->pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $requete->execute([$email]);
        return $requete->fetch() !== false;
    }

    /**
     * creer()
     * Crée le compte avec email_verifie = 0 par défaut (valeur de la BDD).
     * Le token de vérification est fourni par le controller pour qu'il
     * puisse aussi servir à construire le lien envoyé par email.
     */
    public function creer(string $nom, string $email, string $motDePasseHache, string $role, string $telephone, string $tokenVerification): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO utilisateurs (nom, email, mot_de_passe, role, telephone, token_verification, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $requete->execute([$nom, $email, $motDePasseHache, $role, $telephone, $tokenVerification]);

        return (int) $this->pdo->lastInsertId();
    }

    public function trouverParEmail(string $email): ?array
    {
        $requete = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
        $requete->execute([$email]);
        return $requete->fetch() ?: null;
    }

    public function trouverParToken(string $token): ?array
    {
        $requete = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE token_verification = ?');
        $requete->execute([$token]);
        return $requete->fetch() ?: null;
    }

    /**
     * marquerEmailVerifie()
     * Le token est mis à NULL après usage : un lien de vérification ne
     * doit fonctionner qu'UNE seule fois, sinon il resterait valable
     * indéfiniment si quelqu'un d'autre mettait la main dessus.
     */
    public function marquerEmailVerifie(int $id): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE utilisateurs SET email_verifie = 1, token_verification = NULL WHERE id = ?'
        );
        $requete->execute([$id]);
    }

    public function enregistrerEchecConnexion(int $id): void
    {
        $requete = $this->pdo->prepare('UPDATE utilisateurs SET tentatives_connexion = tentatives_connexion + 1 WHERE id = ?');
        $requete->execute([$id]);

        $requete = $this->pdo->prepare('SELECT tentatives_connexion FROM utilisateurs WHERE id = ?');
        $requete->execute([$id]);
        $tentatives = (int) $requete->fetchColumn();

        if ($tentatives >= 5) {
            $requete = $this->pdo->prepare(
                'UPDATE utilisateurs SET verrouille_jusqu_a = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?'
            );
            $requete->execute([$id]);
        }
    }

    public function reinitialiserTentatives(int $id): void
    {
        $requete = $this->pdo->prepare('UPDATE utilisateurs SET tentatives_connexion = 0, verrouille_jusqu_a = NULL WHERE id = ?');
        $requete->execute([$id]);
    }

    public function estVerrouille(array $utilisateur): bool
    {
        return !empty($utilisateur['verrouille_jusqu_a']) && strtotime($utilisateur['verrouille_jusqu_a']) > time();
    }

    public function majDerniereConnexion(int $id): void
    {
        $requete = $this->pdo->prepare('UPDATE utilisateurs SET date_derniere_connexion = NOW() WHERE id = ?');
        $requete->execute([$id]);
    }

    /**
     * regenererTokenVerification()
     * Utilisé si l'utilisateur demande à renvoyer l'email de vérification.
     */
    public function regenererTokenVerification(int $id, string $nouveauToken): void
    {
        $requete = $this->pdo->prepare('UPDATE utilisateurs SET token_verification = ? WHERE id = ?');
        $requete->execute([$nouveauToken, $id]);
    }

    /**
     * trouverParId()
     * Utilisé pour la page de profil public et le profil "Mon compte".
     */
    public function trouverParId(int $id): ?array
    {
        $requete = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
        $requete->execute([$id]);
        return $requete->fetch() ?: null;
    }

    /**
     * mettreAJourProfil()
     * Modifie les informations modifiables du compte. On ne touche
     * JAMAIS ici à l'email, au mot de passe ou au rôle — ces champs
     * sensibles auront leurs propres écrans dédiés avec leurs propres
     * vérifications, jamais mélangés à un simple formulaire de profil.
     */
    public function mettreAJourProfil(int $id, string $nom, string $telephone, string $bio): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE utilisateurs SET nom = ?, telephone = ?, bio = ? WHERE id = ?'
        );
        $requete->execute([$nom, $telephone, $bio, $id]);
    }

    public function mettreAJourAvatar(int $id, string $cheminFichier): void
    {
        $requete = $this->pdo->prepare('UPDATE utilisateurs SET photo_profil = ? WHERE id = ?');
        $requete->execute([$cheminFichier, $id]);
    }

    public function compterTous(): int
    {
        $requete = $this->pdo->query('SELECT COUNT(*) FROM utilisateurs');
        return (int) $requete->fetchColumn();
    }

    /**
     * listerTous()
     * Pour l'espace admin (Jour 16). On exclut volontairement le champ
     * mot_de_passe de la sélection — même haché, un admin n'a aucune
     * raison légitime d'y accéder depuis cette liste.
     */
    public function listerTous(): array
    {
        $requete = $this->pdo->query(
            'SELECT id, nom, email, role, statut, date_creation, date_derniere_connexion
             FROM utilisateurs ORDER BY date_creation DESC'
        );
        return $requete->fetchAll();
    }

    /**
     * changerStatut()
     * Suspend ou réactive un compte. Un compte suspendu ne peut plus se
     * connecter (vérifié dans AuthController::traiterConnexion).
     */
    public function changerStatut(int $id, string $statut): void
    {
        $requete = $this->pdo->prepare('UPDATE utilisateurs SET statut = ? WHERE id = ?');
        $requete->execute([$statut, $id]);
    }

    /**
     * definirTokenReinitialisation()
     * Réutilise la colonne token_verification (libre depuis le retrait de
     * la vérification d'email) plutôt que d'en créer une nouvelle
     * redondante — voir jour22-migration.sql pour la colonne d'expiration.
     */
    public function definirTokenReinitialisation(int $id, string $token): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE utilisateurs SET token_verification = ?, token_expiration = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?'
        );
        $requete->execute([$token, $id]);
    }

    /**
     * trouverParTokenReinitialisationValide()
     * Ne renvoie l'utilisateur QUE si le jeton existe ET n'a pas expiré —
     * un lien de réinitialisation vieux de plus d'1h est traité comme
     * invalide, même si la chaîne de caractères correspond encore.
     */
    public function trouverParTokenReinitialisationValide(string $token): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT * FROM utilisateurs WHERE token_verification = ? AND token_expiration > NOW()'
        );
        $requete->execute([$token]);
        return $requete->fetch() ?: null;
    }

    public function reinitialiserMotDePasse(int $id, string $motDePasseHache): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE utilisateurs SET mot_de_passe = ?, token_verification = NULL, token_expiration = NULL WHERE id = ?'
        );
        $requete->execute([$motDePasseHache, $id]);
    }
    /**
     * Vérifie si l'email est déjà utilisé par un autre compte.
     */
    public function emailExistePourAutreUtilisateur(
        string $email,
        int $idUtilisateur
    ): bool {
        $requete = $this->pdo->prepare(
            'SELECT id
             FROM utilisateurs
             WHERE email = ?
             AND id <> ?
             LIMIT 1'
        );

        $requete->execute([
            $email,
            $idUtilisateur
        ]);

        return $requete->fetch() !== false;
    }

    /**
     * Enregistre temporairement le nouvel email.
     */
    public function preparerChangementEmail(
        int $id,
        string $nouvelEmail,
        string $token
    ): void {
        $requete = $this->pdo->prepare(
            'UPDATE utilisateurs
             SET email_en_attente = ?,
                 token_changement_email = ?,
                 expiration_changement_email =
                     DATE_ADD(NOW(), INTERVAL 1 HOUR)
             WHERE id = ?'
        );

        $requete->execute([
            $nouvelEmail,
            $token,
            $id
        ]);
    }

    /**
     * Recherche une demande de changement encore valide.
     */
    public function trouverChangementEmailValide(
        string $token
    ): ?array {
        $requete = $this->pdo->prepare(
            'SELECT *
             FROM utilisateurs
             WHERE token_changement_email = ?
             AND expiration_changement_email > NOW()
             AND email_en_attente IS NOT NULL'
        );

        $requete->execute([$token]);

        return $requete->fetch() ?: null;
    }

    /**
     * Applique définitivement le changement.
     */
    public function validerChangementEmail(
        int $id,
        string $nouvelEmail
    ): void {
        $requete = $this->pdo->prepare(
            'UPDATE utilisateurs
             SET email = ?,
                 email_en_attente = NULL,
                 token_changement_email = NULL,
                 expiration_changement_email = NULL
             WHERE id = ?'
        );

        $requete->execute([
            $nouvelEmail,
            $id
        ]);
    }

    /**
     * Annule une demande de changement.
     */
    public function annulerChangementEmail(int $id): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE utilisateurs
             SET email_en_attente = NULL,
                 token_changement_email = NULL,
                 expiration_changement_email = NULL
             WHERE id = ?'
        );

        $requete->execute([$id]);
    }
    /**
     * anonymiserCompte()
     * "Suppression" RGPD du compte : on anonymise les données
     * personnelles plutôt que de supprimer la ligne en base — ses
     * messages et avis passés restent visibles pour les autres
     * utilisateurs (comme sur la plupart des plateformes), mais son
     * identité réelle disparaît totalement. Le compte devient
     * définitivement inutilisable : mot de passe aléatoire
     * irrécupérable, statut 'supprime' qui bloque toute connexion
     * (voir AuthController::traiterConnexion).
     */
    public function anonymiserCompte(int $id): void
    {
        $motDePasseInutilisable = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $emailAnonyme = 'compte-supprime-' . $id . '@immoapp.local';

        $requete = $this->pdo->prepare(
            "UPDATE utilisateurs SET
                nom = 'Compte supprimé',
                email = ?,
                mot_de_passe = ?,
                telephone = NULL,
                bio = NULL,
                photo_profil = NULL,
                statut = 'supprime',
                date_suppression = NOW(),
                token_verification = NULL,
                token_expiration = NULL
             WHERE id = ?"
        );
        $requete->execute([$emailAnonyme, $motDePasseInutilisable, $id]);
    }


}
