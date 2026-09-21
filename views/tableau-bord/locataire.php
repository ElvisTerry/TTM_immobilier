<h1 class="h4 mb-1">Bonjour, <?= nettoyer($_SESSION['utilisateur_nom']) ?></h1>
<p class="text-muted mb-4">Voici où vous en êtes sur TTM.</p>

<div class="grille-stats mb-5">
    <div class="carte-stat">
        <div class="carte-stat-chiffre"><?= count($visitesActives) ?></div>
        <div class="carte-stat-libelle">Visites en cours</div>
    </div>
    <div class="carte-stat">
        <div class="carte-stat-chiffre"><?= count($mesFavoris) ?></div>
        <div class="carte-stat-libelle">Favoris</div>
    </div>
    <div class="carte-stat">
        <div class="carte-stat-chiffre"><?= count($mesAlertes) ?></div>
        <div class="carte-stat-libelle">Alertes actives</div>
    </div>
    <div class="carte-stat">
        <div class="carte-stat-chiffre"><?= $messagesNonLus ?></div>
        <div class="carte-stat-libelle">Messages non lus</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-7">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Prochaines visites</h2>
            <a href="<?= url('mes-visites') ?>" class="small">Tout voir</a>
        </div>
        <?php if (empty($visitesActives)): ?>
            <p class="text-muted small mb-4">Aucune visite en cours. <a href="<?= url('biens/recherche') ?>">Trouver un logement</a>.</p>
        <?php else: ?>
            <div class="list-group mb-4">
                <?php foreach (array_slice($visitesActives, 0, 5) as $visite): ?>
                    <a href="<?= url('mes-visites') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold"><?= nettoyer($visite['bien_titre']) ?></div>
                                <div class="small text-muted"><?= nettoyer(date('d/m/Y', strtotime($visite['date_visite']))) ?> à <?= nettoyer($visite['heure_visite']) ?></div>
                            </div>
                            <span class="badge" style="background-color: <?= $visite['statut'] === 'acceptee' ? 'var(--c-foret)' : 'var(--c-or)' ?>;">
                                <?= $visite['statut'] === 'acceptee' ? 'Acceptée' : 'En attente' ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Messages récents</h2>
            <a href="<?= url('messages') ?>" class="small">Tout voir</a>
        </div>
        <?php if (empty($conversationsRecentes)): ?>
            <p class="text-muted small">Aucune conversation pour l'instant.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($conversationsRecentes as $conv): ?>
                    <a href="<?= url('biens/' . (int) $conv['bien_id'] . '/messages/' . (int) $conv['autre_id']) ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold"><?= nettoyer($conv['autre_nom']) ?></div>
                            <div class="small text-muted"><?= nettoyer($conv['bien_titre']) ?></div>
                        </div>
                        <?php if ($conv['non_lus'] > 0): ?>
                            <span class="badge rounded-pill" style="background-color: var(--couleur-accent);"><?= (int) $conv['non_lus'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <div class="col-12 col-lg-5">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Mes favoris</h2>
            <a href="<?= url('mes-favoris') ?>" class="small">Tout voir</a>
        </div>
        <?php if (empty($mesFavoris)): ?>
            <p class="text-muted small mb-4">Aucun favori pour l'instant.</p>
        <?php else: ?>
            <div class="d-flex flex-column gap-2 mb-4">
                <?php foreach (array_slice($mesFavoris, 0, 4) as $favori): ?>
                    <a href="<?= url('biens/detail', [(int) $favori['id']]) ?>" class="d-flex align-items-center gap-2 text-decoration-none text-dark card p-2">
                        <?php if ($favori['photo_principale']): ?>
                            <img src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($favori['photo_principale']) ?>"
                                 style="width:54px;height:54px;object-fit:cover;border-radius:var(--r-sm);flex-shrink:0;" alt="">
                        <?php else: ?>
                            <div style="width:54px;height:54px;border-radius:var(--r-sm);background:var(--c-fond-alt);flex-shrink:0;"></div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <div class="small fw-semibold"><?= nettoyer($favori['titre']) ?></div>
                            <div class="small text-muted"><?= number_format((float) $favori['prix'], 0, ',', ' ') ?> FCFA</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Mes alertes</h2>
            <a href="<?= url('mes-alertes') ?>" class="small">Gérer</a>
        </div>
        <?php if (empty($mesAlertes)): ?>
            <p class="text-muted small">Aucune alerte enregistrée. <a href="<?= url('biens/recherche') ?>">Créer une alerte</a>.</p>
        <?php else: ?>
            <ul class="list-unstyled">
                <?php foreach ($mesAlertes as $alerte): ?>
                    <li class="small mb-2 pb-2" style="border-bottom: 1px solid var(--c-ligne);"><?= nettoyer($alerte['nom_recherche']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
</div>






