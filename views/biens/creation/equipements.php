<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <?php $etapeActuelle = 2; require __DIR__ . '/_progression.php'; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Équipements et caractéristiques</h1>

                <form method="POST" action="<?= url('biens/creer/equipements') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="row">
                        <div class="col-6 col-md-4 mb-3">
                            <label class="form-label">Nombre de chambres</label>
                            <input type="number" name="nombre_chambres" class="form-control" min="0"
                                   value="<?= (int) ($donnees['nombre_chambres'] ?? 1) ?>">
                        </div>
                        <div class="mb-3">
    <label for="nombre_salles_bain" class="form-label">
        Nombre de salles de bain
    </label>

    <input
        type="number"
        id="nombre_salles_bain"
        name="nombre_salles_bain"
        class="form-control"
        min="0"
        max="20"
        value="<?= htmlspecialchars($donnees['nombre_salles_bain'] ?? 0, ENT_QUOTES, 'UTF-8') ?>"
    >
</div>

                        <div class="col-6 col-md-4 mb-3">
                            <label class="form-label">Superficie (m²)</label>
                            <input type="number" step="0.1" name="superficie_m2" class="form-control" min="0"
                                   value="<?= nettoyer((string) ($donnees['superficie_m2'] ?? '')) ?>">
                        </div>
                    </div>

                    <label class="form-label d-block mt-2">Équipements disponibles</label>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <?php
                        $equipements = ['meuble' => 'Meublé', 'eau' => 'Eau', 'electricite' => 'Électricité', 'parking' => 'Parking'];
                        foreach ($equipements as $champ => $libelle): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="<?= $champ ?>" id="<?= $champ ?>"
                                       <?= !empty($donnees[$champ]) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $champ ?>"><?= $libelle ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= url('biens/creer/infos') ?>" class="btn btn-outline-secondary flex-fill">Retour</a>
                        <button type="submit" class="btn btn-primary flex-fill">Continuer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
