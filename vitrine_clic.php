<?php
// =============================================================================
// SCRIPT      : vitrine_clic.php
// REVISION    : 1.0 - Suivi et comptage des clics des widgets partenaires
// =============================================================================

require_once 'config.php';

$id_annonce = (int)($_GET['id'] ?? 0);
$token = trim($_GET['token'] ?? '');

if ($id_annonce > 0 && !empty($token)) {
    try {
        // Incrémenter le compteur de clics pour ce partenaire spécifique
        $stmt = $bdd->prepare("UPDATE jevend_annuaire_partenaire SET nb_clics = nb_clics + 1 WHERE widget_token = ?");
        $stmt->execute([$token]);
    } catch (PDOException $e) {
        // En cas d'erreur silencieuse, on ne bloque pas l'utilisateur
    }
}

// Redirection immédiate vers l'annonce originale sur Jevend
if ($id_annonce > 0) {
    header("Location: details.php?id=" . $id_annonce);
    exit;
} else {
    header("Location: index.php");
    exit;
}
