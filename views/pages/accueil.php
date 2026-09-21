<section class="hero-accueil">
    <div class="contenu-hero" align="center">
        <span class="hero-eyebrow">TTM Cameroun</span>
        <h1 class="hero-titre fw-bold mb-3">Trouve Ton Milieu au Cameroun en un clic.</h1>
        <p class="hero-soustitre mb-4">Chambres, studios, appartements et maisons, à louer ou à vendre, partout au Cameroun.</p>
    </div>
     <form method="GET" action="<?= url('biens/recherche') ?>" class="barre-recherche-accueil">
                <input type="text" name="ville" placeholder="Ville ou quartier" autocomplete="off">
                <select name="type_bien">
                    <option value="">Tous les logements</option>
                    <option value="chambre">Chambre</option>
                    <option value="studio">Studio</option>
                    <option value="appartement">Appartement</option>
                    <option value="maison">Maison</option>
                </select>
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
    <div class="grille-stats">
                <div class="carte-stat">
                    <div class="carte-stat-chiffre"><?= $totalAnnonces ?>+</div>
                    <div class="carte-stat-libelle">Annonces</div>
                </div>
                <div class="carte-stat">
                    <div class="carte-stat-chiffre"><?= $totalVilles ?></div>
                    <div class="carte-stat-libelle">Villes couvertes</div>
                </div>
                <div class="carte-stat">
                    <div class="carte-stat-chiffre"><?= $totalAvis ?></div>
                    <div class="carte-stat-libelle">Avis de locataires</div>
                </div>
            </div>
</section>

