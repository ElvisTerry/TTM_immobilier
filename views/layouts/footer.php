</main>

<footer class="text-white-50 py-4 mt-5" style="background-color: var(--c-foret-sombre);">
    <div class="container text-center small">
        &copy; <?= date('Y') ?> TTM - Trouve Ton Milieu
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/**
 * Bascule de thème clair/sombre — persistante via localStorage.
 */
(function () {
    const bouton = document.getElementById('boutonTheme');
    if (!bouton) return;

    function mettreAJourIcone() {
        bouton.textContent = document.documentElement.getAttribute('data-theme') === 'sombre' ? '☀️' : '🌙';
    }
    mettreAJourIcone();

    bouton.addEventListener('click', () => {
        const sombreActif = document.documentElement.getAttribute('data-theme') === 'sombre';
        if (sombreActif) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'clair');
        } else {
            document.documentElement.setAttribute('data-theme', 'sombre');
            localStorage.setItem('theme', 'sombre');
        }
        mettreAJourIcone();
    });
})();
</script>

<script>
/**
 * Menu plein écran (Lot 2) : ouverture/fermeture, icône hamburger qui
 * se transforme en croix, focus déplacé dans le menu à l'ouverture et
 * restitué au bouton à la fermeture (même logique d'accessibilité que
 * la lightbox photo), fermeture au clavier (Échap) et au clic sur un lien.
 */
(function () {
    const boutonMenu = document.getElementById('boutonMenu');
    const menu = document.getElementById('menuPleinEcran');
    if (!boutonMenu || !menu) return;

    let elementAvantOuverture = null;

    function ouvrirMenu() {
        elementAvantOuverture = document.activeElement;
        menu.classList.add('ouvert');
        boutonMenu.classList.add('ouvert');
        boutonMenu.setAttribute('aria-expanded', 'true');
        boutonMenu.setAttribute('aria-label', 'Fermer le menu');
        menu.setAttribute('aria-hidden', 'false');
        document.body.classList.add('menu-plein-ecran-actif');

        const premierLien = menu.querySelector('.lien-menu');
        if (premierLien) premierLien.focus();
    }

    function fermerMenu() {
        menu.classList.remove('ouvert');
        boutonMenu.classList.remove('ouvert');
        boutonMenu.setAttribute('aria-expanded', 'false');
        boutonMenu.setAttribute('aria-label', 'Ouvrir le menu');
        menu.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('menu-plein-ecran-actif');

        if (elementAvantOuverture) elementAvantOuverture.focus();
    }

    boutonMenu.addEventListener('click', () => {
        menu.classList.contains('ouvert') ? fermerMenu() : ouvrirMenu();
    });
    

    // Un clic sur un lien du menu ferme le panneau avant la navigation.
    menu.querySelectorAll('a').forEach((lien) => {
        lien.addEventListener('click', fermerMenu);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('ouvert')) fermerMenu();
    });
})();
</script>

<?php if (!empty($inclureLeaflet)): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>

<?php if (estConnecte()): ?>
<script>
/**
 * Panneau de notifications : chargé UNIQUEMENT à l'ouverture de la
 * cloche (événement Bootstrap "show.bs.dropdown"), pas au chargement de
 * la page.
 */
