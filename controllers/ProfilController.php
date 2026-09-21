<?php
/**
 * controllers/ProfilController.php
 * -------------------------------------
 */
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../models/Bien.php';
require_once __DIR__ . '/../models/Favori.php';
require_once __DIR__ . '/../models/RechercheSauvegardee.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/ReservationVisite.php';

class ProfilController
{
    private const EXTENSIONS_AUTORISEES = ['jpg', 'jpeg', 'png', 'webp'];
    private const TAILLE_MAX_OCTETS = 2 * 1024 * 1024; // 2 Mo pour un avatar
    private const DOSSIER_UPLOAD = __DIR__ . '/../uploads/avatars/';

    /**
     * voir()
     * Page de profil PUBLIQUE — consultable par n'importe qui, connecté
     * ou non, exactement comme sur Airbnb.
     */
    public function voir(int $id): void
    {
        $utilisateurModel = new Utilisateur();
        $profil = $utilisateurModel->trouverParId($id);

        if (!$profil) {
            http_response_code(404);
            require_once __DIR__ . '/../views/erreurs/404.php';
            return;
        }

        $titrePage = $profil['nom'];

        $moyenneAvis = null;
        $libelleTempsReponse = null;
        if ($profil['role'] === 'proprietaire') {
            require_once __DIR__ . '/../models/Avis.php';
            $moyenneAvis = (new Avis())->moyennePourProprietaire($id);

            require_once __DIR__ . '/../models/Message.php';
            $tempsReponse = (new Message())->tempsReponseMoyenMinutes($id);
            if ($tempsReponse !== null) {
                $libelleTempsReponse = Message::libelleTempsReponse($tempsReponse);
            }
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/profil/voir.php';
        require_once __DIR__ . '/../views/layouts/footer.php';

    }

    /**
     * modifier()
     * Formulaire d'édition de SON PROPRE profil uniquement — on ne
     * prend jamais d'id en paramètre ici, on utilise systématiquement
     * $_SESSION['utilisateur_id'], pour qu'il soit impossible de
     * modifier le profil de quelqu'un d'autre en changeant une URL.
     */
    public function modifier(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $titrePage = 'Modifier mon profil';
        $utilisateurModel = new Utilisateur();
        $profil = $utilisateurModel->trouverParId((int) $_SESSION['utilisateur_id']);
        $erreurs = $_SESSION['erreurs_profil'] ?? [];
        $messageSucces = $_SESSION['message_succes_profil'] ?? null;
        unset($_SESSION['erreurs_profil'], $_SESSION['message_succes_profil']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/profil/modifier.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterModifier(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $nom = trim($_POST['nom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $erreurs = [];
        if (strlen($nom) < 2) {
            $erreurs[] = "Le nom doit contenir au moins 2 caractères.";
        }
        if (strlen($bio) > 500) {
            $erreurs[] = "La biographie ne doit pas dépasser 500 caractères.";
        }

        if (!empty($erreurs)) {
            $_SESSION['erreurs_profil'] = $erreurs;
            header('Location: ' . url('mon-profil'));
            exit;
        }

        $utilisateurModel = new Utilisateur();
        $utilisateurModel->mettreAJourProfil((int) $_SESSION['utilisateur_id'], $nom, $telephone, $bio);

        // Le nom affiché dans la navbar vient de la session : on le
        // met à jour immédiatement pour que le changement soit visible
        // sans devoir se reconnecter.
        $_SESSION['utilisateur_nom'] = $nom;

        $_SESSION['message_succes_profil'] = "Profil mis à jour avec succès.";
        header('Location: ' . url('mon-profil'));
        exit;
    }

    /**
     * traiterAvatar()
     * Upload de la photo de profil — mêmes vérifications de sécurité
     * que pour les photos d'annonces (Jour 5) : extension, type MIME
     * réel, taille, nom de fichier régénéré aléatoirement.
     */
    public function traiterAvatar(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        if (empty($_FILES['avatar']['name']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['erreurs_profil'] = ["Veuillez sélectionner une image valide."];
            header('Location: ' . url('mon-profil'));
            exit;
        }

        $fichier = $_FILES['avatar'];

        if ($fichier['size'] > self::TAILLE_MAX_OCTETS) {
            $_SESSION['erreurs_profil'] = ["L'image dépasse 2 Mo."];
            header('Location: ' . url('mon-profil'));
            exit;
        }

        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS_AUTORISEES, true)) {
            $_SESSION['erreurs_profil'] = ["Format d'image non autorisé."];
            header('Location: ' . url('mon-profil'));
            exit;
        }

        // Vérification du VRAI type de fichier via son contenu binaire,
        // et non son extension déclarée (qui peut être falsifiée).
        $infoFichier = new finfo(FILEINFO_MIME_TYPE);
        $typeMime = $infoFichier->file($fichier['tmp_name']);
        if (!in_array($typeMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $_SESSION['erreurs_profil'] = ["Le fichier envoyé n'est pas une image valide."];
            header('Location: ' . url('mon-profil'));
            exit;
        }

        if (!is_dir(self::DOSSIER_UPLOAD)) {
            mkdir(self::DOSSIER_UPLOAD, 0755, true);
        }

        // Nom de fichier totalement régénéré (toujours .jpg après
        // compression) : empêche l'écrasement d'un autre avatar et toute
        // tentative de path traversal.
        $nomFichier = bin2hex(random_bytes(16)) . '.jpg';
        $destination = self::DOSSIER_UPLOAD . $nomFichier;

        if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
            $_SESSION['erreurs_profil'] = ["Échec de l'envoi de l'image, réessayez."];
            header('Location: ' . url('mon-profil'));
            exit;
        }

        // Un avatar est toujours affiché en petit (100px) : 400px de large
        // suffit largement, pas besoin des 1600px utilisés pour les photos
        // d'annonces.
        compresserImage($destination, $destination, 400);

        $utilisateurModel = new Utilisateur();
        $profilActuel = $utilisateurModel->trouverParId((int) $_SESSION['utilisateur_id']);

        // On supprime l'ancien avatar du disque pour ne pas accumuler
        // des fichiers orphelins à chaque changement de photo.
        if (!empty($profilActuel['photo_profil'])) {
            $ancienChemin = self::DOSSIER_UPLOAD . $profilActuel['photo_profil'];
            if (file_exists($ancienChemin)) {
                unlink($ancienChemin);
            }
        }

        $utilisateurModel->mettreAJourAvatar((int) $_SESSION['utilisateur_id'], $nomFichier);

        $_SESSION['message_succes_profil'] = "Photo de profil mise à jour.";
        header('Location: ' . url('mon-profil'));
        exit;
    }
    /**
     * Demande de changement d'adresse email.
     */
    public function traiterChangementEmail(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $nouvelEmail = strtolower(
            trim($_POST['nouvel_email'] ?? '')
        );

        $motDePasseActuel =
            $_POST['mot_de_passe_actuel'] ?? '';

        $idUtilisateur =
            (int) $_SESSION['utilisateur_id'];

        $utilisateurModel =
            new Utilisateur();

        $utilisateur =
            $utilisateurModel->trouverParId(
                $idUtilisateur
            );

        $erreurs = [];

        if (!$utilisateur) {
            $erreurs[] = "Utilisateur introuvable.";
        }

        if (
            !filter_var(
                $nouvelEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $erreurs[] =
                "L'adresse email saisie n'est pas valide.";
        }

        if (
            $utilisateur &&
            strcasecmp(
                $nouvelEmail,
                $utilisateur['email']
            ) === 0
        ) {
            $erreurs[] =
                "Cette adresse est déjà votre adresse email actuelle.";
        }

        if (
            $utilisateur &&
            !password_verify(
                $motDePasseActuel,
                $utilisateur['mot_de_passe']
            )
        ) {
            $erreurs[] =
                "Votre mot de passe actuel est incorrect.";
        }

        if (
            empty($erreurs) &&
            $utilisateurModel->emailExistePourAutreUtilisateur(
                $nouvelEmail,
                $idUtilisateur
            )
        ) {
            $erreurs[] =
                "Cette adresse email est déjà utilisée par un autre compte.";
        }

        if (!empty($erreurs)) {

            $_SESSION['erreurs_profil'] = $erreurs;

            header(
                'Location: ' . url('mon-profil')
            );

            exit;
        }

        /*
         * Token cryptographiquement sécurisé.
         */
        $token = bin2hex(
            random_bytes(32)
        );

        /*
         * On ne modifie PAS encore l'email.
         */
        $utilisateurModel->preparerChangementEmail(
            $idUtilisateur,
            $nouvelEmail,
            $token
        );

        /*
         * Construction du lien.
         *
         * On utilise la fonction url() déjà utilisée
         * dans ton application.
         */
        $lienConfirmation = url(
            'confirmer-changement-email/' . $token
        );

        /*
         * Envoi au nouvel email.
         */
        Mailer::envoyerChangementEmail(
            $nouvelEmail,
            $utilisateur['nom'],
            $lienConfirmation
        );

        $_SESSION['message_succes_profil'] =
            "Un lien de confirmation a été envoyé à votre nouvelle adresse email. "
            . "Votre ancienne adresse reste active jusqu'à la confirmation.";

        header(
            'Location: ' . url('mon-profil')
        );

        exit;
    }










/**
     * Confirme définitivement le changement d'email.
     */
    public function confirmerChangementEmail(
        string $token
    ): void {
        $utilisateurModel =
            new Utilisateur();

        $utilisateur =
            $utilisateurModel
                ->trouverChangementEmailValide($token);

        if (!$utilisateur) {

            $_SESSION['erreurs_connexion'] = [
                "Le lien de changement d'email est invalide ou a expiré."
            ];

            header(
                'Location: ' . url('connexion')
            );

            exit;
        }

        $nouvelEmail =
            strtolower(
                trim(
                    $utilisateur['email_en_attente']
                )
            );

        /*
         * Sécurité supplémentaire :
         * l'adresse ne doit pas avoir été prise
         * entre-temps par un autre compte.
         */
        if (
            $utilisateurModel
                ->emailExistePourAutreUtilisateur(
                    $nouvelEmail,
                    (int) $utilisateur['id']
                )
        ) {

            $utilisateurModel
                ->annulerChangementEmail(
                    (int) $utilisateur['id']
                );

            $_SESSION['erreurs_connexion'] = [
                "Cette adresse email est maintenant utilisée par un autre compte."
            ];

            header(
                'Location: ' . url('connexion')
            );

            exit;
        }

        /*
         * Changement définitif.
         */
        $utilisateurModel->validerChangementEmail(
            (int) $utilisateur['id'],
            $nouvelEmail
        );

        /*
         * Si l'utilisateur est toujours connecté,
         * on met également à jour sa session.
         */
        if (
            isset($_SESSION['utilisateur_id']) &&
            (int) $_SESSION['utilisateur_id']
                === (int) $utilisateur['id']
        ) {
            $_SESSION['utilisateur_email'] =
                $nouvelEmail;
        }

        $_SESSION['message_succes'] =
            "Votre adresse email a été modifiée avec succès.";

        header(
            'Location: ' . url('connexion')
        );

        exit;
    }
    // ==================== SUPPRESSION DE COMPTE (RGPD) ====================

    /**
     * formulaireSuppression()
     * Affiche la page de confirmation, avec la liste des annonces qui
     * seront supprimées si l'utilisateur est propriétaire — pour qu'il
     * sache exactement ce qu'il perd avant de confirmer.
     */
    public function formulaireSuppression(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }

        $titrePage = 'Supprimer mon compte';
        $erreurs = $_SESSION['erreurs_suppression'] ?? [];
        unset($_SESSION['erreurs_suppression']);

        $mesAnnonces = [];
        if ($_SESSION['utilisateur_role'] === 'proprietaire') {
            $bienModel = new Bien();
            $mesAnnonces = $bienModel->listerTousPourProprietaire((int) $_SESSION['utilisateur_id']);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/profil/supprimer.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * traiterSuppression()
     * Suppression définitive et irréversible du compte. Exige le mot
     * de passe ET la saisie du mot "SUPPRIMER" — une double confirmation
     * volontairement stricte pour une action qu'on ne peut pas annuler.
     */
    public function traiterSuppression(): void
    {
        if (!estConnecte()) {
            header('Location: ' . url('connexion'));
            exit;
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $utilisateurId = (int) $_SESSION['utilisateur_id'];
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        $confirmationTexte = trim($_POST['confirmation_texte'] ?? '');

        $utilisateurModel = new Utilisateur();
        $utilisateur = $utilisateurModel->trouverParId($utilisateurId);

        $erreurs = [];
        if (!$utilisateur || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            $erreurs[] = "Mot de passe incorrect.";
        }
        if (strtoupper($confirmationTexte) !== 'SUPPRIMER') {
            $erreurs[] = 'Veuillez taper "SUPPRIMER" pour confirmer.';
        }

        if (!empty($erreurs)) {
            $_SESSION['erreurs_suppression'] = $erreurs;
            header('Location: ' . url('mon-compte/supprimer'));
            exit;
        }

        // --- Suppression des annonces si propriétaire (photos + ligne) ---
        $bienModel = new Bien();
        if ($_SESSION['utilisateur_role'] === 'proprietaire') {
            foreach ($bienModel->listerTousPourProprietaire($utilisateurId) as $annonce) {
                foreach ($bienModel->photosDuBien((int) $annonce['id']) as $nomFichier) {
                    $chemin = __DIR__ . '/../public/uploads/biens/' . $nomFichier;
                    if (file_exists($chemin)) {
                        unlink($chemin);
                    }
                }
                $bienModel->supprimer((int) $annonce['id']);
            }
        }

        // --- Nettoyage des données personnelles annexes ---
        (new Favori())->supprimerPourUtilisateur($utilisateurId);
        (new RechercheSauvegardee())->supprimerPourUtilisateur($utilisateurId);
        (new Notification())->supprimerPourUtilisateur($utilisateurId);
        (new ReservationVisite())->annulerToutesPourLocataire($utilisateurId);

        // --- Suppression de l'avatar sur le disque ---
        if (!empty($utilisateur['photo_profil'])) {
            $cheminAvatar = __DIR__ . '/../public/uploads/avatars/' . $utilisateur['photo_profil'];
            if (file_exists($cheminAvatar)) {
                unlink($cheminAvatar);
            }
        }

        // --- Anonymisation définitive du compte ---
        $utilisateurModel->anonymiserCompte($utilisateurId);

        // --- Déconnexion immédiate ---
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain']);
        }
        session_destroy();

        session_start();
        $_SESSION['message_succes'] = "Votre compte a été supprimé. Vos données personnelles ont été effacées.";
        header('Location: ' . url('connexion'));
        exit;
    }



}





