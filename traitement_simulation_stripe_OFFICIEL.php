<?php
// =============================================================================
// NOM DU SCRIPT : traitement_simulation_stripe_OFFICIEL.php
// REVISION     : 2.0 - Intégration Stripe Checkout LIVE avec clés BDD
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once __DIR__ . '/stripe-php/init.php';

date_default_timezone_set('America/Montreal');

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

// Récupération de la clé secrète de production depuis jevend_parametres
$stmt_key = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'stripe_sk_live'");
$stmt_key->execute();
$stripe_secret_key = $stmt_key->fetchColumn();

if (empty($stripe_secret_key)) {
    $_SESSION['erreur_achat'] = "Configuration Stripe de production manquante.";
    header('Location: espace_membre.php');
    exit();
}

\Stripe\Stripe::setApiKey($stripe_secret_key);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilisateur = $_SESSION['id_utilisateur'];
    $id_annonce     = (int)($_POST['id_annonce'] ?? 0);
    $type_produit   = trim($_POST['type_banniere'] ?? '');
    $duree_jours    = (int)($_POST['duree_jours'] ?? 0);
    $texte_banniere = trim($_POST['texte_banniere'] ?? '');

    if ($id_annonce <= 0 || $type_produit !== 'reguliere' || $duree_jours < 10 || empty($texte_banniere)) {
        $_SESSION['erreur_achat'] = "Formulaire incomplet ou option invalide.";
        header('Location: espace_membre.php');
        exit();
    }

    if (mb_strlen($texte_banniere) > 120) {
        $texte_banniere = mb_substr($texte_banniere, 0, 120);
    }

    $stmt_cfg = $bdd->query("SELECT prix_par_jour, duree_min_jours FROM jevend_tarifs_publicites WHERE type_produit = 'reguliere'");
    $cfg = $stmt_cfg->fetch(PDO::FETCH_ASSOC) ?: ['prix_par_jour' => 1.00, 'duree_min_jours' => 10];
    
    $prix_par_jour = (float)$cfg['prix_par_jour'];
    $min_jours     = (int)$cfg['duree_min_jours'];

    if ($duree_jours < $min_jours) {
        $_SESSION['erreur_achat'] = "La durée minimale est de " . $min_jours . " jours.";
        header('Location: espace_membre.php');
        exit();
    }

    try {
        $stmt_annonces = $bdd->query("SELECT COUNT(*) FROM jevend_annonces WHERE statut = 'actif'");
        $quota_max = ceil((int)$stmt_annonces->fetchColumn() * 0.50);
        
        $chk = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE statut_affichage = 'active' AND type_banniere = 'reguliere'");
        if ((int)$chk->fetchColumn() >= $quota_max) { 
            $_SESSION['erreur_achat'] = "Le quota maximum de bannières régulières est atteint."; 
            header('Location: espace_membre.php'); 
            exit(); 
        }
    } catch (PDOException $e) {
        $_SESSION['erreur_achat'] = "Erreur technique : " . $e->getMessage();
        header('Location: espace_membre.php');
        exit();
    }

    $montant_paye = $duree_jours * $prix_par_jour;

    $titre_annonce = "Annonce #" . $id_annonce;
    try {
        $stmt_titre = $bdd->prepare("SELECT titre_objet_nettoye FROM jevend_annonces WHERE id_annonces = ?");
        $stmt_titre->execute([$id_annonce]);
        $res_titre = $stmt_titre->fetch(PDO::FETCH_ASSOC);
        if ($res_titre && !empty($res_titre['titre_objet_nettoye'])) {
            $titre_annonce = $res_titre['titre_objet_nettoye'];
        }
    } catch (PDOException $e) { }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host     = $_SERVER['HTTP_HOST'];
    $baseUrl  = $protocol . $host;

    try {
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'cad',
                    'product_data' => [
                        'name' => 'Bannière Régulière - ' . $titre_annonce,
                        'description' => 'Durée : ' . $duree_jours . ' jours | Texte : ' . $texte_banniere,
                    ],
                    'unit_amount' => (int)round($montant_paye * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $baseUrl . '/confirmation_paiement_reguliere.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $baseUrl . '/espace_membre.php?statut=annule',
            'metadata' => [
                'id_utilisateur' => $id_utilisateur,
                'id_annonce'     => $id_annonce,
                'duree_jours'    => $duree_jours,
                'texte_banniere' => $texte_banniere,
                'montant_paye'   => $montant_paye,
                'titre_annonce'  => $titre_annonce,
                'type_banniere'  => 'reguliere'
            ]
        ]);

        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);
        exit();

    } catch (Exception $e) {
        $_SESSION['erreur_achat'] = "Erreur de paiement Stripe : " . $e->getMessage();
        header('Location: espace_membre.php');
        exit();
    }
}
