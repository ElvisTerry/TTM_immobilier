<div class="row">
    <!-- ========================= FILTRES ========================= -->
    <div class="col-12 col-lg-3 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Filtrer les biens</h2>

                <div class="mb-3">
                    <label class="form-label small">Ville</label>
                    <input type="text" id="filtreVille" class="form-control form-control-sm"
                           placeholder="Ex : Yaoundé" list="suggestionsVilles" autocomplete="off">
                    <datalist id="suggestionsVilles"></datalist>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Quartier</label>
                    <input type="text" id="filtreQuartier" class="form-control form-control-sm"
                           placeholder="Ex : Bastos" list="suggestionsQuartiers" autocomplete="off" disabled>
                    <datalist id="suggestionsQuartiers"></datalist>
                    <div id="quartierLocalise" class="small text-muted mt-1"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Type de bien</label>
                    <select id="filtreTypeBien" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="chambre">Chambre</option>
                        <option value="studio">Studio</option>
                        <option value="appartement">Appartement</option>
                        <option value="maison">Maison</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Transaction</label>
                    <select id="filtreTypeTransaction" class="form-select form-select-sm">
                        <option value="">Location ou vente</option>
                        <option value="location">Location</option>
                        <option value="vente">Vente</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Prix (FCFA)</label>
                    <div class="d-flex gap-2">
                        <input type="number" min="0" step="1" id="filtrePrixMin" class="form-control form-control-sm" placeholder="Min">
                        <input type="number" min="0" step="1" id="filtrePrixMax" class="form-control form-control-sm" placeholder="Max">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Superficie (m²)</label>
                    <div class="d-flex gap-2">
                        <input type="number" min="0" step="1" id="filtreSuperficieMin" class="form-control form-control-sm" placeholder="Min">
                        <input type="number" min="0" step="1" id="filtreSuperficieMax" class="form-control form-control-sm" placeholder="Max">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Chambres minimum</label>
                    <select id="filtreChambres" class="form-select form-select-sm">
                        <option value="">Peu importe</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Salles de bain minimum</label>
                    <select id="filtreSallesBain" class="form-select form-select-sm">
                        <option value="">Peu importe</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Équipements</label>
                    <div class="form-check">
                        <input class="form-check-input filtre-equipement" type="checkbox" value="1" id="filtreMeuble">
                        <label class="form-check-label small" for="filtreMeuble">Meublé</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input filtre-equipement" type="checkbox" value="1" id="filtreEau">
                        <label class="form-check-label small" for="filtreEau">Eau</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input filtre-equipement" type="checkbox" value="1" id="filtreElectricite">
                        <label class="form-check-label small" for="filtreElectricite">Électricité</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input filtre-equipement" type="checkbox" value="1" id="filtreParking">
                        <label class="form-check-label small" for="filtreParking">Parking</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Localisation</label>
                    <button type="button" id="boutonLocaliserQuartier" class="btn btn-outline-primary btn-sm w-100">
                         Localiser ce quartier
                    </button>
                    <div id="statutLocalisation" class="small mt-2" aria-live="polite"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Trier par</label>
                    <select id="filtreTri" class="form-select form-select-sm">
                        <option value="recent">🕒 Plus récentes</option>
                        <option value="prix_asc">💰 Prix croissant</option>
                        <option value="prix_desc">💰 Prix décroissant</option>
                        <option value="vues_desc">🔥 Plus consultées</option>
                        <option value="recommande">⭐ Recommandées</option>
                    </select>
                </div>

                <?php if (estConnecte()): ?>
                    <button type="button" id="boutonSauvegarderRecherche" class="btn btn-outline-primary btn-sm w-100">
                        🔔 Créer une alerte sur cette recherche
                    </button>
                    <div id="messageAlerte" class="small mt-2"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========================= RESULTATS + CARTE ========================= -->
    <div class="col-12 col-lg-9">
        <div id="carteResultats" style="height:360px;border-radius:12px;overflow:hidden;" class="mb-3"></div>

        <p id="compteurResultats" class="text-muted small" aria-live="polite">Chargement...</p>

        <div id="grilleResultats" class="row g-4"></div>

        <div class="text-center mt-4">
            <button id="boutonChargerPlus" class="btn btn-outline-primary d-none">Charger plus de résultats</button>
        </div>
    </div>
</div>

<script>window.TTM_URL_COMPARATEUR = <?= json_encode(url('biens/comparateur')) ?>;</script>
<script src="<?= cheminBase() ?>/js/comparateur.js"></script>


