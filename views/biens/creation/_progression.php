<?php
/**
 * views/biens/creation/_progression.php
 * ------------------------------------------
 * Composant réutilisable inclus en haut de chaque étape de l'assistant.
 * $etapeActuelle doit être définie (1 à 5) par la vue qui l'inclut.
 * Le préfixe "_" dans le nom du fichier est une convention (pas une règle
 * PHP) pour signaler "ceci est un fragment inclus, pas une page complète".
 */
$etapes = [1 => 'Infos', 2 => 'Équipements', 3 => 'Localisation', 4 => 'Photos', 5 => 'Récapitulatif'];
?>
<div class="d-flex justify-content-between mb-4 flex-wrap gap-2">
    <?php foreach ($etapes as $numero => $libelle): ?>
        <div class="d-flex align-items-center gap-2 <?= $numero > $etapeActuelle ? 'text-muted' : '' ?>">
            <span class="rounded-circle d-flex align-items-center justify-content-center"
                  style="width:28px;height:28px;font-size:0.85rem;
                         background-color: <?= $numero <= $etapeActuelle ? 'var(--couleur-primaire)' : '#e9ecef' ?>;
                         color: <?= $numero <= $etapeActuelle ? '#fff' : '#6c757d' ?>;">
                <?= $numero < $etapeActuelle ? '✓' : $numero ?>
            </span>
            <span class="small d-none d-md-inline"><?= $libelle ?></span>
        </div>
    <?php endforeach; ?>
</div>
