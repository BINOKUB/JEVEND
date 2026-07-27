<?php
// =============================================================================
// SCRIPT : gerer_liste_envie.php
// REVISION : 2.0 - Interrupteur AJAX (Toggle) pour Ajouter/Retirer des favoris
// =============================================================================
session_start();
require_once 'config.php';

// Sécurité : On vérifie que l'utilisateur est connecté et que l'annonce est valide
$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
$id_annonce = isset($_POST['id_annonce']) ? (int)$_POST['id_annonce'] : null;

if (!$id_utilisateur || !$id_annonce) {
    echo json_encode(['status' => 'erreur', 'message' => 'Connexion requise']);
    exit();
}

try {
    // 1. VERIFICATION : L'annonce est-elle déjà dans la liste d'envie de cet utilisateur ?
    $stmt_check = $bdd->prepare("
        SELECT id_envie FROM jevend_listes_envie 
        WHERE id_utilisateur = ? AND id_annonce = ?
    ");
    $stmt_check->execute([$id_utilisateur, $id_annonce]);
    $favoris = $stmt_check->fetch();

    if ($favoris) {
        // L'objet y est déjà : l'utilisateur a recliqué pour le retirer
        $stmt_delete = $bdd->prepare("
            DELETE FROM jevend_listes_envie 
            WHERE id_utilisateur = ? AND id_annonce = ?
        ");
        $stmt_delete->execute([$id_utilisateur, $id_annonce]);
        
        echo json_encode(['status' => 'retire', 'message' => 'Retiré des favoris']);
    } else {
        // L'objet n'y est pas : on l'ajoute
        $stmt_insert = $bdd->prepare("
            INSERT INTO jevend_listes_envie (id_utilisateur, id_annonce) 
            VALUES (?, ?)
        ");
        $stmt_insert->execute([$id_utilisateur, $id_annonce]);
        
        echo json_encode(['status' => 'ajoute', 'message' => 'Ajouté aux favoris']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'erreur', 'message' => 'Erreur serveur SQL']);
}
