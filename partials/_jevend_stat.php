<?php
// =============================================================================
// NOM DU SCRIPT : partials/_jevend_stat.php
// REVISION : 1.2 - Intégration complète de la journalisation des vues publicitaires
// MODULE UNIQUE
// =============================================================================

/**
 * Incrémente le compteur de vues d'une annonce selon le type de session.
 */
function incrementerVueAnnonce($bdd, $id_annonce, $id_utilisateur_connecte) {
    if ($id_annonce <= 0) return;

    try {
        if ($id_utilisateur_connecte) {
            $stmt = $bdd->prepare("UPDATE jevend_annonces SET nb_vues_membres = nb_vues_membres + 1 WHERE id_annonces = ?");
        } else {
            $stmt = $bdd->prepare("UPDATE jevend_annonces SET nb_vues_visiteurs = nb_vues_visiteurs + 1 WHERE id_annonces = ?");
        }
        $stmt->execute([$id_annonce]);
    } catch (PDOException $e) {
        // Enregistrement silencieux en production
    }
}

/**
 * Incrémente le compteur d'affichages (vues) d'une bannière publicitaire.
 */
function incrementerVueBanniere($bdd, $id_banniere) {
    if ($id_banniere <= 0) return;

    try {
        $stmt = $bdd->prepare("UPDATE jevend_bannieres_actives SET nb_vues = nb_vues + 1 WHERE id_banniere = ?");
        $stmt->execute([$id_banniere]);
    } catch (PDOException $e) {
        // Enregistrement silencieux en production
    }
}
