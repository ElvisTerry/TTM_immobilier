<?php
/**
 * controllers/BienController.php
 * ----------------------------------
 * Assistant de publication en 5 étapes. Les données saisies sont
 * accumulées dans $_SESSION['assistant_annonce'] au fil des étapes,
 * et écrites en base UNIQUEMENT à la toute dernière (finaliser()).
 * Ça permet à l'utilisateur de revenir en arrière sans rien perdre,
 * et évite de créer des annonces "à moitié remplies" en base si jamais
 * il abandonne en cours de route.
 */
require_once __DIR__ . '/../models/Bien.php';

class BienController
{
    private const EXTENSIONS_AUTORISEES = ['jpg', 'jpeg', 'png', 'webp'];
    private const TAILLE_MAX_OCTETS = 5 * 1024 * 1024;
    private const DOSSIER_UPLOAD = __DIR__ . '/../uploads/biens/';

    /**
     * verifierAccesAssistant()
     * Appelée en tout début de chaque étape : vérifie le rôle avant de
     * laisser accéder à l'assistant de création d'annonce.
     */
    private function verifierAccesAssistant(): void
    {
        exigerRole('proprietaire');
    }

    /**
     * verifierEtapeAtteinte()
     * Empêche de sauter directement à une étape avancée (ex: /biens/creer/photos)
     * sans être passé par les étapes précédentes, en vérifiant qu'un champ
     * clé de l'étape précédente est déjà présent dans la session.
     */
    private function verifierEtapeAtteinte(string $champRequis): void
    {
        if (empty($_SESSION['assistant_annonce'][$champRequis])) {
            header('Location: ' . url('biens/creer/infos'));
            exit;
        }
    }

    // ==================== ÉTAPE 1 : INFOS DE BASE ====================

