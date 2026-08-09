<?php
// =============================================================================
// NOM DU SCRIPT : partials/_check_ann_reguliere.php
// REVISION     : 2.0 - Purge automatique unifiée des annonces, images et bannières associées
// DESCRIPTION  : Nettoie les annonces expirées, supprime leurs images physiques 
//                du dossier uploads/ et supprime les bannières régulières liées.
// =============================================================================

if (!isset($bdd)) {
    return;
}

try {
    // 1. Récupérer les annonces expirées avec leurs images et IDs
    $stmt_expirees = $bdd->query("
        SELECT id_annonces, image_courante 
        FROM jevend_annonces 
        WHERE date_expiration IS NOT NULL 
          AND date_expiration < NOW()
    ");
    $annonces_expirees = $stmt_expirees->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($annonces_expirees)) {
        foreach ($annonces_expirees as $annonce) {
            $id_annonce = (int)$annonce['id_annonces'];
            $image_courante = trim($annonce['image_courante'] ?? '');

            // 2. Suppression de l'image physique dans uploads/ si elle existe
            if (!empty($image_courante)) {
                $chemin_image = "uploads/" . $image_courante;
                if (file_exists($chemin_image) && is_file($chemin_image)) {
                    @unlink($chemin_image);
                }
            }

            // 3. Suppression de la bannière régulière associée (s'il y en a une)
            $stmt_banniere = $bdd->prepare("DELETE FROM jevend_bannieres_actives WHERE id_annonce = ?");
            $stmt_banniere->execute([$id_annonce]);

            // 4. Suppression de l'annonce elle-même
            $stmt_annonce = $bdd->prepare("DELETE FROM jevend_annonces WHERE id_annonces = ?");
            $stmt_annonce->execute([$id_annonce]);
        }
    }

} catch (PDOException $e) {
    error_log("Erreur lors de la purge automatique des annonces expirées : " . $e->getMessage());
}
