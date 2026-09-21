<h1 class="h3 mb-4">Espace administrateur</h1>

<div class="d-flex gap-2 mb-4">
    <a href="<?= url('admin/signalements') ?>" class="btn btn-sm btn-outline-danger position-relative">
        🚩 Signalements
        <?php if ($signalementsEnAttente > 0): ?>
            <span class="badge rounded-pill bg-danger"><?= $signalementsEnAttente ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= url('admin/utilisateurs') ?>" class="btn btn-sm btn-outline-secondary">👥 Utilisateurs</a>
    <a href="<?= url('admin/journal-acces') ?>" class="btn btn-sm btn-outline-secondary">🛡️ Journal des accès refusés</a>
    <form method="POST" action="<?= url('admin/sauvegarde') ?>" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
        <button type="submit" class="btn btn-sm btn-outline-secondary">💾 Télécharger une sauvegarde</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-warning"><?= $enAttente ?></div>
            <div class="small text-muted">En attente</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-success"><?= $approuvees ?></div>
            <div class="small text-muted">Approuvées</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-danger"><?= $rejetees ?></div>
            <div class="small text-muted">Rejetées</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= url('admin/utilisateurs') ?>" class="text-decoration-none">
            <div class="card shadow-sm text-center p-3">
                <div class="fs-4 fw-bold" style="color: var(--couleur-primaire);"><?= $totalUtilisateurs ?></div>
                <div class="small text-muted">Utilisateurs</div>
            </div>
        </a>
    </div>
</div>

<h2 class="h5 mb-3">Annonces en attente de validation</h2>

<?php if (empty($annoncesEnAttente)): ?>
    <p class="text-muted text-center py-5">Aucune annonce en attente, tout est à jour !</p>
<?php endif; ?>

<div id="listeAnnoncesAttente" class="d-flex flex-column gap-3">
    <?php foreach ($annoncesEnAttente as $annonce): ?>
        <div class="card shadow-sm" data-id="<?= (int) $annonce['id'] ?>">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-2">
                        <?php if (!empty($annonce['photo_principale'])): ?>
                            <img loading="lazy" src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($annonce['photo_principale']) ?>"
                                 class="rounded w-100" style="height:80px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:80px;">
                                <span class="text-muted small">Aucune photo</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <a href="<?= url('biens/detail', [(int) $annonce['id']]) ?>" class="fw-semibold text-decoration-none" target="_blank">
                            <?= nettoyer($annonce['titre']) ?>
                        </a>
                        <div class="small text-muted">
                            <?= nettoyer($annonce['ville']) ?> · <?= number_format((float) $annonce['prix'], 0, ',', ' ') ?> FCFA
                        </div>
                        <div class="small text-muted">
                            Par <?= nettoyer($annonce['proprietaire_nom']) ?> (<?= nettoyer($annonce['proprietaire_email']) ?>)
                        </div>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-sm btn-success bouton-decision" data-action="approuver">✓ Approuver</button>
                        <button type="button" class="btn btn-sm btn-outline-danger bouton-decision" data-action="rejeter">✗ Rejeter</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const cheminBase = <?= json_encode(cheminBase()) ?>;

    document.getElementById('listeAnnoncesAttente').addEventListener('click', async (e) => {
        if (!e.target.classList.contains('bouton-decision')) return;

        const carte = e.target.closest('[data-id]');
        const id = carte.dataset.id;
        const action = e.target.dataset.action; // 'approuver' ou 'rejeter'

        if (action === 'rejeter' && !(await window.confirmerAction('Rejeter cette annonce ?'))) return;

        const donnees = new FormData();
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(`${cheminBase}/admin/annonces/${id}/${action}`, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (resultat.succes) {
                carte.style.transition = 'opacity 0.2s';
                carte.style.opacity = '0';
                setTimeout(() => carte.remove(), 200);
            } else {
                alert(resultat.erreur || "Échec de l'opération.");
            }
        } catch (erreur) {
            alert('Erreur réseau, réessayez.');
        }
    });
})();
</script>
