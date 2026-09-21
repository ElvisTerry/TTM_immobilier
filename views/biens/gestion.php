<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1 class="h3 mb-0">Gestion de mes annonces</h1>
    <a href="<?= url('biens/creer/infos') ?>" class="btn btn-primary btn-sm">+ Publier une annonce</a>
</div>

<?php if (empty($mesAnnonces)): ?>
    <p class="text-muted text-center py-5">Vous n'avez pas encore publié d'annonce.</p>
<?php endif; ?>

<?php
$libellesModeration = ['en_attente' => 'En modération', 'approuve' => 'Publiée', 'rejete' => 'Rejetée'];
$couleursModeration = ['en_attente' => 'secondary', 'approuve' => 'success', 'rejete' => 'danger'];
$libellesCommercial = ['disponible' => 'Disponible', 'loue' => 'Loué', 'vendu' => 'Vendu'];
?>

<div id="listeGestion" class="d-flex flex-column gap-3">
    <?php foreach ($mesAnnonces as $annonce): ?>
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

                    <div class="col-12 col-md-4">
                        <a href="<?= url('biens/detail', [(int) $annonce['id']]) ?>" class="fw-semibold text-decoration-none">
                            <?= nettoyer($annonce['titre']) ?>
                        </a>
                        <div class="small text-muted"><?= nettoyer($annonce['ville']) ?> · <?= number_format((float) $annonce['prix'], 0, ',', ' ') ?> FCFA</div>
                        <span class="badge bg-<?= $couleursModeration[$annonce['statut_moderation']] ?>">
                            <?= $libellesModeration[$annonce['statut_moderation']] ?>
                        </span>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Statut commercial</label>
                        <select class="form-select form-select-sm selecteur-statut-commercial">
                            <?php foreach ($libellesCommercial as $valeur => $libelle): ?>
                                <option value="<?= $valeur ?>" <?= $annonce['statut'] === $valeur ? 'selected' : '' ?>><?= $libelle ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex flex-wrap gap-2 justify-content-md-end">
                        <a href="<?= url('biens/' . (int) $annonce['id'] . '/modifier') ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                        <a href="<?= url('biens/' . (int) $annonce['id'] . '/disponibilites') ?>" class="btn btn-sm btn-outline-secondary">Calendrier</a>
                        <button type="button" class="btn btn-sm btn-outline-danger bouton-supprimer">Supprimer</button>
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
    const liste = document.getElementById('listeGestion');

    // --- Changement de statut commercial (disponible / loué / vendu) ---
    liste.addEventListener('change', async (e) => {
        if (!e.target.classList.contains('selecteur-statut-commercial')) return;

        const carte = e.target.closest('[data-id]');
        const id = carte.dataset.id;
        const statut = e.target.value;

        const donnees = new FormData();
        donnees.append('statut', statut);
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(`${cheminBase}/biens/${id}/statut-commercial`, { method: 'POST', body: donnees });
            const resultat = await reponse.json();
            if (!resultat.succes) {
                alert(resultat.erreur || "Échec de la mise à jour.");
            }
        } catch (erreur) {
            alert('Erreur réseau, réessayez.');
        }
    });

    // --- Suppression (confirmation stylée du Jour 17 + AJAX) ---
    liste.addEventListener('click', async (e) => {
        if (!e.target.classList.contains('bouton-supprimer')) return;

        const carte = e.target.closest('[data-id]');
        const id = carte.dataset.id;

        const confirme = await window.confirmerAction(
            'Supprimer définitivement cette annonce ? Cette action est irréversible : photos, messages, avis et réservations liés seront aussi supprimés.'
        );
        if (!confirme) return;

        const donnees = new FormData();
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(`${cheminBase}/biens/${id}/supprimer`, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (resultat.succes) {
                carte.style.transition = 'opacity 0.2s';
                carte.style.opacity = '0';
                setTimeout(() => carte.remove(), 200);
            } else {
                alert(resultat.erreur || "Échec de la suppression.");
            }
        } catch (erreur) {
            alert('Erreur réseau, réessayez.');
        }
    });
})();
</script>
