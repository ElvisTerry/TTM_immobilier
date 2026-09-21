<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= nettoyer($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-danger">
            <div class="card-body p-4">
                <h2 class="h5 mb-3 text-danger">⚠️ Supprimer mon compte</h2>

                <p class="small">Cette action est <strong>définitive et irréversible</strong>. Voici ce qui va se passer :</p>

                <ul class="small text-muted">
                    <li>Votre nom, email, téléphone, biographie et photo de profil seront <strong>définitivement effacés</strong>.</li>
                    <li>Vous ne pourrez plus jamais vous connecter avec ce compte.</li>
                    <?php if (!empty($mesAnnonces)): ?>
                        <li><strong><?= count($mesAnnonces) ?> annonce(s)</strong> que vous avez publiée(s) seront supprimées, avec leurs photos.</li>
                    <?php endif; ?>
                    <li>Vos favoris et alertes de recherche seront supprimés.</li>
                    <li>Vos demandes de visite encore actives seront annulées.</li>
                    <li>Les messages et avis déjà échangés avec d'autres utilisateurs resteront visibles pour eux, mais afficheront "Compte supprimé" à la place de votre nom.</li>
                </ul>

                <?php if (!empty($mesAnnonces)): ?>
                    <div class="alert alert-warning small">
                        <strong>Annonces qui seront supprimées :</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($mesAnnonces as $annonce): ?>
                                <li><?= nettoyer($annonce['titre']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('mon-compte/supprimer') ?>" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Confirmez avec votre mot de passe</label>
                        <input type="password" name="mot_de_passe" class="form-control" autocomplete="current-password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tapez <strong>SUPPRIMER</strong> pour confirmer</label>
                        <input type="text" name="confirmation_texte" class="form-control" placeholder="SUPPRIMER" required>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= url('mon-profil') ?>" class="btn btn-outline-secondary w-100">Annuler</a>
                        <button type="submit" class="btn btn-danger w-100">Supprimer définitivement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
