<h1 class="h4 mb-4">Messagerie</h1>

<?php if (empty($conversations)): ?>
    <p class="text-muted text-center py-5">Aucune conversation pour le moment.</p>
<?php endif; ?>

<div class="list-group">
    <?php foreach ($conversations as $conv): ?>
        <a href="<?= url('biens/' . (int) $conv['bien_id'] . '/messages/' . (int) $conv['autre_id']) ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <div class="fw-semibold"><?= nettoyer($conv['autre_nom']) ?></div>
                <div class="small text-muted"><?= nettoyer($conv['bien_titre']) ?></div>
            </div>
            <?php if ($conv['non_lus'] > 0): ?>
                <span class="badge rounded-pill" style="background-color: var(--couleur-accent);"><?= (int) $conv['non_lus'] ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($page > 1 || $pageSuivanteExiste): ?>
    <div class="d-flex justify-content-between mt-3">
        <?php if ($page > 1): ?>
            <a href="<?= url('messages') ?>?page=<?= $page - 1 ?>" class="btn btn-sm btn-outline-secondary">← Plus récent</a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($pageSuivanteExiste): ?>
            <a href="<?= url('messages') ?>?page=<?= $page + 1 ?>" class="btn btn-sm btn-outline-secondary">Plus ancien →</a>
        <?php endif; ?>
    </div>
<?php endif; ?>