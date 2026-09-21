<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <?php $etapeActuelle = 5; require __DIR__ . '/_progression.php'; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Vérifiez avant de publier</h1>

                <?php if (!empty($donnees['photos'])): ?>
                    <div class="d-flex gap-2 mb-3 flex-wrap">
                        <?php foreach ($donnees['photos'] as $photo): ?>
                            <img src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($photo) ?>"
                                 style="width:90px;height:90px;object-fit:cover;border-radius:8px;" alt="Photo">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <dl class="row small">
                    <dt class="col-4 col-md-3">Titre</dt><dd class="col-8 col-md-9"><?= nettoyer($donnees['titre']) ?></dd>
                    <dt class="col-4 col-md-3">Type</dt><dd class="col-8 col-md-9"><?= nettoyer(ucfirst($donnees['type_bien'])) ?> - <?= nettoyer(ucfirst($donnees['type_transaction'])) ?></dd>
                    <dt class="col-4 col-md-3">Prix</dt><dd class="col-8 col-md-9"><?= number_format((float) $donnees['prix'], 0, ',', ' ') ?> FCFA</dd>
                    <dt class="col-4 col-md-3">Lieu</dt><dd class="col-8 col-md-9"><?= nettoyer($donnees['quartier'] ?? '') ?>, <?= nettoyer($donnees['ville']) ?></dd>
                    <dt class="col-4 col-md-3">Chambres</dt><dd class="col-8 col-md-9"><?= (int) ($donnees['nombre_chambres'] ?? 0) ?></dd>
                    <dt class="col-4 col-md-3">Équipements</dt>
                    <dd class="col-8 col-md-9">
                        <?php
                        $labels = [];
                        foreach (['meuble' => 'Meublé', 'eau' => 'Eau', 'electricite' => 'Électricité', 'parking' => 'Parking'] as $champ => $libelle) {
                            if (!empty($donnees[$champ])) $labels[] = $libelle;
                        }
                        echo $labels ? nettoyer(implode(', ', $labels)) : '—';
                        ?>
                    </dd>
                </dl>

                <div class="alert alert-info small">
                    Votre annonce sera visible publiquement après validation par notre équipe (généralement sous 24h).
                </div>

                <form method="POST" action="<?= url('biens/creer/finaliser') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                    <div class="d-flex gap-2">
                        <a href="<?= url('biens/creer/photos') ?>" class="btn btn-outline-secondary flex-fill">Retour</a>
                        <button type="submit" class="btn btn-accent flex-fill">Publier l'annonce</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
