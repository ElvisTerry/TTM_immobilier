<?php
/**
 * includes/Mailer.php
 * -----------------------
 * Envoi (ou simulation) des emails transactionnels.
 *
 * Pourquoi "simuler" en développement plutôt qu'utiliser directement
 * mail() partout ?
 * Laragon n'a pas de serveur SMTP configuré par défaut : mail() y échoue
 * silencieusement, sans erreur visible, ce qui rendrait le test de
 * l'inscription impossible en local. En développement, on écrit donc
 * l'email dans un fichier — en production (Jour 21, sur InfinityFree),
 * on utilisera mail() ou un vrai service transactionnel.
 *
 * Limite assumée : mail() sur un hébergement mutualisé gratuit comme
 * InfinityFree part souvent en spam ou est bloqué par les gros
 * fournisseurs (Gmail, Outlook). Pour un vrai lancement, la bonne
 * pratique serait un service dédié (Brevo, Mailjet...) — hors du
 * périmètre gratuit de ce projet, mais à garder en tête.
 */
class Mailer
{
    public static function envoyer(string $destinataire, string $sujet, string $corps): void
    {
        if (ENVIRONNEMENT === 'production') {
            $entetes = "From: no-reply@ttm-app.ifree.page\r\nContent-Type: text/plain; charset=UTF-8";
            mail($destinataire, $sujet, $corps, $entetes);
            return;
        }

        // --- Mode développement : on "simule" l'envoi ---
        $dossierLogs = __DIR__ . '/../logs/emails-simules';
        if (!is_dir($dossierLogs)) {
            mkdir($dossierLogs, 0755, true);
        }

        $nomFichier = $dossierLogs . '/' . date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $destinataire) . '.txt';
        file_put_contents($nomFichier, "À : $destinataire\nSujet : $sujet\n\n$corps");
    }

    public static function envoyerReinitialisationEmail(string $destinataire, string $nom, string $lienReinitialisation): void
    {
        $sujet = 'Réinitialisation de votre mot de passe - ImmoApp';
        $corps = "Bonjour $nom,\n\nVous avez demandé à réinitialiser votre mot de passe.\n\n"
               . "Cliquez sur ce lien pour en choisir un nouveau (valable 1 heure) :\n$lienReinitialisation\n\n"
               . "Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.\n";

        self::envoyer($destinataire, $sujet, $corps);
    }
    /**
     * Envoie le lien de confirmation de changement d'email.
     */
    public static function envoyerChangementEmail(
        string $destinataire,
        string $nom,
        string $lienConfirmation
    ): void {
        $sujet = 'Confirmation de votre nouvelle adresse email - ImmoApp';

        $corps =
            "Bonjour $nom,\n\n"
            . "Vous avez demandé à modifier l'adresse email de votre compte ImmoApp.\n\n"
            . "Cliquez sur le lien suivant pour confirmer votre nouvelle adresse email :\n\n"
            . "$lienConfirmation\n\n"
            . "Ce lien est valable pendant 1 heure.\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, "
            . "ignorez simplement cet email.\n\n"
            . "Cordialement,\n"
            . "L'équipe ImmoApp";

        self::envoyer(
            $destinataire,
            $sujet,
            $corps
        );
    }

}
