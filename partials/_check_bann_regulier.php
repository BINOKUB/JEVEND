<?php
// =============================================================================
// NOM DU SCRIPT : partials/_check_bann_regulier.php
// REVISION     : 1.0 - Purge automatique et nettoyage des bannières régulières expirées
// DESCRIPTION  : Supprime de la table jevend_bannieres_actives les bannières 
//                régulières dont le terme est échu. Libère automatiquement l'annonce
//                pour qu'elle soit de nouveau éligible au réachat dans l'espace membre.
// =============================================================================

if (!isset($bdd)) {
    // Si la connexion BDD n'est pas chargée, on quitte silencieusement
    return;
}

try {
    // Purge chirurgicale des bannières régulières dont l'échéance est dépassée
    $sql_purge_regulieres = "
        DELETE FROM jevend_bannieres_actives 
        WHERE type_banniere = 'reguliere' 
          AND (
            (date_debut_activation IS NOT NULL AND DATE_ADD(date_debut_activation, INTERVAL duree_jours DAY) < NOW())
            OR 
            (date_debut_activation IS NULL AND DATE_ADD(date_enregistrement, INTERVAL duree_jours DAY) < NOW())
          )
    ";
    
    $bdd->exec($sql_purge_regulieres);

} catch (PDOException $e) {
    // Journalisation silencieuse pour ne jamais impacter le chargement de la page d'accueil
    error_log("Erreur lors de la purge des bannières régulières : " . $e->getMessage());
}
