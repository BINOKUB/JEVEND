<?php
// =============================================================================
// NOM DU SCRIPT : confirmation_paiement_reguliere.php
// REVISION     : 2.1 - Correction ajustée aux schémas jevend_bannieres_actives & jevend_preuve_dachat
// =============================================================================
session_start();
require_once 'config.php';
require_once __DIR__ . '/stripe-php/init.php';

date_default_timezone_set('America/Montreal');

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

$session_id = $_GET['session_id'] ?? '';

if (empty($session_id)) {
    $_SESSION['erreur_achat'] = "Identifiant de session manquant.";
    header('Location: espace_membre.php');
    exit();
}


// Détection de l'environnement via la base de données (Interrupteur global)
$mode_actuel = 'simulation';
$stmt_mode = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'mode_paiement_pro'");
$stmt_mode->execute();
if ($res_mode = $stmt_mode->fetch(PDO::FETCH_ASSOC)) {
    $mode_actuel = trim($res_mode['valeur_parametre']);
}

// Si on est en mode 'stripe' réel, on prend la clé live, sinon la clé de test
$cle_recherchee = ($mode_actuel === 'stripe') ? 'stripe_sk_live' : 'stripe_sk_test';




$stmt_key = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = ?");
$stmt_key->execute([$cle_recherchee]);
$stripe_secret_key = $stmt_key->fetchColumn();

if (empty($stripe_secret_key)) {
    $_SESSION['erreur_achat'] = "Erreur de configuration Stripe lors de la confirmation.";
    header('Location: espace_membre.php');
    exit();
}

\Stripe\Stripe::setApiKey($stripe_secret_key);

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status === 'paid') {
        $metaData       = $session->metadata;
        $id_utilisateur = (int)$metaData->id_utilisateur;
        $id_annonce     = (int)$metaData->id_annonce;
        $duree_jours    = (int)$metaData->duree_jours;
        $texte_banniere = $metaData->texte_banniere;
        $montant_paye   = (float)$metaData->montant_paye;
        $titre_annonce  = $metaData->titre_annonce;

        $date_actuelle = date('Y-m-d H:i:s');
        $date_debut    = new DateTime($date_actuelle);
        $date_fin      = (clone $date_debut)->modify('+' . $duree_jours . ' days');

        $bdd->beginTransaction();

        // 1. Insertion dans jevend_bannieres_actives
        $sql_banniere = "INSERT INTO jevend_bannieres_actives 
                (id_annonce, id_utilisateur, type_banniere, texte_banniere, duree_jours, date_enregistrement, date_debut_activation, statut_affichage, nb_vues, nb_clics) 
                VALUES (?, ?, 'reguliere', ?, ?, ?, ?, 'active', 0, 0)";
        $stmt_bann = $bdd->prepare($sql_banniere);
        $stmt_bann->execute([
            $id_annonce, 
            $id_utilisateur, 
            $texte_banniere, 
            $duree_jours, 
            $date_actuelle, 
            $date_actuelle
        ]);

        $id_banniere_creee = $bdd->lastInsertId();
        $no_transaction    = "#REG-" . str_pad((string)$id_banniere_creee, 5, '0', STR_PAD_LEFT);
        
        $description_recu  = "Bannière Régulière : \"" . $titre_annonce . "\" (" . $duree_jours . " jours) - \"" . $texte_banniere . "\"";

        // 2. Insertion dans jevend_preuve_dachat
        $sql_preuve = "INSERT INTO jevend_preuve_dachat 
                (id_utilisateur, type_client, type_banniere, no_transaction, description_achat, prix_paye, duree_mois, date_achat, date_debut, date_fin, statut_paiement) 
                VALUES (?, 'regulier', 'reguliere', ?, ?, ?, 0, NOW(), ?, ?, 'Payé')";
        $stmt_preuve = $bdd->prepare($sql_preuve);
        $stmt_preuve->execute([
            $id_utilisateur,
            $no_transaction,
            $description_recu,
            $montant_paye,
            $date_debut->format('Y-m-d H:i:s'),
            $date_fin->format('Y-m-d H:i:s')
        ]);

        $bdd->commit();

        $_SESSION['succes_achat'] = "Félicitations ! Votre paiement de " . number_format($montant_paye, 2, ',', ' ') . " $ CAD a été confirmé. Votre bannière régulière est en ligne !";
    } else {
        $_SESSION['erreur_achat'] = "Le paiement n'a pas été validé par la banque.";
    }

} catch (Exception $e) {
    if ($bdd->inTransaction()) { $bdd->rollBack(); }
    $_SESSION['erreur_achat'] = "Erreur lors de la confirmation : " . $e->getMessage();
}

header('Location: espace_membre.php');
exit();
