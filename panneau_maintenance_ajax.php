<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Sécurité Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé.']);
    exit();
}

if (($_POST['action'] ?? '') === 'update_maintenance') {
    $actif = $_POST['maintenance_actif'] ?? '0';
    $heure = trim($_POST['maintenance_heure_ouverture'] ?? '');
    $message = trim($_POST['maintenance_message'] ?? '');

    try {
        $stmt = $bdd->prepare("
            INSERT INTO jevend_parametres (cle_parametre, valeur_parametre) 
            VALUES 
                ('maintenance_actif', ?),
                ('maintenance_heure_ouverture', ?),
                ('maintenance_message', ?)
            ON DUPLICATE KEY UPDATE valeur_parametre = VALUES(valeur_parametre)
        ");
        
        // Exécution pour chaque clé
        $bdd->prepare("UPDATE jevend_parametres SET valeur_parametre = ? WHERE cle_parametre = 'maintenance_actif'")->execute([$actif]);
        $bdd->prepare("UPDATE jevend_parametres SET valeur_parametre = ? WHERE cle_parametre = 'maintenance_heure_ouverture'")->execute([$heure]);
        $bdd->prepare("UPDATE jevend_parametres SET valeur_parametre = ? WHERE cle_parametre = 'maintenance_message'")->execute([$message]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur SQL lors de la sauvegarde.']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Action invalide.']);
