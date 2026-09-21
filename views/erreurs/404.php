<?php
/**
 * views/erreurs/404.php
 * ------------------------
 * Affichée par le Router quand aucune route ne correspond. C'est une
 * page HTML autonome et complète (elle n'utilise pas header.php/footer.php).
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable - ImmoApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600&family=Work+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= cheminBase() ?>/css/design-system.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="text-center px-3">
        <h1 class="display-4 fw-bold" style="color: var(--couleur-accent);">404</h1>
        <p class="text-muted">Cette page n'existe pas ou plus.</p>
        <a href="<?= url('') ?>" class="btn btn-primary">Retour à l'accueil</a>
    </div>
</body>
</html>
