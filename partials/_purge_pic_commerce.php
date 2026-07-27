<?php
// =============================================================================
// NOM DU SCRIPT : _purge_pic_commerce.php
// REVISION : 1.0 - Module de purge automatique des visuels publicitaires PRO expirés
// =============================================================================

if (!isset($bdd)) {
    // Si le fichier est appelé directement, on charge la config
    require_once 'config.php';
}

try {
    // Délai de grâce : On garde les images physiques jusqu'à 30 jours après la date de fin (pour permettre les renouvellements de dernière minute)
    $delai_grace_jours = 30;

    // Sélectionner les bannières expirées dont l'image n'a pas encore été purgée
    $sql_a_purger = "
        SELECT id_banniere_pro, image_url 
        FROM jevend_bannieres_actives_pro 
        WHERE date_fin < DATE_SUB(NOW(), INTERVAL :delai DAY)
          AND image_url != '' 
          AND image_url IS NOT NULL
    ";
    
    $stmt = $bdd->prepare($sql_a_purger);
    $stmt->bindValue(':delai', $delai_grace_jours, PDO::PARAM_INT);
    $stmt->execute();
    $bannieres_a_nettoyer = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $compteur_purges = 0;

    foreach ($bannieres_a_nettoyer as $bann) {
        $chemin_fichier = $bann['image_url'];

        // Vérifier si le fichier physique existe avant de tenter de le supprimer
        if (!empty($chemin_fichier) && file_exists($chemin_fichier)) {
            @unlink($chemin_fichier); // Supprime l'image du serveur
        }

        // Vider le champ image_url en base pour signifier que le visuel est purgé, 
        // tout en conservant l'historique, les prix, les dates et les nb_clics !
        $sql_update = "UPDATE jevend_bannieres_actives_pro SET image_url = '' WHERE id_banniere_pro = ?";
        $update_stmt = $bdd->prepare($sql_update);
        $update_stmt->execute([$bann['id_banniere_pro']]);

        $compteur_purges++;
    }

    // Optionnel : Consigner dans les logs du serveur si des purges ont eu lieu
    if ($compteur_purges > 0) {
        error_log("[PURGE PRO] $image(s) publicitaire(s) expirée(s) purgée(s) du serveur avec succès.");
    }

} catch (PDOException $e) {
    error_log("[ERREUR PURGE PRO] " . $e->getMessage());
}
?>
