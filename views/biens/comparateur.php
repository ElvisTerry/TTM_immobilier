<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Comparateur de biens</h1>
    <a href="<?= url('biens/recherche') ?>" class="small">Retour à la recherche</a>
</div>

<?php if (count($biens) < 2): ?>
    <p class="text-muted text-center py-5">
        Sélectionnez au moins 2 annonces depuis la recherche pour les comparer ici.
    </p>
<?php else: ?>
    <?php
    $prix = array_column($biens, 'prix');
    $superficies = array_map(fn($b) => (float) ($b['superficie_m2'] ?? 0), $biens);
    $chambres = array_map(fn($b) => (int) $b['nombre_chambres'], $biens);

    $indexPrixMin = array_keys($prix, min($prix))[0];
    $indexSuperficieMax = max($superficies) > 0 ? array_keys($superficies, max($superficies))[0] : null;
    $indexChambresMax = array_keys($chambres, max($chambres))[0];
    ?>

    <div class="table-responsive">
        <table class="table align-middle text-center comparateur-table">
            <thead>
                <tr>
                    <th class="text-start">&nbsp;</th>
                    <?php foreach ($biens as $bien): ?>
                        <th>
                            <?php if (!empty($bien['photos'][0]['chemin_fichier'])): ?>
                                <img src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($bien['photos'][0]['chemin_fichier']) ?>"
                                     style="width:100%;height:110px;object-fit:cover;border-radius:var(--r-sm);" alt="">
                            <?php else: ?>
                                <div style="height:110px;border-radius:var(--r-sm);background:var(--c-fond-alt);"></div>
                            <?php endif; ?>
                            <div class="small fw-semibold mt-2"><?= nettoyer($bien['titre']) ?></div>
                            <button type="button" class="btn btn-link btn-sm text-muted p-0 bouton-retirer-comparateur" data-id="<?= (int) $bien['id'] ?>">Retirer</button>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start fw-semibold">Prix</td>
                    <?php foreach ($biens as $index => $bien): ?>
                        <td class="<?= $index === $indexPrixMin ? 'cellule-gagnante' : '' ?>">
                            <?= number_format((float) $bien['prix'], 0, ',', ' ') ?> FCFA
                            <?php if ($bien['type_transaction'] === 'location'): ?><span class="small text-muted d-block">/ mois</span><?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Ville</td>
                    <?php foreach ($biens as $bien): ?><td><?= nettoyer($bien['ville']) ?></td><?php endforeach; ?>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Quartier</td>
                    <?php foreach ($biens as $bien): ?><td><?= nettoyer($bien['quartier'] ?? '—') ?></td><?php endforeach; ?>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Type</td>
                    <?php foreach ($biens as $bien): ?><td><?= nettoyer(ucfirst($bien['type_bien'])) ?></td><?php endforeach; ?>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Superficie</td>
                    <?php foreach ($biens as $index => $bien): ?>
                        <td class="<?= $indexSuperficieMax !== null && $index === $indexSuperficieMax && (float) ($bien['superficie_m2'] ?? 0) > 0 ? 'cellule-gagnante' : '' ?>">
                            <?= $bien['superficie_m2'] ? nettoyer((string) $bien['superficie_m2']) . ' m²' : '—' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Chambres</td>
                    <?php foreach ($biens as $index => $bien): ?>
                        <td class="<?= $index === $indexChambresMax ? 'cellule-gagnante' : '' ?>"><?= (int) $bien['nombre_chambres'] ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Salles de bain</td>
                    <?php foreach ($biens as $bien): ?><td><?= (int) ($bien['nombre_salles_bain'] ?? 0) ?></td><?php endforeach; ?>
                </tr>
                <?php foreach (['meuble' => 'Meublé', 'eau' => 'Eau', 'electricite' => 'Électricité', 'parking' => 'Parking'] as $champ => $libelle): ?>
                    <tr>
                        <td class="text-start fw-semibold"><?= $libelle ?></td>
                        <?php foreach ($biens as $bien): ?>
                            <td><?= !empty($bien[$champ]) ? '✓' : '—' ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="text-start fw-semibold">Avis</td>
                    <?php foreach ($biens as $bien): ?>
                        <td>
                            <?= $bien['statistiques_avis']['total'] > 0
                                ? nettoyer((string) $bien['statistiques_avis']['moyenne']) . ' ★ (' . $bien['statistiques_avis']['total'] . ')'
                                : 'Aucun avis' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td class="text-start">&nbsp;</td>
                    <?php foreach ($biens as $bien): ?>
                        <td><a href="<?= url('biens/detail', [(int) $bien['id']]) ?>" class="btn btn-primary btn-sm w-100">Voir l'annonce</a></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
(function () {
    document.querySelectorAll('.bouton-retirer-comparateur').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            window.retirerDuComparateur(parseInt(bouton.dataset.id, 10));
            bouton.closest('table').querySelectorAll('td, th').forEach((cellule) => {
                // Recharge simplement la page filtrée sur les biens restants,
                // plus fiable que de retirer une colonne en JS pur.
            });
            const params = new URLSearchParams(window.location.search);
            const idsRestants = (params.get('ids') || '').split(',').filter((id) => id != bouton.dataset.id);
            if (idsRestants.length >= 2) {
                window.location.search = 'ids=' + idsRestants.join(',');
            } else {
                window.location.href = <?= json_encode(url('biens/recherche')) ?>;
            }
        });
    });
})();
</script>

