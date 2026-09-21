<div class="page-auth">
    <div class="auth-carte">
        <div class="auth-marque">
            <span class="repere"></span>
            <h1 class="h4 mb-1">Créer un compte</h1>
            <p class="text-muted small mb-0">Rapide, gratuit, sans engagement.</p>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($erreurs as $erreur): ?>
                                <li><?= nettoyer($erreur) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('inscription') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Nom complet</label>
                        <input type="text" name="nom" class="form-control"
                               value="<?= nettoyer($anciennesValeurs['nom'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= nettoyer($anciennesValeurs['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control"
                               value="<?= nettoyer($anciennesValeurs['telephone'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6 mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="mot_de_passe" class="form-control" autocomplete="new-password" required>
                            <div class="form-text">8 caractères min., 1 majuscule, 1 chiffre.</div>
                        </div>
                        <div class="col-12 col-sm-6 mb-3">
                            <label class="form-label">Confirmation</label>
                            <input type="password" name="confirmation" class="form-control" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Type de compte</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" value="locataire" id="role_locataire" checked>
                            <label class="form-check-label" for="role_locataire">Locataire / Acheteur</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" value="proprietaire" id="role_proprietaire">
                            <label class="form-check-label" for="role_proprietaire">Propriétaire</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Créer mon compte</button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 mb-0 small">
            Déjà un compte ? <a href="<?= url('connexion') ?>">Connectez-vous</a>
        </p>
    </div>
</div>