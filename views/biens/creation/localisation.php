<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <?php $etapeActuelle = 3; require __DIR__ . '/_progression.php'; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Où se trouve le bien ?</h1>

                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-danger"><?php foreach ($erreurs as $e): ?><div><?= nettoyer($e) ?></div><?php endforeach; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= url('biens/creer/localisation') ?>" id="formulaireLocalisation">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                    <input type="hidden" name="latitude" id="champLatitude" value="<?= nettoyer((string) ($donnees['latitude'] ?? '')) ?>">
                    <input type="hidden" name="longitude" id="champLongitude" value="<?= nettoyer((string) ($donnees['longitude'] ?? '')) ?>">

                    <div class="row">
                        <div class="col-12 col-md-7 mb-3">
                            <label class="form-label">Ville</label>
                            <input type="text" name="ville" id="champVille" class="form-control"
                                   value="<?= nettoyer($donnees['ville'] ?? '') ?>" required>
                        </div>
                        <div class="col-12 col-md-5 mb-3">
                            <label class="form-label">Quartier</label>
                            <input type="text" name="quartier" class="form-control"
                                   value="<?= nettoyer($donnees['quartier'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Emplacement précis (facultatif)</label>
                        <button type="button" id="boutonLocaliserVille" class="btn btn-outline-primary btn-sm">
                             Localiser cette ville
                        </button>
                    </div>
                    <p class="small text-muted">Cliquez sur la carte, ou faites glisser le repère, pour indiquer l'emplacement exact du bien.</p>

                    <div id="carte" style="height:350px;border-radius:12px;overflow:hidden;" class="mb-2"></div>
                    <p id="etatCoordonnees" class="small text-muted mb-4">Aucun emplacement précis sélectionné pour l'instant.</p>

                    <div class="d-flex gap-2">
                        <a href="<?= url('biens/creer/equipements') ?>" class="btn btn-outline-secondary flex-fill">Retour</a>
                        <button type="submit" class="btn btn-primary flex-fill">Continuer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Centré sur Yaoundé par défaut — un point de départ raisonnable
    // pour un service qui démarre au Cameroun.
    const centreParDefaut = [3.8480, 11.5021];
    const latitudeExistante = <?= json_encode($donnees['latitude'] ?? null) ?>;
    const longitudeExistante = <?= json_encode($donnees['longitude'] ?? null) ?>;

    const carte = L.map('carte').setView(
        latitudeExistante && longitudeExistante ? [latitudeExistante, longitudeExistante] : centreParDefaut,
        latitudeExistante ? 15 : 12
    );

    // Tuiles OpenStreetMap : gratuites, sans clé API, attribution obligatoire.
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(carte);

    const champLatitude = document.getElementById('champLatitude');
    const champLongitude = document.getElementById('champLongitude');
    const etatCoordonnees = document.getElementById('etatCoordonnees');

    let marqueur = null;
    if (latitudeExistante && longitudeExistante) {
        marqueur = L.marker([latitudeExistante, longitudeExistante], { draggable: true }).addTo(carte);
        marqueur.on('dragend', () => mettreAJourCoordonnees(marqueur.getLatLng()));
        afficherEtat(latitudeExistante, longitudeExistante);
    }

    function mettreAJourCoordonnees(latLng) {
        champLatitude.value = latLng.lat.toFixed(6);
        champLongitude.value = latLng.lng.toFixed(6);
        afficherEtat(latLng.lat, latLng.lng);
    }

    function afficherEtat(lat, lng) {
        etatCoordonnees.textContent = `Emplacement sélectionné : ${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;
    }

    // Clic sur la carte : place ou déplace le repère.
    carte.on('click', (e) => {
        if (marqueur) {
            marqueur.setLatLng(e.latlng);
        } else {
            marqueur = L.marker(e.latlng, { draggable: true }).addTo(carte);
            marqueur.on('dragend', () => mettreAJourCoordonnees(marqueur.getLatLng()));
        }
        mettreAJourCoordonnees(e.latlng);
    });

    /**
     * "Localiser cette ville" : recentre la carte grâce à Nominatim,
     * le service de géocodage gratuit d'OpenStreetMap (aucune clé API).
     * On ne place PAS automatiquement le repère précis — juste la vue de
     * la carte — pour laisser le propriétaire pointer lui-même l'endroit
     * exact d'un simple clic ensuite.
     */
    document.getElementById('boutonLocaliserVille').addEventListener('click', async () => {
        const ville = document.getElementById('champVille').value.trim();
        if (!ville) {
            alert('Indiquez d\'abord une ville.');
            return;
        }

        try {
            const reponse = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=cm&q=${encodeURIComponent(ville)}`
            );
            const resultats = await reponse.json();

            if (resultats.length === 0) {
                alert('Ville introuvable, essayez un autre nom ou pointez directement sur la carte.');
                return;
            }

            const { lat, lon } = resultats[0];
            carte.setView([lat, lon], 13);
        } catch (erreur) {
            alert('Impossible de localiser cette ville pour le moment.');
        }
    });
})();
</script>
