<div class="page-auth">
    <div class="auth-carte">
        <div class="auth-marque">
            <span class="repere"></span>
            <h1 class="h4 mb-1">Mot de passe oublié</h1>
            <p class="text-muted small mb-0">Indiquez votre email, nous vous enverrons un lien pour en choisir un nouveau.</p>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <?php if (!empty($messageSucces)): ?>
                    <div class="alert alert-success small"><?= nettoyer($messageSucces) ?></div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['lien_reinitialisation_dev'])): ?>
                    <div class="alert alert-warning small">
                        <strong>Mode développement</strong> — lien de réinitialisation :
                        <a href="<?= nettoyer($_SESSION['lien_reinitialisation_dev']) ?>"><?= nettoyer($_SESSION['lien_reinitialisation_dev']) ?></a>
                    </div>
                    <?php unset($_SESSION['lien_reinitialisation_dev']); ?>
                <?php endif; ?>

                <form method="POST" action="<?= url('mot-de-passe-oublie') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                    <div class="mb-3">
                        <label class="form-label">Adresse email</label>
                        <input type="email" name="email" class="form-control" autocomplete="username" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Envoyer le lien</button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 mb-0 small">
            <a href="<?= url('connexion') ?>">Retour à la connexion</a>
        </p>
    </div>
</div>