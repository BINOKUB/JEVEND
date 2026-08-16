<?php
// =============================================================================
// NOM DU SCRIPT : partials/_delete_je_cherche.php
// DESCRIPTION  : Purge automatique des anciennes recherches expirées ou trouvées
// =============================================================================

if (isset($bdd)) {
    // Fichier témoin pour éviter d'exécuter la requête à CHAQUE visite sur l'index
    $fichier_verrou = __DIR__ . '/../cache_purge_cherche.tmp';
    $frequence_jours = 1; // Exécuter au maximum 1 fois par jour

    // On vérifie si le fichier existe et s'il a moins de 24 heures
    if (!file_exists($fichier_verrou) || (time() - file_get_contents($fichier_verrou)) > ($frequence_jours * 86400)) {
        
        try {
            // 1. Sélectionner les IDs des recherches à purger 
            // (Ex: Expirées depuis plus de 30 jours OU Trouvées depuis plus de 30 jours)
            $delai_jours = 30;
            
            $stmt_select = $bdd->prepare("
                SELECT id_recherche 
                FROM jevend_recherches 
                WHERE (statut = 'expire' AND date_expiration < DATE_SUB(NOW(), INTERVAL ? DAY))
                   OR (statut = 'trouve' AND date_creation < DATE_SUB(NOW(), INTERVAL ? DAY))
            ");
            $stmt_select->execute([$delai_jours, $delai_jours]);
            $ids_a_purger = $stmt_select->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ids_a_purger)) {
                // Création d'une liste propre pour la requête SQL IN (...)
                $placeholders = implode(',', array_fill(0, count($ids_a_purger), '?'));

                // 2. Supprimer d'abord les réponses associées dans jevend_reponses_recherche
                $stmt_del_rep = $bdd->prepare("DELETE FROM jevend_reponses_recherche WHERE id_recherche IN ($placeholders)");
                $stmt_del_rep->execute($ids_a_purger);

                // 3. Supprimer ensuite les recherches elles-mêmes
                $stmt_del_rech = $bdd->prepare("DELETE FROM jevend_recherches WHERE id_recherche IN ($placeholders)");
                $stmt_del_rech->execute($ids_a_purger);
            }

            // Mettre à jour le fichier témoin avec l'heure actuelle
            file_put_contents($fichier_verrou, time());

        } catch (PDOException $e) {
            // En cas d'erreur SQL silencieuse pour ne pas casser l'affichage de l'index
            error_log("Erreur lors de la purge Je Cherche : " . $e->getMessage());
        }
    }
}
?>
