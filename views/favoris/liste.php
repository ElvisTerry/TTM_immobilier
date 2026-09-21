<h1 class="h4 mb-4">Mes favoris</h1>

<?php if (empty($favoris)): ?>
    <p class="text-muted text-center py-5">Vous n'avez pas encore de favoris. Parcourez les annonces pour en ajouter.</p>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($favoris as $bien): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= url('biens/detail', [(int) $bien['id']]) ?>" class="text-decoration-none text-dark">
                <div class="card carte-bien h-100 shadow-sm">
                    <?php if (!empty($bien['photo_principale'])): ?>
                        <img loading="lazy" src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($bien['photo_principale']) ?>"
                             class="card-img-top" style="height:180px;object-fit:cover;" alt="<?= nettoyer($bien['titre']) ?>">
                    <?php else: ?>
                        <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="height:180px;">
                            <span class="text-muted small">Aucune photo</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title fs-6"><?= nettoyer($bien['titre']) ?></h5>
                        <p class="card-text small text-muted mb-1">📍 <?= nettoyer($bien['ville']) ?></p>
                        <p class="card-text fw-bold" style="color: var(--couleur-primaire);">
                            <?= number_format((float) $bien['prix'], 0, ',', ' ') ?> FCFA
                        </p>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
