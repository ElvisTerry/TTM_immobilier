<?php
/**
 * controllers/AuthController.php
 * ---------------------------------
 */
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../includes/Mailer.php';

class AuthController
{
    public function formulaireInscription(): void
    {
        $titrePage = 'Inscription';
        $erreurs = $_SESSION['erreurs_inscription'] ?? [];
        $anciennesValeurs = $_SESSION['anciennes_valeurs'] ?? [];
        unset($_SESSION['erreurs_inscription'], $_SESSION['anciennes_valeurs']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/auth/inscription.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterInscription(): void
    {
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';
        $role = $_POST['role'] ?? '';
        $telephone = trim($_POST['telephone'] ?? '');

        $erreurs = [];
        $utilisateurModel = new Utilisateur();

        if (strlen($nom) < 2) {
            $erreurs[] = "Le nom doit contenir au moins 2 caractères.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'adresse email n'est pas valide.";
        } elseif ($utilisateurModel->emailExiste($email)) {
            $erreurs[] = "Cet email est déjà utilisé.";
        }
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $motDePasse)) {
            $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.";
        }
        if ($motDePasse !== $confirmation) {
            $erreurs[] = "Les mots de passe ne correspondent pas.";
        }
        if (!in_array($role, ['proprietaire', 'locataire'], true)) {
            $erreurs[] = "Veuillez choisir un type de compte valide.";
        }

        if (!empty($erreurs)) {
            $_SESSION['erreurs_inscription'] = $erreurs;
            $_SESSION['anciennes_valeurs'] = ['nom' => $nom, 'email' => $email, 'telephone' => $telephone];
            header('Location: ' . url('inscription'));
            exit;
        }

        $motDePasseHache = password_hash($motDePasse, PASSWORD_BCRYPT);

        // Plus de jeton de vérification à générer : le compte est actif
        // dès sa création, comme demandé.
        $nouvelId = $utilisateurModel->creer($nom, $email, $motDePasseHache, $role, $telephone, '');
        $utilisateurModel->marquerEmailVerifie($nouvelId);

        $_SESSION['message_succes'] = "Compte créé avec succès !";

        /**
         * Pré-remplissage à usage UNIQUE de la page de connexion : on
         * dépose l'email et le mot de passe fraîchement choisis dans une
         * variable de session "flash" (lue puis immédiatement effacée par
         * formulaireConnexion()). Elle ne survit qu'à cette seule
         * redirection — rien n'est stocké de façon durable, ni en cookie,
         * ni en base : dès que la page de connexion l'a lue une fois,
         * cette donnée disparaît.
         */
        $_SESSION['prefill_connexion'] = ['email' => $email, 'mot_de_passe' => $motDePasse];

        header('Location: ' . url('connexion'));
        exit;
    }

