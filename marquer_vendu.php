<?php
// =============================================================================
// NOM DU SCRIPT : marquer_vendu.php
// REVISION : 1.1 - Passage instantané d'une vitrine à "Vendu" et purge des chats associés
// =============================================================================
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'erreur', 'message' => 'Connexion requise']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_annonce'])) {
    $id_annonce = (int)$_POST['id_annonce'];
    $id_utilisateur = $_SESSION['id_utilisateur'];

    try {
        // 1. Sécurité : On vérifie que l'annonce appartient bien à l'utilisateur connecté et on la marque comme vendue
        $stmt = $bdd->prepare("UPDATE jevend_annonces SET statut_vente = 'vendu' WHERE id_annonces = ? AND id_utilisateur = ?");
        $stmt->execute([$id_annonce, $id_utilisateur]);

        if ($stmt->rowCount() > 0) {
            // 2. Nettoyage immédiat : Suppression de tous les messages de tchat liés à cette annonce
            $stmt_del_chat = $bdd->prepare("DELETE FROM jevend_chat WHERE id_annonce = ?");
            $stmt_del_chat->execute([$id_annonce]);

            echo json_encode(['status' => 'succes', 'message' => 'L\'objet est désormais marqué comme vendu et les conversations associées ont été purgées.']);
        } else {
            echo json_encode(['status' => 'erreur', 'message' => 'Action impossible ou annonce introuvable']);
        }
        exit();
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'erreur', 'message' => $e->getMessage()]);
        exit();
    }
}

header('HTTP/1.1 400 Bad Request');
echo json_encode(['status' => 'erreur', 'message' => 'Requête invalide']);
exit();
