<?php
// =============================================================================
// NOM DU SCRIPT : partials/_check_ann_reguliere.php
// REVISION     : 1.0 - Purge automatique des annonces régulières expirées
// DESCRIPTION  : Nettoie la table jevend_annonces des annonces dont la date
//                d'expiration est dépassée.
// =============================================================================

if (!isset($bdd)) {
    return;
}

try {
    // Suppression des annonces régulières dont l'échéance est dépassée
    $sql_purge_annonces = "
        DELETE FROM jevend_annonces 
        WHERE date_expiration IS NOT NULL 
          AND date_expiration < NOW()
    ";
    
    $bdd->exec($sql_purge_annonces);

} catch (PDOException $e) {
    error_log("Erreur lors de la purge des annonces expirées : " . $e->getMessage());
}
