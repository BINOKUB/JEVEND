<?php
// =============================================================================
// NOM DU SCRIPT : sauvegarder_description.php
// REVISION : 1.0 - Sauvegarde asynchrone de la description de la boutique
// =============================================================================
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'erreur', 'message' => 'Connexion requise']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilisateur = $_SESSION['id_utilisateur'];
    // On nettoie le texte reçu
    $description = isset($_POST['description_magasin']) ? trim($_POST['description_magasin']) : '';

    try {
        $stmt = $bdd->prepare("UPDATE jevend_utilisateurs SET description_magasin = ? WHERE id_utilisateur = ?");
        $stmt->execute([$description, $id_utilisateur]);

        echo json_encode(['status' => 'succes', 'message' => 'Votre mot de bienvenue a été enregistré avec succès !']);
        exit();
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'erreur', 'message' => 'Erreur SQL : ' . $e->getMessage()]);
        exit();
    }
}

header('HTTP/1.1 400 Bad Request');
echo json_encode(['status' => 'erreur', 'message' => 'Requête invalide']);
exit();

