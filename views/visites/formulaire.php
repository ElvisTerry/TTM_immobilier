<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <h1 class="h4 mb-1">Réserver une visite</h1>
        <p class="text-muted small mb-4"><?= nettoyer($bien['titre']) ?> - <?= nettoyer($bien['ville']) ?></p>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($erreurs as $e): ?><li><?= nettoyer($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <!-- Mini-calendrier "fait main" : pas de librairie externe,
                     juste une grille de jours en JavaScript vanilla, cohérent
                     avec le reste du projet. -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <button type="button" id="moisPrecedent" class="btn btn-sm btn-outline-secondary">‹</button>
                    <strong id="libelleMois"></strong>
                    <button type="button" id="moisSuivant" class="btn btn-sm btn-outline-secondary">›</button>
                </div>
                <div id="grilleCalendrier" class="mb-3"></div>

                <form method="POST" action="<?= url('biens/' . (int) $bien['id'] . '/visite') ?>" id="formulaireVisite">
                    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
                    <input type="hidden" name="date_visite" id="champDateVisite" required>

                    <div class="mb-3">
                        <label class="form-label small">Date sélectionnée</label>
                        <div id="dateSelectionnee" class="form-control form-control-sm bg-light">Cliquez sur un jour disponible ci-dessus</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Créneau horaire</label>
                        <select name="heure_visite" class="form-select" required>
                            <?php foreach ($creneaux as $creneau): ?>
                                <option value="<?= $creneau ?>"><?= $creneau ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Message (facultatif)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Précisez vos disponibilités, vos questions..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Envoyer la demande</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Périodes bloquées transmises par le serveur (Jour 10) : le calendrier
    // grise ces jours pour éviter de proposer une date déjà indisponible.
    const periodesBloquees = <?= json_encode(array_map(fn($p) => ['debut' => $p['date_debut'], 'fin' => $p['date_fin']], $periodesBloquees)) ?>;

    const grille = document.getElementById('grilleCalendrier');
    const libelleMois = document.getElementById('libelleMois');
    const champDate = document.getElementById('champDateVisite');
    const dateSelectionneeAffichee = document.getElementById('dateSelectionnee');

    const noms_mois = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    const aujourdHui = new Date();
    aujourdHui.setHours(0, 0, 0, 0);
    let moisAffiche = new Date(aujourdHui.getFullYear(), aujourdHui.getMonth(), 1);

    function versISO(date) {
        return date.toISOString().split('T')[0];
    }

    function estBloque(dateISO) {
        return periodesBloquees.some((p) => dateISO >= p.debut && dateISO <= p.fin);
    }

    function dessinerCalendrier() {
        grille.innerHTML = '';
        libelleMois.textContent = `${noms_mois[moisAffiche.getMonth()]} ${moisAffiche.getFullYear()}`;

        const conteneur = document.createElement('div');
        conteneur.className = 'row row-cols-7 g-1 text-center small';

        const premierJourSemaine = (moisAffiche.getDay() + 6) % 7; // lundi = 0
        const nombreJours = new Date(moisAffiche.getFullYear(), moisAffiche.getMonth() + 1, 0).getDate();

        for (let i = 0; i < premierJourSemaine; i++) {
            conteneur.appendChild(document.createElement('div'));
        }

        for (let jour = 1; jour <= nombreJours; jour++) {
            const dateJour = new Date(moisAffiche.getFullYear(), moisAffiche.getMonth(), jour);
            const dateISO = versISO(dateJour);
            const passe = dateJour < aujourdHui;
            const bloque = estBloque(dateISO);

            const cellule = document.createElement('button');
            cellule.type = 'button';
            cellule.textContent = jour;
            cellule.className = 'btn btn-sm p-1 ' + (passe || bloque ? 'btn-light text-muted' : 'btn-outline-primary');
            cellule.disabled = passe || bloque;
            cellule.style.width = '14.28%';

            cellule.addEventListener('click', () => {
                document.querySelectorAll('#grilleCalendrier button').forEach((b) => b.classList.remove('btn-primary', 'text-white'));
                cellule.classList.add('btn-primary', 'text-white');
                champDate.value = dateISO;
                dateSelectionneeAffichee.textContent = `${jour} ${noms_mois[moisAffiche.getMonth()]} ${moisAffiche.getFullYear()}`;
            });

            conteneur.appendChild(cellule);
        }

        grille.appendChild(conteneur);
    }

    document.getElementById('moisPrecedent').addEventListener('click', () => {
        moisAffiche.setMonth(moisAffiche.getMonth() - 1);
        dessinerCalendrier();
    });
    document.getElementById('moisSuivant').addEventListener('click', () => {
        moisAffiche.setMonth(moisAffiche.getMonth() + 1);
        dessinerCalendrier();
    });

    document.getElementById('formulaireVisite').addEventListener('submit', (e) => {
        if (!champDate.value) {
            e.preventDefault();
            alert('Veuillez sélectionner une date sur le calendrier.');
        }
    });

    dessinerCalendrier();
})();
</script>
