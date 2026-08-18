<?php
// =============================================================================
// NOM DU SCRIPT : partials/_cloture_recherche.php
// DESCRIPTION  : Traitement de la clôture immédiate d'une recherche et purge des chats
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_marquer_trouve'])) {
    if (isset($est_auteur) && $est_auteur && isset($id_recherche) && $id_recherche > 0) {
        try {
            // 1. Marquer la recherche comme trouvée/résolue
            $stmt_upd = $bdd->prepare("UPDATE jevend_recherches SET statut = 'trouve' WHERE id_recherche = ?");
            $stmt_upd->execute([$id_recherche]);
            
            // 2. Supprimer immédiatement les messages de tchat associés à cette recherche
            $stmt_del_chat = $bdd->prepare("DELETE FROM jevend_chat WHERE id_recherche = ?");
            $stmt_del_chat->execute([$id_recherche]);

            $succes = "Félicitations ! Votre demande a été marquée comme résolue et les conversations en direct ont été purgées.";
            $demande['statut'] = 'trouve';
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la clôture de la recherche : " . $e->getMessage();
        }
    }
}
