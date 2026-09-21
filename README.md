# TTM  Trouve Ton Milieu

Plateforme immobilière camerounaise permettant à des propriétaires de publier des annonces (location ou vente) et à des locataires/acheteurs de rechercher, comparer, contacter et visiter des biens — le tout avec modération, messagerie intégrée, avis, favoris, alertes et un espace d'administration complet.

---

## Sommaire

1. [Présentation](#1-présentation)
2. [Rôles et fonctionnalités](#2-rôles-et-fonctionnalités)
3. [Stack technique](#3-stack-technique)
4. [Architecture du projet](#4-architecture-du-projet)
5. [Sécurité](#5-sécurité)
6. [Design system](#6-design-system)
7. [Installation en local (Laragon)](#7-installation-en-local-laragon)
8. [Déploiement en production](#8-déploiement-en-production)
9. [Base de données](#9-base-de-données)
10. [Limites connues et pistes d'amélioration](#10-limites-connues-et-pistes-damélioration)

---

## 1. Présentation

TTM (Trouve Ton Milieu) est une application web PHP « maison » (sans framework externe type Laravel/Symfony) construite pour répondre à un besoin concret : permettre à n'importe qui au Cameroun de publier ou trouver un logement (chambre, studio, appartement, maison) à louer ou à vendre, avec un système de confiance intégré (modération des annonces, avis, signalements) et une expérience mobile-first.

Le projet a été développé de façon incrémentale, « jour par jour », chaque fonctionnalité s'appuyant sur les précédentes sans jamais casser l'existant. Les fichiers de migration SQL portent cette numérotation (`jour1-schema.sql`, `jour24-migration.sql`, etc.) et font office d'historique daté du schéma de base de données.

---

## 2. Rôles et fonctionnalités

L'application distingue 3 rôles : `locataire`, `proprietaire` et `admin`.

### a) Visiteur (non connecté)
- Recherche de biens avec filtres complets : ville, quartier, type de bien, type de transaction, budget, **nombre de chambres**, **superficie**, et **rayon géographique autour d'un point choisi sur la carte**
- Tri par pertinence, prix croissant/décroissant, ou **distance** lorsque la recherche par rayon est active
- Aperçu des annonces en page d'accueil (le détail complet nécessite un compte)
- Consultation du profil public d'un propriétaire (annonces, note moyenne, temps de réponse moyen)
- Inscription / connexion / réinitialisation de mot de passe

### b) Locataire
Tout ce qui précède, plus :
- Consultation de la fiche complète d'un bien (photos en lightbox, équipements, localisation sur carte, avis)
- **Comparateur de biens** : sélection jusqu'à 4 annonces depuis la recherche ou la fiche détail, tableau comparatif avec mise en évidence automatique de la meilleure valeur par critère (prix le plus bas, plus grande superficie, plus de chambres)
- **Contact WhatsApp direct** du propriétaire, avec message pré-rempli (canal prioritaire), complété par la messagerie interne TTM
- Messagerie intégrée (accusé de lecture, mise à jour en temps quasi-réel par sondage)
- Réservation d'une visite sur un créneau disponible, avec **relais WhatsApp proposé juste après** pour accélérer la confirmation
- Annulation d'une demande de visite en attente ou déjà acceptée
- Ajout de biens en favoris
- Création d'alertes de recherche sauvegardées (notification automatique dès qu'une nouvelle annonce correspondante est approuvée)
- Dépôt d'un avis noté (1 à 5 étoiles) après une visite acceptée
- Signalement d'une annonce suspecte (motif + description)
- **Tableau de bord personnel** : visites en cours, favoris, alertes actives et messages récents réunis en une seule vue
- Suppression définitive de son compte (voir [Sécurité](#5-sécurité))
- **Suggestions de biens similaires** affichées au bas de chaque fiche

### c) Propriétaire
Tout ce qui précède (sauf réserver une visite sur son propre bien), plus :
- Assistant de publication en 5 étapes (infos → équipements → localisation → photos → récapitulatif), avec sauvegarde de session à chaque étape pour ne rien perdre en cas d'abandon
- Upload de photos par glisser-déposer, réordonnancement, compression automatique
- **Gestion des photos après publication** (ajout/suppression sans repasser par l'assistant complet, minimum 1 photo conservée)
- Modification d'une annonce déjà publiée (repasse automatiquement en modération, voir Sécurité)
- Gestion des disponibilités (calendrier de créneaux bloqués, avec **détection de chevauchement de périodes**)
- Acceptation/refus des demandes de visite : l'acceptation **bloque automatiquement le créneau** dans le calendrier et **refuse les demandes concurrentes** sur la même date ; une annulation ou un refus ultérieur **libère automatiquement** le créneau
- Réponse publique aux avis laissés sur ses biens
- Tableau de bord avec statistiques (vues, biens actifs, favoris, avis reçus)
- Badge « temps de réponse moyen » calculé automatiquement et affiché sur son profil public (à partir de 3 échanges répondus sur les 6 derniers mois)

### d) Administrateur
- Modération des annonces (approuver / rejeter), avec notification automatique au propriétaire et déclenchement des alertes de recherche correspondantes
- Gestion des signalements (traiter / rejeter)
- Gestion des comptes utilisateurs (suspendre / réactiver)
- **Journal des accès refusés** : trace chaque tentative d'accès à une page admin par un compte non autorisé (IP, route visée, utilisateur si connu)
- Export SQL complet de la base en un clic (sauvegarde manuelle, utile sur un hébergement mutualisé sans accès `cron`/shell)
- Nettoyage des fichiers photo orphelins (uploads interrompus, non rattachés à une annonce)

### Notifications
Chaque événement important déclenche **à la fois** une notification in-app (cloche) **et un email** — pour que l'utilisateur soit informé même déconnecté :
- Nouveau message reçu
- Nouvelle demande de visite / visite acceptée / refusée / annulée
- Nouvel avis reçu
- Annonce validée ou rejetée par la modération
- Nouvelle annonce correspondant à une alerte sauvegardée

---

## 3. Stack technique

| Composant | Choix | Pourquoi |
|---|---|---|
| Langage serveur | **PHP 8** (natif, POO, sans framework) | Contrôle total, hébergement mutualisé compatible |
| Base de données | **MySQL / MariaDB** via **PDO** (requêtes préparées partout) | Standard, disponible sur tout hébergement |
| Frontend | **Bootstrap 5.3** + CSS custom (design system maison) | Rapide à mettre en œuvre, personnalisable |
| Cartes | **Leaflet.js** + **OpenStreetMap** | Gratuit, sans clé API (contrairement à Google Maps) |
| JavaScript | Vanilla JS (aucun framework, aucune dépendance de build) | Pas de build step, simplicité de déploiement |
| Emails | `mail()` PHP natif (simulation en fichier local en développement) | Compatible hébergement mutualisé gratuit |
| Hébergement | Laragon (local) → InfinityFree (production) | Gratuit, accessible sans budget serveur |

---

## 4. Architecture du projet

Le projet suit un pattern **MVC fait maison**, avec un routeur minimaliste basé sur des expressions régulières.

```
htdocs/
├── config/
│   ├── environnement.php       # ENVIRONNEMENT = 'developpement' | 'production'
│   └── database.php            # Connexion PDO
├── controllers/                # Un controller par domaine fonctionnel
│   ├── AuthController.php
│   ├── BienController.php      # + assistant de publication, recherche, comparateur
│   ├── AdminController.php
│   ├── MessageController.php
│   ├── ProfilController.php
│   ├── ReservationController.php
│   ├── DisponibiliteController.php
│   ├── TableauBordController.php   # branche selon le rôle (propriétaire / locataire)
│   ├── AlerteController.php
│   ├── FavoriController.php
│   ├── AvisController.php
│   ├── SignalementController.php
│   └── NotificationController.php
├── models/                     # Un modèle par table/entité (PDO direct, pas d'ORM)
│   ├── Utilisateur.php
│   ├── Bien.php
│   ├── Message.php
│   ├── Notification.php
│   ├── Disponibilite.php
│   ├── ReservationVisite.php
│   ├── Avis.php
│   ├── Favori.php
│   ├── RechercheSauvegardee.php
│   ├── Signalement.php
│   ├── AccesAdminRefuse.php
│   └── TentativeIp.php
├── views/                      # Templates PHP, découpés par domaine
│   ├── layouts/                # header.php (+ menu plein écran) / footer.php
│   ├── auth/
│   ├── pages/                  # accueil
│   ├── biens/
│   │   └── creation/           # Les 5 étapes de l'assistant de publication
│   ├── tableau-bord/           # index.php (propriétaire) / locataire.php
│   ├── admin/
│   ├── profil/
│   ├── messages/
│   └── erreurs/
├── includes/
│   ├── Router.php              # Routeur maison (regex sur les URLs)
│   ├── helpers.php             # url(), nettoyer(), CSRF, permissions, urlWhatsApp(), compression image...
│   └── Mailer.php              # Envoi (ou simulation) d'emails transactionnels
├── uploads/                    # Fichiers uploadés — À LA RACINE, pas dans public/
│   ├── biens/
│   └── avatars/
├── logs/
│   └── emails-simules/         # Emails "envoyés" en développement (fichiers .txt)
├── css/
│   └── design-system.css
├── js/
│   └── comparateur.js          # Logique partagée du comparateur (localStorage)
└── index.php                   # Point d'entrée UNIQUE de l'application
```

### ⚠️ Deux pièges d'architecture à connaître

**1. Le dossier `uploads/` est à la racine**, pas dans `public/`. Les constantes `DOSSIER_UPLOAD` des controllers pointent donc vers `__DIR__ . '/../uploads/...'`.

**2. Toujours utiliser `cheminBase()` pour les ressources statiques.** Un chemin relatif (`../css/...`) se résout différemment selon la profondeur de l'URL et casse silencieusement le CSS sur les pages imbriquées (`/biens/detail/4`, `/biens/4/messages/2`). La forme correcte est :
```php
<link rel="stylesheet" href="<?= cheminBase() ?>/css/design-system.css?v=8">
```
Le paramètre `?v=` est incrémenté à chaque modification du CSS pour forcer le rechargement chez tous les visiteurs (cache-busting).

### Le routeur

Toutes les requêtes passent par `index.php`, qui déclare les routes puis délègue à `Router::dispatch()` :

```php
$router->get('biens/detail/{id}', 'BienController', 'detail');
$router->post('biens/{id}/modifier', 'BienController', 'traiterModification');
```

Les `{id}` sont automatiquement convertis en `(\d+)` (entiers uniquement), les autres `{paramètre}` en `([a-zA-Z0-9]+)` (utile pour les tokens de réinitialisation de mot de passe).

> ⚠️ **Ordre des routes** : les routes littérales doivent être déclarées **avant** les routes à paramètre du même préfixe. Exemple : `biens/comparateur` doit précéder `biens/{id}/...`, sinon le routeur interprète « comparateur » comme un identifiant.

### La fonction `url()`

Jamais d'URL écrite en dur dans les vues. `url('biens/detail', [12])` détecte automatiquement si le site est servi depuis la racine ou un sous-dossier. `urlAbsolue()` fait la même chose avec le domaine complet (nécessaire dans les emails).

---

## 5. Sécurité

### Authentification
- Mots de passe hachés avec `password_hash()` (bcrypt)
- Verrouillage de compte après 5 échecs de connexion (15 minutes)
- Blocage anti-brute-force **par adresse IP** en complément (20 tentatives, 15 minutes) — protège contre un attaquant qui viserait plusieurs comptes différents sans jamais déclencher le verrou individuel
- Régénération de l'identifiant de session à la connexion (anti fixation de session)
- Réinitialisation de mot de passe par token à usage unique, expirant après 1 heure
- Messages d'erreur volontairement génériques (« email ou mot de passe incorrect ») pour empêcher l'énumération de comptes existants — y compris pour les comptes supprimés

### Autorisation
- Vérification systématique de la propriété d'une ressource avant modification/suppression (impossible de modifier l'annonce, le profil, la conversation ou la visite de quelqu'un d'autre en changeant un ID dans l'URL)
- Journal des tentatives d'accès admin refusées, consultable par les administrateurs

### Contenu
- Protection CSRF sur tous les formulaires (token par session, vérifié avec `hash_equals()`)
- Échappement systématique en sortie (`htmlspecialchars`) pour prévenir les failles XSS
- Vérification du **type MIME réel** des fichiers uploadés (pas seulement l'extension déclarée), noms de fichiers régénérés aléatoirement, vérification post-écriture que le fichier existe réellement
- **Toute modification d'une annonce (texte ou photos) la repasse automatiquement en modération** — empêche un propriétaire de faire approuver une annonce propre puis d'en changer le contenu sans contrôle
- Coordonnées GPS validées côté serveur (bornes lat/lng), jamais acceptées aveuglément depuis un POST

### Infrastructure
- En-têtes HTTP de sécurité (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- En-têtes anti-cache sur les pages dynamiques personnalisées (`Cache-Control: no-store`)
- Cookies de session `httponly` + `samesite=Lax` (+ `secure` en production)
- Requêtes SQL exclusivement préparées (PDO), aucune concaténation de valeurs utilisateur

### Confidentialité (RGPD-friendly)
- Suppression de compte = **anonymisation** (pas de suppression brutale) : les données personnelles sont effacées et le compte devient définitivement inutilisable, mais les messages/avis échangés avec d'autres utilisateurs restent visibles pour eux (affichant « Compte supprimé »), pour ne pas casser leur historique
- Les annonces d'un compte supprimé sont réellement supprimées, avec leurs photos sur le disque
- Favoris, alertes et notifications supprimés ; visites actives automatiquement annulées

---

## 6. Design system

Palette resserrée autour d'une couleur dominante et d'un seul accent utilisé avec parcimonie, plutôt que de multiplier les couleurs concurrentes.

| Rôle | Clair | Sombre | Usage |
|---|---|---|---|
| Fond | `#FBF7EF` (sable chaud) | `#0E1912` | Arrière-plan |
| Encre | `#16231B` | `#F4F1E8` | Texte |
| Forêt (primaire) | `#0B4D34` | `#3FA179` | Actions principales, liens |
| Terre cuite (accent) | `#BD4B1F` | `#E0703A` | **Un seul usage fort par écran** |
| Or | `#C99A34` | `#C99A34` | Traits fins, étoiles d'avis, focus clavier |

- **Typographies** : Fraunces (titres, empattée, du caractère) + Work Sans (corps de texte)
- **Mode sombre** intégré, persistant via `localStorage`, appliqué dans le `<head>` pour éviter tout flash au chargement
- **Menu plein écran** sur mobile (panneau opaque, hamburger qui se transforme en croix, fermeture au clavier/Échap/clic sur lien, focus piégé)
- **Signature visuelle** : le trait qui glisse sous l'élément actif, réutilisé comme langage commun (navigation, onglets locataire/propriétaire, catégories)
- Composants maison : squelettes de chargement animés pendant les recherches AJAX, lightbox photo avec navigation clavier et swipe tactile, modale de confirmation stylée remplaçant `window.confirm()`, barre flottante du comparateur
- Accessibilité : focus clavier toujours visible, lien d'évitement, respect de `prefers-reduced-motion`

---

## 7. Installation en local (Laragon)

1. Copier le projet dans le dossier `www` de Laragon.
2. Créer la base de données dans HeidiSQL (ou phpMyAdmin), puis exécuter **dans l'ordre** tous les fichiers `jourX-schema.sql` / `jourX-migration.sql`.
3. Vérifier `config/database.php` (identifiants MySQL locaux).
4. Vérifier que `ENVIRONNEMENT` vaut `'developpement'` dans `config/environnement.php` — les emails transactionnels s'écrivent alors dans `logs/emails-simules/` au lieu d'être réellement envoyés (Laragon n'a pas de serveur SMTP configuré par défaut).
5. Démarrer Apache/MySQL depuis Laragon, accéder au site via l'URL configurée.
6. Vérifier les droits d'écriture sur `uploads/biens/`, `uploads/avatars/` et `logs/`.

---

## 8. Déploiement en production

Hébergement actuel : **InfinityFree** (mutualisé gratuit).

- `ENVIRONNEMENT` doit valoir `'production'` → active `mail()` réel, cookies `secure`.
- **Rappel d'architecture** : `uploads/` est un dossier **à la racine de `htdocs/`**, frère de `controllers/` et `public/` — vérifier les permissions d'écriture (755/775) sur `uploads/biens/` et `uploads/avatars/`.
- Après toute modification du CSS, **incrémenter le `?v=`** du lien dans `header.php` — sinon les navigateurs (surtout mobiles) continuent de servir l'ancienne version depuis leur cache.
- Limite connue : `mail()` sur un hébergement mutualisé gratuit part fréquemment en spam ou est bloqué par les gros fournisseurs (Gmail, Outlook). Pour un vrai lancement, migrer vers un service transactionnel dédié (Brevo, Mailjet…) est recommandé.
- Aucune tâche planifiée (`cron`) disponible sur ce plan → la sauvegarde de base de données se fait manuellement via le bouton export SQL de l'espace admin.

---

## 9. Base de données

Tables principales (MySQL/InnoDB, clés étrangères actives) :

| Table | Rôle |
|---|---|
| `utilisateurs` | Comptes (locataire/propriétaire/admin), sécurité de connexion, statut (`actif`/`suspendu`/`supprime`), anonymisation |
| `biens` | Annonces, avec statut de modération ET statut commercial distincts |
| `photos_biens` | Photos liées à une annonce, avec ordre d'affichage |
| `disponibilites` | Périodes bloquées par un propriétaire (vérification anti-chevauchement) |
| `reservations_visites` | Demandes de visite, liées à une disponibilité auto-créée si acceptées (`disponibilite_id`) |
| `messages` | Messagerie, un fil = (bien, interlocuteur) |
| `notifications` | Notifications in-app (cloche), doublées d'un email |
| `avis` | Avis notés + réponse du propriétaire |
| `favoris` | Biens mis en favoris par un locataire |
| `recherches_sauvegardees` | Alertes de recherche |
| `signalements` | Signalements d'annonces |
| `tentatives_ip` | Anti-brute-force par IP |
| `acces_admin_refuses` | Journal de sécurité (accès admin refusés) |

> 💡 Le détail complet des colonnes se trouve dans les fichiers `jourX-schema.sql` / `jourX-migration.sql`, qui font office d'historique de migration daté.

---

## 10. Limites connues et pistes d'amélioration

- **Fiabilité des emails** : `mail()` sur hébergement mutualisé gratuit atterrit souvent en spam. Une intégration Brevo/Mailjet réglerait la question sans changer l'architecture.
- **Pas de vérification d'identité (KYC)** pour les propriétaires - un badge « propriétaire vérifié » renforcerait la confiance, d'autant que le signalement « arnaque suspectée » existe déjà.
- **Pas de pièces jointes** dans la messagerie (texte seul).
- **Pas de contrat/bail téléchargeable** ni de gestion de paiement en ligne (Mobile Money serait le canal adapté au marché local).
- **Pas d'export PDF** d'une annonce.
- **Pas d'historique de prix** sur une annonce.
- **Pas de multilingue** (tout est en français, câblé en dur dans les vues).
- **Emails de messagerie non groupés** : une conversation active génère un email par message reçu.

---

