<?php
// =============================================================================
// NOM DU SCRIPT : traitement_paiement_pro_LOCAL.php
// REVISION     : 2.2 - Mode TEST STRIPE (Paiement factice / Exercice)
// DESCRIPTION  : Effectue le traitement GD et redirige vers Stripe Checkout TEST
//                utilisant la clé stripe_sk_test de la base de données. ATTENTE
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once __DIR__ . '/stripe-php/init.php';

date_default_timezone_set('America/Montreal');

// Protection : Accès réservé aux utilisateurs connectés
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

$id_user = $_SESSION['id_utilisateur'];

// 1. Récupération obligatoire de la clé de TEST depuis la BDD
$stmt_key = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'stripe_sk_test'");
$stmt_key->execute();
$stripe_secret_key = $stmt_key->fetchColumn();

if (empty($stripe_secret_key)) {
    header('Location: espace_membre_pro.php?erreur=stripe_test_key_manquante');
    exit();
}

\Stripe\Stripe::setApiKey($stripe_secret_key);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choix_forfait   = $_POST['choix_forfait'] ?? '';
    $texte_banniere  = trim($_POST['texte_banniere'] ?? '');
    $url_redirection = trim($_POST['url_redirection'] ?? '');

    if (empty($url_redirection) || !in_array($choix_forfait, ['supreme', 'premium'])) {
        header('Location: espace_membre_pro.php?erreur=formulaire_invalide');
        exit();
    }

    if (strpos($url_redirection, 'http') !== 0) {
        $url_redirection = 'https://' . $url_redirection;
    }

    // 2. Traitement et vérification de l'image (Center-Crop GD)
    if (!isset($_FILES['image_banniere']) || $_FILES['image_banniere']['error'] !== UPLOAD_ERR_OK) {
        header('Location: espace_membre_pro.php?erreur=image_manquante');
        exit();
    }

    $file_tmp = $_FILES['image_banniere']['tmp_name'];
    $info_image = @getimagesize($file_tmp);
    if ($info_image === false) {
        header('Location: espace_membre_pro.php?erreur=format_image_invalide');
        exit();
    }

    $largeur_cible = ($choix_forfait === 'supreme') ? 1200 : 600;
    $hauteur_cible = 400;

    $source_gdm = imagecreatefromstring(file_get_contents($file_tmp));
    $image_finale = imagecreatetruecolor($largeur_cible, $hauteur_cible);

    $largeur_orig = $info_image[0];
    $hauteur_orig = $info_image[1];
    $ratio_orig   = $largeur_orig / $hauteur_orig;
    $ratio_cible  = $largeur_cible / $hauteur_cible;

    if ($ratio_orig > $ratio_cible) {
        $crop_h = $hauteur_orig;
        $crop_w = (int)($hauteur_orig * $ratio_cible);
        $crop_x = (int)(($largeur_orig - $crop_w) / 2);
        $crop_y = 0;
    } else {
        $crop_w = $largeur_orig;
        $crop_h = (int)($largeur_orig / $ratio_cible);
        $crop_x = 0;
        $crop_y = (int)(($hauteur_orig - $crop_h) / 2);
    }

    imagecopyresampled($image_finale, $source_gdm, 0, 0, $crop_x, $crop_y, $largeur_cible, $hauteur_cible, $crop_w, $crop_h);

    $dossier_destination = __DIR__ . '/uploads/bannieres/';
    if (!file_exists($dossier_destination)) { 
        mkdir($dossier_destination, 0755, true); 
    }

    $nom_fichier = 'bann_pro_temp_' . $id_user . '_' . time() . '.jpg';
    $chemin_complet = $dossier_destination . $nom_fichier;
    $relatif_db = 'uploads/bannieres/' . $nom_fichier;

    imagejpeg($image_finale, $chemin_complet, 82);
    imagedestroy($source_gdm);
    imagedestroy($image_finale);

    // 3. Calcul du montant de test
    $stmt_tarif = $bdd->prepare("SELECT prix_mensuel FROM jevend_tarifs_pro WHERE type_forfait = ?");
    $stmt_tarif->execute([$choix_forfait]);
    $tarif_db = $stmt_tarif->fetch(PDO::FETCH_ASSOC);
    $prix_mensuel = (float)($tarif_db['prix_mensuel'] ?? ($choix_forfait === 'supreme' ? 129.00 : 49.00));

    $duree_mois = ($choix_forfait === 'supreme') ? (int)($_POST['duree_bloc_supreme'] ?? 1) : (int)($_POST['duree_bloc_premium'] ?? 1);
    $prix_total = $prix_mensuel * $duree_mois;

    $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $hote      = $_SERVER['HTTP_HOST'];
    $dossier   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base_url  = $protocole . $hote . $dossier;

    // 4. Redirection vers Stripe TEST (Checkout avec fausses cartes)
    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'cad',
                    'product_data' => [
                        'name' => '[TEST] Abonnement Marchand PRO (' . strtoupper($choix_forfait) . ')',
                        'description' => 'TEST - Durée : ' . $duree_mois . ' mois - ' . $texte_banniere,
                    ],
                    'unit_amount' => round($prix_total * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $base_url . '/confirmation_paiement_pro.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $base_url . '/espace_membre_pro.php?erreur=paiement_annule',
            'metadata' => [
                'id_utilisateur' => $id_user,
                'type_forfait'   => $choix_forfait,
                'duree_mois'     => $duree_mois,
                'url_redirection'=> $url_redirection,
                'texte_banniere' => $texte_banniere,
                'image_relatif'  => $relatif_db
            ]
        ]);

        header("HTTP/1.1 303 See Other");
        header("Location: " . $session->url);
        exit();
    } catch (Exception $e) {
        header('Location: espace_membre_pro.php?erreur=stripe_erreur');
        exit();
    }
}
