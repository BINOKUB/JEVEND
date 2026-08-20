<?php
// =============================================================================
// NOM DU SCRIPT : partials/_delete_je_cherche.php
// REVISION     : 2.4 - Purge automatique (60 jours) avec verrouillage en base de données
// DESCRIPTION  : Nettoie les recherches expirées de plus de 60 jours (1 fois par 24h via la BDD).
// =============================================================================

if (isset($bdd)) {
    try {
        // 1. S'assurer que la table de suivi système existe en base de données
        $bdd->exec("CREATE TABLE IF NOT EXISTS jevend_meta (
            cle VARCHAR(50) PRIMARY KEY,
            valeur TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. Vérifier la dernière exécution de la purge enregistrée en BDD
        $stmt_verif = $bdd->prepare("SELECT valeur FROM jevend_meta WHERE cle = 'derniere_purge_cherche'");
        $stmt_verif->execute();
        $derniere_purge = $stmt_verif->fetchColumn();

        $maintenant = time();
        $frequence_secondes = 86400; // 24 heures en secondes

        // Si aucune purge n'a jamais eu lieu ou si 24h se sont écoulées
        if (!$derniere_purge || ($maintenant - (int)$derniere_purge) > $frequence_secondes) {
            
            $delai_jours = 60; // Règle des 60 jours demandée
            
            // Sélectionner les IDs et les images des recherches expirées de plus de 60 jours
            $stmt_select = $bdd->prepare("
                SELECT id_recherche, image_reference 
                FROM jevend_recherches 
                WHERE statut = 'expire' AND date_expiration < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt_select->execute([$delai_jours]);
            $recherches_a_purger = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($recherches_a_purger)) {
                $ids_a_purger = [];

                foreach ($recherches_a_purger as $rech) {
                    $ids_a_purger[] = $rech['id_recherche'];
                    
                    // Suppression physique de l'image sur le serveur si elle existe
                    if (!empty($rech['image_reference']) && file_exists(__DIR__ . '/../uploads/' . $rech['image_reference'])) {
                        @unlink(__DIR__ . '/../uploads/' . $rech['image_reference']);
                    }
                }

                $placeholders = implode(',', array_fill(0, count($ids_a_purger), '?'));

                // 3. Supprimer les messages de chat liés à ces recherches
                $stmt_del_chat = $bdd->prepare("DELETE FROM jevend_chat WHERE id_recherche IN ($placeholders)");
                $stmt_del_chat->execute($ids_a_purger);

                // 4. Supprimer les réponses associées dans jevend_reponses_recherche
                $stmt_del_rep = $bdd->prepare("DELETE FROM jevend_reponses_recherche WHERE id_recherche IN ($placeholders)");
                $stmt_del_rep->execute($ids_a_purger);

                // 5. Supprimer enfin les recherches elles-mêmes de la base
                $stmt_del_rech = $bdd->prepare("DELETE FROM jevend_recherches WHERE id_recherche IN ($placeholders)");
                $stmt_del_rech->execute($ids_a_purger);
            }

            // 6. Mettre à jour le chronomètre en base de données pour les prochaines 24h
            $stmt_upd = $bdd->prepare("INSERT INTO jevend_meta (cle, valeur) VALUES ('derniere_purge_cherche', ?) ON DUPLICATE KEY UPDATE valeur = ?");
            $stmt_upd->execute([$maintenant, $maintenant]);
        }

    } catch (PDOException $e) {
        error_log("Erreur lors de la purge automatique des 60 jours (DB) : " . $e->getMessage());
    }
}
?>
