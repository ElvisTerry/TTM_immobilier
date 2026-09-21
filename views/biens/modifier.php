<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Modifier l'annonce</h1>
            <a href="<?= url('mes-annonces') ?>" class="small"> Retour à la gestion</a>
        </div>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($erreurs as $e): ?><li><?= nettoyer($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="<?= url('biens/' . (int) $bien['id'] . '/modifier') ?>">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Titre</label>
                        <input type="text" name="titre" class="form-control" value="<?= nettoyer($bien['titre']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= nettoyer($bien['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Type de bien</label>
                            <select name="type_bien" class="form-select">
                                <?php foreach (['chambre', 'studio', 'appartement', 'maison'] as $type): ?>
                                    <option value="<?= $type ?>" <?= $bien['type_bien'] === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Transaction</label>
                            <select name="type_transaction" class="form-select">
                                <?php foreach (['location', 'vente'] as $type): ?>
                                    <option value="<?= $type ?>" <?= $bien['type_transaction'] === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Prix (FCFA)</label>
                            <input type="number" name="prix" class="form-control" min="1" value="<?= nettoyer((string) $bien['prix']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Ville</label>
                            <input type="text" name="ville" class="form-control" value="<?= nettoyer($bien['ville']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Quartier</label>
                            <input type="text" name="quartier" class="form-control" value="<?= nettoyer($bien['quartier'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 col-md-4 mb-3">
                            <label class="form-label">Chambres</label>
                            <input type="number" name="nombre_chambres" class="form-control" min="0" value="<?= (int) $bien['nombre_chambres'] ?>">
                        </div>
                        <div class="col-6 col-md-4 mb-3">
                            <label class="form-label">Salles de bain</label>
                            <input type="number" name="nombre_salles_bain" class="form-control" min="0" max="20"
                                   value="<?= (int) ($bien['nombre_salles_bain'] ?? 0) ?>">
                        </div>
                        <div class="col-6 col-md-4 mb-3">
                            <label class="form-label">Superficie (m²)</label>
                            <input type="number" step="0.1" name="superficie_m2" class="form-control" min="0" value="<?= nettoyer((string) ($bien['superficie_m2'] ?? '')) ?>">
                        </div>
                    </div>

                    <label class="form-label d-block">Équipements</label>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <?php foreach (['meuble' => 'Meublé', 'eau' => 'Eau', 'electricite' => 'Électricité', 'parking' => 'Parking'] as $champ => $libelle): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="<?= $champ ?>" id="<?= $champ ?>" <?= !empty($bien[$champ]) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $champ ?>"><?= $libelle ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Enregistrer les modifications</button>
                 
                </form>
            </div>
        </div>

        <!-- ==================== Gestion des photos (indépendante du formulaire ci-dessus) ==================== -->
        <div class="card shadow-sm mt-3">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Photos</h2>
                <p class="small text-muted">Ajouter ou supprimer une photo repasse également l'annonce en modération.</p>

                <div id="messageErreurPhoto" class="alert alert-danger d-none"></div>

                <div id="listePhotosExistantes" class="d-flex flex-wrap gap-2 mb-3">
                    <?php foreach ($bien['photos'] as $photo): ?>
                        <div class="photo-vignette" data-photo-id="<?= (int) $photo['id'] ?>">
                            <img src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($photo['chemin_fichier']) ?>" alt="Photo"
                                 style="width:90px;height:90px;object-fit:cover;display:block;">
                            <button type="button" class="bouton-suppression-photo" title="Supprimer" aria-label="Supprimer cette photo">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="zoneDepotModif" class="border border-2 border-dashed rounded-3 p-3 text-center"
                     style="cursor:pointer; border-color: var(--couleur-primaire-claire) !important;">
                    <p class="mb-1 small">📷 Cliquez ou déposez une photo ici</p>
                    <p class="small text-muted mb-0">JPG, PNG ou WEBP, 5 Mo max, 8 photos maximum</p>
                    <input type="file" id="entreeFichierModif" accept="image/jpeg,image/png,image/webp" multiple hidden>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const urlAjout = <?= json_encode(url('biens/' . (int) $bien['id'] . '/photos/ajouter')) ?>;
    const urlSuppressionBase = <?= json_encode(url('biens/' . (int) $bien['id'] . '/photos')) ?>;

    const liste = document.getElementById('listePhotosExistantes');
    const zoneDepot = document.getElementById('zoneDepotModif');
    const entreeFichier = document.getElementById('entreeFichierModif');
    const messageErreur = document.getElementById('messageErreurPhoto');

    function afficherErreur(texte) {
        messageErreur.textContent = texte;
        messageErreur.classList.remove('d-none');
    }
    function masquerErreur() {
        messageErreur.classList.add('d-none');
    }

    async function envoyerFichier(fichier) {
        if (liste.children.length >= 8) {
            afficherErreur('Maximum 8 photos par annonce.');
            return;
        }
        masquerErreur();

        const donnees = new FormData();
        donnees.append('photo', fichier);
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(urlAjout, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (!resultat.succes) {
                afficherErreur(resultat.erreur || "Échec de l'envoi de la photo.");
                return;
            }

            const vignette = document.createElement('div');
            vignette.className = 'photo-vignette';
            vignette.dataset.photoId = resultat.photoId;
            vignette.innerHTML = `<img src="${resultat.url}" alt="Photo" style="width:90px;height:90px;object-fit:cover;display:block;"><button type="button" class="bouton-suppression-photo" title="Supprimer" aria-label="Supprimer cette photo">&times;</button>`;
            liste.appendChild(vignette);
        } catch (erreur) {
            afficherErreur("Erreur réseau pendant l'envoi de la photo.");
        }
    }

    zoneDepot.addEventListener('click', () => entreeFichier.click());
    entreeFichier.addEventListener('change', (e) => Array.from(e.target.files).forEach(envoyerFichier));

    ['dragenter', 'dragover'].forEach((evt) => {
        zoneDepot.addEventListener(evt, (e) => { e.preventDefault(); zoneDepot.classList.add('bg-light'); });
    });
    ['dragleave', 'drop'].forEach((evt) => {
        zoneDepot.addEventListener(evt, (e) => { e.preventDefault(); zoneDepot.classList.remove('bg-light'); });
    });
    zoneDepot.addEventListener('drop', (e) => Array.from(e.dataTransfer.files).forEach(envoyerFichier));

    liste.addEventListener('click', async (e) => {
        if (!e.target.classList.contains('bouton-suppression-photo')) return;

        const vignette = e.target.closest('.photo-vignette');
        const photoId = vignette.dataset.photoId;

        if (!(await window.confirmerAction('Supprimer cette photo ?'))) return;

        const donnees = new FormData();
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(`${urlSuppressionBase}/${photoId}/supprimer`, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (resultat.succes) {
                vignette.remove();
            } else {
                afficherErreur(resultat.erreur || 'Échec de la suppression.');
            }
        } catch (erreur) {
            afficherErreur('Erreur réseau, réessayez.');
        }
    });
})();
</script>