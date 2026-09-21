 
/**
 * js/comparateur.js
 * ----------------------
 * Logique partagée du comparateur de biens, chargée à la fois sur la
 * page de recherche et sur la fiche détail. La sélection est stockée en
 * localStorage : elle survit à la navigation entre plusieurs pages de
 * résultats, contrairement à un simple état en mémoire qui se perdrait
 * au moindre rechargement.
 */
(function () {
    const CLE_STOCKAGE = 'ttm_comparateur';
    const MAX_BIENS = 4;

    function lireSelection() {
        try {
            return JSON.parse(localStorage.getItem(CLE_STOCKAGE) || '[]');
        } catch (erreur) {
            return [];
        }
    }

    function ecrireSelection(liste) {
        localStorage.setItem(CLE_STOCKAGE, JSON.stringify(liste));
    }

    function creerBarre() {
        let barre = document.getElementById('barreComparateur');
        if (!barre) {
            barre = document.createElement('div');
            barre.id = 'barreComparateur';
            barre.className = 'barre-comparateur';
            document.body.appendChild(barre);
        }
        return barre;
    }

    function actualiserBarre() {
        const selection = lireSelection();
        const barre = creerBarre();

        if (selection.length === 0) {
            barre.classList.remove('visible');
            barre.innerHTML = '';
            return;
        }

        barre.classList.add('visible');
        barre.innerHTML = `
            <div class="barre-comparateur-contenu">
                <div class="barre-comparateur-miniatures">
                    ${selection.map((b) => `
                        <div class="mini-comparateur" title="${b.titre}">
                            ${b.photo ? `<img src="${b.photo}" alt="">` : '<span class="mini-comparateur-vide"></span>'}
                            <button type="button" class="mini-comparateur-retirer" data-id="${b.id}" aria-label="Retirer du comparateur">&times;</button>
                        </div>
                    `).join('')}
                </div>
                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                    <span class="small text-muted d-none d-sm-inline">${selection.length}/${MAX_BIENS}</span>
                    ${selection.length >= 2
                        ? `<a href="${window.TTM_URL_COMPARATEUR}?ids=${selection.map((b) => b.id).join(',')}" class="btn btn-accent btn-sm">Comparer</a>`
                        : `<span class="small text-muted">Choisissez-en au moins 2</span>`}
                    <button type="button" id="boutonViderComparateur" class="btn btn-link btn-sm text-muted p-0">Vider</button>
                </div>
            </div>
        `;

        barre.querySelectorAll('.mini-comparateur-retirer').forEach((bouton) => {
            bouton.addEventListener('click', () => retirerDuComparateur(parseInt(bouton.dataset.id, 10)));
        });
        const boutonVider = document.getElementById('boutonViderComparateur');
        if (boutonVider) {
            boutonVider.addEventListener('click', () => {
                ecrireSelection([]);
                actualiserBarre();
                actualiserCases();
            });
        }
    }

    function actualiserCases() {
        const idsSelectionnes = lireSelection().map((b) => b.id);
        document.querySelectorAll('.case-comparateur').forEach((caseAComparer) => {
            const id = parseInt(caseAComparer.dataset.id, 10);
            const estSelectionne = idsSelectionnes.includes(id);
            caseAComparer.checked = estSelectionne;

            const texte = caseAComparer.closest('label')?.querySelector('.texte-comparateur');
            if (texte) texte.textContent = estSelectionne ? 'Retirer du comparateur' : 'Ajouter au comparateur';
        });
    }
    window.actualiserCasesComparateur = actualiserCases;

    function ajouterAuComparateur(id, titre, prix, photo) {
        const selection = lireSelection();
        if (selection.some((b) => b.id === id)) return;
        if (selection.length >= MAX_BIENS) {
            alert(`Vous pouvez comparer ${MAX_BIENS} biens maximum. Retirez-en un d'abord.`);
            actualiserCases();
            return;
        }
        selection.push({ id, titre, prix, photo });
        ecrireSelection(selection);
        actualiserBarre();
    }

    function retirerDuComparateur(id) {
        ecrireSelection(lireSelection().filter((b) => b.id !== id));
        actualiserBarre();
        actualiserCases();
    }
    window.retirerDuComparateur = retirerDuComparateur;

    document.addEventListener('change', (e) => {
        if (!e.target.classList.contains('case-comparateur')) return;
        const id = parseInt(e.target.dataset.id, 10);
        if (e.target.checked) {
            ajouterAuComparateur(id, e.target.dataset.titre, e.target.dataset.prix, e.target.dataset.photo || '');
        } else {
            retirerDuComparateur(id);
        }
        actualiserCases();
    });

    document.addEventListener('DOMContentLoaded', () => {
        actualiserCases();
        actualiserBarre();
    });
})();


