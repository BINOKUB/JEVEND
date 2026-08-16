<?php
// =============================================================================
// NOM DU SCRIPT : traitement_paiement_pro_OFFICIEL.php
// REVISION     : 2.1 - Construction dynamique universelle des URL de redirection
// DESCRIPTION  : Traitement d'image GD, création de la session Stripe Checkout
//                et redirection sécurisée (compatible local & production).
// =============================================================================

$stmt_keys = $bdd->query("SELECT cle_parametre, valeur_parametre FROM jevend_parametres WHERE cle_parametre LIKE 'stripe_%'");
$keys = $stmt_keys->fetchAll(PDO::FETCH_KEY_PAIR);

// Extraction du mode de paiement et sélection de la clé secrète
$stmt_mode = $bdd->query("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'mode_paiement_pro'");
$res_mode = $stmt_mode->fetch(PDO::FETCH_ASSOC);
$mode_paiement = $res_mode['valeur_parametre'] ?? 'simulation';

$sk_key = ($mode_paiement === 'stripe' && !empty($keys['stripe_sk_live'])) ? $keys['stripe_sk_live'] : ($keys['stripe_sk_test'] ?? '');

if (empty($sk_key)) {
    header('Location: espace_membre_pro.php?erreur=stripe_config');
    exit();
}

require_once __DIR__ . '/stripe-php/init.php';
\Stripe\Stripe::setApiKey($sk_key);

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

    // Traitement GD et recadrage de l'image (Center-Crop)
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

    $nom_fichier = 'bann_pro_temp_' . $_SESSION['id_utilisateur'] . '_' . time() . '.jpg';
    $chemin_complet = $dossier_destination . $nom_fichier;
    $relatif_db = 'uploads/bannieres/' . $nom_fichier;

    imagejpeg($image_finale, $chemin_complet, 82);
    imagedestroy($source_gdm);
    imagedestroy($image_finale);

    // Calculs de durée et montant
    $stmt_tarif = $bdd->prepare("SELECT prix_mensuel FROM jevend_tarifs_pro WHERE type_forfait = ?");
    $stmt_tarif->execute([$choix_forfait]);
    $tarif_db = $stmt_tarif->fetch(PDO::FETCH_ASSOC);
    $prix_mensuel = (float)($tarif_db['prix_mensuel'] ?? ($choix_forfait === 'supreme' ? 129.00 : 49.00));

    $duree_mois = ($choix_forfait === 'supreme') ? (int)($_POST['duree_bloc_supreme'] ?? 1) : (int)($_POST['duree_bloc_premium'] ?? 1);
    $prix_total = $prix_mensuel * $duree_mois;

    // --- RECONSTRUCTION DYNAMIQUE DE L'URL RACINE ---
    $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $hote      = $_SERVER['HTTP_HOST'];
    $dossier   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base_url  = $protocole . $hote . $dossier;

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'cad',
                    'product_data' => [
                        'name' => 'Abonnement Marchand PRO (' . strtoupper($choix_forfait) . ')',
                        'description' => 'Durée : ' . $duree_mois . ' mois - ' . $texte_banniere,
                    ],
                    'unit_amount' => round($prix_total * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $base_url . '/confirmation_paiement_pro.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $base_url . '/espace_membre_pro.php?erreur=paiement_annule',
            'metadata' => [
                'id_utilisateur' => $_SESSION['id_utilisateur'],
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
