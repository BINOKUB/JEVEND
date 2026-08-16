<?php
// =============================================================================
// NOM DU SCRIPT : traitement_simulation_stripe.php
// REVISION     : 3.0 - Routeur dynamique Membres Réguliers (Local vs Stripe Officiel)
// =============================================================================
session_start();
require_once 'config.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

// Lecture du mode de paiement actif depuis jevend_parametres
$mode_actuel = 'simulation';
try {
    $stmt_mode = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'mode_paiement_pro'");
    $stmt_mode->execute();
    $res = $stmt_mode->fetch(PDO::FETCH_ASSOC);
    if ($res && !empty($res['valeur_parametre'])) {
        $mode_actuel = $res['valeur_parametre'];
    }
} catch (PDOException $e) {
    $mode_actuel = 'simulation';
}

// AIGUILLAGE AUTOMATIQUE DU TRAITEMENT
if ($mode_actuel === 'stripe' && file_exists('traitement_stripe_officiel_regulier.php')) {
    require_once 'traitement_stripe_officiel_regulier.php';
} else {
    require_once 'traitement_simulation_stripe_LOCAL.php';
}
?>
