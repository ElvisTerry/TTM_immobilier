<div class="page-auth">
    <div class="auth-carte">
        <div class="auth-marque">
            <span class="repere"></span>
            <h1 class="h4 mb-1">Connexion</h1>
            <p class="text-muted small mb-0">Ravi de vous revoir sur TTM.</p>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <?php if (!empty($messageSucces)): ?>
                    <div class="alert alert-success"><?= nettoyer($messageSucces) ?></div>
                <?php endif; ?>

                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($erreurs as $erreur): ?>
                            <div><?= nettoyer($erreur) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('connexion') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Adresse email</label>
                        <input type="email" name="email" class="form-control" value="<?= nettoyer($emailPrefill) ?>"
                               autocomplete="username" required <?= $emailPrefill ? '' : 'autofocus' ?>>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="mot_de_passe" class="form-control"
                               value="<?= nettoyer($motDePassePrefill) ?>" autocomplete="current-password" required
                               <?= $emailPrefill && !$motDePassePrefill ? 'autofocus' : '' ?>>
                    </div>
                    <div class="mb-4 text-end">
                        <a href="<?= url('mot-de-passe-oublie') ?>" class="small">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 mb-0 small">
            Pas encore de compte ? <a href="<?= url('inscription') ?>">Inscrivez-vous</a>
        </p>
    </div>
</div>