    public function formulaireConnexion(): void
    {
        $titrePage = 'Connexion';
        $erreurs = $_SESSION['erreurs_connexion'] ?? [];
        $messageSucces = $_SESSION['message_succes'] ?? null;
        unset($_SESSION['erreurs_connexion'], $_SESSION['message_succes']);

        // Pré-remplissage à usage unique juste après une inscription —
        // lu puis effacé immédiatement, ne survit pas à un second chargement.
        $emailPrefill = '';
        $motDePassePrefill = '';
        if (!empty($_SESSION['prefill_connexion'])) {
            $emailPrefill = $_SESSION['prefill_connexion']['email'];
            $motDePassePrefill = $_SESSION['prefill_connexion']['mot_de_passe'];
            unset($_SESSION['prefill_connexion']);
        } elseif (!empty($_COOKIE['dernier_email'])) {
            // Pour un retour ultérieur (jours/semaines après) : on ne
            // conserve JAMAIS le mot de passe nulle part (bcrypt le rend
            // volontairement irrécupérable — voir l'explication donnée à
            // l'utilisateur), seulement le dernier email utilisé, dans un
            // simple cookie, pour lui éviter de le retaper.
            $emailPrefill = $_COOKIE['dernier_email'];
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/auth/connexion.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterConnexion(): void
    {
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        require_once __DIR__ . '/../models/TentativeIp.php';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $tentativeIpModel = new TentativeIp();

        if ($tentativeIpModel->estBloquee($ip)) {
            $this->redirigerAvecErreur("Trop de tentatives depuis cette connexion. Réessayez dans 15 minutes.");
        }

        $email = trim($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';

        $utilisateurModel = new Utilisateur();
        $utilisateur = $utilisateurModel->trouverParEmail($email);

        // Message générique volontaire : évite qu'un attaquant devine
        // quels emails sont inscrits sur le site (énumération de comptes).
        $messageGenerique = "Email ou mot de passe incorrect.";

        if (!$utilisateur) {
            $tentativeIpModel->enregistrerEchec($ip);
            $this->redirigerAvecErreur($messageGenerique);
        }

        if ($utilisateur['statut'] === 'suspendu') {
            $this->redirigerAvecErreur("Ce compte a été suspendu. Contactez le support.");
        }

        if ($utilisateur['statut'] === 'supprime') {
            $this->redirigerAvecErreur($messageGenerique);
        }

        if ($utilisateurModel->estVerrouille($utilisateur)) {
            $this->redirigerAvecErreur("Compte temporairement bloqué suite à plusieurs échecs. Réessayez dans 15 minutes.");
        }

        if (!password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            $utilisateurModel->enregistrerEchecConnexion((int) $utilisateur['id']);
            $tentativeIpModel->enregistrerEchec($ip);
            $this->redirigerAvecErreur($messageGenerique);
        }

        $utilisateurModel->reinitialiserTentatives((int) $utilisateur['id']);
        $utilisateurModel->majDerniereConnexion((int) $utilisateur['id']);
        $tentativeIpModel->reinitialiser($ip);

        // Empêche la fixation de session : nouvel identifiant à la connexion
        session_regenerate_id(true);

        $_SESSION['utilisateur_id'] = $utilisateur['id'];
        $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
        $_SESSION['utilisateur_role'] = $utilisateur['role'];

        /**
         * On retient l'email (jamais le mot de passe) dans un cookie longue
         * durée, pour pré-remplir ce champ à la prochaine visite — même
         * dans plusieurs semaines. httponly=false ici volontairement : ce
         * cookie ne sert qu'à préremplir un champ de formulaire côté PHP,
         * il ne contient aucune information sensible (juste un email),
         * donc l'exposer en JS ne présente aucun risque.
         */
        setcookie('dernier_email', $email, [
            'expires' => time() + (60 * 60 * 24 * 90), // 90 jours
            'path' => '/',
            'secure' => ENVIRONNEMENT === 'production',
            'samesite' => 'Lax',
        ]);

        header('Location: ' . url(''));
        exit;
    }

    public function deconnexion(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain']);
        }

        session_destroy();
        header('Location: ' . url(''));
        exit;
    }

    // ==================== MOT DE PASSE OUBLIÉ ====================

    public function formulaireMotDePasseOublie(): void
    {
        $titrePage = 'Mot de passe oublié';
        $messageSucces = $_SESSION['message_succes'] ?? null;
        unset($_SESSION['message_succes']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/auth/mot-de-passe-oublie.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterMotDePasseOublie(): void
    {
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $email = trim($_POST['email'] ?? '');
        $utilisateurModel = new Utilisateur();
        $utilisateur = $utilisateurModel->trouverParEmail($email);

        /**
         * Message identique QUE le compte existe ou non — sinon ce
         * formulaire deviendrait lui-même un outil pour deviner quels
         * emails sont inscrits sur le site (même principe qu'à la connexion).
         */
        if ($utilisateur) {
            $token = bin2hex(random_bytes(32));
            $utilisateurModel->definirTokenReinitialisation((int) $utilisateur['id'], $token);

            $lien = urlAbsolue('reinitialiser-mot-de-passe', [$token]);
            Mailer::envoyerReinitialisationEmail($email, $utilisateur['nom'], $lien);

            if (ENVIRONNEMENT !== 'production') {
                $_SESSION['lien_reinitialisation_dev'] = $lien;
            }
        }

        $_SESSION['message_succes'] = "Si un compte existe avec cet email, un lien de réinitialisation vient d'être envoyé.";
        header('Location: ' . url('mot-de-passe-oublie'));
        exit;
    }

    public function formulaireReinitialisation(string $token): void
    {
        $utilisateurModel = new Utilisateur();
        $utilisateur = $utilisateurModel->trouverParTokenReinitialisationValide($token);

        if (!$utilisateur) {
            $_SESSION['erreurs_connexion'] = ["Ce lien de réinitialisation est invalide ou a expiré."];
            header('Location: ' . url('mot-de-passe-oublie'));
            exit;
        }

        $titrePage = 'Nouveau mot de passe';
        $erreurs = $_SESSION['erreurs_reinitialisation'] ?? [];
        unset($_SESSION['erreurs_reinitialisation']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/auth/reinitialiser-mot-de-passe.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterReinitialisation(string $token): void
    {
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $utilisateurModel = new Utilisateur();
        $utilisateur = $utilisateurModel->trouverParTokenReinitialisationValide($token);

        if (!$utilisateur) {
            $_SESSION['erreurs_connexion'] = ["Ce lien de réinitialisation est invalide ou a expiré."];
            header('Location: ' . url('mot-de-passe-oublie'));
            exit;
        }

        $motDePasse = $_POST['mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $motDePasse)) {
            $_SESSION['erreurs_reinitialisation'] = ["Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre."];
            header('Location: ' . url('reinitialiser-mot-de-passe/' . $token));
            exit;
        }
        if ($motDePasse !== $confirmation) {
            $_SESSION['erreurs_reinitialisation'] = ["Les mots de passe ne correspondent pas."];
            header('Location: ' . url('reinitialiser-mot-de-passe/' . $token));
            exit;
        }

        $utilisateurModel->reinitialiserMotDePasse((int) $utilisateur['id'], password_hash($motDePasse, PASSWORD_BCRYPT));
        $utilisateurModel->reinitialiserTentatives((int) $utilisateur['id']); // on lève aussi un éventuel verrouillage en cours

        $_SESSION['message_succes'] = "Mot de passe mis à jour. Vous pouvez vous connecter.";
        header('Location: ' . url('connexion'));
        exit;
    }

    private function redirigerAvecErreur(string $message): void
    {
        $_SESSION['erreurs_connexion'] = [$message];
        header('Location: ' . url('connexion'));
        exit;
    }
}


