<?php
// =============================================================================
// SCRIPT : verifier_file_queue.php
// REVISION : 2.3 - Correction strictement validée selon DESCRIBE jevend_bannieres_actives
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
    // 1. Statut Suprême (Table PRO)
    $stmt = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'supreme' AND statut_affichage = 'active' AND date_fin >= NOW()");
    $supreme_libre = ((int)$stmt->fetchColumn() < 1);

    // 2. Statut Premium (Table PRO)
    $stmt = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'premium' AND statut_affichage = 'active' AND date_fin >= NOW()");
    $premium_libre = ((int)$stmt->fetchColumn() < 4);

    // 3. Statut Flux / Régulière (Table RÉGULIÈRE : jevend_bannieres_actives)
    $stmt_annonces = $bdd->query("SELECT COUNT(*) FROM jevend_annonces WHERE statut = 'actif'");
    $total_annonces = (int)$stmt_annonces->fetchColumn();
    $quota_max = ceil($total_annonces * 0.50);

    // Calcul direct via date_debut_activation + duree_jours
    $sql_flux = "SELECT COUNT(*) FROM jevend_bannieres_actives 
                 WHERE statut_affichage = 'active' 
                   AND type_banniere = 'reguliere'
                   AND DATE_ADD(date_debut_activation, INTERVAL duree_jours DAY) >= NOW()";
    
    $stmt_flux = $bdd->query($sql_flux);
    $flux_libre = ((int)$stmt_flux->fetchColumn() < $quota_max);

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
