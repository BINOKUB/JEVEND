<?php
// =============================================================================
// NOM DU SCRIPT : enregistrer_score_memory.php
// REVISION     : 1.0 - Traitement du score Memory et purge automatique
// =============================================================================
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['status' => 'erreur', 'message' => 'Non connecté']);
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$coups = isset($_POST['coups']) ? (int)$_POST['coups'] : 0;
$temps = isset($_POST['temps']) ? (int)$_POST['temps'] : 0;
$kit = isset($_POST['kit']) ? trim($_POST['kit']) : 'utilitaire';

if ($coups <= 0) {
    echo json_encode(['status' => 'erreur', 'message' => 'Score invalide']);
    exit();
}

try {
    // 1. Vérification du score existant du membre
    $stmt = $bdd->prepare("SELECT nombre_coups, temps_secondes FROM jevend_score_memory WHERE id_utilisateur = ?");
    $stmt->execute([$id_utilisateur]);
    $existant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existant) {
        // Mise à jour uniquement si le nouveau score est meilleur (moins de coups, ou même coups mais plus rapide)
        if ($coups < $existant['nombre_coups'] || ($coups == $existant['nombre_coups'] && $temps < $existant['temps_secondes'])) {
            $update = $bdd->prepare("UPDATE jevend_score_memory SET nombre_coups = ?, temps_secondes = ?, nom_kit = ?, date_enregistrement = NOW() WHERE id_utilisateur = ?");
            $update->execute([$coups, $temps, $kit, $id_utilisateur]);
        }
    } else {
        // Premier score pour ce membre
        $insert = $bdd->prepare("INSERT INTO jevend_score_memory (id_utilisateur, nombre_coups, temps_secondes, nom_kit, date_enregistrement) VALUES (?, ?, ?, ?, NOW())");
        $insert->execute([$id_utilisateur, $coups, $temps, $kit]);
    }

    // 2. Récupération du champion de la semaine en cours
    $sql_top = "
        SELECT s.nombre_coups, s.temps_secondes, u.nom 
        FROM jevend_score_memory s
        JOIN jevend_utilisateurs u ON s.id_utilisateur = u.id_utilisateur
        WHERE s.date_enregistrement >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY s.nombre_coups ASC, s.temps_secondes ASC 
        LIMIT 1
    ";
    $top = $bdd->query($sql_top)->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'succes', 'top' => $top]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'erreur', 'message' => $e->getMessage()]);
}
?>