(function () {
    const cloche = document.getElementById('clocheNotifications');
    if (!cloche) return;

    const liste = document.getElementById('listeNotifications');
    const badge = document.getElementById('badgeNotifications');
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;
    const urlListe = <?= json_encode(url('notifications/liste')) ?>;
    const urlMarquerLues = <?= json_encode(url('notifications/marquer-lues')) ?>;

    let dejaChargees = false;
    let decalageNotifications = 0;

    function texteRelatif(dateISO) {
        const diffMinutes = Math.round((Date.now() - new Date(dateISO.replace(' ', 'T'))) / 60000);
        if (diffMinutes < 1) return "à l'instant";
        if (diffMinutes < 60) return `il y a ${diffMinutes} min`;
        const diffHeures = Math.round(diffMinutes / 60);
        if (diffHeures < 24) return `il y a ${diffHeures} h`;
        return `il y a ${Math.round(diffHeures / 24)} j`;
    }

    function ajouterNotifications(notifications) {
        notifications.forEach((notification) => {
            const item = document.createElement('li');
            const lien = document.createElement('a');
            lien.className = 'dropdown-item small py-2 ' + (notification.lu === '0' || notification.lu === 0 ? 'fw-semibold' : 'text-muted');
            lien.href = notification.lien || '#';

            const texte = document.createElement('div');
            texte.textContent = notification.contenu;
            const date = document.createElement('div');
            date.className = 'text-muted';
            date.style.fontSize = '0.75rem';
            date.textContent = texteRelatif(notification.date_creation);

            lien.appendChild(texte);
            lien.appendChild(date);
            item.appendChild(lien);
            liste.appendChild(item);
        });
    }

    async function chargerNotifications(decalage) {
        try {
            const reponse = await fetch(urlListe + '?decalage=' + decalage);
            const donnees = await reponse.json();
            const notifications = donnees.notifications || [];

            document.getElementById('boutonPlusNotifications')?.remove();

            if (decalage === 0) {
                liste.innerHTML = '';
                if (notifications.length === 0) {
                    liste.innerHTML = '<li class="text-muted small text-center py-3">Aucune notification.</li>';
                    return;
                }
            }

            ajouterNotifications(notifications);
            decalageNotifications = decalage + notifications.length;

            if (donnees.aPlus) {
                const itemBouton = document.createElement('li');
                itemBouton.innerHTML = '<button type="button" id="boutonPlusNotifications" class="dropdown-item small text-center text-primary">Charger plus</button>';
                liste.appendChild(itemBouton);
                itemBouton.querySelector('button').addEventListener('click', () => chargerNotifications(decalageNotifications));
            }
        } catch (erreur) {
            liste.innerHTML = '<li class="text-danger small text-center py-3">Erreur de chargement.</li>';
        }
    }

    async function marquerToutesLues() {
        badge.classList.add('d-none');
        badge.textContent = '0';

        const donnees = new FormData();
        donnees.append('csrf_token', csrfToken);
        try {
            await fetch(urlMarquerLues, { method: 'POST', body: donnees });
        } catch (erreur) {
            // Échec silencieux : au pire, le badge réapparaîtra au
            // prochain chargement de page — rien de grave.
        }
    }

    cloche.addEventListener('show.bs.dropdown', () => {
        if (!dejaChargees) {
            chargerNotifications(0);
            dejaChargees = true;
        }
        marquerToutesLues();
    });

    if (window.bootstrap && window.bootstrap.Dropdown) {
        window.bootstrap.Dropdown.getOrCreateInstance(cloche);
    }
})();
</script>
<?php endif; ?>

<!-- ==================== Confirmation stylée ====================
     Remplace window.confirm() par une modale cohérente avec le design
     du site, réutilisable via window.confirmerAction(message). -->
<div class="modal fade" id="modaleConfirmation" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <p id="messageConfirmation" class="mb-4"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" id="boutonAnnulerConfirmation">Annuler</button>
                    <button type="button" class="btn btn-accent" id="boutonValiderConfirmation">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
window.confirmerAction = function (message) {
    return new Promise((resoudre) => {
        const modaleElement = document.getElementById('modaleConfirmation');
        const modale = window.bootstrap.Modal.getOrCreateInstance(modaleElement);
        document.getElementById('messageConfirmation').textContent = message;

        const boutonValider = document.getElementById('boutonValiderConfirmation');
        const boutonAnnuler = document.getElementById('boutonAnnulerConfirmation');

        const nouveauValider = boutonValider.cloneNode(true);
        const nouveauAnnuler = boutonAnnuler.cloneNode(true);
        boutonValider.replaceWith(nouveauValider);
        boutonAnnuler.replaceWith(nouveauAnnuler);

        nouveauValider.addEventListener('click', () => { modale.hide(); resoudre(true); });
        nouveauAnnuler.addEventListener('click', () => { modale.hide(); resoudre(false); });
        modaleElement.addEventListener('hidden.bs.modal', () => resoudre(false), { once: true });

        modale.show();
    });
};
</script>

</body>
</html>