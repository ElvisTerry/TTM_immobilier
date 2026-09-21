<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        <?php if (!empty($messageSucces)): ?>
            <div class="alert alert-success"><?= nettoyer($messageSucces) ?></div>
        <?php endif; ?>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= nettoyer($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulaire dédié à l'avatar : séparé du formulaire d'infos
             pour ne pas avoir à ressaisir nom/bio à chaque changement de photo. -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <?php if (!empty($profil['photo_profil'])): ?>
                    <img loading="lazy" src="<?= cheminBase() ?>/uploads/avatars/<?= nettoyer($profil['photo_profil']) ?>"
                         class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover;" alt="Avatar actuel">
                <?php endif; ?>

                <form method="POST" action="<?= url('mon-profil/avatar') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                    <input type="file" name="avatar" class="form-control mb-2" accept="image/jpeg,image/png,image/webp" required>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Changer la photo</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">Mes informations</h2>

                <form method="POST" action="<?= url('mon-profil') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Nom complet</label>
                        <input type="text" name="nom" class="form-control" value="<?= nettoyer($profil['nom']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control" value="<?= nettoyer($profil['telephone'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Biographie</label>
                        <textarea name="bio" class="form-control" rows="4" maxlength="500"><?= nettoyer($profil['bio'] ?? '') ?></textarea>
                        <div class="form-text">500 caractères maximum, visible sur votre profil public.</div>
                    </div>

    

</div>

<!-- ========================================================= -->
<!-- CHANGEMENT D'ADRESSE EMAIL                               -->
<!-- ========================================================= -->

<div class="card mb-4">

    <div class="card-body">

      

        <!-- Email actuellement associé au compte -->
        <div class="mb-3">

            <label class="form-label">
                Adresse email actuelle
            </label>

            <input
                type="email"
                class="form-control"
                value="<?= htmlspecialchars($profil['email'], ENT_QUOTES, 'UTF-8') ?>"
                disabled
            >

            

        </div>

        

    </div>

</div>


                    <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                </form>

               <a href="<?= url('profil', [(int) $profil['id']]) ?>" class="d-block text-center mt-3 small">
                    Voir mon profil public
                </a>
                <a href="<?= url('mon-compte/supprimer') ?>" class="d-block text-center mt-2 small text-danger">
                    Supprimer mon compte
                </a>

            </div>
        </div>
    </div>
</div>
