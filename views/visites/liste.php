<h1 class="h4 mb-4">Mes visites</h1>

<?php if (empty($visites)): ?>
    <p class="text-muted text-center py-5">
        <?= $vueProprietaire ? "Aucune demande de visite reçue pour l'instant." : "Vous n'avez encore demandé aucune visite." ?>
    </p>
<?php endif; ?>

<div class="d-flex flex-column gap-2">
    <?php foreach ($visites as $visite): ?>
        <div class="card shadow-sm" data-id="<?= (int) $visite['id'] ?>">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-semibold">
                        <a href="<?= url('biens/detail', [(int) $visite['bien_id']]) ?>" class="text-decoration-none">
                            <?= nettoyer($visite['bien_titre']) ?>
                        </a>
                    </div>
                    <div class="small text-muted">
                         <?= nettoyer(date('d/m/Y', strtotime($visite['date_visite']))) ?> à <?= nettoyer(substr($visite['heure_visite'], 0, 5)) ?>
                        <?php if ($vueProprietaire): ?>
                            - demandé par <strong><?= nettoyer($visite['locataire_nom']) ?></strong>
                            <?php if (!empty($visite['locataire_telephone'])): ?> (<?= nettoyer($visite['locataire_telephone']) ?>)<?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($visite['message'])): ?>
                        <div class="small text-muted mt-1 fst-italic">"<?= nettoyer($visite['message']) ?>"</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bouton-statut bg-<?= ['en_attente' => 'warning', 'acceptee' => 'success', 'refusee' => 'danger', 'annulee' => 'secondary'][$visite['statut']] ?>">
                        <?= ['en_attente' => 'En attente', 'acceptee' => 'Acceptée', 'refusee' => 'Refusée', 'annulee' => 'Annulée'][$visite['statut']] ?>
                    </span>

                    <?php if ($vueProprietaire && $visite['statut'] === 'en_attente'): ?>
                        <button class="btn btn-sm btn-outline-success bouton-changer-statut" data-statut="acceptee">Accepter</button>
                        <button class="btn btn-sm btn-outline-danger bouton-changer-statut" data-statut="refusee">Refuser</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($vueProprietaire): ?>
<script>
(function () {
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;

    document.querySelectorAll('.bouton-changer-statut').forEach((bouton) => {
        bouton.addEventListener('click', async () => {
            const carte = bouton.closest('[data-id]');
            const id = carte.dataset.id;
            const statut = bouton.dataset.statut;

            const donnees = new FormData();
            donnees.append('statut', statut);
            donnees.append('csrf_token', csrfToken);

            try {
                const reponse = await fetch(`${<?= json_encode(cheminBase()) ?>}/visites/${id}/statut`, { method: 'POST', body: donnees });
                const resultat = await reponse.json();

                if (resultat.succes) {
                    const badge = carte.querySelector('.bouton-statut');
                    const libelles = { acceptee: 'Acceptée', refusee: 'Refusée' };
                    const couleurs = { acceptee: 'success', refusee: 'danger' };
                    badge.className = 'badge bouton-statut bg-' + couleurs[statut];
                    badge.textContent = libelles[statut];
                    carte.querySelectorAll('.bouton-changer-statut').forEach((b) => b.remove());
                } else {
                    alert(resultat.erreur || "Échec de la mise à jour.");
                }
            } catch (erreur) {
                alert('Erreur réseau, réessayez.');
            }
        });
    });
})();
</script>
<?php endif; ?>
