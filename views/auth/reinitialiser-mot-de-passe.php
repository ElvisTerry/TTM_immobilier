<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="mb-4 text-center">Nouveau mot de passe</h2>

                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-danger small">
                        <?php foreach ($erreurs as $e): ?><div><?= nettoyer($e) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('reinitialiser-mot-de-passe/' . $token) ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="mot_de_passe" class="form-control" autocomplete="new-password" required autofocus>
                        <div class="form-text">8 caractères min., 1 majuscule, 1 chiffre.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirmation</label>
                        <input type="password" name="confirmation" class="form-control" autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Réinitialiser mon mot de passe</button>
                </form>
            </div>
        </div>
    </div>
</div>
 
