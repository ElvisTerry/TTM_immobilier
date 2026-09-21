<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <?php $etapeActuelle = 1; require __DIR__ . '/_progression.php'; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Parlez-nous de votre bien</h1>

                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($erreurs as $e): ?><li><?= nettoyer($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('biens/creer/infos') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Titre de l'annonce</label>
                        <input type="text" name="titre" class="form-control"
                               placeholder="Ex : Studio meublé proche université"
                               value="<?= nettoyer($donnees['titre'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= nettoyer($donnees['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Type de bien</label>
                            <select name="type_bien" class="form-select" required>
                                <?php foreach (['chambre', 'studio', 'appartement', 'maison'] as $type): ?>
                                    <option value="<?= $type ?>" <?= ($donnees['type_bien'] ?? '') === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Transaction</label>
                            <select name="type_transaction" class="form-select" required>
                                <?php foreach (['location', 'vente'] as $type): ?>
                                    <option value="<?= $type ?>" <?= ($donnees['type_transaction'] ?? '') === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Prix (FCFA)</label>
                        <input type="number" name="prix" class="form-control" min="1"
                               value="<?= nettoyer((string) ($donnees['prix'] ?? '')) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Continuer</button>
                </form>
            </div>
        </div>
    </div>
</div>
