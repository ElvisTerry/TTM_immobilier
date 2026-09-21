<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titrePage) ? nettoyer($titrePage) . ' - TTM' : 'TTM — Trouve Ton Milieu' ?></title>

    <!-- Polices du design system : Sora pour les titres (impact visuel
         fort, façon "app"), Work Sans pour le corps de texte. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= cheminBase() ?>/css/design-system.css?v=7">

    <?php if (!empty($inclureLeaflet)): ?>
        <!-- Leaflet + OpenStreetMap : carte interactive gratuite, sans clé API
             (contrairement à Google Maps), chargée seulement sur les pages
             qui en ont besoin pour ne pas alourdir le reste du site.
             Le script JS est chargé ICI (dans le head), et non en bas de
             page : le code de la carte s'exécute dès le contenu de la vue,
             donc Leaflet doit déjà être disponible AVANT — sinon "L is not
             defined" et rien ne fonctionne silencieusement. -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>
    <script>
        // Appliqué ici (dans le <head>, avant tout affichage) plutôt qu'en
        // bas de page : évite un "flash" de thème clair pendant une
        // fraction de seconde avant que le mode sombre ne se déclenche.
        if (localStorage.getItem('theme') === 'sombre') {
            document.documentElement.setAttribute('data-theme', 'sombre');
        }
    </script>
</head>
<body>

