<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Disponibilités - <?= nettoyer($bien['titre']) ?></h1>
            <a href="<?= url('biens/detail', [(int) $bien['id']]) ?>" class="small">Retour à l'annonce</a>
        </div>

        <p class="text-muted small">
            Bloquez les périodes pendant lesquelles ce bien n'est pas disponible (déjà loué, indisponible pour visite...).
            Toute date qui n'apparaît pas ci-dessous est considérée disponible par défaut.
        </p>

        <div id="messageErreurDispo" class="alert alert-danger d-none"></div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="formulaireAjoutPeriode" class="row g-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Du</label>
                        <input type="date" name="date_debut" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Au</label>
                        <input type="date" name="date_fin" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small">Motif (facultatif)</label>
                        <input type="text" name="motif" class="form-control form-control-sm" placeholder="Ex : déjà loué">
                    </div>
                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">+</button>
                    </div>
                </form>
            </div>
        </div>

        <ul id="listePeriodes" class="list-group">
            <?php foreach ($periodes as $periode): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?= (int) $periode['id'] ?>">
                    <span>
                        <strong><?= nettoyer(date('d/m/Y', strtotime($periode['date_debut']))) ?></strong>
                        →
                        <strong><?= nettoyer(date('d/m/Y', strtotime($periode['date_fin']))) ?></strong>
                        <span class="text-muted small">(<?= nettoyer($periode['motif']) ?>)</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger bouton-supprimer-periode">Retirer</button>
                </li>
            <?php endforeach; ?>
        </ul>
        <p id="messageAucunePeriode" class="text-muted small mt-2 <?= !empty($periodes) ? 'd-none' : '' ?>">Aucune période bloquée pour l'instant.</p>
    </div>
</div>

<script>
(function () {
    const bienId = <?= (int) $bien['id'] ?>;
    const urlAjouter = <?= json_encode(url('biens/' . (int) $bien['id'] . '/disponibilites/ajouter')) ?>;
    const urlSupprimer = <?= json_encode(url('biens/' . (int) $bien['id'] . '/disponibilites/supprimer')) ?>;
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;

    const formulaire = document.getElementById('formulaireAjoutPeriode');
    const liste = document.getElementById('listePeriodes');
    const messageAucunePeriode = document.getElementById('messageAucunePeriode');
    const messageErreur = document.getElementById('messageErreurDispo');

    function afficherErreur(texte) {
        messageErreur.textContent = texte;
        messageErreur.classList.remove('d-none');
    }

    function formaterDate(iso) {
        const [annee, mois, jour] = iso.split('-');
        return `${jour}/${mois}/${annee}`;
    }

    formulaire.addEventListener('submit', async (e) => {
        e.preventDefault();
        messageErreur.classList.add('d-none');

        const donnees = new FormData(formulaire);

        try {
            const reponse = await fetch(urlAjouter, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (!resultat.succes) {
                afficherErreur(resultat.erreur || "Impossible d'ajouter cette période.");
                return;
            }

            const item = document.createElement('li');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.dataset.id = resultat.id;
            item.innerHTML = `<span><strong>${formaterDate(resultat.dateDebut)}</strong> → <strong>${formaterDate(resultat.dateFin)}</strong> <span class="text-muted small">(${resultat.motif})</span></span>
                               <button type="button" class="btn btn-sm btn-outline-danger bouton-supprimer-periode">Retirer</button>`;
            liste.appendChild(item);
            messageAucunePeriode.classList.add('d-none');
            formulaire.reset();
        } catch (erreur) {
            afficherErreur('Erreur réseau, réessayez.');
        }
    });

    liste.addEventListener('click', async (e) => {
        if (!e.target.classList.contains('bouton-supprimer-periode')) return;

        const item = e.target.closest('li');
        const donnees = new FormData();
        donnees.append('id', item.dataset.id);
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(urlSupprimer, { method: 'POST', body: donnees });
            const resultat = await reponse.json();
            if (resultat.succes) {
                item.remove();
                if (liste.children.length === 0) messageAucunePeriode.classList.remove('d-none');
            } else {
                afficherErreur(resultat.erreur || 'Échec de la suppression.');
            }
        } catch (erreur) {
            afficherErreur('Erreur réseau, réessayez.');
        }
    });
})();
</script>
