<?php
/*
====================================================
Fichier       : sauvegarder_plan_vente.php
Révision      : v1.0
Description   : Enregistrement et annulation des Plans de Vente (Prix Spécial Flash)
====================================================
*/
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['status' => 'erreur', 'message' => 'Non connecté']);
    exit();
}

require_once 'config.php';

$id_utilisateur = $_SESSION['id_utilisateur'];
$id_annonce     = (int)($_POST['id_annonce'] ?? 0);
$action         = $_POST['action'] ?? 'activer';

if ($id_annonce <= 0) {
    echo json_encode(['status' => 'erreur', 'message' => 'Annonce invalide']);
    exit();
}

try {
    // Vérification de propriété de l'annonce
    $stmtCheck = $bdd->prepare("SELECT id_annonces, prix FROM jevend_annonces WHERE id_annonces = ? AND id_utilisateur = ?");
    $stmtCheck->execute([$id_annonce, $id_utilisateur]);
    $annonce = $stmtCheck->fetch();

    if (!$annonce) {
        echo json_encode(['status' => 'erreur', 'message' => 'Annonce introuvable ou accès non autorisé']);
        exit();
    }

    if ($action === 'annuler') {
        // Annuler la promo
        $stmtCancel = $bdd->prepare("UPDATE jevend_annonces SET prix_promo = NULL, date_fin_promo = NULL WHERE id_annonces = ?");
        $stmtCancel->execute([$id_annonce]);

        echo json_encode(['status' => 'succes', 'message' => 'Le Plan de Vente a été annulé. Le prix régulier est rétabli.']);
        exit();
    }

    // Activer la promo
    $prix_promo   = (float)($_POST['prix_promo'] ?? 0);
    $duree_heures = (int)($_POST['duree_heures'] ?? 48);

    if ($prix_promo <= 0) {
        echo json_encode(['status' => 'erreur', 'message' => 'Veuillez saisir un prix spécial valide.']);
        exit();
    }

    if ($prix_promo >= (float)$annonce['prix']) {
        echo json_encode(['status' => 'erreur', 'message' => 'Le prix spécial doit être inférieur au prix régulier.']);
        exit();
    }

    // Calcul de la date de fin
    $date_fin = date('Y-m-d H:i:s', strtotime("+{$duree_heures} hours"));

    $stmtUpdate = $bdd->prepare("UPDATE jevend_annonces SET prix_promo = ?, date_fin_promo = ? WHERE id_annonces = ?");
    $stmtUpdate->execute([$prix_promo, $date_fin, $id_annonce]);

    echo json_encode([
        'status' => 'succes', 
        'message' => '🚀 Plan de Vente activé avec succès ! L\'offre est maintenant visible pour vos prospects.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'erreur', 'message' => 'Erreur SQL : ' . $e->getMessage()]);
}
