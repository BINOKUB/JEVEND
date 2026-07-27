<?php
/*
====================================================
Fichier       : compter_resultats_search.php
Révision      : v1.0
Description   : Endpoint AJAX pour comptage dynamique du moteur de recherche
====================================================
*/
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$q     = trim($_GET['q'] ?? '');
$cat   = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$ville = isset($_GET['ville']) ? (int)$_GET['ville'] : 0;

try {
    $sql = "SELECT COUNT(*) FROM jevend_annonces a 
            JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur 
            WHERE a.statut = 'actif'";
    $params = [];

    if (!empty($q)) {
        $sql .= " AND a.titre_objet_nettoye LIKE :q";
        $params[':q'] = '%' . $q . '%';
    }
    if ($cat > 0) {
        $sql .= " AND a.id_categorie = :cat";
        $params[':cat'] = $cat;
    }
    if ($ville > 0) {
        $sql .= " AND u.id_ville = :ville";
        $params[':ville'] = $ville;
    }

    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    echo json_encode(['status' => 'succes', 'total' => $total]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'erreur', 'total' => 0, 'message' => $e->getMessage()]);
}