    public function etapeInfos(): void
    {
        $this->verifierAccesAssistant();

        $titrePage = 'Publier une annonce - Infos';
        $donnees = $_SESSION['assistant_annonce'] ?? [];
        $erreurs = $_SESSION['erreurs_assistant'] ?? [];
        unset($_SESSION['erreurs_assistant']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/creation/infos.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterEtapeInfos(): void
    {
        $this->verifierAccesAssistant();

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $typeBien = $_POST['type_bien'] ?? '';
        $typeTransaction = $_POST['type_transaction'] ?? '';
        $prix = $_POST['prix'] ?? '';

        $erreurs = [];
        if (strlen($titre) < 5) $erreurs[] = "Le titre doit contenir au moins 5 caractères.";
        if (!in_array($typeBien, ['chambre', 'studio', 'appartement', 'maison'], true)) $erreurs[] = "Type de bien invalide.";
        if (!in_array($typeTransaction, ['location', 'vente'], true)) $erreurs[] = "Type de transaction invalide.";
        if (!is_numeric($prix) || $prix <= 0) $erreurs[] = "Le prix doit être un nombre positif.";

        if (!empty($erreurs)) {
            $_SESSION['erreurs_assistant'] = $erreurs;
            // On garde ce qui a été saisi pour ne pas faire tout retaper
            $_SESSION['assistant_annonce'] = array_merge($_SESSION['assistant_annonce'] ?? [], compact('titre', 'description', 'typeBien', 'typeTransaction', 'prix'));
            header('Location: ' . url('biens/creer/infos'));
            exit;
        }

        $_SESSION['assistant_annonce'] = array_merge($_SESSION['assistant_annonce'] ?? [], [
            'titre' => $titre, 'description' => $description,
            'type_bien' => $typeBien, 'type_transaction' => $typeTransaction, 'prix' => $prix,
        ]);

        header('Location: ' . url('biens/creer/equipements'));
        exit;
    }

    // ==================== ÉTAPE 2 : ÉQUIPEMENTS ====================

    public function etapeEquipements(): void
    {
        $this->verifierAccesAssistant();
        $this->verifierEtapeAtteinte('titre');

        $titrePage = 'Publier une annonce - Équipements';
        $donnees = $_SESSION['assistant_annonce'];

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/creation/equipements.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterEtapeEquipements(): void
    {
        $this->verifierAccesAssistant();

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $_SESSION['assistant_annonce'] = array_merge($_SESSION['assistant_annonce'] ?? [], [
            'nombre_chambres' => (int) ($_POST['nombre_chambres'] ?? 0),
            'nombre_salles_bain' => (int) ($_POST['nombre_salles_bain'] ?? 0),
            'superficie_m2' => $_POST['superficie_m2'] !== '' ? (float) $_POST['superficie_m2'] : null,
            'meuble' => isset($_POST['meuble']) ? 1 : 0,
            'eau' => isset($_POST['eau']) ? 1 : 0,
            'electricite' => isset($_POST['electricite']) ? 1 : 0,
            'parking' => isset($_POST['parking']) ? 1 : 0,
        ]);

        header('Location: ' . url('biens/creer/localisation'));
        exit;
    }

    // ==================== ÉTAPE 3 : LOCALISATION ====================
    // Version simple aujourd'hui (ville/quartier en texte) ; la carte
    // interactive (Leaflet, latitude/longitude précises) arrive au Jour 7
    // et viendra enrichir cette même étape sans changer le reste de l'assistant.

    public function etapeLocalisation(): void
    {
        $this->verifierAccesAssistant();
        $this->verifierEtapeAtteinte('titre');

        $titrePage = 'Publier une annonce - Localisation';
        $donnees = $_SESSION['assistant_annonce'];
        $erreurs = $_SESSION['erreurs_assistant'] ?? [];
        unset($_SESSION['erreurs_assistant']);
        $inclureLeaflet = true;

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/creation/localisation.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterEtapeLocalisation(): void
    {
        $this->verifierAccesAssistant();

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $ville = trim($_POST['ville'] ?? '');
        $quartier = trim($_POST['quartier'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');

        if ($ville === '') {
            $_SESSION['erreurs_assistant'] = ["La ville est obligatoire."];
            header('Location: ' . url('biens/creer/localisation'));
            exit;
        }

        /**
         * Les coordonnées sont OPTIONNELLES (un propriétaire peut ne pas
         * vouloir pointer précisément sur la carte), mais si elles sont
         * fournies, on vérifie qu'elles sont bien des nombres dans les
         * bornes valides d'un point GPS — jamais confiance aveugle dans
         * une valeur envoyée par un formulaire, même générée par notre
         * propre JavaScript (le POST peut toujours être forgé à la main).
         */
        $latitudeValidee = null;
        $longitudeValidee = null;
        if ($latitude !== '' && $longitude !== '' && is_numeric($latitude) && is_numeric($longitude)) {
            $latitudeFloat = (float) $latitude;
            $longitudeFloat = (float) $longitude;
            if ($latitudeFloat >= -90 && $latitudeFloat <= 90 && $longitudeFloat >= -180 && $longitudeFloat <= 180) {
                $latitudeValidee = $latitudeFloat;
                $longitudeValidee = $longitudeFloat;
            }
        }

        $_SESSION['assistant_annonce'] = array_merge($_SESSION['assistant_annonce'] ?? [], [
            'ville' => $ville,
            'quartier' => $quartier,
            'latitude' => $latitudeValidee,
            'longitude' => $longitudeValidee,
        ]);

        header('Location: ' . url('biens/creer/photos'));
        exit;
    }

    // ==================== ÉTAPE 4 : PHOTOS ====================
    // Upload simple aujourd'hui ; le glisser-déposer avec réordonnancement
    // AJAX arrive au Jour 6 sans changer la logique de sécurité ci-dessous.

    public function etapePhotos(): void
    {
        $this->verifierAccesAssistant();
        $this->verifierEtapeAtteinte('ville');

        $titrePage = 'Publier une annonce - Photos';

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/creation/photos.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * uploaderPhotoAjax()
     * Reçoit UNE photo à la fois, envoyée en AJAX dès qu'elle est
     * déposée/sélectionnée (pas besoin de cliquer sur "Continuer" pour
     * que l'upload parte). Répond en JSON, jamais en redirection HTTP —
     * une redirection n'aurait aucun sens pour du JavaScript qui attend
     * une réponse à traiter, pas une nouvelle page.
     */
    public function uploaderPhotoAjax(): void
    {
        if (!estConnecte() || $_SESSION['utilisateur_role'] !== 'proprietaire') {
            $this->reponseJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $photosActuelles = $_SESSION['assistant_annonce']['photos'] ?? [];
        if (count($photosActuelles) >= 8) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Maximum 8 photos par annonce.'], 422);
        }

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $messagesErreur = [
                UPLOAD_ERR_INI_SIZE => "dépasse la taille maximale autorisée par le serveur",
                UPLOAD_ERR_FORM_SIZE => "dépasse la taille maximale du formulaire",
                UPLOAD_ERR_PARTIAL => "n'a été envoyée que partiellement",
            ];
            $code = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
            $this->reponseJson(['succes' => false, 'erreur' => "Envoi échoué : " . ($messagesErreur[$code] ?? "fichier manquant ou invalide.")], 422);
        }

        $erreur = null;
        $cheminTemporaire = $this->validerUnePhoto($_FILES['photo'], $erreur);

        if ($cheminTemporaire === null) {
            $this->reponseJson(['succes' => false, 'erreur' => $erreur], 422);
        }

        $nomFichier = $this->deplacerPhoto($cheminTemporaire);
        if ($nomFichier === null) {
            $this->reponseJson(['succes' => false, 'erreur' => "Échec de l'enregistrement du fichier."], 500);
        }

        $photosActuelles[] = $nomFichier;
        $_SESSION['assistant_annonce']['photos'] = $photosActuelles;

        $this->reponseJson([
            'succes' => true,
            'nomFichier' => $nomFichier,
            'url' => cheminBase() . '/uploads/biens/' . $nomFichier,
        ]);
    }

    /**
     * supprimerPhotoAjax()
     * Retire une photo de la session ET du disque, sans recharger la page.
     */
    public function supprimerPhotoAjax(): void
    {
        if (!estConnecte()) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $nomFichier = basename((string) ($_POST['nomFichier'] ?? ''));
        $photosActuelles = $_SESSION['assistant_annonce']['photos'] ?? [];

        /**
         * On ne supprime QUE si le nom de fichier fait bien partie du
         * brouillon de CET utilisateur (présent dans sa propre session) —
         * ça empêche qu'une requête AJAX forgée à la main supprime le
         * fichier de quelqu'un d'autre en devinant un nom.
         */
        $index = array_search($nomFichier, $photosActuelles, true);
        if ($index === false) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Photo introuvable dans votre brouillon.'], 404);
        }

        $chemin = self::DOSSIER_UPLOAD . $nomFichier;
        if (file_exists($chemin)) {
            unlink($chemin);
        }

        unset($photosActuelles[$index]);
        $_SESSION['assistant_annonce']['photos'] = array_values($photosActuelles);

        $this->reponseJson(['succes' => true]);
    }

    /**
     * traiterEtapePhotos()
     * Appelée au clic sur "Continuer" : les photos sont déjà toutes
     * uploadées en AJAX à ce stade, donc cette méthode se contente
     * d'enregistrer l'ORDRE final choisi par glisser-déposer (le
     * JavaScript le place dans un champ caché avant la soumission).
     */
    public function traiterEtapePhotos(): void
    {
        $this->verifierAccesAssistant();

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $photosActuelles = $_SESSION['assistant_annonce']['photos'] ?? [];
        $ordreRecu = array_filter(explode(',', $_POST['ordre'] ?? ''));

        /**
         * On ne fait CONFIANCE à l'ordre reçu que pour les noms de fichiers
         * qui existent réellement dans le brouillon de l'utilisateur — tout
         * nom inconnu envoyé dans le champ cachÃ© (accidentellement ou non)
         * est simplement ignoré plutôt que d'être ajouté aveuglément.
         */
        $ordreValide = array_values(array_intersect($ordreRecu, $photosActuelles));

        // Sécurité supplémentaire : si un fichier du brouillon n'apparaît
        // pas dans l'ordre reçu (JS désactivé, champ vide...), on le garde
        // quand même à la fin plutôt que de le perdre silencieusement.
        $manquants = array_values(array_diff($photosActuelles, $ordreValide));

        $_SESSION['assistant_annonce']['photos'] = array_merge($ordreValide, $manquants);

        header('Location: ' . url('biens/creer/recapitulatif'));
        exit;
    }

    // ==================== ÉTAPE 5 : RÉCAPITULATIF ====================

    public function etapeRecapitulatif(): void
    {
        $this->verifierAccesAssistant();
        $this->verifierEtapeAtteinte('ville');

        $titrePage = 'Publier une annonce - Récapitulatif';
        $donnees = $_SESSION['assistant_annonce'];

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/creation/recapitulatif.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * finaliser()
     * Seul point de la vérité où l'annonce est réellement écrite en base.
     */
    public function finaliser(): void
    {
        $this->verifierAccesAssistant();

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $donnees = $_SESSION['assistant_annonce'] ?? [];
        if (empty($donnees['titre']) || empty($donnees['ville'])) {
            header('Location: ' . url('biens/creer/infos'));
            exit;
        }

        $bienModel = new Bien();
        $bienId = $bienModel->creer([
            'proprietaire_id' => $_SESSION['utilisateur_id'],
            'titre' => $donnees['titre'],
            'description' => $donnees['description'] ?? '',
            'type_bien' => $donnees['type_bien'],
            'type_transaction' => $donnees['type_transaction'],
            'prix' => $donnees['prix'],
            'ville' => $donnees['ville'],
            'quartier' => $donnees['quartier'] ?? '',
            'latitude' => $donnees['latitude'] ?? null,
            'longitude' => $donnees['longitude'] ?? null,
            'superficie_m2' => $donnees['superficie_m2'] ?? null,
            'nombre_chambres' => $donnees['nombre_chambres'] ?? 0,
            'nombre_salles_bain' => $donnees['nombre_salles_bain'] ?? 0,
            'meuble' => $donnees['meuble'] ?? 0,
            'eau' => $donnees['eau'] ?? 0,
            'electricite' => $donnees['electricite'] ?? 0,
            'parking' => $donnees['parking'] ?? 0,
        ]);

        foreach (($donnees['photos'] ?? []) as $ordre => $nomFichier) {
            $bienModel->ajouterPhoto($bienId, $nomFichier, $ordre);
        }

        // On vide le brouillon de session — l'assistant est terminé.
        unset($_SESSION['assistant_annonce']);

        $_SESSION['message_succes'] = "Annonce créée ! Elle sera visible publiquement après validation par notre équipe.";
        header('Location: ' . url('biens/detail', [$bienId]));
        exit;
    }

    // ==================== PAGE DÉTAIL ====================

    // ==================== RECHERCHE ====================

    /**
     * recherche()
     * Affiche la PAGE de recherche (formulaire + grille + carte). Le
     * contenu initial des résultats est chargé en AJAX par le
     * JavaScript juste après, pas ici — cette méthode ne fait que
     * poser le décor.
     */
    public function recherche(): void
    {
        $titrePage = 'Rechercher un bien';
        $inclureLeaflet = true;

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/recherche.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * rechercheAjax()
     * Endpoint interrogé par le JavaScript à chaque changement de filtre.
     * Répond en JSON — jamais en HTML complet — pour que la page ne soit
     * jamais rechargée pendant que l'utilisateur affine sa recherche.
     */
    public function rechercheAjax(): void
    {
        $filtres = [
            'ville' => trim($_GET['ville'] ?? ''),
            'quartier' => trim($_GET['quartier'] ?? ''),
            'type_bien' => $_GET['type_bien'] ?? '',
            'type_transaction' => $_GET['type_transaction'] ?? '',
            'prix_min' => $_GET['prix_min'] ?? '',
            'prix_max' => $_GET['prix_max'] ?? '',
            'superficie_min' => $_GET['superficie_min'] ?? '',
            'superficie_max' => $_GET['superficie_max'] ?? '',
            'nombre_chambres' => $_GET['nombre_chambres'] ?? '',
            'nombre_salles_bain' => $_GET['nombre_salles_bain'] ?? '',
            'meuble' => $_GET['meuble'] ?? '',
            'eau' => $_GET['eau'] ?? '',
            'electricite' => $_GET['electricite'] ?? '',
            'parking' => $_GET['parking'] ?? '',
            'latitude' => $_GET['latitude'] ?? '',
            'longitude' => $_GET['longitude'] ?? '',
            'rayon' => $_GET['rayon'] ?? '',
        ];

        // Bornes serveur : impossible de demander un rayon arbitraire.
        if ($filtres['rayon'] !== '' && is_numeric($filtres['rayon'])) {
            $filtres['rayon'] = max(0.1, min(50, (float) $filtres['rayon']));
        }

        $triDemande = $_GET['tri'] ?? 'recent';
        $tri = in_array(
            $triDemande,
            ['recent', 'prix_asc', 'prix_desc', 'vues_desc', 'recommande'],
            true
        ) ? $triDemande : 'recent';

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $parPage = 12;

        $bienModel = new Bien();
        $resultats = $bienModel->rechercher($filtres, $tri, $page, $parPage);
        $total = $bienModel->compterResultats($filtres);

        foreach ($resultats as &$bien) {
            $bien['url_detail'] = url('biens/detail', [(int) $bien['id']]);
            $bien['photo_url'] = $bien['photo_principale']
                ? cheminBase() . '/uploads/biens/' . $bien['photo_principale']
                : null;
        }
        unset($bien);

        $this->reponseJson([
            'resultats' => $resultats,
            'total' => $total,
            'page' => $page,
            'aPlus' => ($page * $parPage) < $total,
        ]);
    }

    /**
     * suggestionsVillesAjax()
     * Alimente l'autocomplétion du champ ville (via une <datalist> HTML
     * native, remplie dynamiquement — pas de librairie JS nécessaire).
     */
    public function suggestionsVillesAjax(): void
    {
        $recherche = trim($_GET['q'] ?? '');
        if (mb_strlen($recherche) < 2) {
            $this->reponseJson(['suggestions' => []]);
        }

        $bienModel = new Bien();
        $this->reponseJson(['suggestions' => $bienModel->suggestionsVilles($recherche)]);
    }

    /**
     * suggestionsQuartiersAjax()
     * Dépendante de la ville choisie — ne renvoie que les quartiers
     * réellement présents en base pour CETTE ville, comme demandé.
     */
    public function suggestionsQuartiersAjax(): void
    {
        $ville = trim($_GET['ville'] ?? '');
        if ($ville === '') {
            $this->reponseJson(['suggestions' => []]);
        }

        $bienModel = new Bien();
        $this->reponseJson(['suggestions' => $bienModel->suggestionsQuartiers($ville)]);
    }

    // ==================== GESTION (Jour 20) ====================

    /**
     * gestion()
     * Liste TOUTES les annonces du propriétaire connecté, quel que soit
     * leur statut, avec les actions disponibles sur chacune.
     */
    public function gestion(): void
    {
        exigerRole('proprietaire');

        $titrePage = 'Gestion de mes annonces';
        $bienModel = new Bien();
        $mesAnnonces = $bienModel->listerTousPourProprietaire((int) $_SESSION['utilisateur_id']);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/gestion.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    /**
     * ajouterPhotoExistanteAjax()
     * Ajoute une photo à une annonce DÉJÀ publiée — même validations
     * que uploaderPhotoAjax() (assistant de création), mais écrit
     * directement en base plutôt qu'en session, puisqu'il n'y a plus
     * de brouillon ici.
     */
    public function ajouterPhotoExistanteAjax(int $id): void
    {
        if (!estConnecte() || $_SESSION['utilisateur_role'] !== 'proprietaire') {
            $this->reponseJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        if (!$bienModel->appartientA($id, (int) $_SESSION['utilisateur_id'])) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        if ($bienModel->compterPhotos($id) >= 8) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Maximum 8 photos par annonce.'], 422);
        }

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->reponseJson(['succes' => false, 'erreur' => "Envoi échoué : fichier manquant ou invalide."], 422);
        }

        $erreur = null;
        $cheminTemporaire = $this->validerUnePhoto($_FILES['photo'], $erreur);
        if ($cheminTemporaire === null) {
            $this->reponseJson(['succes' => false, 'erreur' => $erreur], 422);
        }

        $nomFichier = $this->deplacerPhoto($cheminTemporaire);
        if ($nomFichier === null) {
            $this->reponseJson(['succes' => false, 'erreur' => "Échec de l'enregistrement du fichier."], 500);
        }

        $ordre = $bienModel->prochainOrdrePhoto($id);
        $photoId = $bienModel->ajouterPhoto($id, $nomFichier, $ordre);

        // Une photo fait partie du contenu de l'annonce au même titre
        // que le titre ou le prix : ce changement la repasse en
        // modération, cohérent avec mettreAJour() (voir le correctif
        // de sécurité de la Priorité 1).
        $bienModel->changerStatutModeration($id, 'en_attente');

        $this->reponseJson([
            'succes' => true,
            'photoId' => $photoId,
            'url' => cheminBase() . '/uploads/biens/' . $nomFichier,
        ]);
    }

    /**
     * supprimerPhotoExistanteAjax()
     * Refuse de vider entièrement une annonce : au moins 1 photo doit
     * toujours rester, pour éviter une fiche sans aucune image.
     */
    public function supprimerPhotoExistanteAjax(int $id, int $photoId): void
    {
        if (!estConnecte() || $_SESSION['utilisateur_role'] !== 'proprietaire') {
            $this->reponseJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        if (!$bienModel->appartientA($id, (int) $_SESSION['utilisateur_id'])) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $photo = $bienModel->trouverPhoto($photoId, $id);
        if (!$photo) {
            $this->reponseJson(['succes' => false, 'erreur' => 'Photo introuvable.'], 404);
        }

        if ($bienModel->compterPhotos($id) <= 1) {
            $this->reponseJson(['succes' => false, 'erreur' => "Impossible de supprimer la dernière photo — une annonce doit garder au moins une photo."], 422);
        }

        $chemin = self::DOSSIER_UPLOAD . $photo['chemin_fichier'];
        if (file_exists($chemin)) {
            unlink($chemin);
        }

        $bienModel->supprimerPhotoParId($photoId);
        $bienModel->changerStatutModeration($id, 'en_attente');

        $this->reponseJson(['succes' => true]);
    }


    public function formulaireModification(int $id): void
    {
        exigerRole('proprietaire');

        $bienModel = new Bien();
        if (!$bienModel->appartientA($id, (int) $_SESSION['utilisateur_id'])) {
            http_response_code(403);
            die("Vous n'êtes pas autorisé à modifier cette annonce.");
        }

        $bien = $bienModel->trouverParId($id);
        $titrePage = 'Modifier l\'annonce';
        $erreurs = $_SESSION['erreurs_modification'] ?? [];
        unset($_SESSION['erreurs_modification']);
        $inclureLeaflet = false;

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/modifier.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function traiterModification(int $id): void
    {
        exigerRole('proprietaire');

        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            die('Requête invalide (jeton de sécurité incorrect).');
        }

        $bienModel = new Bien();
        if (!$bienModel->appartientA($id, (int) $_SESSION['utilisateur_id'])) {
            http_response_code(403);
            die("Vous n'êtes pas autorisé à modifier cette annonce.");
        }

        $titre = trim($_POST['titre'] ?? '');
        $prix = $_POST['prix'] ?? '';
        $ville = trim($_POST['ville'] ?? '');

        $erreurs = [];
        if (strlen($titre) < 5) $erreurs[] = "Le titre doit contenir au moins 5 caractères.";
        if (!is_numeric($prix) || $prix <= 0) $erreurs[] = "Le prix doit être un nombre positif.";
        if ($ville === '') $erreurs[] = "La ville est obligatoire.";

        if (!empty($erreurs)) {
            $_SESSION['erreurs_modification'] = $erreurs;
            header('Location: ' . url('biens/' . $id . '/modifier'));
            exit;
        }

        $bienModel->mettreAJour($id, [
            'titre' => $titre,
            'description' => trim($_POST['description'] ?? ''),
            'type_bien' => $_POST['type_bien'] ?? '',
            'type_transaction' => $_POST['type_transaction'] ?? '',
            'prix' => $prix,
            'ville' => $ville,
            'quartier' => trim($_POST['quartier'] ?? ''),
            'superficie_m2' => $_POST['superficie_m2'] !== '' ? (float) $_POST['superficie_m2'] : null,
            'nombre_chambres' => (int) ($_POST['nombre_chambres'] ?? 0),
            'nombre_salles_bain' => (int) ($_POST['nombre_salles_bain'] ?? 0),
            'meuble' => isset($_POST['meuble']) ? 1 : 0,
            'eau' => isset($_POST['eau']) ? 1 : 0,
            'electricite' => isset($_POST['electricite']) ? 1 : 0,
            'parking' => isset($_POST['parking']) ? 1 : 0,
        ]);

        $_SESSION['message_succes'] = "Annonce mise à jour.";
        header('Location: ' . url('biens/detail', [$id]));
        exit;
    }

    /**
     * changerStatutCommercialAjax()
     * Marquer une annonce comme louée/vendue/de nouveau disponible —
     * accessible depuis la page Gestion, sans passer par l'admin (ce
     * statut est distinct de la modération, voir le schéma du Jour 1).
     */
    public function changerStatutCommercialAjax(int $id): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        if (!$bienModel->appartientA($id, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, ['disponible', 'loue', 'vendu'], true)) {
            repondreJson(['succes' => false, 'erreur' => 'Statut invalide.'], 422);
        }

        $bienModel->changerStatutCommercial($id, $statut);
        repondreJson(['succes' => true, 'statut' => $statut]);
    }

    /**
     * supprimerAjax()
     * Supprime l'annonce ET ses fichiers photo sur le disque (les lignes
     * liées en base — favoris, messages, avis, etc. — partent en cascade,
     * voir Bien::supprimer()).
     */
    public function supprimerAjax(int $id): void
    {
        if (!estConnecte()) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            repondreJson(['succes' => false, 'erreur' => 'Jeton de sécurité invalide.'], 403);
        }

        $bienModel = new Bien();
        if (!$bienModel->appartientA($id, (int) $_SESSION['utilisateur_id'])) {
            repondreJson(['succes' => false, 'erreur' => 'Non autorisé.'], 403);
        }

        foreach ($bienModel->photosDuBien($id) as $nomFichier) {
            $chemin = __DIR__ . '/../uploads/biens/' . $nomFichier;
            if (file_exists($chemin)) {
                unlink($chemin);
            }
        }

        $bienModel->supprimer($id);

        repondreJson(['succes' => true]);
    }

    public function detail(int $id): void
    {
        $bienModel = new Bien();
        $bien = $bienModel->trouverParId($id);

        if (!$bien) {
            http_response_code(404);
            require_once __DIR__ . '/../views/erreurs/404.php';
            return;
        }

        $estProprietaireDuBien = estConnecte() && (int) $_SESSION['utilisateur_id'] === (int) $bien['proprietaire_id'];
        $estAdmin = estConnecte() && $_SESSION['utilisateur_role'] === 'admin';

        /**
         * Une annonce non encore validée n'est visible QUE par son
         * propriétaire ou un administrateur — jamais par le grand public,
         * même en connaissant l'URL directe.
         */
        if ($bien['statut_moderation'] !== 'approuve' && !$estProprietaireDuBien && !$estAdmin) {
            http_response_code(404);
            require_once __DIR__ . '/../views/erreurs/404.php';
            return;
        }

       $titrePage = $bien['titre'];
        $messageSucces = $_SESSION['message_succes'] ?? null;
        unset($_SESSION['message_succes']);
        $proposerWhatsappVisite = $_SESSION['proposer_whatsapp_visite'] ?? false;
        unset($_SESSION['proposer_whatsapp_visite']);
        $inclureLeaflet = !empty($bien['latitude']) && !empty($bien['longitude']);


        /**
         * Enregistrement de la vue : jamais quand le propriétaire consulte
         * sa propre annonce (fausserait ses statistiques), et une seule
         * fois par session pour qu'un simple F5 répété ne gonfle pas
         * artificiellement le compteur.
         */
        if (!$estProprietaireDuBien) {
            $_SESSION['vues_enregistrees'] = $_SESSION['vues_enregistrees'] ?? [];
            if (!in_array($id, $_SESSION['vues_enregistrees'], true)) {
                $bienModel->enregistrerVue($id, $_SERVER['REMOTE_ADDR'] ?? '');
                $_SESSION['vues_enregistrees'][] = $id;
            }
        }

        require_once __DIR__ . '/../models/Avis.php';
        require_once __DIR__ . '/../models/ReservationVisite.php';
        $avisModel = new Avis();
        $avis = $avisModel->listerPourBien($id);
        $statistiquesAvis = $avisModel->statistiquesPourBien($id);
        $erreursAvis = $_SESSION['erreurs_avis'] ?? [];
        unset($_SESSION['erreurs_avis']);

        // L'utilisateur peut-il laisser un avis ? Uniquement s'il est
        // connecté, n'est pas le propriétaire, a eu une visite acceptée,
        // et n'a pas déjà noté ce bien.
        $peutLaisserAvis = false;
        if (estConnecte() && !$estProprietaireDuBien) {
            $reservationModel = new ReservationVisite();
            $peutLaisserAvis = $reservationModel->visiteAccepteePour($id, (int) $_SESSION['utilisateur_id'])
                && !$avisModel->aDejaNote($id, (int) $_SESSION['utilisateur_id']);
        }

        $estFavori = false;
        if (estConnecte() && !$estProprietaireDuBien) {
            require_once __DIR__ . '/../models/Favori.php';
            $estFavori = (new Favori())->estFavori((int) $_SESSION['utilisateur_id'], $id);
        }

        // Biens similaires : uniquement si l'annonce est publique, pas la
        // peine de suggérer des alternatives sur une annonce en attente
        // que seul son propriétaire peut voir.
        $biensSimilaires = [];
        if ($bien['statut_moderation'] === 'approuve') {
            $biensSimilaires = $bienModel->trouverSimilaires($bien);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/detail.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // ==================== UTILITAIRES UPLOAD (sécurité) ====================

    /**
     * reponseJson()
     * Envoie une réponse JSON avec le bon code HTTP et coupe l'exécution.
     * Centralisé ici pour ne jamais oublier le Content-Type sur un des
     * endpoints AJAX.
     */
    private function reponseJson(array $donnees, int $codeHttp = 200): void
    {
        http_response_code($codeHttp);
        header('Content-Type: application/json');
        echo json_encode($donnees);
        exit;
    }
    /**
     * comparateur()
     * Reçoit jusqu'à 4 identifiants via ?ids=1,2,3 et affiche un tableau
     * comparatif. Un bien introuvable, retiré ou non approuvé est
     * silencieusement ignoré plutôt que de faire planter la page — le
     * visiteur voit simplement les biens restants comparables.
     */
    public function comparateur(): void
    {
        $idsBruts = trim($_GET['ids'] ?? '');
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsBruts)))));
        $ids = array_slice($ids, 0, 4);

        require_once __DIR__ . '/../models/Avis.php';
        $bienModel = new Bien();
        $avisModel = new Avis();

        $biens = [];
        foreach ($ids as $id) {
            $bien = $bienModel->trouverParId($id);
            if ($bien && $bien['statut_moderation'] === 'approuve') {
                $bien['statistiques_avis'] = $avisModel->statistiquesPourBien($id);
                $biens[] = $bien;
            }
        }

        $titrePage = 'Comparateur de biens';

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/biens/comparateur.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }


    /**
     * validerUnePhoto()
     * Version "un seul fichier" des vérifications de sécurité — utilisée
     * par l'upload AJAX (Jour 6), qui envoie une photo à la fois dès
     * qu'elle est déposée, plutôt qu'un lot complet d'un coup.
     */
    private function validerUnePhoto(array $fichier, ?string &$erreur): ?string
    {
        if ($fichier['size'] > self::TAILLE_MAX_OCTETS) {
            $erreur = "La photo dépasse 5 Mo.";
            return null;
        }

        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS_AUTORISEES, true)) {
            $erreur = "Format non autorisé (jpg, png ou webp uniquement).";
            return null;
        }

        // Vérification du VRAI type de fichier (contenu binaire), pas
        // seulement de l'extension déclarée — protection contre un script
        // malveillant déguisé en image.
        $infoFichier = new finfo(FILEINFO_MIME_TYPE);
        $typeMime = $infoFichier->file($fichier['tmp_name']);
        if (!in_array($typeMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $erreur = "Ce fichier n'est pas une image valide.";
            return null;
        }

        return $fichier['tmp_name'];
    }

    private function deplacerPhoto(string $cheminTemporaire): ?string
    {
        if (!is_dir(self::DOSSIER_UPLOAD)) {
            mkdir(self::DOSSIER_UPLOAD, 0755, true);
        }

        // compresserImage() réencode TOUJOURS en JPEG (format le plus
        // compact pour une photo) — le nom de fichier stocké porte donc
        // systématiquement l'extension .jpg, quel que soit le format
        // d'origine envoyé par l'utilisateur.
        $nomFichier = bin2hex(random_bytes(16)) . '.jpg';
        $destination = self::DOSSIER_UPLOAD . $nomFichier;

        if (!move_uploaded_file($cheminTemporaire, $destination)) {
            return null;
        }

        // Si la compression échoue pour une raison quelconque (image trop
        // exotique pour GD...), le fichier original déplacé reste utilisable
        // tel quel — on ne bloque jamais l'upload pour un souci d'optimisation.
        compresserImage($destination, $destination);

        return $nomFichier;
    }
}