<!-- Lien d'évitement : invisible tant qu'il n'a pas le focus clavier,
     permet à un utilisateur naviguant au Tab (ou lecteur d'écran) de
     sauter directement au contenu sans devoir traverser tout le menu
     à chaque page — une bonne pratique d'accessibilité de base. -->
<a href="#contenu-principal" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 p-2 bg-white rounded shadow"
   style="z-index: 1300;">Aller au contenu principal</a>

<header class="barre-entete sticky-top">
    <div class="barre-entete-interieure">
        <a class="navbar-brand logo-marque" href="<?= url('') ?>">TTM</a>

        <div class="d-flex align-items-center gap-2">
            <?php if (estConnecte()): ?>
                <?php
                /**
                 * Compteurs calculés ici, dans le gabarit partagé, car ils
                 * doivent apparaître sur TOUTE page tant qu'on est connecté.
                 */
                require_once __DIR__ . '/../../models/Message.php';
                $messagesNonLus = (new Message())->compterNonLus((int) $_SESSION['utilisateur_id']);

                require_once __DIR__ . '/../../models/Notification.php';
                $notificationsNonLues = (new Notification())->compterNonLues((int) $_SESSION['utilisateur_id']);
                ?>
                <!-- Cloche de notifications : reste dans la barre toujours
                     visible (pas dans le menu plein écran) puisqu'elle a
                     son propre panneau déroulant et doit rester accessible
                     rapidement, comme les messages. -->
                <div class="dropdown">
                    <button class="bouton-theme position-relative" href="#" id="clocheNotifications" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="<?= $notificationsNonLues > 0 ? 'Notifications (' . $notificationsNonLues . ' non lues)' : 'Notifications' ?>">
                        <span aria-hidden="true">🔔</span>
                        <span id="badgeNotifications" class="badge rounded-pill position-absolute top-0 start-100 translate-middle <?= $notificationsNonLues > 0 ? '' : 'd-none' ?>"
                              style="background-color: var(--couleur-accent); font-size: 0.65rem;"><?= $notificationsNonLues ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2" id="listeNotifications" style="min-width:320px;max-height:400px;overflow-y:auto;" aria-live="polite">
                        <li class="text-muted small text-center py-3">Chargement...</li>
                    </ul>
                </div>
            <?php endif; ?>

            <button type="button" id="boutonTheme" class="bouton-theme" aria-label="Basculer le thème sombre">🌙</button>

            <button type="button" id="boutonMenu" class="bouton-hamburger" aria-label="Ouvrir le menu"
                    aria-expanded="false" aria-controls="menuPleinEcran">
                <span class="trait trait-1"></span>
                <span class="trait trait-2"></span>
                <span class="trait trait-3"></span>
            </button>
        </div>
    </div>
</header>

<!-- ==================== Menu plein écran (Lot 2) ==================== -->
<div id="menuPleinEcran" class="menu-plein-ecran" aria-hidden="true">
    <nav class="menu-plein-ecran-liens">
        <a href="<?= url('') ?>" class="lien-menu <?= estPageActive('', true) ? 'actif' : '' ?>">Accueil</a>
        <a href="<?= url('biens/recherche') ?>" class="lien-menu <?= estPageActive('biens/recherche') ? 'actif' : '' ?>">Rechercher</a>

        <?php if (estConnecte() && $_SESSION['utilisateur_role'] === 'proprietaire'): ?>
            <a href="<?= url('biens/creer/infos') ?>" class="lien-menu lien-menu-cta">+ Publier une annonce</a>
        <?php endif; ?>

        <?php if (estConnecte()): ?>
            <?php
            // Le lien "Messages" doit aussi s'allumer sur une page de
            // conversation individuelle (/biens/12/messages/5), pas
            // seulement sur la boîte de réception elle-même.
            $estSurMessagerie = estPageActive('messages', true) || str_contains($_SERVER['REQUEST_URI'], '/messages/');
            ?>
            <a href="<?= url('messages') ?>" class="lien-menu <?= $estSurMessagerie ? 'actif' : '' ?>">
                Messages
                <?php if ($messagesNonLus > 0): ?>
                    <span class="badge rounded-pill" style="background-color: var(--couleur-accent);"><?= $messagesNonLus ?></span>
                <?php endif; ?>
            </a>

            <?php if ($_SESSION['utilisateur_role'] !== 'proprietaire'): ?>
                <a href="<?= url('tableau-bord') ?>" class="lien-menu <?= estPageActive('tableau-bord', true) ? 'actif' : '' ?>">Tableau de bord</a>
                <a href="<?= url('mes-favoris') ?>" class="lien-menu <?= estPageActive('mes-favoris') ? 'actif' : '' ?>">Favoris</a>
                <a href="<?= url('mes-alertes') ?>" class="lien-menu <?= estPageActive('mes-alertes') ? 'actif' : '' ?>">Alertes</a>
            <?php endif; ?>


            <?php if ($_SESSION['utilisateur_role'] === 'admin'): ?>
                <a href="<?= url('admin') ?>" class="lien-menu <?= estPageActive('admin') ? 'actif' : '' ?>"> Administration</a>
            <?php endif; ?>

            <?php if ($_SESSION['utilisateur_role'] === 'proprietaire'): ?>
                <a href="<?= url('tableau-bord') ?>" class="lien-menu <?= estPageActive('tableau-bord', true) ? 'actif' : '' ?>">Dashboard</a>
                <a href="<?= url('mes-annonces') ?>" class="lien-menu <?= estPageActive('mes-annonces') ? 'actif' : '' ?>">Gestion</a>
            <?php endif; ?>

            <a href="<?= url('mes-visites') ?>" class="lien-menu <?= estPageActive('mes-visites', true) ? 'actif' : '' ?>">Mes visites</a>
            <a href="<?= url('mon-profil') ?>" class="lien-menu <?= estPageActive('mon-profil') ? 'actif' : '' ?>">Mon profil</a>
        <?php endif; ?>
    </nav>

    <div class="menu-plein-ecran-pied">
        <?php if (estConnecte()): ?>
            <a href="<?= url('deconnexion') ?>" class="btn btn-outline-secondary w-100">Déconnexion</a>
        <?php else: ?>
            <a href="<?= url('inscription') ?>" class="btn btn-outline-primary w-100">S'inscrire</a>
            <a href="<?= url('connexion') ?>" class="btn btn-primary w-100">Se connecter</a>
        <?php endif; ?>
    </div>
</div>

<main id="contenu-principal" class="container my-4">