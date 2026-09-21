<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <?php $etapeActuelle = 4; require __DIR__ . '/_progression.php'; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Ajoutez des photos</h1>

                <div id="messageErreurPhoto" class="alert alert-danger d-none"></div>

                <!-- Zone de glisser-déposer -->
                <div id="zoneDepot" class="border border-2 border-dashed rounded-3 p-4 text-center mb-3"
                     style="cursor:pointer; border-color: var(--couleur-primaire-claire) !important;">
                    <p class="mb-1">📷 Glissez vos photos ici, ou cliquez pour parcourir</p>
                    <p class="small text-muted mb-0">JPG, PNG ou WEBP - compression automatique, 8 photos maximum</p>
                    <input type="file" id="entreeFichier" accept="image/jpeg,image/png,image/webp" multiple hidden>
                </div>

                <!-- Vignettes des photos déjà envoyées : glissables pour réordonner -->
                <div id="listePhotos" class="d-flex flex-wrap gap-2 mb-4">
                    <?php foreach (($_SESSION['assistant_annonce']['photos'] ?? []) as $photo): ?>
                        <div class="photo-vignette" draggable="true" data-filename="<?= nettoyer($photo) ?>">
                            <img src="<?= cheminBase() ?>/uploads/biens/<?= nettoyer($photo) ?>" alt="Photo"
                                 style="width:90px;height:90px;object-fit:cover;display:block;">
                            <button type="button" class="bouton-suppression-photo" title="Supprimer" aria-label="Supprimer cette photo">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p class="small text-muted">Glissez une vignette pour réordonner les photos (la première sera la photo principale).</p>

                <form method="POST" action="<?= url('biens/creer/photos') ?>" id="formulaireContinuer">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                    <input type="hidden" name="ordre" id="champOrdre" value="">

                    <div class="d-flex gap-2">
                        <a href="<?= url('biens/creer/localisation') ?>" class="btn btn-outline-secondary flex-fill">Retour</a>
                        <button type="submit" class="btn btn-primary flex-fill">Continuer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {

    'use strict';

    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const urlUpload = <?= json_encode(url('biens/creer/photos/upload')) ?>;
    const urlSuppression = <?= json_encode(url('biens/creer/photos/supprimer')) ?>;

    const zoneDepot = document.getElementById('zoneDepot');
    const entreeFichier = document.getElementById('entreeFichier');
    const listePhotos = document.getElementById('listePhotos');
    const messageErreur = document.getElementById('messageErreurPhoto');
    const champOrdre = document.getElementById('champOrdre');
    const formulaire = document.getElementById('formulaireContinuer');

    const MAX_PHOTOS = 8;

    /*
     * Une photo provenant d'un téléphone peut facilement dépasser
     * plusieurs Mo.
     *
     * Nous la réduisons AVANT de l'envoyer au serveur.
     */
    const LARGEUR_MAX = 1600;
    const HAUTEUR_MAX = 1600;

    /*
     * Taille cible approximative du fichier envoyé au serveur.
     *
     * Cela évite d'envoyer les énormes photos originales
     * des appareils mobiles.
     */
    const TAILLE_CIBLE = 1.8 * 1024 * 1024;

    function afficherErreur(texte) {
        messageErreur.textContent = texte;
        messageErreur.classList.remove('d-none');
    }

    function masquerErreur() {
        messageErreur.textContent = '';
        messageErreur.classList.add('d-none');
    }

    function ajouterVignette(nomFichier, url) {

        const vignette = document.createElement('div');

        vignette.className = 'photo-vignette';
        vignette.draggable = true;
        vignette.dataset.filename = nomFichier;

        const image = document.createElement('img');

        image.src = url;
        image.alt = 'Photo du bien';
        image.loading = 'lazy';

        image.style.width = '90px';
        image.style.height = '90px';
        image.style.objectFit = 'cover';
        image.style.display = 'block';

        const bouton = document.createElement('button');

        bouton.type = 'button';
        bouton.className = 'bouton-suppression-photo';
        bouton.title = 'Supprimer';
        bouton.setAttribute('aria-label', 'Supprimer cette photo');
        bouton.innerHTML = '&times;';

        vignette.appendChild(image);
        vignette.appendChild(bouton);

        listePhotos.appendChild(vignette);

        activerVignette(vignette);
    }

    /*
     * Compression de l'image directement dans le navigateur.
     *
     * Cela fonctionne sur les navigateurs modernes Android,
     * iPhone et ordinateur.
     */
    async function compresserPhoto(fichier) {

        if (!fichier || !fichier.type.startsWith('image/')) {
            throw new Error('Le fichier sélectionné n’est pas une image.');
        }

        /*
         * Si le navigateur ne supporte pas FileReader,
         * on abandonne proprement.
         */
        if (!window.FileReader) {
            return fichier;
        }

        const image = await chargerImage(fichier);

        let largeur = image.naturalWidth;
        let hauteur = image.naturalHeight;

        /*
         * Calcul du nouveau format sans déformer l'image.
         */
        const ratio = Math.min(
            LARGEUR_MAX / largeur,
            HAUTEUR_MAX / hauteur,
            1
        );

        largeur = Math.round(largeur * ratio);
        hauteur = Math.round(hauteur * ratio);

        const canvas = document.createElement('canvas');

        canvas.width = largeur;
        canvas.height = hauteur;

        const contexte = canvas.getContext('2d', {
            alpha: false
        });

        if (!contexte) {
            throw new Error('Impossible de préparer la photo.');
        }

        /*
         * Fond blanc.
         *
         * Cela évite les problèmes avec les images PNG
         * transparentes lorsqu'elles deviennent JPEG.
         */
        contexte.fillStyle = '#ffffff';
        contexte.fillRect(0, 0, largeur, hauteur);

        contexte.drawImage(
            image,
            0,
            0,
            largeur,
            hauteur
        );

        /*
         * Première compression.
         */
        let qualite = 0.82;

        let blob = await canvasToBlob(
            canvas,
            'image/jpeg',
            qualite
        );

        /*
         * Si le fichier est encore trop gros,
         * on réduit progressivement la qualité.
         */
        while (
            blob.size > TAILLE_CIBLE &&
            qualite > 0.55
        ) {

            qualite -= 0.07;

            blob = await canvasToBlob(
                canvas,
                'image/jpeg',
                qualite
            );
        }

        /*
         * Nom temporaire.
         *
         * Le serveur générera ensuite son propre nom sécurisé.
         */
        return new File(
            [blob],
            'photo.jpg',
            {
                type: 'image/jpeg',
                lastModified: Date.now()
            }
        );
    }

    function chargerImage(fichier) {

        return new Promise((resolve, reject) => {

            const lecteur = new FileReader();

            lecteur.onload = function (event) {

                const image = new Image();

                image.onload = function () {
                    resolve(image);
                };

                image.onerror = function () {
                    reject(
                        new Error(
                            'Impossible de lire cette image.'
                        )
                    );
                };

                image.src = event.target.result;
            };

            lecteur.onerror = function () {
                reject(
                    new Error(
                        'Impossible de lire le fichier.'
                    )
                );
            };

            lecteur.readAsDataURL(fichier);
        });
    }

    function canvasToBlob(
        canvas,
        type,
        qualite
    ) {

        return new Promise((resolve, reject) => {

            canvas.toBlob(
                function (blob) {

                    if (!blob) {
                        reject(
                            new Error(
                                'La compression de la photo a échoué.'
                            )
                        );

                        return;
                    }

                    resolve(blob);
                },
                type,
                qualite
            );
        });
    }

    /*
     * Envoi d'une seule photo.
     */
    async function envoyerFichier(fichier) {

        if (listePhotos.children.length >= MAX_PHOTOS) {

            afficherErreur(
                'Maximum 8 photos par annonce.'
            );

            return;
        }

        masquerErreur();

        try {

            /*
             * Compression côté téléphone AVANT l'envoi.
             */
            const photoCompressee =
                await compresserPhoto(fichier);

            /*
             * Sécurité côté navigateur.
             *
             * Le serveur fera également sa propre validation.
             */
            if (
                photoCompressee.size >
                2 * 1024 * 1024
            ) {

                afficherErreur(
                    'Cette photo reste trop volumineuse après compression. ' +
                    'Veuillez choisir une autre photo.'
                );

                return;
            }

            const donnees = new FormData();

            donnees.append(
                'photo',
                photoCompressee,
                'photo.jpg'
            );

            donnees.append(
                'csrf_token',
                csrfToken
            );

            /*
             * Indication visuelle pendant l'envoi.
             */
            zoneDepot.style.opacity = '0.6';
            zoneDepot.style.pointerEvents = 'none';

            const reponse = await fetch(
                urlUpload,
                {
                    method: 'POST',
                    body: donnees,
                    credentials: 'same-origin'
                }
            );

            /*
             * Vérification du type de réponse.
             */
            const typeReponse =
                reponse.headers.get('content-type') || '';

            if (
                !typeReponse.includes(
                    'application/json'
                )
            ) {

                throw new Error(
                    'Le serveur a renvoyé une réponse inattendue.'
                );
            }

            const resultat = await reponse.json();

            if (!reponse.ok || !resultat.succes) {

                afficherErreur(
                    resultat.erreur ||
                    'Échec de l’envoi de la photo.'
                );

                return;
            }

            /*
             * La photo est maintenant réellement
             * enregistrée sur le serveur.
             */
            ajouterVignette(
                resultat.nomFichier,
                resultat.url
            );

        } catch (erreur) {

            console.error(
                'Erreur upload photo :',
                erreur
            );

            afficherErreur(
                erreur.message ||
                'Erreur réseau pendant l’envoi de la photo.'
            );

        } finally {

            zoneDepot.style.opacity = '';
            zoneDepot.style.pointerEvents = '';
        }
    }

    function gererFichiersSelectionnes(fichiers) {

        const fichiersArray =
            Array.from(fichiers);

        if (!fichiersArray.length) {
            return;
        }

        /*
         * Respect strict de la limite de 8 photos.
         */
        const placesRestantes =
            MAX_PHOTOS -
            listePhotos.children.length;

        if (placesRestantes <= 0) {

            afficherErreur(
                'Maximum 8 photos par annonce.'
            );

            return;
        }

        const fichiersAEnvoyer =
            fichiersArray.slice(
                0,
                placesRestantes
            );

        fichiersAEnvoyer.forEach(
            envoyerFichier
        );
    }

    /*
     * Clic sur la zone d'upload.
     */
    zoneDepot.addEventListener(
        'click',
        function () {
            entreeFichier.click();
        }
    );

    /*
     * Sélection depuis le téléphone.
     */
    entreeFichier.addEventListener(
        'change',
        function (e) {

            gererFichiersSelectionnes(
                e.target.files
            );

            /*
             * Permet de sélectionner à nouveau
             * le même fichier.
             */
            entreeFichier.value = '';
        }
    );

    /*
     * Drag & Drop ordinateur.
     */
    ['dragenter', 'dragover'].forEach(
        function (evt) {

            zoneDepot.addEventListener(
                evt,
                function (e) {

                    e.preventDefault();

                    zoneDepot.classList.add(
                        'bg-light'
                    );
                }
            );
        }
    );

    ['dragleave', 'drop'].forEach(
        function (evt) {

            zoneDepot.addEventListener(
                evt,
                function (e) {

                    e.preventDefault();

                    zoneDepot.classList.remove(
                        'bg-light'
                    );
                }
            );
        }
    );

    zoneDepot.addEventListener(
        'drop',
        function (e) {

            gererFichiersSelectionnes(
                e.dataTransfer.files
            );
        }
    );

    /*
     * Suppression AJAX.
     */
    listePhotos.addEventListener(
        'click',
        async function (e) {

            if (
                !e.target.classList.contains(
                    'bouton-suppression-photo'
                )
            ) {
                return;
            }

            const vignette =
                e.target.closest(
                    '.photo-vignette'
                );

            if (!vignette) {
                return;
            }

            const nomFichier =
                vignette.dataset.filename;

            const donnees =
                new FormData();

            donnees.append(
                'nomFichier',
                nomFichier
            );

            donnees.append(
                'csrf_token',
                csrfToken
            );

            try {

                const reponse =
                    await fetch(
                        urlSuppression,
                        {
                            method: 'POST',
                            body: donnees,
                            credentials: 'same-origin'
                        }
                    );

                const resultat =
                    await reponse.json();

                if (resultat.succes) {

                    vignette.remove();

                } else {

                    afficherErreur(
                        resultat.erreur ||
                        'Échec de la suppression.'
                    );
                }

            } catch (erreur) {

                console.error(erreur);

                afficherErreur(
                    'Erreur réseau pendant la suppression.'
                );
            }
        }
    );

    /*
     * Réordonnancement des photos.
     */
    let elementGlisse = null;

    function activerVignette(vignette) {

        vignette.addEventListener(
            'dragstart',
            function () {

                elementGlisse =
                    vignette;

                vignette.classList.add(
                    'opacity-50'
                );
            }
        );

        vignette.addEventListener(
            'dragend',
            function () {

                elementGlisse = null;

                vignette.classList.remove(
                    'opacity-50'
                );
            }
        );

        vignette.addEventListener(
            'dragover',
            function (e) {

                e.preventDefault();
            }
        );

        vignette.addEventListener(
            'drop',
            function (e) {

                e.preventDefault();

                if (
                    !elementGlisse ||
                    elementGlisse === vignette
                ) {
                    return;
                }

                const rectangle =
                    vignette.getBoundingClientRect();

                const apres =
                    (
                        e.clientX -
                        rectangle.left
                    ) >
                    rectangle.width / 2;

                vignette.parentNode.insertBefore(
                    elementGlisse,
                    apres
                        ? vignette.nextSibling
                        : vignette
                );
            }
        );
    }

    document
        .querySelectorAll(
            '.photo-vignette'
        )
        .forEach(
            activerVignette
        );

    /*
     * Avant de passer au récapitulatif,
     * on enregistre l'ordre des photos.
     */
    formulaire.addEventListener(
        'submit',
        function () {

            const ordre =
                Array.from(
                    listePhotos.children
                ).map(
                    function (vignette) {
                        return vignette.dataset.filename;
                    }
                );

            champOrdre.value =
                ordre.join(',');
        }
    );

})();
</script>