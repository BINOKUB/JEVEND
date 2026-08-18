<?php
// =============================================================================
// NOM DU SCRIPT : traitement_simulation_stripe.php
// REVISION     : 2.0 - Routeur DYNAMIQUE Bannières Régulières (via Base de Données)
// =============================================================================
session_start();
require_once 'config.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

// 1. Lecture de l'interrupteur dans la base de données
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

// 2. Aiguillage intelligent
if ($mode_actuel === 'stripe' && file_exists(__DIR__ . '/traitement_simulation_stripe_OFFICIEL.php')) {
    // Mode PRODUCTION (Stripe Live)
    require_once __DIR__ . '/traitement_simulation_stripe_OFFICIEL.php';
} else {
    // Mode LOCAL/TEST (Stripe Test)
    require_once __DIR__ . '/traitement_simulation_stripe_LOCAL.php';
}
