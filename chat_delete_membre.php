<?php
// =============================================================================
// NOM DU SCRIPT : chat_delete_membre.php
// DESCRIPTION  : Purge automatique silencieuse des messages de chat > 30 jours
// =============================================================================
if (isset($bdd)) {
    try {
        $bdd->exec("DELETE FROM jevend_chat WHERE date_envoi < NOW() - INTERVAL 30 DAY");
    } catch (PDOException $e) {
        // Silencieux pour ne pas impacter la navigation
    }
}
