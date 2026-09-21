<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Journal des accès refusés</h1>
    <a href="<?= url('admin') ?>" class="small">Retour au tableau de bord</a>
</div>

<p class="text-muted small mb-4">
    Chaque ligne correspond à une tentative d'accès à une page réservée aux administrateurs
    par quelqu'un qui n'en avait pas le droit (utilisateur non-admin, ou visiteur non connecté).
    Une même IP ou un même compte revenant plusieurs fois ici mérite un coup d'œil.
</p>

<?php if (empty($accesRefuses)): ?>
    <p class="text-muted text-center py-5">Aucune tentative d'accès refusée enregistrée. </p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Adresse IP</th>
                    <th>Route visée</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accesRefuses as $acces): ?>
                    <tr>
                        <td class="text-nowrap small"><?= nettoyer(date('d/m/Y H:i', strtotime($acces['date_tentative']))) ?></td>
                        <td class="small">
                            <?= $acces['utilisateur_nom'] ? nettoyer($acces['utilisateur_nom']) : '<span class="text-muted">Non connecté</span>' ?>
                        </td>
                        <td class="small font-monospace"><?= nettoyer($acces['ip']) ?></td>
                        <td class="small text-break"><?= nettoyer($acces['route']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="text-muted small mt-2">Les 100 tentatives les plus récentes sont affichées.</p>
<?php endif; ?>
