<?php
// =============================================================================
// NOM DU SCRIPT : traitement_paiement_pro.php
// REVISION     : 2.0 - ROUTEUR DYNAMIQUE MARCHANDS PRO (Simulation vs Stripe)
// DESCRIPTION  : Lit le mode de paiement dans jevend_parametres et aiguille
//                soit vers le traitement local, soit vers Stripe Officiel.
// =============================================================================
session_start();
require_once 'config.php';

// Protection : Accès réservé aux utilisateurs connectés
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

// 1. Extraction du mode de paiement configuré dans l'administration
$mode_actuel = 'simulation';
try {
    $stmt_mode = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'mode_paiement_pro'");
    $stmt_mode->execute();
    $res = $stmt_mode->fetch(PDO::FETCH_ASSOC);
    if ($res && !empty($res['valeur_parametre'])) {
        $mode_actuel = trim($res['valeur_parametre']);
    }
} catch (PDOException $e) {
    $mode_actuel = 'simulation';
}

// 2. Aiguillage transparent vers le bon moteur
if ($mode_actuel === 'stripe' && file_exists(__DIR__ . '/traitement_paiement_pro_OFFICIEL.php')) {
    // Mode Stripe Officiel (Redirection Checkout)
    require_once __DIR__ . '/traitement_paiement_pro_OFFICIEL.php';
} else {
    // Mode Simulation Local (Validation BDD directe)
    require_once __DIR__ . '/traitement_paiement_pro_LOCAL.php';
}
?>
