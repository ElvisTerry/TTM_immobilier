
<?php if (!empty($messageSucces)): ?>
    <div class="alert alert-success"><?= nettoyer($messageSucces) ?></div>
<?php endif; ?>

<?php if (!empty($proposerWhatsappVisite)):
    $lienWhatsappVisite = urlWhatsApp(
        $bien['proprietaire_telephone'],
        "Bonjour, je viens de demander une visite pour \"{$bien['titre']}\" sur TTM. Pouvez-vous me confirmer la disponibilité ?"
    );
?>
    <?php if ($lienWhatsappVisite): ?>
        <div class="alert d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color: rgba(37,211,102,0.12); color: #1a8a45;">
            <span>💡 Pour une réponse plus rapide, confirmez directement sur WhatsApp.</span>
            <a href="<?= nettoyer($lienWhatsappVisite) ?>" target="_blank" rel="noopener" class="btn btn-sm" style="background-color:#25D366;color:#fff;">
                Confirmer sur WhatsApp
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($bien['statut_moderation'] === 'en_attente' && $estProprietaireDuBien): ?>
    <div class="alert alert-warning">⏳ Cette annonce est en attente de validation, elle n'est visible que par vous pour l'instant.</div>
<?php elseif ($bien['statut_moderation'] === 'rejete' && $estProprietaireDuBien): ?>
    <div class="alert alert-danger">❌ Cette annonce a été rejetée par notre équipe de modération.</div>
<?php endif; ?>

<div class="row">
    <div class="col-12 col-lg-7">
        <?php if (!empty($bien['photos'])): ?>
            <div id="galeriePhotos" class="carousel slide rounded overflow-hidden shadow-sm" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($bien['photos'] as $index => $photo): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($photo['chemin_fichier']) ?>"
                                 <?= $index === 0 ? '' : 'loading="lazy"' ?>
                                 class="d-block w-100 photo-ouvre-lightbox" style="max-height:400px;object-fit:cover;cursor:zoom-in;"
                                 alt="Photo <?= $index + 1 ?> sur <?= count($bien['photos']) ?> — <?= nettoyer($bien['titre']) ?>" data-index="<?= $index ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($bien['photos']) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#galeriePhotos" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Photo précédente</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#galeriePhotos" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Photo suivante</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-secondary bg-opacity-10 rounded d-flex align-items-center justify-content-center" style="height:300px;">
                <span class="text-muted">Aucune photo</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-5 mt-4 mt-lg-0">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <h1 class="h3 mb-0"><?= nettoyer($bien['titre']) ?></h1>
            <button type="button" id="boutonPartager" class="btn btn-outline-secondary btn-sm flex-shrink-0" aria-label="Partager cette annonce">🔗 Partager</button>
        </div>

        <?php if ($statistiquesAvis['total'] > 0): ?>
            <p class="mb-1">
                <span style="color: var(--couleur-or);">★</span>
                <strong><?= nettoyer((string) $statistiquesAvis['moyenne']) ?></strong>
                <span class="text-muted small">(<?= $statistiquesAvis['total'] ?> avis)</span>
            </p>
        <?php endif; ?>

        <p class="text-muted mb-1"> <?= nettoyer($bien['quartier']) ?>, <?= nettoyer($bien['ville']) ?></p>
        <p class="fs-4 fw-bold" style="color: var(--couleur-primaire);">
            <?= number_format((float) $bien['prix'], 0, ',', ' ') ?> FCFA
            <?= $bien['type_transaction'] === 'location' ? '/ mois' : '' ?>
        </p>

        <ul class="list-unstyled small text-muted">
            <li>Type : <?= nettoyer(ucfirst($bien['type_bien'])) ?></li>
            <li> Chambres : <?= (int) $bien['nombre_chambres'] ?></li>
            <li>
    Salles de bain :
    <?= (int) ($bien['nombre_salles_bain'] ?? 0) ?>
