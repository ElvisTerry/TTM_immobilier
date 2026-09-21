<?php
/**
 * models/Bien.php
 * ------------------
 */
class Bien
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConnexion();
    }

    /**
     * creer()
     * Insère l'annonce. statut_moderation reste à sa valeur par défaut
     * ('en_attente') définie dans le schéma — elle n'apparaîtra en
     * recherche publique qu'une fois validée par un admin (Jour 15).
     */
    public function creer(array $donnees): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO biens
                (proprietaire_id, titre, description, type_bien, type_transaction,
                 prix, ville, quartier, latitude, longitude, superficie_m2,
                 nombre_chambres, nombre_salles_bain, meuble, eau, electricite, parking, date_publication)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        $requete->execute([
            $donnees['proprietaire_id'], $donnees['titre'], $donnees['description'],
            $donnees['type_bien'], $donnees['type_transaction'], $donnees['prix'],
            $donnees['ville'], $donnees['quartier'], $donnees['latitude'], $donnees['longitude'],
            $donnees['superficie_m2'], $donnees['nombre_chambres'], $donnees['nombre_salles_bain'], $donnees['meuble'],
            $donnees['eau'], $donnees['electricite'], $donnees['parking'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

   public function ajouterPhoto(int $bienId, string $cheminFichier, int $ordre): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO photos_biens (bien_id, chemin_fichier, ordre_affichage) VALUES (?, ?, ?)'
        );
        $requete->execute([$bienId, $cheminFichier, $ordre]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * trouverParId()
     * Renvoie le bien avec son propriétaire (nom, avatar) et ses photos
     * en une seule fois — évite plusieurs allers-retours à la BDD pour
     * afficher la page détail.
     */
   public function trouverParId(int $id): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT b.*, u.nom AS proprietaire_nom, u.photo_profil AS proprietaire_photo, u.telephone AS proprietaire_telephone
             FROM biens b
             INNER JOIN utilisateurs u ON u.id = b.proprietaire_id
             WHERE b.id = ?'
        );
        $requete->execute([$id]);
        $bien = $requete->fetch();

        if (!$bien) {
            return null;
        }

        $requetePhotos = $this->pdo->prepare(
            'SELECT id, chemin_fichier FROM photos_biens WHERE bien_id = ? ORDER BY ordre_affichage'
        );
        $requetePhotos->execute([$id]);
        $bien['photos'] = $requetePhotos->fetchAll();

        return $bien;
    }

    /**
     * appartientA()
     * Vérification anti-IDOR : ce bien appartient-il vraiment à cet
     * utilisateur ? Indispensable avant toute modification/suppression.
     */
    public function appartientA(int $bienId, int $utilisateurId): bool
    {
        $requete = $this->pdo->prepare('SELECT id FROM biens WHERE id = ? AND proprietaire_id = ?');
        $requete->execute([$bienId, $utilisateurId]);
        return $requete->fetch() !== false;
    }

    /**
     * listerTousPourProprietaire()
     * Contrairement à listerAvecStatistiquesParProprietaire() (tableau de
     * bord), celle-ci renvoie TOUTES les annonces sans agrégats, pour la
     * page "Gestion" où chaque bien doit rester modifiable/supprimable
     * quel que soit son statut de modération ou commercial.
     */
    public function listerTousPourProprietaire(int $proprietaireId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT b.*,
                    (SELECT chemin_fichier FROM photos_biens p WHERE p.bien_id = b.id ORDER BY p.ordre_affichage LIMIT 1) AS photo_principale
             FROM biens b
             WHERE b.proprietaire_id = ?
             ORDER BY b.date_publication DESC'
        );
        $requete->execute([$proprietaireId]);
        return $requete->fetchAll();
    }

    /**
     * mettreAJour()
     * Modification des champs éditables. On ne touche jamais ici à
     * proprietaire_id ni à statut_moderation (le propriétaire ne peut
     * pas s'auto-approuver) — une éventuelle re-modération après
     * modification est un choix produit qu'on laisse volontairement de
     * côté pour l'instant (l'annonce garde son statut de modération actuel).
     */
    public function mettreAJour(int $id, array $donnees): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE biens SET
                titre = ?, description = ?, type_bien = ?, type_transaction = ?,
                prix = ?, ville = ?, quartier = ?, superficie_m2 = ?,
                nombre_chambres = ?, nombre_salles_bain = ?, meuble = ?, eau = ?, electricite = ?, parking = ?
             WHERE id = ?'
        );
        $requete->execute([
            $donnees['titre'], $donnees['description'], $donnees['type_bien'], $donnees['type_transaction'],
            $donnees['prix'], $donnees['ville'], $donnees['quartier'], $donnees['superficie_m2'],
            $donnees['nombre_chambres'], $donnees['nombre_salles_bain'], $donnees['meuble'], $donnees['eau'], $donnees['electricite'], $donnees['parking'],
            $id,
        ]);
    }

    /**
     * changerStatutCommercial()
     * 'disponible' / 'loue' / 'vendu' — distinct du statut de MODÉRATION.
     * C'est ce qui permet au propriétaire de marquer un bien comme loué
     * ou vendu sans passer par l'admin.
     */
    public function changerStatutCommercial(int $id, string $statut): void
    {
        $requete = $this->pdo->prepare('UPDATE biens SET statut = ? WHERE id = ?');
        $requete->execute([$statut, $id]);
    }

    public function photosDuBien(int $bienId): array
    {
        $requete = $this->pdo->prepare('SELECT chemin_fichier FROM photos_biens WHERE bien_id = ?');
        $requete->execute([$bienId]);
        return $requete->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * supprimer()
     * Les tables liées (photos_biens, favoris, messages, disponibilites,
     * reservations_visites, avis, vues_annonces) ont toutes une clé
     * étrangère ON DELETE CASCADE vers biens.id (voir le schéma du
     * Jour 1) — elles sont donc nettoyées automatiquement par MySQL,
     * pas besoin de le faire manuellement ici.
     */
    public function supprimer(int $id): void
    {
        $requete = $this->pdo->prepare('DELETE FROM biens WHERE id = ?');
        $requete->execute([$id]);
    }

    /**
     * enregistrerVue()
     * Journalise une consultation de l'annonce — alimente les statistiques
     * du tableau de bord (Jour 14).
     */
    public function enregistrerVue(int $bienId, string $ipVisiteur): void
    {
        $requete = $this->pdo->prepare('INSERT INTO vues_annonces (bien_id, date_vue, ip_visiteur) VALUES (?, NOW(), ?)');
        $requete->execute([$bienId, $ipVisiteur]);
    }

    /**
     * listerAvecStatistiquesParProprietaire()
     * Chaque annonce du propriétaire avec son nombre de vues et de
     * favoris. LEFT JOIN (et non INNER JOIN) : une annonce sans aucune
     * vue ni favori doit quand même apparaître, avec 0 partout — un
     * INNER JOIN la ferait disparaître de la liste.
     */
    public function listerAvecStatistiquesParProprietaire(int $proprietaireId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT b.*,
                    COUNT(DISTINCT v.id) AS nb_vues,
                    COUNT(DISTINCT f.id) AS nb_favoris
             FROM biens b
             LEFT JOIN vues_annonces v ON v.bien_id = b.id
             LEFT JOIN favoris f ON f.bien_id = b.id
             WHERE b.proprietaire_id = ?
             GROUP BY b.id
             ORDER BY b.date_publication DESC'
        );
        $requete->execute([$proprietaireId]);
        return $requete->fetchAll();
    }

    /**
     * vuesParJourPourProprietaire()
     * Total des vues, TOUS biens confondus, groupées par jour sur les
     * $jours derniers jours — alimente le graphique en courbe du
     * tableau de bord.
     */
    public function vuesParJourPourProprietaire(int $proprietaireId, int $jours = 30): array
    {
        $jours = max(1, min(90, $jours));
        $requete = $this->pdo->prepare(
            'SELECT DATE(v.date_vue) AS jour, COUNT(*) AS total
             FROM vues_annonces v
             INNER JOIN biens b ON b.id = v.bien_id
             WHERE b.proprietaire_id = ? AND v.date_vue >= DATE_SUB(CURDATE(), INTERVAL ' . $jours . ' DAY)
             GROUP BY DATE(v.date_vue)
             ORDER BY jour ASC'
        );
        $requete->execute([$proprietaireId]);
        return $requete->fetchAll();
    }

    /**
     * construireConditions()
     * Construit dynamiquement la clause WHERE et le tableau des valeurs
     * séparément — jamais les valeurs directement dans la chaîne SQL —
     * pour rester protégé contre l'injection SQL même avec un nombre
     * variable de filtres actifs.
     *
     * 'approuve' et 'disponible' sont TOUJOURS imposés : la recherche
     * publique ne doit jamais montrer une annonce en attente de
     * modération ou déjà louée/vendue, même si un filtre malicieux
     * tentait de le contourner (impossible ici puisque ces deux
     * conditions ne dépendent d'aucune entrée utilisateur).
     */
    private function construireConditions(array $filtres): array
    {
        $conditions = [
            "b.statut_moderation = 'approuve'",
            "b.statut = 'disponible'"
        ];
        $valeurs = [];

        if (!empty($filtres['ville'])) {
            $conditions[] = 'b.ville LIKE ?';
            $valeurs[] = '%' . $filtres['ville'] . '%';
        }
        if (!empty($filtres['quartier'])) {
            $conditions[] = 'b.quartier LIKE ?';
            $valeurs[] = '%' . $filtres['quartier'] . '%';
        }
        if (!empty($filtres['type_bien'])) {
            $conditions[] = 'b.type_bien = ?';
            $valeurs[] = $filtres['type_bien'];
        }
        if (!empty($filtres['type_transaction'])) {
            $conditions[] = 'b.type_transaction = ?';
            $valeurs[] = $filtres['type_transaction'];
        }
        if ($filtres['prix_min'] !== '' && is_numeric($filtres['prix_min'])) {
            $conditions[] = 'b.prix >= ?';
            $valeurs[] = (float) $filtres['prix_min'];
        }
        if ($filtres['prix_max'] !== '' && is_numeric($filtres['prix_max'])) {
            $conditions[] = 'b.prix <= ?';
            $valeurs[] = (float) $filtres['prix_max'];
        }
        if ($filtres['superficie_min'] !== '' && is_numeric($filtres['superficie_min'])) {
            $conditions[] = 'b.superficie_m2 >= ?';
            $valeurs[] = (float) $filtres['superficie_min'];
        }
        if ($filtres['superficie_max'] !== '' && is_numeric($filtres['superficie_max'])) {
            $conditions[] = 'b.superficie_m2 <= ?';
            $valeurs[] = (float) $filtres['superficie_max'];
        }
        if ($filtres['nombre_chambres'] !== '' && is_numeric($filtres['nombre_chambres'])) {
            $conditions[] = 'b.nombre_chambres >= ?';
            $valeurs[] = (int) $filtres['nombre_chambres'];
        }
        if ($filtres['nombre_salles_bain'] !== '' && is_numeric($filtres['nombre_salles_bain'])) {
            $conditions[] = 'b.nombre_salles_bain >= ?';
            $valeurs[] = (int) $filtres['nombre_salles_bain'];
        }

        foreach (['meuble', 'eau', 'electricite', 'parking'] as $equipement) {
            if (isset($filtres[$equipement]) && $filtres[$equipement] !== '') {
                $conditions[] = 'b.' . $equipement . ' = ?';
                $valeurs[] = (int) $filtres[$equipement];
            }
        }

        // Recherche géographique : rayon maximum autour du point GPS.
        // Les valeurs GPS sont toujours des paramètres SQL, jamais concaténées.
        if (
            $filtres['latitude'] !== '' && is_numeric($filtres['latitude']) &&
            $filtres['longitude'] !== '' && is_numeric($filtres['longitude']) &&
            $filtres['rayon'] !== '' && is_numeric($filtres['rayon'])
        ) {
            $latitude = max(-90, min(90, (float) $filtres['latitude']));
            $longitude = max(-180, min(180, (float) $filtres['longitude']));
            $rayon = max(0.1, min(50, (float) $filtres['rayon']));

            $conditions[] = '(b.latitude IS NOT NULL AND b.longitude IS NOT NULL AND
                (6371 * ACOS(
                    LEAST(1, GREATEST(-1,
                        COS(RADIANS(?)) * COS(RADIANS(b.latitude)) *
                        COS(RADIANS(b.longitude) - RADIANS(?)) +
                        SIN(RADIANS(?)) * SIN(RADIANS(b.latitude))
                    ))
                )) <= ?)';
            $valeurs[] = $latitude;
            $valeurs[] = $longitude;
            $valeurs[] = $latitude;
            $valeurs[] = $rayon;
        }

        return [$conditions, $valeurs];
    }

    /**
     * rechercher()
     * Recherche publique paginée avec filtres avancés, rayon GPS et tris.
     */
    public function rechercher(array $filtres, string $tri = 'recent', int $page = 1, int $parPage = 12): array
    {
        [$conditions, $valeurs] = $this->construireConditions($filtres);

        $tris = [
            'recent' => 'b.date_publication DESC',
            'prix_asc' => 'b.prix ASC',
            'prix_desc' => 'b.prix DESC',
            'vues_desc' => 'nb_vues DESC, b.date_publication DESC',
            'recommande' => '(COUNT(DISTINCT v.id) * 0.6 + COUNT(DISTINCT f.id) * 1.2 + GREATEST(0, 30 - DATEDIFF(CURDATE(), DATE(b.date_publication))) * 0.5) DESC, b.date_publication DESC',
        ];
        $ordreTri = $tris[$tri] ?? $tris['recent'];

        $page = max(1, $page);
        $offset = ($page - 1) * $parPage;

        $sql = 'SELECT b.id, b.titre, b.prix, b.ville, b.quartier, b.type_bien,
                       b.type_transaction, b.latitude, b.longitude,
                       b.superficie_m2, b.nombre_chambres, b.nombre_salles_bain,
                       b.meuble, b.eau, b.electricite, b.parking, b.date_publication,
                       COUNT(DISTINCT v.id) AS nb_vues,
                       COUNT(DISTINCT f.id) AS nb_favoris,
                       (SELECT chemin_fichier FROM photos_biens p
                        WHERE p.bien_id = b.id ORDER BY p.ordre_affichage LIMIT 1) AS photo_principale
                FROM biens b
                LEFT JOIN vues_annonces v ON v.bien_id = b.id
                LEFT JOIN favoris f ON f.bien_id = b.id
                WHERE ' . implode(' AND ', $conditions) . '
                GROUP BY b.id
                ORDER BY ' . $ordreTri . '
                LIMIT ' . (int) $parPage . ' OFFSET ' . (int) $offset;

        $requete = $this->pdo->prepare($sql);
        $requete->execute($valeurs);
        return $requete->fetchAll();
    }

    /**
     * compterResultats()
     * Compte les résultats avec exactement les mêmes filtres que la recherche.
     */
    public function compterResultats(array $filtres): int
    {
        [$conditions, $valeurs] = $this->construireConditions($filtres);
        $sql = 'SELECT COUNT(*) FROM biens b WHERE ' . implode(' AND ', $conditions);

        $requete = $this->pdo->prepare($sql);
        $requete->execute($valeurs);
        return (int) $requete->fetchColumn();
    }

    /**
     * listerParStatutModeration()
     * Utilisée par l'espace admin (Jour 15) pour afficher les annonces
     * en attente de validation, avec le nom du propriétaire pour contexte.
     */
    public function listerParStatutModeration(string $statut): array
    {
        $requete = $this->pdo->prepare(
            'SELECT b.*, u.nom AS proprietaire_nom, u.email AS proprietaire_email,
                    (SELECT chemin_fichier FROM photos_biens p WHERE p.bien_id = b.id ORDER BY p.ordre_affichage LIMIT 1) AS photo_principale
             FROM biens b
             INNER JOIN utilisateurs u ON u.id = b.proprietaire_id
             WHERE b.statut_moderation = ?
             ORDER BY b.date_publication ASC'
        );
        $requete->execute([$statut]);
        return $requete->fetchAll();
    }

    /**
     * changerStatutModeration()
     * Seule façon d'approuver ou rejeter une annonce — jamais de
     * modification directe en base, toujours par cette méthode qui
     * garantit qu'une seule des 3 valeurs autorisées de l'ENUM est écrite.
     */
    public function changerStatutModeration(int $id, string $statut): void
    {
        $requete = $this->pdo->prepare('UPDATE biens SET statut_moderation = ? WHERE id = ?');
        $requete->execute([$statut, $id]);
    }

    public function compterParStatutModeration(string $statut): int
    {
        $requete = $this->pdo->prepare('SELECT COUNT(*) FROM biens WHERE statut_moderation = ?');
        $requete->execute([$statut]);
        return (int) $requete->fetchColumn();
    }

    /**
     * compterVillesDistinctes()
     * Utilisée pour la barre de confiance de l'accueil ("X villes
     * couvertes") — ne compte que les villes ayant au moins une annonce
     * réellement publique (approuvée et disponible).
     */
    public function compterVillesDistinctes(): int
    {
        $requete = $this->pdo->query(
            "SELECT COUNT(DISTINCT ville) FROM biens WHERE statut_moderation = 'approuve' AND statut = 'disponible'"
        );
        return (int) $requete->fetchColumn();
    }

    public function suggestionsVilles(string $recherche): array
    {
        $requete = $this->pdo->prepare(
            "SELECT DISTINCT ville FROM biens
             WHERE statut_moderation = 'approuve' AND ville LIKE ?
             ORDER BY ville LIMIT 8"
        );
        $requete->execute(['%' . $recherche . '%']);
        return $requete->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * suggestionsQuartiers()
     * Ne renvoie que les quartiers RÉELLEMENT présents en base pour la
     * ville donnée — comme demandé, on choisit parmi les quartiers déjà
     * existants plutôt que de laisser un champ libre.
     */
    public function suggestionsQuartiers(string $ville): array
    {
        $requete = $this->pdo->prepare(
            "SELECT DISTINCT quartier FROM biens
             WHERE statut_moderation = 'approuve' AND ville = ? AND quartier IS NOT NULL AND quartier != ''
             ORDER BY quartier LIMIT 30"
        );
        $requete->execute([$ville]);
        return $requete->fetchAll(PDO::FETCH_COLUMN);
    }
    /**
     * trouverSimilaires()
     * Suggestions affichées sur la page détail : même type de bien,
     * fourchette de prix proche (±30%), en priorité dans la même ville.
     * Si la ville ne donne pas assez de résultats, on élargit à toutes
     * les villes plutôt que d'afficher une section vide ou incomplète.
     */
    public function trouverSimilaires(array $bien, int $limite = 4): array
    {
        $prixMin = (float) $bien['prix'] * 0.7;
        $prixMax = (float) $bien['prix'] * 1.3;

        $sqlBase = 'SELECT b.id, b.titre, b.prix, b.ville, b.type_bien,
                           (SELECT chemin_fichier FROM photos_biens p
                            WHERE p.bien_id = b.id ORDER BY p.ordre_affichage LIMIT 1) AS photo_principale
                    FROM biens b
                    WHERE b.statut_moderation = \'approuve\'
                      AND b.statut = \'disponible\'
                      AND b.id != ?
                      AND b.type_bien = ?
                      AND b.prix BETWEEN ? AND ?';

        // --- Tentative 1 : même ville ---
        $requete = $this->pdo->prepare(
            $sqlBase . ' AND b.ville = ? ORDER BY RAND() LIMIT ' . (int) $limite
        );
        $requete->execute([$bien['id'], $bien['type_bien'], $prixMin, $prixMax, $bien['ville']]);
        $resultats = $requete->fetchAll();

        // --- Repli : pas assez de résultats dans la même ville, on élargit ---
        if (count($resultats) < $limite) {
            $idsExclus = array_merge([(int) $bien['id']], array_column($resultats, 'id'));
            $placeholders = implode(',', array_fill(0, count($idsExclus), '?'));

            $requete = $this->pdo->prepare(
                'SELECT b.id, b.titre, b.prix, b.ville, b.type_bien,
                        (SELECT chemin_fichier FROM photos_biens p
                         WHERE p.bien_id = b.id ORDER BY p.ordre_affichage LIMIT 1) AS photo_principale
                 FROM biens b
                 WHERE b.statut_moderation = \'approuve\'
                   AND b.statut = \'disponible\'
                   AND b.type_bien = ?
                   AND b.prix BETWEEN ? AND ?
                   AND b.id NOT IN (' . $placeholders . ')
                 ORDER BY RAND()
                 LIMIT ' . (int) ($limite - count($resultats))
            );
            $requete->execute(array_merge([$bien['type_bien'], $prixMin, $prixMax], $idsExclus));
            $resultats = array_merge($resultats, $requete->fetchAll());
        }

        return $resultats;
    }
    /**
     * trouverPhoto()
     * Vérification anti-IDOR : cette photo appartient-elle vraiment à
     * CE bien ? Indispensable avant toute suppression individuelle.
     */
    public function trouverPhoto(int $photoId, int $bienId): ?array
    {
        $requete = $this->pdo->prepare('SELECT * FROM photos_biens WHERE id = ? AND bien_id = ?');
        $requete->execute([$photoId, $bienId]);
        return $requete->fetch() ?: null;
    }

    public function supprimerPhotoParId(int $photoId): void
    {
        $requete = $this->pdo->prepare('DELETE FROM photos_biens WHERE id = ?');
        $requete->execute([$photoId]);
    }

    public function compterPhotos(int $bienId): int
    {
        $requete = $this->pdo->prepare('SELECT COUNT(*) FROM photos_biens WHERE bien_id = ?');
        $requete->execute([$bienId]);
        return (int) $requete->fetchColumn();
    }

    /**
     * prochainOrdrePhoto()
     * Place une photo ajoutée après coup à la SUITE des photos
     * existantes (jamais en première position, qui reste la photo
     * principale déjà choisie à la publication).
     */
    public function prochainOrdrePhoto(int $bienId): int
    {
        $requete = $this->pdo->prepare('SELECT COALESCE(MAX(ordre_affichage), -1) + 1 FROM photos_biens WHERE bien_id = ?');
        $requete->execute([$bienId]);
        return (int) $requete->fetchColumn();
    }


}
