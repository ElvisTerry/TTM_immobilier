<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1 class="h3 mb-0">Mon tableau de bord</h1>
    <a href="<?= url('biens/creer/infos') ?>" class="btn btn-primary btn-sm">+ Publier une annonce</a>
</div>

<!-- Cartes de résumé -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <div class="fs-4 fw-bold" style="color: var(--couleur-primaire);"><?= count($mesBiens) ?></div>
            <div class="small text-muted">Annonce(s)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <div class="fs-4 fw-bold" style="color: var(--couleur-primaire);"><?= $totalVues ?></div>
            <div class="small text-muted">Vues cumulées</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <div class="fs-4 fw-bold" style="color: var(--couleur-primaire);"><?= $totalFavoris ?></div>
            <div class="small text-muted">Favoris cumulés</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <div class="fs-4 fw-bold" style="color: var(--couleur-or);">
                <?= $noteMoyenne !== null ? '★ ' . nettoyer((string) $noteMoyenne) : '—' ?>
            </div>
            <div class="small text-muted">Note moyenne</div>
        </div>
    </div>
</div>

<?php if (!empty($visitesEnAttente)): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>📅 <?= count($visitesEnAttente) ?> demande(s) de visite en attente de réponse.</span>
        <a href="<?= url('mes-visites') ?>" class="btn btn-sm btn-outline-dark">Voir les demandes</a>
    </div>
<?php endif; ?>

<!-- Graphiques -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">Vues des 30 derniers jours</h2>
                <canvas id="graphiqueVues" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">Vues par annonce</h2>
                <canvas id="graphiqueComparaison" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Liste des annonces -->
    <div class="col-12 col-lg-7">
        <h2 class="h6 mb-3">Mes annonces</h2>
        <?php if (empty($mesBiens)): ?>
            <p class="text-muted small">Vous n'avez pas encore publié d'annonce.</p>
        <?php endif; ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($mesBiens as $bien): ?>
                <div class="card shadow-sm">
                    <div class="card-body py-2">
                        <div class="row align-items-center g-2">
                            <div class="col-12 col-md-5">
                                <a href="<?= url('biens/detail', [(int) $bien['id']]) ?>" class="fw-semibold text-decoration-none small">
                                    <?= nettoyer($bien['titre']) ?>
                                </a>
                                <div class="small">
                                    <span class="badge bg-<?= $bien['statut_moderation'] === 'approuve' ? 'success' : ($bien['statut_moderation'] === 'rejete' ? 'danger' : 'secondary') ?>">
                                        <?= ['approuve' => 'Publiée', 'en_attente' => 'En modération', 'rejete' => 'Rejetée'][$bien['statut_moderation']] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-4 col-md-2 text-center small">👁️ <?= (int) $bien['nb_vues'] ?></div>
                            <div class="col-4 col-md-2 text-center small">❤️ <?= (int) $bien['nb_favoris'] ?></div>
                            <div class="col-4 col-md-3 text-end">
                                <a href="<?= url('biens/' . (int) $bien['id'] . '/disponibilites') ?>" class="btn btn-sm btn-outline-secondary">Calendrier</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Derniers avis -->
    <div class="col-12 col-lg-5">
        <h2 class="h6 mb-3">Derniers avis reçus</h2>
        <?php if (empty($avisRecents)): ?>
            <p class="text-muted small">Aucun avis pour l'instant.</p>
        <?php endif; ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($avisRecents as $avis): ?>
                <div class="card shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between">
                            <strong class="small"><?= nettoyer($avis['auteur_nom']) ?></strong>
                            <span style="color: var(--couleur-or);" class="small"><?= str_repeat('★', (int) $avis['note']) ?></span>
                        </div>
                        <div class="small text-muted"><?= nettoyer($avis['bien_titre']) ?></div>
                        <?php if (!empty($avis['commentaire'])): ?>
                            <p class="small mb-0 mt-1"><?= nettoyer(mb_strimwidth($avis['commentaire'], 0, 100, '...')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    // --- Graphique 1 : vues des 30 derniers jours (courbe) ---
  
    const donneesVues = <?= json_encode($vuesParJour, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    new Chart(document.getElementById('graphiqueVues'), {
        type: 'line',
        data: {
            labels: donneesVues.map((v) => {
                const d = new Date(v.jour);
                return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
            }),
            datasets: [{
                label: 'Vues',
                data: donneesVues.map((v) => v.total),
                borderColor: '#0B5D3B',
                backgroundColor: 'rgba(11, 93, 59, 0.1)',
                fill: true,
                tension: 0.3,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });

    // --- Graphique 2 : comparaison des vues entre annonces (barres) ---
    const mesBiens = <?= json_encode(array_map(fn($b) => ['titre' => mb_strimwidth($b['titre'], 0, 20, '...'), 'vues' => (int) $b['nb_vues']], $mesBiens), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    new Chart(document.getElementById('graphiqueComparaison'), {
        type: 'bar',
        data: {
            labels: mesBiens.map((b) => b.titre),
            datasets: [{
                label: 'Vues',
                data: mesBiens.map((b) => b.vues),
                backgroundColor: '#C1440E',
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
})();
</script>
