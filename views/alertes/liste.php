<h1 class="h4 mb-4">Mes alertes</h1>

<?php if (empty($alertes)): ?>
    <p class="text-muted text-center py-5">
        Aucune alerte pour l'instant. Depuis la <a href="<?= url('biens/recherche') ?>">recherche</a>,
        affinez vos critères puis cliquez sur "Créer une alerte".
    </p>
<?php endif; ?>

<div id="listeAlertes" class="d-flex flex-column gap-2">
    <?php foreach ($alertes as $alerte): ?>
        <div class="card shadow-sm" data-id="<?= (int) $alerte['id'] ?>">
            <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-semibold"><?= nettoyer($alerte['nom_recherche']) ?></div>
                    <div class="small text-muted">
                        <?= nettoyer($alerte['ville'] ?: 'Toutes villes') ?>
                        <?php if ($alerte['type_bien']): ?> · <?= nettoyer(ucfirst($alerte['type_bien'])) ?><?php endif; ?>
                        <?php if ($alerte['prix_max']): ?> · jusqu'à <?= number_format((float) $alerte['prix_max'], 0, ',', ' ') ?> FCFA<?php endif; ?>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger bouton-supprimer-alerte">Supprimer</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const cheminBase = <?= json_encode(cheminBase()) ?>;

    document.getElementById('listeAlertes').addEventListener('click', async (e) => {
        if (!e.target.classList.contains('bouton-supprimer-alerte')) return;

        const carte = e.target.closest('[data-id]');
        const id = carte.dataset.id;

        const donnees = new FormData();
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(`${cheminBase}/alertes/${id}/supprimer`, { method: 'POST', body: donnees });
            const resultat = await reponse.json();
            if (resultat.succes) {
                carte.remove();
            } else {
                alert(resultat.erreur || "Échec de la suppression.");
            }
        } catch (erreur) {
            alert('Erreur réseau, réessayez.');
        }
    });
})();
</script>
 
