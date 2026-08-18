<?php
// =============================================================================
// NOM DU SCRIPT : partials/_delete_je_cherche.php
// REVISION     : 2.0 - Purge automatique incluant les messages de chat (id_recherche)
// DESCRIPTION  : Nettoie les recherches expirées/trouvées, leurs réponses 
//                et les messages de chat associés de manière silencieuse.
// =============================================================================

if (isset($bdd)) {
    $fichier_verrou = __DIR__ . '/../cache_purge_cherche.tmp';
    $frequence_jours = 1;

    if (!file_exists($fichier_verrou) || (time() - file_get_contents($fichier_verrou)) > ($frequence_jours * 86400)) {
        
        try {
            $delai_jours = 30;
            
            // 1. Sélectionner les IDs des recherches à purger (Expirées ou Trouvées > 30 jours)
            $stmt_select = $bdd->prepare("
                SELECT id_recherche 
                FROM jevend_recherches 
                WHERE (statut = 'expire' AND date_expiration < DATE_SUB(NOW(), INTERVAL ? DAY))
                   OR (statut = 'trouve' AND date_creation < DATE_SUB(NOW(), INTERVAL ? DAY))
            ");
            $stmt_select->execute([$delai_jours, $delai_jours]);
            $ids_a_purger = $stmt_select->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ids_a_purger)) {
                $placeholders = implode(',', array_fill(0, count($ids_a_purger), '?'));

                // 2. Supprimer les messages de chat liés à ces recherches
                $stmt_del_chat = $bdd->prepare("DELETE FROM jevend_chat WHERE id_recherche IN ($placeholders)");
                $stmt_del_chat->execute($ids_a_purger);

                // 3. Supprimer les réponses associées dans jevend_reponses_recherche
                $stmt_del_rep = $bdd->prepare("DELETE FROM jevend_reponses_recherche WHERE id_recherche IN ($placeholders)");
                $stmt_del_rep->execute($ids_a_purger);

                // 4. Supprimer enfin les recherches elles-mêmes
                $stmt_del_rech = $bdd->prepare("DELETE FROM jevend_recherches WHERE id_recherche IN ($placeholders)");
                $stmt_del_rech->execute($ids_a_purger);
            }

            file_put_contents($fichier_verrou, time());

        } catch (PDOException $e) {
            error_log("Erreur lors de la purge Je Cherche & Chat : " . $e->getMessage());
        }
    }
}
?>
