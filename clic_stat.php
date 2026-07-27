<?php
// =============================================================================
// NOM DU SCRIPT : clic_stat.php
// REVISION : 1.2 - Gestion de l'incrémentation des clics PRO (id_banniere_pro) et Régulière (id_banniere)
// =============================================================================
require_once 'config.php';

// 1. GESTION DES CLICS POUR LES BANNIÈRES PRO
if (isset($_GET['id_banniere_pro'])) {
    $id_pro = (int)$_GET['id_banniere_pro'];
    if ($id_pro > 0) {
        try {
            $stmt = $bdd->prepare("UPDATE jevend_bannieres_actives_pro SET nb_clics = nb_clics + 1 WHERE id_banniere_pro = ?");
            $stmt->execute([$id_pro]);
            echo json_encode(['status' => 'ok', 'type' => 'pro', 'id' => $id_pro]);
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'erreur']);
            exit();
        }
    }
}

// 2. GESTION DES CLICS POUR LES BANNIÈRES RÉGULIÈRES DU FLUX
if (isset($_GET['id_banniere'])) {
    $id_reg = (int)$_GET['id_banniere'];
    if ($id_reg > 0) {
        try {
            $stmt = $bdd->prepare("UPDATE jevend_bannieres_actives SET nb_clics = nb_clics + 1 WHERE id_banniere = ?");
            $stmt->execute([$id_reg]);
            echo json_encode(['status' => 'ok', 'type' => 'reguliere', 'id' => $id_reg]);
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'erreur']);
            exit();
        }
    }
}

http_response_code(400);
echo json_encode(['status' => 'requete_invalide']);
