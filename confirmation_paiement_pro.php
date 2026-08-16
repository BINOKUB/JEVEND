<?php
// =============================================================================
// NOM DU SCRIPT : confirmation_paiement_pro.php
// REVISION     : 2.1 - Validation Stripe Checkout et double insertion BDD
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

$session_id = trim($_GET['session_id'] ?? '');

if (empty($session_id)) {
    header('Location: espace_membre_pro.php?erreur=session_invalide');
    exit();
}

// Récupération de la clé secrète Stripe selon le mode actif
$stmt_params = $bdd->query("SELECT cle_parametre, valeur_parametre FROM jevend_parametres");
$params_raw = $stmt_params->fetchAll(PDO::FETCH_KEY_PAIR);

$mode_actuel = $params_raw['mode_paiement_pro'] ?? 'simulation';
$sk_key = ($mode_actuel === 'stripe' && !empty($params_raw['stripe_sk_live'])) ? $params_raw['stripe_sk_live'] : ($params_raw['stripe_sk_test'] ?? '');

if (empty($sk_key)) {
    header('Location: espace_membre_pro.php?erreur=stripe_config');
    exit();
}

require_once __DIR__ . '/stripe-php/init.php';
\Stripe\Stripe::setApiKey($sk_key);

try {
    $checkout_session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($checkout_session->payment_status === 'paid') {
        $meta = $checkout_session->metadata;
        
        $id_user         = (int)$meta->id_utilisateur;
        $choix_forfait   = $meta->type_forfait;
        $duree_mois      = (int)$meta->duree_mois;
        $url_redirection = $meta->url_redirection;
        $texte_banniere  = $meta->texte_banniere;
        $image_relatif   = !empty($meta->image_relatif) ? $meta->image_relatif : 'uploads/bannieres/en_attente.jpg';
        $prix_total      = ($checkout_session->amount_total) / 100;

        $date_debut  = new DateTime();
        $date_fin    = (clone $date_debut)->modify('+' . $duree_mois . ' months');
        $date_butoir = (clone $date_fin)->modify('-10 days');

        $bdd->beginTransaction();

        // 1. Insertion dans jevend_bannieres_actives_pro
        $stmt_act = $bdd->prepare("
            INSERT INTO jevend_bannieres_actives_pro 
            (id_utilisateur, type_banniere, duree_mois, prix_paye, image_url, texte_banniere, url_redirection, date_debut, date_fin, date_butoir_renouvellement, statut_affichage) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");

        $stmt_act->execute([
            $id_user,
            $choix_forfait,
            $duree_mois,
            $prix_total,
            $image_relatif,
            $texte_banniere,
            $url_redirection,
            $date_debut->format('Y-m-d H:i:s'),
            $date_fin->format('Y-m-d H:i:s'),
            $date_butoir->format('Y-m-d H:i:s')
        ]);

        $id_banniere_creee = $bdd->lastInsertId();
        $no_transaction = "#PRO-" . str_pad((string)$id_banniere_creee, 5, '0', STR_PAD_LEFT);

        // 2. Insertion dans jevend_preuve_dachat (Grand Livre Comptable)
        $description_comptable = "Abonnement PRO " . strtoupper($choix_forfait);
        if (!empty($texte_banniere)) {
            $description_comptable .= " - \"" . $texte_banniere . "\"";
        }

        $stmt_preuve = $bdd->prepare("
            INSERT INTO jevend_preuve_dachat 
            (id_utilisateur, type_client, type_banniere, no_transaction, description_achat, prix_paye, duree_mois, date_achat, date_debut, date_fin, statut_paiement) 
            VALUES (?, 'pro', ?, ?, ?, ?, ?, NOW(), ?, ?, 'Payé')
        ");

        $stmt_preuve->execute([
            $id_user,
            $choix_forfait,
            $no_transaction,
            $description_comptable,
            $prix_total,
            $duree_mois,
            $date_debut->format('Y-m-d H:i:s'),
            $date_fin->format('Y-m-d H:i:s')
        ]);

        $bdd->commit();

        header('Location: espace_membre_pro.php?succes=paiement_stripe_valide');
        exit();
    } else {
        header('Location: espace_membre_pro.php?erreur=paiement_incomplet');
        exit();
    }

} catch (Exception $e) {
    if ($bdd->inTransaction()) { 
        $bdd->rollBack(); 
    }
    header('Location: espace_membre_pro.php?erreur=erreur_stripe');
    exit();
}
