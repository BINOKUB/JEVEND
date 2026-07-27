<?php
// =============================================================================
// SCRIPT : verifier_file_queue.php
// REVISION : 2.1 - Correction de la table cible (jevend_bannieres_actives_pro) et contrôle date_fin >= NOW()
// NOM DU SCRIPT : verifier_file_queue.php
// =============================================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['statut' => 'erreur', 'message' => 'Non connecté']);
    exit();
}

require_once 'config.php';

try {
    // 1. Statut Suprême : Libre s'il y en a 0 active et non expirée dans la table PRO
    $stmt = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'supreme' AND statut_affichage = 'active' AND date_fin >= NOW()");
    $supreme_libre = ((int)$stmt->fetchColumn() < 1);

    // 2. Statut Premium : Libre s'il y en a moins de 4 actives et non expirées dans la table PRO
    $stmt = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'premium' AND statut_affichage = 'active' AND date_fin >= NOW()");
    $premium_libre = ((int)$stmt->fetchColumn() < 4);

    // 3. Statut Flux (Bronze / Régulière) : Soumis à la règle des 50% d'occupation
    $stmt_annonces = $bdd->query("SELECT COUNT(*) FROM jevend_annonces WHERE statut = 'actif'");
    $total_annonces = (int)$stmt_annonces->fetchColumn();
    $quota_max = ceil($total_annonces * 0.50);

    $stmt_flux = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE statut_affichage = 'active' AND date_fin >= NOW() AND type_banniere IN ('bronze', 'reguliere')");
    $flux_libre = ((int)$stmt_flux->fetchColumn() < $quota_max);

    // On renvoie un état binaire propre pour chaque forfait
    echo json_encode([
        'statut' => 'succes',
        'supreme' => $supreme_libre ? 'libre' : 'occupe',
        'premium' => $premium_libre ? 'libre' : 'occupe',
        'bronze' => $flux_libre ? 'libre' : 'occupe',
        'reguliere' => $flux_libre ? 'libre' : 'occupe'
    ]);

} catch (PDOException $e) {
    echo json_encode(['statut' => 'erreur', 'message' => $e->getMessage()]);
}