<template id="gabaritCarteResultat">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="position-relative">
            <label class="case-comparateur-label" title="Ajouter au comparateur" onclick="event.stopPropagation()">
                <input type="checkbox" class="case-comparateur">
            </label>
        <a class="text-decoration-none text-dark lien-carte-resultat" href="#">
            <div class="card carte-bien h-100 shadow-sm">
                <img class="card-img-top image-resultat" loading="lazy" style="height:180px;object-fit:cover;" alt="">
                <div class="card-body">
                    <h5 class="card-title fs-6 titre-resultat"></h5>
                    <p class="card-text small text-muted mb-1 localisation-resultat"></p>
                    <p class="card-text small text-muted mb-1 caracteristiques-resultat"></p>
                    <p class="card-text fw-bold prix-resultat" style="color: var(--couleur-primaire);"></p>
                    <p class="card-text small text-muted stats-resultat"></p>
               
                </div>
            </div>
        </a>
        </div>
    </div>
</template>

<script>
(function () {
    const urlRecherche = <?= json_encode(url('biens/recherche/ajax')) ?>;
    const urlVilles = <?= json_encode(url('biens/recherche/villes')) ?>;
    const urlQuartiers = <?= json_encode(url('biens/recherche/quartiers')) ?>;

    const champs = {
        ville: document.getElementById('filtreVille'),
        quartier: document.getElementById('filtreQuartier'),
        typeBien: document.getElementById('filtreTypeBien'),
        typeTransaction: document.getElementById('filtreTypeTransaction'),
        prixMin: document.getElementById('filtrePrixMin'),
        prixMax: document.getElementById('filtrePrixMax'),
        superficieMin: document.getElementById('filtreSuperficieMin'),
        superficieMax: document.getElementById('filtreSuperficieMax'),
        chambres: document.getElementById('filtreChambres'),
        sallesBain: document.getElementById('filtreSallesBain'),
        meuble: document.getElementById('filtreMeuble'),
        eau: document.getElementById('filtreEau'),
        electricite: document.getElementById('filtreElectricite'),
        parking: document.getElementById('filtreParking'),
        tri: document.getElementById('filtreTri'),
    };

    const grille = document.getElementById('grilleResultats');
    const compteur = document.getElementById('compteurResultats');
    const gabarit = document.getElementById('gabaritCarteResultat');
    const boutonChargerPlus = document.getElementById('boutonChargerPlus');
    const boutonLocaliser = document.getElementById('boutonLocaliserQuartier');
    const statutLocalisation = document.getElementById('statutLocalisation');
    const quartierLocalise = document.getElementById('quartierLocalise');
    const listeSuggestionsVilles = document.getElementById('suggestionsVilles');
    const listeSuggestionsQuartiers = document.getElementById('suggestionsQuartiers');

    const carte = L.map('carteResultats').setView([3.8480, 11.5021], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(carte);

    let groupeMarqueurs = L.layerGroup().addTo(carte);
    let marqueurUtilisateur = null;
    let pageActuelle = 1;
    let rechercheEnCours = false;

    function formaterPrix(prix) {
        return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(prix)) + ' FCFA';
    }

    function formaterSuperficie(valeur) {
        return valeur ? Number(valeur).toLocaleString('fr-FR') + ' m²' : '';
    }

    function afficherSquelettes() {
        grille.innerHTML = Array.from({ length: 6 }).map(() => `
            <div class="col-12 col-md-6 col-xl-4">
                <div class="squelette-carte">
                    <div class="squelette-bloc" style="height:180px;"></div>
                    <div class="p-3">
                        <div class="squelette-bloc mb-2" style="height:14px;width:70%;border-radius:4px;"></div>
                        <div class="squelette-bloc" style="height:12px;width:40%;border-radius:4px;"></div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function echapperHtml(texte) {
        const div = document.createElement('div');
        div.textContent = texte ?? '';
        return div.innerHTML;
    }

    function ajouterCartesResultats(resultats) {
        const pointsCarte = [];

        resultats.forEach((bien) => {
            const carteResultat = gabarit.content.cloneNode(true);
            carteResultat.querySelector('.lien-carte-resultat').href = bien.url_detail;
            carteResultat.querySelector('.image-resultat').src = bien.photo_url || '';
            carteResultat.querySelector('.image-resultat').alt = bien.titre || 'Annonce immobilière';
            carteResultat.querySelector('.titre-resultat').textContent = bien.titre;

            const localisation = bien.quartier
                ? '📍 ' + bien.quartier + ', ' + bien.ville
                : '📍 ' + bien.ville;
            carteResultat.querySelector('.localisation-resultat').textContent = localisation;

            const caracteristiques = [];
            if (bien.superficie_m2) caracteristiques.push(formaterSuperficie(bien.superficie_m2));
            caracteristiques.push((Number(bien.nombre_chambres) || 0) + ' ch.');
            caracteristiques.push((Number(bien.nombre_salles_bain) || 0) + ' sdb.');
            if (Number(bien.meuble) === 1) caracteristiques.push('Meublé');
            if (Number(bien.parking) === 1) caracteristiques.push('Parking');
            carteResultat.querySelector('.caracteristiques-resultat').textContent = caracteristiques.join(' • ');

           carteResultat.querySelector('.prix-resultat').textContent = formaterPrix(bien.prix);
            carteResultat.querySelector('.stats-resultat').textContent =
                '👁 ' + (Number(bien.nb_vues) || 0) + ' vues • ❤️ ' + (Number(bien.nb_favoris) || 0) + ' favoris';

            const caseComparateur = carteResultat.querySelector('.case-comparateur');
            caseComparateur.dataset.id = bien.id;
            caseComparateur.dataset.titre = bien.titre;
            caseComparateur.dataset.prix = bien.prix;
            caseComparateur.dataset.photo = bien.photo_url || '';

            grille.appendChild(carteResultat);

            if (bien.latitude !== null && bien.longitude !== null && bien.latitude !== '' && bien.longitude !== '') {
                const lat = Number(bien.latitude);
                const lng = Number(bien.longitude);
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    const marqueur = L.marker([lat, lng]).bindPopup(
                        '<strong>' + echapperHtml(bien.titre) + '</strong><br>' +
                        formaterPrix(bien.prix) + '<br>' +
                        '<a href="' + encodeURI(bien.url_detail) + '">Voir l’annonce</a>'
                    );
                    groupeMarqueurs.addLayer(marqueur);
                    pointsCarte.push([lat, lng]);
                }
            }
        });

        return pointsCarte;
    }

    function obtenirParametres() {
        return new URLSearchParams({
            ville: champs.ville.value.trim(),
            quartier: champs.quartier.value.trim(),
            type_bien: champs.typeBien.value,
            type_transaction: champs.typeTransaction.value,
            prix_min: champs.prixMin.value,
            prix_max: champs.prixMax.value,
            superficie_min: champs.superficieMin.value,
            superficie_max: champs.superficieMax.value,
            nombre_chambres: champs.chambres.value,
            nombre_salles_bain: champs.sallesBain.value,
            meuble: champs.meuble.checked ? '1' : '',
            eau: champs.eau.checked ? '1' : '',
            electricite: champs.electricite.checked ? '1' : '',
            parking: champs.parking.checked ? '1' : '',
            tri: champs.tri.value,
            page: pageActuelle,
        });
    }

    async function lancerRecherche(reinitialiser) {
        if (rechercheEnCours) return;
        rechercheEnCours = true;

        if (reinitialiser) {
            pageActuelle = 1;
            groupeMarqueurs.clearLayers();
            compteur.textContent = 'Recherche en cours...';
            afficherSquelettes();
        }

        try {
            const reponse = await fetch(urlRecherche + '?' + obtenirParametres().toString(), {
                headers: { 'Accept': 'application/json' }
            });

            if (!reponse.ok) throw new Error('HTTP ' + reponse.status);

            const donnees = await reponse.json();
            const resultats = Array.isArray(donnees.resultats) ? donnees.resultats : [];

            if (reinitialiser) grille.innerHTML = '';

            const pointsCarte = ajouterCartesResultats(resultats);

            if (grille.children.length === 0) {
                grille.innerHTML = '<p class="text-muted text-center py-5 col-12">Aucun bien ne correspond à votre recherche.</p>';
            }

            compteur.textContent = donnees.total + ' résultat(s)';
            boutonChargerPlus.classList.toggle('d-none', !donnees.aPlus);

            if (reinitialiser && pointsCarte.length > 0 && rayonLat === null) {
                carte.fitBounds(pointsCarte, { maxZoom: 13, padding: [20, 20] });
            }

            if (window.actualiserCasesComparateur) window.actualiserCasesComparateur();

        } catch (erreur) {
            console.error(erreur);
            compteur.textContent = 'Erreur lors du chargement des résultats.';
        } finally {
            rechercheEnCours = false;
        }
    }

    function debounce(fonction, delai) {
        let minuteur;
        return (...args) => {
            clearTimeout(minuteur);
            minuteur = setTimeout(() => fonction(...args), delai);
        };
    }

    const rechercheAvecDelai = debounce(() => lancerRecherche(true), 350);

    champs.ville.addEventListener('input', () => {
        champs.quartier.value = '';
        chargementQuartiersAvecDelai();
        rechercheAvecDelai();
    });
    champs.quartier.addEventListener('input', rechercheAvecDelai);
    champs.prixMin.addEventListener('input', rechercheAvecDelai);
    champs.prixMax.addEventListener('input', rechercheAvecDelai);
    champs.superficieMin.addEventListener('input', rechercheAvecDelai);
    champs.superficieMax.addEventListener('input', rechercheAvecDelai);

    [champs.typeBien, champs.typeTransaction, champs.chambres, champs.sallesBain, champs.tri]
        .forEach((champ) => champ.addEventListener('change', () => lancerRecherche(true)));

    [champs.meuble, champs.eau, champs.electricite, champs.parking]
        .forEach((champ) => champ.addEventListener('change', () => lancerRecherche(true)));

    boutonChargerPlus.addEventListener('click', () => {
        pageActuelle += 1;
        lancerRecherche(false);
    });

    // ===================== AUTOCOMPLETION VILLE =====================
    const suggestionsAvecDelai = debounce(async () => {
        const terme = champs.ville.value.trim();
        if (terme.length < 2) {
            listeSuggestionsVilles.innerHTML = '';
            return;
        }
        try {
            const reponse = await fetch(urlVilles + '?q=' + encodeURIComponent(terme));
            const donnees = await reponse.json();
            listeSuggestionsVilles.innerHTML = (donnees.suggestions || [])
                .map((ville) => `<option value="${echapperHtml(ville)}">`)
                .join('');
        } catch (erreur) {}
    }, 300);
    champs.ville.addEventListener('input', suggestionsAvecDelai);

    // ===================== AUTOCOMPLETION QUARTIER =====================
    async function chargerQuartiers(ville) {
        if (!ville) {
            champs.quartier.disabled = true;
            champs.quartier.placeholder = "Choisissez d'abord une ville";
            champs.quartier.value = '';
            listeSuggestionsQuartiers.innerHTML = '';
            return;
        }

        try {
            const reponse = await fetch(urlQuartiers + '?ville=' + encodeURIComponent(ville));
            const donnees = await reponse.json();
            const quartiers = donnees.suggestions || [];
            listeSuggestionsQuartiers.innerHTML = quartiers
                .map((q) => `<option value="${echapperHtml(q)}">`)
                .join('');
            champs.quartier.disabled = false;
            champs.quartier.placeholder = quartiers.length > 0
                ? 'Tous les quartiers'
                : 'Aucun quartier connu pour cette ville';
        } catch (erreur) {
            champs.quartier.disabled = true;
        }
    }

    const chargementQuartiersAvecDelai = debounce(
        () => chargerQuartiers(champs.ville.value.trim()),
        400
    );

    // ===================== LOCALISATION PAR QUARTIER (simple) =====================
    async function localiserQuartier() {
        const ville = champs.ville.value.trim();
        const quartier = champs.quartier.value.trim();

        if (!ville && !quartier) {
            statutLocalisation.textContent = 'Veuillez renseigner une ville et un quartier.';
            statutLocalisation.className = 'small mt-2 text-danger';
            return;
        }

        let adresse = '';
        if (quartier) {
            adresse = quartier + ', ' + ville;
        } else {
            adresse = ville;
        }
        adresse += ', Cameroun';

        statutLocalisation.textContent = 'Recherche du quartier...';
        statutLocalisation.className = 'small mt-2 text-primary';
        boutonLocaliser.disabled = true;

        try {
            // On force la recherche au Cameroun avec countrycodes=cm
            const url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(adresse) + '&limit=1&countrycodes=cm&addressdetails=1';
            const reponse = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const donnees = await reponse.json();

            if (donnees && donnees.length > 0) {
                const resultat = donnees[0];
                const lat = parseFloat(resultat.lat);
                const lon = parseFloat(resultat.lon);

                if (!isNaN(lat) && !isNaN(lon)) {
                    // Supprimer l'ancien marqueur
                    if (marqueurUtilisateur) carte.removeLayer(marqueurUtilisateur);
                    marqueurUtilisateur = L.marker([lat, lon], {
                        title: 'Quartier sélectionné'
                    }).addTo(carte).bindPopup('<strong>📍 ' + echapperHtml(adresse) + '</strong>').openPopup();

                    carte.setView([lat, lon], 16);
                    statutLocalisation.textContent = '📍 Localisé : ' + adresse;
                    statutLocalisation.className = 'small mt-2 text-success';
                } else {
                    statutLocalisation.textContent = 'Coordonnées invalides pour ce quartier.';
                    statutLocalisation.className = 'small mt-2 text-danger';
                }
            } else {
                // Deuxième tentative : uniquement le quartier + Cameroun
                if (quartier) {
                    const url2 = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(quartier + ', Cameroun') + '&limit=1&countrycodes=cm&addressdetails=1';
                    const reponse2 = await fetch(url2, { headers: { 'Accept': 'application/json' } });
                    const donnees2 = await reponse2.json();
                    if (donnees2 && donnees2.length > 0) {
                        const resultat = donnees2[0];
                        const lat = parseFloat(resultat.lat);
                        const lon = parseFloat(resultat.lon);
                        if (!isNaN(lat) && !isNaN(lon)) {
                            if (marqueurUtilisateur) carte.removeLayer(marqueurUtilisateur);
                            marqueurUtilisateur = L.marker([lat, lon], {
                                title: 'Quartier sélectionné'
                            }).addTo(carte).bindPopup('<strong>📍 ' + echapperHtml(quartier) + '</strong>').openPopup();
                            carte.setView([lat, lon], 16);
                            statutLocalisation.textContent = '📍 Localisé : ' + quartier + ' (approximatif)';
                            statutLocalisation.className = 'small mt-2 text-success';
                        } else {
                            statutLocalisation.textContent = 'Aucun résultat trouvé.';
                            statutLocalisation.className = 'small mt-2 text-danger';
                        }
                    } else {
                        statutLocalisation.textContent = 'Aucun résultat trouvé pour ce quartier.';
                        statutLocalisation.className = 'small mt-2 text-danger';
                    }
                } else {
                    statutLocalisation.textContent = 'Aucun résultat trouvé pour cette ville.';
                    statutLocalisation.className = 'small mt-2 text-danger';
                }
            }
        } catch (erreur) {
            console.error(erreur);
            statutLocalisation.textContent = 'Erreur lors de la localisation.';
            statutLocalisation.className = 'small mt-2 text-danger';
        } finally {
            boutonLocaliser.disabled = false;
        }
    }

    boutonLocaliser.addEventListener('click', localiserQuartier);

    // ===================== URL DE PRE-REMPLISSAGE =====================
    const parametresURL = new URLSearchParams(window.location.search);
    if (parametresURL.get('ville')) {
        champs.ville.value = parametresURL.get('ville');
        chargerQuartiers(parametresURL.get('ville'));
    }
    if (parametresURL.get('quartier')) champs.quartier.value = parametresURL.get('quartier');
    if (parametresURL.get('type_bien')) champs.typeBien.value = parametresURL.get('type_bien');
    if (parametresURL.get('prix_max')) champs.prixMax.value = parametresURL.get('prix_max');
    // ===================== ALERTES DE RECHERCHE =====================
    const boutonAlerte = document.getElementById('boutonSauvegarderRecherche');
    if (boutonAlerte) {
        boutonAlerte.addEventListener('click', async () => {
            const nom = prompt('Nom de cette alerte (ex : "Studio à Yaoundé") :');
            if (!nom) return;

            const donnees = new FormData();
            donnees.append('nom', nom);
            donnees.append('ville', champs.ville.value.trim());
            donnees.append('quartier', champs.quartier.value.trim());
            donnees.append('type_bien', champs.typeBien.value);
            donnees.append('type_transaction', champs.typeTransaction.value);
            donnees.append('prix_min', champs.prixMin.value);
            donnees.append('prix_max', champs.prixMax.value);
            donnees.append('csrf_token', <?= json_encode(genererTokenCSRF()) ?>);

            const messageAlerte = document.getElementById('messageAlerte');
            try {
                const reponse = await fetch(<?= json_encode(url('alertes/sauvegarder')) ?>, {
                    method: 'POST',
                    body: donnees
                });
                const resultat = await reponse.json();
                messageAlerte.textContent = resultat.succes
                    ? '✓ Alerte créée — retrouvez-la dans "Mes alertes".'
                    : (resultat.erreur || 'Échec de la création de l’alerte.');
                messageAlerte.className = 'small mt-2 ' + (resultat.succes ? 'text-success' : 'text-danger');
            } catch (erreur) {
                messageAlerte.textContent = 'Erreur réseau, réessayez.';
                messageAlerte.className = 'small mt-2 text-danger';
            }
        });
    }

    lancerRecherche(true);
})();
</script>