</li>
            <?php if ($bien['superficie_m2']): ?><li> Superficie : <?= nettoyer((string) $bien['superficie_m2']) ?> m²</li><?php endif; ?>
            <li> Meublé : <?= $bien['meuble'] ? 'Oui' : 'Non' ?></li>
            <li> Eau : <?= $bien['eau'] ? 'Oui' : 'Non' ?></li>
            <li> Électricité : <?= $bien['electricite'] ? 'Oui' : 'Non' ?></li>
            <li> Parking : <?= $bien['parking'] ? 'Oui' : 'Non' ?></li>
        </ul>

        <?php if (!empty($bien['description'])): ?>
            <p><?= nl2br(nettoyer($bien['description'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($bien['latitude']) && !empty($bien['longitude'])): ?>
            <div id="carteDetail" style="height:220px;border-radius:12px;overflow:hidden;" class="mb-3"></div>
            <script>
                (function () {
                    const carte = L.map('carteDetail', { zoomControl: false, dragging: false, scrollWheelZoom: false })
                        .setView([<?= (float) $bien['latitude'] ?>, <?= (float) $bien['longitude'] ?>], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(carte);
                    L.marker([<?= (float) $bien['latitude'] ?>, <?= (float) $bien['longitude'] ?>]).addTo(carte);
                })();
            </script>
        <?php endif; ?>

        <!-- Lien vers le profil du propriétaire : navigation par clic, comme demandé -->
        <a href="<?= url('profil', [(int) $bien['proprietaire_id']]) ?>" class="d-flex align-items-center gap-2 text-decoration-none text-dark mt-3 p-2 border rounded">
            <?php if (!empty($bien['proprietaire_photo'])): ?>
                <img loading="lazy" src="<?= cheminBase() ?>/uploads/avatars/<?= nettoyer($bien['proprietaire_photo']) ?>"
                     class="rounded-circle" style="width:40px;height:40px;object-fit:cover;" alt="">
            <?php else: ?>
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                    <?= nettoyer(mb_strtoupper(mb_substr($bien['proprietaire_nom'], 0, 1))) ?>
                </div>
            <?php endif; ?>
            <span class="small">Publié par <strong><?= nettoyer($bien['proprietaire_nom']) ?></strong></span>
        </a>

        <?php if ($estProprietaireDuBien): ?>
            <div class="d-grid gap-2 mt-3">
                
                <a href="<?= url('mes-visites') ?>" class="btn btn-outline-secondary">Demandes de visite reçues</a>
                <a href="<?= url('mes-annonces') ?>" class="text-center small">Gérer cette annonce (modifier, supprimer, statut) →</a>
            </div>
       <?php elseif (estConnecte()):
            $lienWhatsappContact = urlWhatsApp(
                $bien['proprietaire_telephone'],
                "Bonjour, je vous contacte au sujet de votre annonce \"{$bien['titre']}\" sur TTM (" . urlAbsolue('biens/detail', [(int) $bien['id']]) . ")."
            );
        ?>
            <a href="<?= url('biens/' . (int) $bien['id'] . '/visite') ?>" class="btn btn-primary w-100 mt-3">Réserver une visite</a>

            <?php if ($lienWhatsappContact): ?>
                <a href="<?= nettoyer($lienWhatsappContact) ?>" target="_blank" rel="noopener"
                   class="btn w-100 mt-2" style="background-color:#25D366;color:#fff;">
                    Contacter sur WhatsApp
                </a>
            <?php endif; ?>

            <a href="<?= url('biens/' . (int) $bien['id'] . '/messages/' . (int) $bien['proprietaire_id']) ?>" class="btn btn-outline-secondary w-100 mt-2">Contacter via TTM</a>

            <!-- Favori en un clic, sans rechargement de page -->
            <button type="button" id="boutonFavori" class="btn btn-outline-secondary w-100 mt-2"
                    data-bien-id="<?= (int) $bien['id'] ?>" data-favori="<?= $estFavori ? '1' : '0' ?>">
                <?= $estFavori ? '❤️ Retirer des favoris' : '🤍 Ajouter aux favoris' ?>
            </button>
            <label class="btn btn-outline-secondary w-100 mt-2" style="cursor:pointer;">
                <input type="checkbox" class="case-comparateur d-none"
                       data-id="<?= (int) $bien['id'] ?>"
                       data-titre="<?= nettoyer($bien['titre']) ?>"
                       data-prix="<?= (float) $bien['prix'] ?>"
                       data-photo="<?= !empty($bien['photos'][0]['chemin_fichier']) ? cheminBase() . '/uploads/biens/' . nettoyer($bien['photos'][0]['chemin_fichier']) : '' ?>">
                <span class="texte-comparateur">Ajouter au comparateur</span>
            </label>

            <!-- Signalement : discret, en petit lien plutôt qu'un bouton
                 imposant — une fonctionnalité qu'on ne veut pas mettre en
                 avant, juste rendre disponible pour les cas nécessaires. -->
            <button type="button" class="btn btn-link btn-sm text-muted w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modaleSignalement">
                🚩 Signaler cette annonce
            </button>
        <?php else: ?>
            <a href="<?= url('connexion') ?>" class="btn btn-primary w-100 mt-3">Connectez-vous pour contacter le propriétaire</a>
        <?php endif; ?>
    </div>
</div>

<?php if (estConnecte() && !$estProprietaireDuBien): ?>
<!-- Modale de signalement -->
<div class="modal fade" id="modaleSignalement" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('biens/' . (int) $bien['id'] . '/signaler') ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Signaler cette annonce</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($_SESSION['erreurs_signalement'])): ?>
                        <div class="alert alert-danger small">
                            <?php foreach ($_SESSION['erreurs_signalement'] as $e): ?><div><?= nettoyer($e) ?></div><?php endforeach; ?>
                        </div>
                        <?php unset($_SESSION['erreurs_signalement']); ?>
                    <?php endif; ?>

                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label small">Motif</label>
                        <select name="motif" class="form-select" required>
                            <option value="fausse_annonce">Fausse annonce</option>
                            <option value="prix_suspect">Prix suspect</option>
                            <option value="contenu_inapproprie">Contenu inapproprié</option>
                            <option value="arnaque_suspectee">Arnaque suspectée</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Détails (facultatif)</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger btn-sm">Envoyer le signalement</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==================== AVIS ==================== -->
<div class="row mt-5">
    <div class="col-12 col-lg-8">
        <h2 class="h5 mb-3">
            Avis
            <?php if ($statistiquesAvis['total'] > 0): ?>
                <span class="text-muted fw-normal">(<?= $statistiquesAvis['total'] ?>)</span>
            <?php endif; ?>
        </h2>

        <?php if (!empty($erreursAvis)): ?>
            <div class="alert alert-danger"><?php foreach ($erreursAvis as $e): ?><div><?= nettoyer($e) ?></div><?php endforeach; ?></div>
        <?php endif; ?>

        <?php if ($peutLaisserAvis): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="h6">Laisser un avis</h3>
                    <form method="POST" action="<?= url('biens/' . (int) $bien['id'] . '/avis') ?>">
                        <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                        <!-- Sélecteur d'étoiles en radio buttons — accessible
                             au clavier, fonctionne même sans JavaScript. -->
                        <div class="mb-2" id="selecteurEtoiles">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="note" value="<?= $i ?>" id="etoile<?= $i ?>" class="etoile-input">
                                <label for="etoile<?= $i ?>" class="etoile-label" title="<?= $i ?> étoile(s)">★</label>
                            <?php endfor; ?>
                        </div>

                        <textarea name="commentaire" class="form-control mb-2" rows="3" placeholder="Votre expérience avec ce bien (facultatif)"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">Publier mon avis</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($avis)): ?>
            <p class="text-muted small">Aucun avis pour l'instant.</p>
        <?php endif; ?>

        <div class="d-flex flex-column gap-3">
            <?php foreach ($avis as $unAvis): ?>
                <div class="border-bottom pb-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if (!empty($unAvis['auteur_photo'])): ?>
                            <img loading="lazy" src="<?= cheminBase() ?>/uploads/avatars/<?= nettoyer($unAvis['auteur_photo']) ?>"
                                 class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.8rem;">
                                <?= nettoyer(mb_strtoupper(mb_substr($unAvis['auteur_nom'], 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                        <strong class="small"><?= nettoyer($unAvis['auteur_nom']) ?></strong>
                        <span style="color: var(--couleur-or);" class="small"><?= str_repeat('★', (int) $unAvis['note']) . str_repeat('☆', 5 - (int) $unAvis['note']) ?></span>
                        <span class="text-muted small ms-auto"><?= nettoyer(date('d/m/Y', strtotime($unAvis['date_avis']))) ?></span>
                    </div>

                    <?php if (!empty($unAvis['commentaire'])): ?>
                        <p class="small mb-2"><?= nl2br(nettoyer($unAvis['commentaire'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($unAvis['reponse_proprietaire'])): ?>
                        <div class="bg-light rounded p-2 small reponse-avis" data-id="<?= (int) $unAvis['id'] ?>">
                            <strong>Réponse du propriétaire</strong> — <?= nl2br(nettoyer($unAvis['reponse_proprietaire'])) ?>
                        </div>
                    <?php elseif ($estProprietaireDuBien): ?>
                        <form class="formulaire-reponse-avis d-flex gap-2 mt-1" data-id="<?= (int) $unAvis['id'] ?>">
                            <input type="text" class="form-control form-control-sm champ-reponse" placeholder="Répondre à cet avis..." maxlength="500">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Envoyer</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($estProprietaireDuBien): ?>
<script>
(function () {
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const cheminBase = <?= json_encode(cheminBase()) ?>;

    document.querySelectorAll('.formulaire-reponse-avis').forEach((formulaire) => {
        formulaire.addEventListener('submit', async (e) => {
            e.preventDefault();
            const avisId = formulaire.dataset.id;
            const champ = formulaire.querySelector('.champ-reponse');
            const reponse = champ.value.trim();
            if (!reponse) return;

            const donnees = new FormData();
            donnees.append('reponse', reponse);
            donnees.append('csrf_token', csrfToken);

            try {
                const reponseServeur = await fetch(`${cheminBase}/avis/${avisId}/repondre`, { method: 'POST', body: donnees });
                const resultat = await reponseServeur.json();

                if (resultat.succes) {
                    const bloc = document.createElement('div');
                    bloc.className = 'bg-light rounded p-2 small reponse-avis';
                    bloc.innerHTML = '<strong>Réponse du propriétaire</strong> — ';
                    // textContent (pas innerHTML) pour la partie utilisateur :
                    // empêche toute injection HTML même si le texte contient
                    // des caractères spéciaux.
                    bloc.appendChild(document.createTextNode(resultat.reponse));
                    formulaire.replaceWith(bloc);
                } else {
                    alert(resultat.erreur || "Échec de l'envoi de la réponse.");
                }
            } catch (erreur) {
                alert('Erreur réseau, réessayez.');
            }
        });
    });
})();
</script>
<?php endif; ?>

<?php if (estConnecte() && !$estProprietaireDuBien): ?>
<script>
(function () {
    const bouton = document.getElementById('boutonFavori');
    if (!bouton) return;

    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const urlBasculer = <?= json_encode(url('biens/' . (int) $bien['id'] . '/favori')) ?>;

    bouton.addEventListener('click', async () => {
        const donnees = new FormData();
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(urlBasculer, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (resultat.succes) {
                bouton.textContent = resultat.estFavori ? '❤️ Retirer des favoris' : '🤍 Ajouter aux favoris';
                bouton.dataset.favori = resultat.estFavori ? '1' : '0';
            } else {
                alert(resultat.erreur || "Échec de l'opération.");
            }
        } catch (erreur) {
            alert('Erreur réseau, réessayez.');
        }
    });
})();
</script>
<?php endif; ?>

<?php if (!empty($bien['photos'])): ?>
<!-- ==================== Lightbox photo (Jour 17) ====================
     "Fait main" en JS vanilla, cohérent avec le reste du projet : zoom
     plein écran, navigation précédent/suivant, clavier (flèches, Échap),
     swipe tactile sur mobile. -->
<div id="lightbox" class="position-fixed top-0 start-0 w-100 h-100 d-none"
     role="dialog" aria-modal="true" aria-label="Galerie photo en grand format"
     style="background: rgba(0,0,0,0.92); z-index: 1080;">
    <button type="button" id="lightboxFermer" class="btn btn-link text-white position-absolute top-0 end-0 m-2 fs-3 text-decoration-none" aria-label="Fermer">&times;</button>
    <button type="button" id="lightboxPrecedent" class="btn btn-link text-white position-absolute top-50 start-0 translate-middle-y fs-1 text-decoration-none" aria-label="Photo précédente">&lsaquo;</button>
    <button type="button" id="lightboxSuivant" class="btn btn-link text-white position-absolute top-50 end-0 translate-middle-y fs-1 text-decoration-none" aria-label="Photo suivante">&rsaquo;</button>

    <div class="d-flex align-items-center justify-content-center h-100 px-5">
        <img id="lightboxImage" src="" alt="Photo agrandie" style="max-width:90%;max-height:85vh;object-fit:contain;">
    </div>
    <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3 text-white small" id="lightboxCompteur"></div>
</div>

<script>
(function () {
    const photos = <?= json_encode(array_map(fn($p) => cheminBase() . '/uploads/biens/' . $p['chemin_fichier'], $bien['photos']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const lightbox = document.getElementById('lightbox');
    const image = document.getElementById('lightboxImage');
    const compteur = document.getElementById('lightboxCompteur');
    let indexActuel = 0;
    let elementAvantOuverture = null;

    function afficher(index) {
        indexActuel = (index + photos.length) % photos.length; // boucle sur les extrémités
        image.src = photos[indexActuel];
        compteur.textContent = `${indexActuel + 1} / ${photos.length}`;
    }

    function ouvrir(index) {
        afficher(index);
        lightbox.classList.remove('d-none');
        document.body.style.overflow = 'hidden'; // empêche le scroll de la page derrière

        // Accessibilité clavier : on mémorise l'élément qui avait le focus
        // (la photo cliquée) pour lui rendre le focus à la fermeture, et on
        // déplace immédiatement le focus dans la lightbox — sinon un
        // utilisateur au clavier ou lecteur d'écran resterait "coincé" sur
        // un élément désormais masqué derrière l'overlay.
        elementAvantOuverture = document.activeElement;
        document.getElementById('lightboxFermer').focus();
    }

    function fermer() {
        lightbox.classList.add('d-none');
        document.body.style.overflow = '';
        if (elementAvantOuverture) {
            elementAvantOuverture.focus();
        }
    }

    document.querySelectorAll('.photo-ouvre-lightbox').forEach((img) => {
        img.addEventListener('click', () => ouvrir(parseInt(img.dataset.index, 10)));
    });

    document.getElementById('lightboxFermer').addEventListener('click', fermer);
    document.getElementById('lightboxPrecedent').addEventListener('click', () => afficher(indexActuel - 1));
    document.getElementById('lightboxSuivant').addEventListener('click', () => afficher(indexActuel + 1));

    // Clic en dehors de l'image (sur le fond sombre) : ferme aussi.
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) fermer(); });

    // Navigation clavier : flèches et Échap, uniquement quand la lightbox est ouverte.
    document.addEventListener('keydown', (e) => {
        if (lightbox.classList.contains('d-none')) return;
        if (e.key === 'Escape') fermer();
        if (e.key === 'ArrowLeft') afficher(indexActuel - 1);
        if (e.key === 'ArrowRight') afficher(indexActuel + 1);

        // Piège de focus : tant que la lightbox est ouverte, Tab reste
        // cantonné à ses 3 boutons — sans ça, la tabulation "s'échapperait"
        // vers des liens de la page masqués derrière l'overlay sombre.
        if (e.key === 'Tab') {
            const boutonsFocusables = [
                document.getElementById('lightboxFermer'),
                document.getElementById('lightboxPrecedent'),
                document.getElementById('lightboxSuivant'),
            ];
            const indexCourant = boutonsFocusables.indexOf(document.activeElement);
            e.preventDefault();
            const prochainIndex = e.shiftKey
                ? (indexCourant - 1 + boutonsFocusables.length) % boutonsFocusables.length
                : (indexCourant + 1) % boutonsFocusables.length;
            boutonsFocusables[prochainIndex === -1 ? 0 : prochainIndex].focus();
        }
    });

    // Swipe tactile (mobile) : comparaison simple de la position de départ/fin.
    let departX = null;
    lightbox.addEventListener('touchstart', (e) => { departX = e.touches[0].clientX; });
    lightbox.addEventListener('touchend', (e) => {
        if (departX === null) return;
        const diff = e.changedTouches[0].clientX - departX;
        if (Math.abs(diff) > 50) afficher(indexActuel + (diff < 0 ? 1 : -1));
        departX = null;
    });
})();
</script>
<?php endif; ?>

<script>
(function () {
    const bouton = document.getElementById('boutonPartager');
    const titre = <?= json_encode($bien['titre'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const lien = window.location.href;

    bouton.addEventListener('click', async () => {
        // navigator.share() : la vraie boîte de partage native du
        // téléphone (WhatsApp, SMS, etc.) — disponible surtout sur mobile.
        if (navigator.share) {
            try {
                await navigator.share({ title: titre, url: lien });
            } catch (erreur) {
                // L'utilisateur a simplement annulé le partage : rien à faire.
            }
            return;
        }

        // Repli sur ordinateur (pas de navigator.share) : on copie le lien.
        try {
            await navigator.clipboard.writeText(lien);
            const texteOriginal = bouton.textContent;
            bouton.textContent = '✓ Lien copié';
            setTimeout(() => { bouton.textContent = texteOriginal; }, 2000);
        } catch (erreur) {
            alert('Copiez ce lien : ' + lien);
        }
    });
})();
<script>window.TTM_URL_COMPARATEUR = <?= json_encode(url('biens/comparateur')) ?>;</script>
<script src="<?= cheminBase() ?>/js/comparateur.js"></script>

</script>
<?php if (!empty($biensSimilaires)): ?>
<!-- ==================== Biens similaires ==================== -->
<div class="mt-5">
    <h2 class="h5 mb-3">Biens similaires</h2>
    <div class="row g-4">
        <?php foreach ($biensSimilaires as $similaire): ?>
            <div class="col-12 col-md-6 col-xl-3">
                <a href="<?= url('biens/detail', [(int) $similaire['id']]) ?>" class="text-decoration-none text-dark">
                    <div class="card carte-bien h-100 shadow-sm">
                        <?php if (!empty($similaire['photo_principale'])): ?>
                            <img loading="lazy" src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($similaire['photo_principale']) ?>"
                                 class="card-img-top" style="height:160px;object-fit:cover;"
                                 alt="<?= nettoyer($similaire['titre']) ?>">
                        <?php else: ?>
                            <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="height:160px;">
                                <span class="text-muted small">Aucune photo</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title fs-6 mb-1"><?= nettoyer($similaire['titre']) ?></h3>
                            <p class="card-text small text-muted mb-1">📍 <?= nettoyer($similaire['ville']) ?></p>
                            <p class="card-text fw-bold mb-0" style="color: var(--couleur-primaire);">
                                <?= number_format((float) $similaire['prix'], 0, ',', ' ') ?> FCFA
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
