<h1 class="h5 mb-1"><?= nettoyer($bien['titre']) ?></h1>
<p class="text-muted small mb-4">
    <a href="<?= url('biens/detail', [(int) $bien['id']]) ?>">Voir l'annonce</a>
</p>

<!-- Fil de messages : bulles à droite pour "moi", à gauche pour l'autre. -->
<div id="filMessages" class="d-flex flex-column gap-2 mb-3" style="max-height:50vh;overflow-y:auto;">
    <?php foreach ($messages as $message): ?>
        <?php $estDeMoi = (int) $message['expediteur_id'] === (int) $_SESSION['utilisateur_id']; ?>
        <div class="d-flex <?= $estDeMoi ? 'justify-content-end' : 'justify-content-start' ?>" data-id="<?= (int) $message['id'] ?>">
            <div class="p-2 px-3 rounded-3 <?= $estDeMoi ? 'text-white' : 'bg-light' ?>"
                 style="max-width:75%; <?= $estDeMoi ? 'background-color: var(--couleur-primaire);' : '' ?>">
                <div><?= nl2br(nettoyer($message['contenu'])) ?></div>
                <div class="small <?= $estDeMoi ? 'text-white-50' : 'text-muted' ?>">
                    <?= nettoyer(date('d/m à H:i', strtotime($message['date_envoi']))) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form id="formulaireMessage">
    <input type="hidden" name="csrf_token" value="<?= genererTokenCSRF() ?>">
    <div class="input-group">
        <textarea name="contenu" id="champMessage" class="form-control" rows="2" placeholder="Votre message..." required></textarea>
        <button type="submit" class="btn btn-primary">Envoyer</button>
    </div>
</form>

<script>
(function () {
    const bienId = <?= (int) $bien['id'] ?>;
    const autreId = <?= (int) $autreId ?>;
    const monId = <?= (int) $_SESSION['utilisateur_id'] ?>;
    const urlEnvoyer = <?= json_encode(url('biens/' . (int) $bien['id'] . '/messages/' . (int) $autreId . '/envoyer')) ?>;
    const urlNouveaux = <?= json_encode(url('biens/' . (int) $bien['id'] . '/messages/' . (int) $autreId . '/nouveaux')) ?>;
    const csrfToken = <?= json_encode(genererTokenCSRF()) ?>;

    const fil = document.getElementById('filMessages');
    const formulaire = document.getElementById('formulaireMessage');
    const champMessage = document.getElementById('champMessage');

    function dernierIdAffiche() {
        const bulles = fil.querySelectorAll('[data-id]');
        if (bulles.length === 0) return 0;
        return Math.max(...Array.from(bulles).map((b) => parseInt(b.dataset.id, 10)));
    }

    function ajouterBulle(message, estDeMoi) {
        const ligne = document.createElement('div');
        ligne.className = 'd-flex ' + (estDeMoi ? 'justify-content-end' : 'justify-content-start');
        ligne.dataset.id = message.id;

        const bulle = document.createElement('div');
        bulle.className = 'p-2 px-3 rounded-3 ' + (estDeMoi ? 'text-white' : 'bg-light');
        if (estDeMoi) bulle.style.backgroundColor = 'var(--couleur-primaire)';

        const contenu = document.createElement('div');
        // textContent (jamais innerHTML) pour le texte du message : la
        // protection anti-XSS ici vient du DOM lui-même, pas d'un
        // échappement manuel qu'on pourrait oublier.
        contenu.textContent = message.contenu;

        const date = document.createElement('div');
        date.className = 'small ' + (estDeMoi ? 'text-white-50' : 'text-muted');
        date.textContent = message.dateEnvoi || message.date_envoi;

        bulle.appendChild(contenu);
        bulle.appendChild(date);
        ligne.appendChild(bulle);
        fil.appendChild(ligne);
        fil.scrollTop = fil.scrollHeight;
    }

    formulaire.addEventListener('submit', async (e) => {
        e.preventDefault();
        const contenu = champMessage.value.trim();
        if (!contenu) return;

        const donnees = new FormData();
        donnees.append('contenu', contenu);
        donnees.append('csrf_token', csrfToken);

        try {
            const reponse = await fetch(urlEnvoyer, { method: 'POST', body: donnees });
            const resultat = await reponse.json();

            if (resultat.succes) {
                ajouterBulle(resultat, true);
                champMessage.value = '';
            } else {
                alert(resultat.erreur || "Échec de l'envoi.");
            }
        } catch (erreur) {
            alert('Erreur réseau, réessayez.');
        }
    });

    /**
     * Polling : toutes les 4 secondes, on demande juste les messages
     * plus récents que le dernier affiché — effet "temps réel" simple,
     * sans WebSocket ni dépendance serveur supplémentaire.
     */
    async function verifierNouveaux() {
        try {
            const reponse = await fetch(urlNouveaux + '?depuis=' + dernierIdAffiche());
            const resultat = await reponse.json();
            if (resultat.succes) {
                resultat.messages.forEach((message) => {
                    ajouterBulle(message, parseInt(message.expediteur_id, 10) === monId);
                });
            }
        } catch (erreur) {
            // Échec silencieux : le polling reprendra à l'intervalle suivant.
        }
    }

    fil.scrollTop = fil.scrollHeight;
    setInterval(verifierNouveaux, 4000);
})();
</script>
