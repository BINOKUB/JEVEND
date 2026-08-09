<?php
/*
====================================================
NOM DU SCRIPT : _nombre_annonces.php
DESCRIPTION   : Vérifie en direct le quota global des annonces vs jevend_parametres
====================================================
*/

$total_annonces_reseau = 0;
$limite_globale_annonces = 2000; // Valeur de repli sécurisée
$quota_annonces_atteint = false;

try {
    // 1. Total actuel des annonces sur la plateforme
    $stmt_tot = $bdd->query("SELECT COUNT(*) FROM jevend_annonces");
    $total_annonces_reseau = (int)$stmt_tot->fetchColumn();

    // 2. Quota global défini dans la table jevend_parametres
    $stmt_limite = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'limite_annonces'");
    $stmt_limite->execute();
    $val_limite = $stmt_limite->fetchColumn();
    
    if ($val_limite !== false) {
        $limite_globale_annonces = (int)$val_limite;
    }

    // 3. Évaluation du seuil critique
    if ($total_annonces_reseau >= $limite_globale_annonces) {
        $quota_annonces_atteint = true;
    }
} catch (PDOException $e) {
    // En cas d'erreur silencieuse, on laisse l'accès ouvert par défaut
    $quota_annonces_atteint = false;
}
?>
