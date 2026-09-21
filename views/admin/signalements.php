<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Signalements</h1>
    <a href="<?= url('admin') ?>" class="small"> Retour au tableau de bord</a>
</div>

<?php if (empty($signalementsEnAttente)): ?>
    <p class="text-muted text-center py-5">Aucun signalement en attente. </p>
<?php endif; ?>

<?php
$libellesMotifs = [
    'fausse_annonce' => 'Fausse annonce',
    'prix_suspect' => 'Prix suspect',
    'contenu_inapproprie' => 'Contenu inapproprié',
    'arnaque_suspectee' => 'Arnaque suspectée',
    'autre' => 'Autre',
];
?>

<div id="listeSignalements" class="d-flex flex-column gap-3">
    <?php foreach ($signalementsEnAttente as $signalement): ?>
        <div class="card shadow-sm" data-id="<?= (int) $signalement['id'] ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="badge bg-danger"><?= nettoyer($libellesMotifs[$signalement['motif']] ?? $signalement['motif']) ?></span>
                        <a href="<?= url('biens/detail', [(int) $signalement['bien_id']]) ?>" class="fw-semibold text-decoration-none ms-2" target="_blank">
                            <?= nettoyer($signalement['bien_titre']) ?>
                        </a>
                        <div class="small text-muted mt-1">
                            Signalé par <?= nettoyer($signalement['auteur_nom']) ?> le <?= nettoyer(date('d/m/Y', strtotime($signalement['date_signalement']))) ?>
                        </div>
                        <?php if (!empty($signalement['description'])): ?>
                            <p class="small mt-2 mb-0 fst-italic">"<?= nettoyer($signalement['description']) ?>"</p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <button type="button" class="btn btn-sm btn-success bouton-decision" data-action="traite">✓ Marquer traité</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary bouton-decision" data-action="rejete">Rejeter le signalement</button>
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

    document.getElementById('listeSignalements').addEventListener('click', async (e) => {
        if (!e.target.classList.contains('bouton-decision')) return;

        const carte = e.target.closest('[data-id]');
        const id = carte.dataset.id;
        const statut = e.target.dataset.action;

        const donnees = new FormData();
        donnees.append('statut', statut);
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(`${cheminBase}/admin/signalements/${id}/traiter`, { method: 'POST', body: donnees });
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
