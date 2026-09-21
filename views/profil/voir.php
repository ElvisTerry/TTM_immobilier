<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm text-center p-4">
            <?php if (!empty($profil['photo_profil'])): ?>
                <img loading="lazy" src="<?= cheminBase() ?>/uploads/avatars/<?= nettoyer($profil['photo_profil']) ?>"
                     class="rounded-circle mx-auto mb-3" style="width:120px;height:120px;object-fit:cover;"
                     alt="Photo de <?= nettoyer($profil['nom']) ?>">
            <?php else: ?>
                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center bg-light"
                     style="width:120px;height:120px;font-size:2.5rem;color:var(--couleur-primaire);">
                    <?= nettoyer(mb_strtoupper(mb_substr($profil['nom'], 0, 1))) ?>
                </div>
            <?php endif; ?>

            <h1 class="h4 mb-1"><?= nettoyer($profil['nom']) ?></h1>
            <p class="text-muted small mb-2">
                <?= $profil['role'] === 'proprietaire' ? 'Propriétaire' : 'Locataire / Acheteur' ?>
                · Membre depuis <?= date('F Y', strtotime($profil['date_creation'])) ?>
            </p>

           <?php if ($moyenneAvis !== null): ?>
                <p class="mb-2"><span style="color: var(--couleur-or);">★</span> <strong><?= nettoyer((string) $moyenneAvis) ?></strong> <span class="text-muted small">note moyenne</span></p>
            <?php endif; ?>

            <?php if ($libelleTempsReponse !== null): ?>
                <p class="mb-2 small">
                    <span style="color: var(--couleur-primaire);">⚡</span>
                    <?= nettoyer($libelleTempsReponse) ?>
                </p>
            <?php endif; ?>


            <?php if (!empty($profil['bio'])): ?>
                <p class="mt-3"><?= nl2br(nettoyer($profil['bio'])) ?></p>
            <?php endif; ?>

            <?php if (estConnecte() && (int) $_SESSION['utilisateur_id'] === (int) $profil['id']): ?>
                <a href="<?= url('mon-profil') ?>" class="btn btn-outline-primary mt-3">Modifier mon profil</a>
            <?php endif; ?>
        </div>

        <!-- Les annonces de ce propriétaire et ses avis s'afficheront ici
             à partir du Jour 5 (annonces) et du Jour 11 (avis). -->
    </div>
</div>
