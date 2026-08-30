<?php
// =============================================================================
// NOM DU SCRIPT : partials/_check_ann_reguliere.php
// REVISION     : 2.1 - Traitement autonome et séparé des expirations (Bannières & Annonces)
// DESCRIPTION  : 1. Purge autonome des bannières payées dont la durée est écoulée (ex: 10 jours).
//                2. Purge complète des annonces expirées (annonces, images et bannières liées).
// =============================================================================

if (!isset($bdd)) {
    return;
}

try {
    // -------------------------------------------------------------------------
    // ÉTAPE 1 : PURGE AUTONOME DES BANNIÈRES EXPIRÉES (ex: 10 jours écoulés)
    // -------------------------------------------------------------------------
    // Supprime les bannières dont le temps d'activation a dépassé la durée payée,
    // même si l'annonce associée est encore active sur le site.
    $stmt_purge_bann = $bdd->prepare("
        DELETE FROM jevend_bannieres_actives 
        WHERE date_debut_activation IS NOT NULL 
          AND DATE_ADD(date_debut_activation, INTERVAL duree_jours DAY) < NOW()
    ");
    $stmt_purge_bann->execute();


    // -------------------------------------------------------------------------
    // ÉTAPE 2 : PURGE GLOBALE DES ANNONCES EXPIRÉES (ex: 30 jours écoulés)
    // -------------------------------------------------------------------------
    // 2.1 Récupérer les annonces expirées avec leurs images et IDs
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

            // 2.2 Suppression de l'image physique dans uploads/ si elle existe
            if (!empty($image_courante)) {
                $chemin_image = "uploads/" . $image_courante;
                if (file_exists($chemin_image) && is_file($chemin_image)) {
                    @unlink($chemin_image);
                }
            }

            // 2.3 Suppression de la bannière régulière associée (si encore présente)
            $stmt_banniere = $bdd->prepare("DELETE FROM jevend_bannieres_actives WHERE id_annonce = ?");
            $stmt_banniere->execute([$id_annonce]);

            // 2.4 Suppression de l'annonce elle-même
            $stmt_annonce = $bdd->prepare("DELETE FROM jevend_annonces WHERE id_annonces = ?");
            $stmt_annonce->execute([$id_annonce]);
        }
    }

} catch (PDOException $e) {
    error_log("Erreur lors de la purge automatique des expirations : " . $e->getMessage());
}