<!-- ==================== RAIL DE CATÉGORIES (élément signature) ==================== -->
<section class="mb-4">
    <?php
    $categories = [
        '' => [ 'libelle' => 'Tout voir'],
        'chambre' => [ 'libelle' => 'Chambre'],
        'studio' => [ 'libelle' => 'Studio'],
        'appartement' => [ 'libelle' => 'Appartement'],
        'maison' => [ 'libelle' => 'Maison'],
    ];
    ?>
    <div class="rail-categories">
        <?php foreach ($categories as $valeur => $categorie): ?>
            <a class="item-categorie" href="<?= url('biens/recherche') ?><?= $valeur ? '?type_bien=' . $valeur : '' ?>">
                <span class="libelle"><?= $categorie['libelle'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ==================== LOCATAIRE / PROPRIÉTAIRE — explication de l'app ==================== -->
<section class="section-roles">
    <div class="text-center">
        <div class="bascule-roles" role="tablist" aria-label="Découvrir TTM selon votre profil">
            <button type="button" class="bouton-role actif" data-role="locataire" role="tab" aria-selected="true">Je cherche un logement</button>
            <button type="button" class="bouton-role" data-role="proprietaire" role="tab" aria-selected="false">Je loue ou je vends</button>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <div class="contenu-role actif" id="role-locataire">
                <ul class="liste-role">
                    <li>
                        <span class="numero-role">1</span>
                        <div>
                            <h4>Cherchez par ville, quartier et budget</h4>
                            <p>Filtrez par type de bien et prix, ou affinez par rayon autour d'un point précis sur la carte.</p>
                        </div>
                    </li>
                    <li>
                        <span class="numero-role">2</span>
                        <div>
                            <h4>Échangez directement avec le propriétaire</h4>
                            <p>Une messagerie intégrée à chaque annonce, sans donner votre numéro tant que vous n'êtes pas prêt.</p>
                        </div>
                    </li>
                    <li>
                        <span class="numero-role">3</span>
                        <div>
                            <h4>Réservez une visite en un clic</h4>
                            <p>Le propriétaire accepte ou refuse selon ses disponibilités réelles - plus d'allers-retours par téléphone.</p>
                        </div>
                    </li>
                    <li>
                        <span class="numero-role">4</span>
                        <div>
                            <h4>Sauvegardez une alerte</h4>
                            <p>Recevez une notification dès qu'une annonce correspond à vos critères, sans revenir chercher chaque jour.</p>
                        </div>
                    </li>
                </ul>
                <a href="<?= url('biens/recherche') ?>" class="btn btn-accent">Explorer les annonces</a>
            </div>

            <div class="contenu-role" id="role-proprietaire">
                <ul class="liste-role">
                    <li>
                        <span class="numero-role">1</span>
                        <div>
                            <h4>Publiez en 5 étapes, gratuitement</h4>
                            <p>Infos, équipements, localisation sur la carte, photos, récapitulatif - quelques minutes suffisent.</p>
                        </div>
                    </li>
                    <li>
                        <span class="numero-role">2</span>
                        <div>
                            <h4>Chaque annonce est vérifiée</h4>
                            <p>Notre équipe valide le contenu avant publication publique - la confiance des visiteurs, ça se construit.</p>
                        </div>
                    </li>
                    <li>
                        <span class="numero-role">3</span>
                        <div>
                            <h4>Gérez vos disponibilités</h4>
                            <p>Bloquez des créneaux, acceptez ou refusez les demandes de visite - le calendrier se met à jour tout seul.</p>
                        </div>
                    </li>
                    <li>
                        <span class="numero-role">4</span>
                        <div>
                            <h4>Suivez vos statistiques</h4>
                            <p>Vues, taux de réponse, avis reçus - un tableau de bord pour savoir ce qui marche.</p>
                        </div>
                    </li>
                </ul>
                <a href="<?= url('inscription') ?>" class="btn btn-accent">Publier une annonce</a>
            </div>

        </div>
    </div>
</section>

<script>
(function () {
    const boutons = document.querySelectorAll('.bouton-role');
    boutons.forEach((bouton) => {
        bouton.addEventListener('click', () => {
            boutons.forEach((b) => { b.classList.remove('actif'); b.setAttribute('aria-selected', 'false'); });
            bouton.classList.add('actif');
            bouton.setAttribute('aria-selected', 'true');

            document.querySelectorAll('.contenu-role').forEach((c) => c.classList.remove('actif'));
            document.getElementById('role-' + bouton.dataset.role).classList.add('actif');
        });
    });
})();
</script>

<!-- ==================== DERNIÈRES ANNONCES ==================== -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Dernières annonces</h2>
        <a href="<?= url('biens/recherche') ?>" class="small">Voir toutes les annonces</a>
    </div>

    <?php if (empty($dernieresAnnonces)): ?>
        <p class="text-muted text-center py-5">Aucune annonce publiée pour l'instant. Revenez bientôt !</p>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($dernieresAnnonces as $index => $annonce): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <?php $balise = estConnecte() ? 'a' : 'div'; ?>
                <<?= $balise ?>
                    <?php if (estConnecte()): ?>href="<?= url('biens/detail', [(int) $annonce['id']]) ?>"<?php endif; ?>
                    class="text-decoration-none text-dark d-block position-relative">
                    <div class="card carte-bien h-100">
                        <div class="position-relative overflow-hidden">
                            <?php if ($annonce['photo_url']): ?>
                                <img src="<?= nettoyer($annonce['photo_url']) ?>" class="card-img-top"
                                     <?= $index < 3 ? '' : 'loading="lazy"' ?>
                                     style="height:190px;object-fit:cover;" alt="<?= nettoyer($annonce['titre']) ?>">
                            <?php else: ?>
                                <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="height:190px;">
                                    <span class="text-muted small">Aucune photo</span>
                                </div>
                            <?php endif; ?>
                            <span class="badge position-absolute top-0 start-0 m-2" style="background-color: var(--c-foret);">
                                <?= $annonce['type_transaction'] === 'location' ? 'Location' : 'Vente' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title fs-6"><?= nettoyer($annonce['titre']) ?></h3>
                            <p class="card-text small text-muted mb-1"><?= nettoyer($annonce['ville']) ?></p>
                            <p class="card-text fw-bold fs-5 mb-0" style="color: var(--c-foret);">
                                <?= number_format((float) $annonce['prix'], 0, ',', ' ') ?> FCFA
                                <?= $annonce['type_transaction'] === 'location' ? '<span class="fs-6 fw-normal text-muted">/ mois</span>' : '' ?>
                            </p>
                            <?php if (!estConnecte()): ?>
                                <div class="small mt-2" style="color: var(--c-terre);">
                                    <a href="<?= url('connexion') ?>">Connectez-vous</a> pour voir le détail
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </<?= $balise ?>>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ==================== COMMENT ÇA MARCHE ==================== -->
<section class="mb-5">
    <h2 class="h5 mb-4">Comment ça marche</h2>
    <div class="row">
        <div class="col-12 col-md-4">
            <div class="etape">
                <span class="etape-chiffre">1</span>
                <div>
                    <h3>Recherchez</h3>
                    <p>Filtrez par ville, quartier, prix et type de bien.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="etape">
                <span class="etape-chiffre">2</span>
                <div>
                    <h3>Visitez et échangez</h3>
                    <p>Réservez une visite, discutez via la messagerie.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="etape">
                <span class="etape-chiffre">3</span>
                <div>
                    <h3>Emménagez</h3>
                    <p>En confiance, avis et modération à l'appui.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== CTA PROPRIÉTAIRES ==================== -->
<?php if (!estConnecte()): ?>
<section class="bandeau-cta mb-4">
    <h2 class="h4 mb-2">Vous avez un bien à louer ou à vendre ?</h2>
    <p class="mb-3">Publiez gratuitement et touchez des milliers de visiteurs.</p>
    <a href="<?= url('inscription') ?>" class="btn btn-accent">Inscrivez-vous et publiez</a>
</section>
<?php endif; ?>