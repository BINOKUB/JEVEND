<?php
// =============================================================================
// NOM DU SCRIPT : panneau_maintenance_ajax.php
// REVISION     : 2.0 - Mise à jour atomique via transaction PDO
// =============================================================================

session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Sécurité Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé.']);
    exit();
}

if (($_POST['action'] ?? '') === 'update_maintenance') {
    
    // Contrôle strict de la présence du champ pour éviter les écrasements accidentels
    if (!isset($_POST['maintenance_actif'])) {
        echo json_encode(['success' => false, 'error' => 'Données manquantes.']);
        exit();
    }

    $actif   = $_POST['maintenance_actif'] === '1' ? '1' : '0';
    $heure   = trim($_POST['maintenance_heure_ouverture'] ?? '');
    $message = trim($_POST['maintenance_message'] ?? '');

    try {
        // Début de la transaction atomique
        $bdd->beginTransaction();

        $stmt = $bdd->prepare("UPDATE jevend_parametres SET valeur_parametre = ? WHERE cle_parametre = ?");
        
        $stmt->execute([$actif, 'maintenance_actif']);
        $stmt->execute([$heure, 'maintenance_heure_ouverture']);
        $stmt->execute([$message, 'maintenance_message']);

        // Validation de la transaction
        $bdd->commit();

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($bdd->inTransaction()) {
            $bdd->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Erreur SQL lors de la sauvegarde.']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Action invalide.']);
