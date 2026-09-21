<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Utilisateurs (<?= count($utilisateurs) ?>)</h1>
    <a href="<?= url('admin') ?>" class="small"> Retour au tableau de bord</a>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead>
            <tr class="small text-muted">
                <th>Nom</th>
                <th class="d-none d-md-table-cell">Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Membre depuis</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="corpsTableauUtilisateurs">
            <?php foreach ($utilisateurs as $utilisateur): ?>
                <tr data-id="<?= (int) $utilisateur['id'] ?>">
                    <td class="small">
                        <a href="<?= url('profil', [(int) $utilisateur['id']]) ?>"><?= nettoyer($utilisateur['nom']) ?></a>
                    </td>
                    <td class="small d-none d-md-table-cell"><?= nettoyer($utilisateur['email']) ?></td>
                    <td class="small"><?= nettoyer(ucfirst($utilisateur['role'])) ?></td>
                    <td>
                        <span class="badge bouton-statut bg-<?= $utilisateur['statut'] === 'actif' ? 'success' : 'secondary' ?>">
                            <?= $utilisateur['statut'] === 'actif' ? 'Actif' : 'Suspendu' ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= nettoyer(date('d/m/Y', strtotime($utilisateur['date_creation']))) ?></td>
                    <td>
                        <?php if ((int) $utilisateur['id'] !== (int) $_SESSION['utilisateur_id']): ?>
                            <button type="button" class="btn btn-sm bouton-changer-statut <?= $utilisateur['statut'] === 'actif' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                    data-statut-cible="<?= $utilisateur['statut'] === 'actif' ? 'suspendu' : 'actif' ?>">
                                <?= $utilisateur['statut'] === 'actif' ? 'Suspendre' : 'Réactiver' ?>
                            </button>
                        <?php else: ?>
                            <span class="text-muted small">(vous)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const cheminBase = <?= json_encode(cheminBase()) ?>;

    document.getElementById('corpsTableauUtilisateurs').addEventListener('click', async (e) => {
        if (!e.target.classList.contains('bouton-changer-statut')) return;

        const ligne = e.target.closest('[data-id]');
        const id = ligne.dataset.id;
        const statutCible = e.target.dataset.statutCible;

        if (statutCible === 'suspendu' && !(await window.confirmerAction('Suspendre ce compte ? Il ne pourra plus se connecter.'))) return;

        const donnees = new FormData();
        donnees.append('statut', statutCible);
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(`${cheminBase}/admin/utilisateurs/${id}/statut`, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (resultat.succes) {
                const badge = ligne.querySelector('.bouton-statut');
                const actif = resultat.statut === 'actif';
                badge.className = 'badge bouton-statut bg-' + (actif ? 'success' : 'secondary');
                badge.textContent = actif ? 'Actif' : 'Suspendu';
                e.target.textContent = actif ? 'Suspendre' : 'Réactiver';
                e.target.dataset.statutCible = actif ? 'suspendu' : 'actif';
                e.target.className = 'btn btn-sm bouton-changer-statut ' + (actif ? 'btn-outline-danger' : 'btn-outline-success');
            } else {
                alert(resultat.erreur || "Échec de l'opération.");
            }
        } catch (erreur) {
            alert('Erreur réseau, réessayez.');
        }
    });
})();
</script>
