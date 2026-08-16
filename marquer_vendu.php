<?php
// =============================================================================
// NOM DU SCRIPT : marquer_vendu.php
// REVISION : 1.0 - Passage instantané d'une vitrine à l'état "Vendu" via AJAX
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
        // Sécurité : On vérifie que l'annonce appartient bien à l'utilisateur connecté
        $stmt = $bdd->prepare("UPDATE jevend_annonces SET statut_vente = 'vendu' WHERE id_annonces = ? AND id_utilisateur = ?");
        $stmt->execute([$id_annonce, $id_utilisateur]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'succes', 'message' => 'L\'objet est désormais marqué comme vendu']);
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
