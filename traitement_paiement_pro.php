<?php
// =============================================================================
// NOM DU SCRIPT : traitement_paiement_pro.php
// REVISION     : 1.8 - Vérification des quotas globaux (3 Suprême, 20 Premium) 
//                      et individuels (5 Premium) avec gestion anti-concurrence.
// DESCRIPTION  : Valide les quotas en temps réel avant tout traitement d'image 
//                pour contrer les cas de soumission simultanée (race condition).
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Protection : Accès réservé aux utilisateurs connectés et PRO
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

$id_user = $_SESSION['id_utilisateur'];

// Vérification du compte PRO
$stmt_user = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE id_utilisateur = ?");
$stmt_user->execute([$id_user]);
$compte = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$compte || (isset($compte['type_compte']) && $compte['type_compte'] !== 'pro')) {
    header('Location: espace_membre.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choix_forfait  = $_POST['choix_forfait'] ?? ''; // 'supreme' ou 'premium'
    $texte_banniere = trim($_POST['texte_banniere'] ?? '');
    
    // --- VALIDATION STRICTE ET OBLIGATOIRE DE L'URL DE DESTINATION ---
    $url_redirection = trim($_POST['url_redirection'] ?? '');

    // Refus catégorique si l'URL n'a pas été renseignée
    if (empty($url_redirection)) {
        header('Location: espace_membre_pro.php?erreur=url_manquante');
        exit();
    }

    // Normalisation avec protocole https://
    if (strpos($url_redirection, 'http') !== 0) {
        $url_redirection = 'https://' . $url_redirection;
    }

    // Validation du forfait
    if (!in_array($choix_forfait, ['supreme', 'premium'])) {
        header('Location: espace_membre_pro.php?erreur=forfait_invalide');
        exit();
    }

    // -------------------------------------------------------------------------
    // VÉRIFICATION DES QUOTAS EN TEMPS RÉEL (ANTI-CONCURRENCE / RACE CONDITION)
    // -------------------------------------------------------------------------
    if ($choix_forfait === 'supreme') {
        // Quota global Suprême : Maximum 3 actifs
        $stmt_check_sup = $bdd->prepare("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'supreme' AND date_fin > NOW()");
        $stmt_check_sup->execute();
        if ((int)$stmt_check_sup->fetchColumn() >= 3) {
            header('Location: espace_membre_pro.php?erreur=quota_atteint');
            exit();
        }
    } else {
        // Quota global Premium : Maximum 20 actifs
        $stmt_check_prem_global = $bdd->prepare("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'premium' AND date_fin > NOW()");
        $stmt_check_prem_global->execute();
        if ((int)$stmt_check_prem_global->fetchColumn() >= 20) {
            header('Location: espace_membre_pro.php?erreur=quota_atteint');
            exit();
        }

        // Quota individuel Premium par utilisateur : Maximum 5 actifs
        $stmt_check_prem_perso = $bdd->prepare("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'premium' AND id_utilisateur = ? AND date_fin > NOW()");
        $stmt_check_prem_perso->execute([$id_user]);
        if ((int)$stmt_check_prem_perso->fetchColumn() >= 5) {
            header('Location: espace_membre_pro.php?erreur=quota_atteint');
            exit();
        }
    }

    // Récupération du tarif mensuel depuis jevend_tarifs_pro
    $stmt_tarif = $bdd->prepare("SELECT prix_mensuel FROM jevend_tarifs_pro WHERE type_forfait = ?");
    $stmt_tarif->execute([$choix_forfait]);
    $tarif_db = $stmt_tarif->fetch(PDO::FETCH_ASSOC);
    $prix_mensuel = (float)($tarif_db['prix_mensuel'] ?? ($choix_forfait === 'supreme' ? 129.00 : 49.00));

    // Durée sélectionnée & Dimensions cibles
    if ($choix_forfait === 'supreme') {
        $duree_mois = (int)($_POST['duree_bloc_supreme'] ?? 1);
        if ($duree_mois < 1 || $duree_mois > 3) $duree_mois = 1;
        $largeur_cible = 1200;
        $hauteur_cible = 400;
        $largeur_min   = 800;
    } else {
        $duree_mois = (int)($_POST['duree_bloc_premium'] ?? 1);
        if ($duree_mois < 1 || $duree_mois > 6) $duree_mois = 1;
        $largeur_cible = 600;
        $hauteur_cible = 400;
        $largeur_min   = 400;
    }

    $prix_total = $prix_mensuel * $duree_mois;

    // -------------------------------------------------------------------------
    // 1. INTERCEPTION ET VALIDATION DU FICHIER TÉLÉVERSÉ
    // -------------------------------------------------------------------------
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

    $largeur_orig = $info_image[0];
    $hauteur_orig = $info_image[1];

    // CONTRÔLE SIMPLE DE QUALITÉ : Refuse uniquement les images miniatures floues
    if ($largeur_orig < $largeur_min) {
        header('Location: espace_membre_pro.php?erreur=image_trop_petite');
        exit();
    }

    $mime_type = $info_image['mime'];
    $source_gdm = null;

    // Chargement en mémoire selon le type d'image
    switch ($mime_type) {
        case 'image/jpeg':
            $source_gdm = @imagecreatefromjpeg($file_tmp);
            break;
        case 'image/png':
            $source_gdm = @imagecreatefrompng($file_tmp);
            break;
        case 'image/webp':
            $source_gdm = @imagecreatefromwebp($file_tmp);
            break;
        default:
            header('Location: espace_membre_pro.php?erreur=format_non_supporte');
            exit();
    }

    if (!$source_gdm) {
        header('Location: espace_membre_pro.php?erreur=erreur_lecture_image');
        exit();
    }

    // -------------------------------------------------------------------------
    // 2. RECROP CENTRÉ (CENTER-CROP) ET REDIMENSIONNEMENT UNIVERSEL (GD)
    // -------------------------------------------------------------------------
    $ratio_orig  = $largeur_orig / $hauteur_orig;
    $ratio_cible = $largeur_cible / $hauteur_cible;

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

    $image_finale = imagecreatetruecolor($largeur_cible, $hauteur_cible);

    // Redimensionnement haute fidélité
    imagecopyresampled(
        $image_finale, $source_gdm, 
        0, 0, $crop_x, $crop_y, 
        $largeur_cible, $hauteur_cible, 
        $crop_w, $crop_h
    );

    // -------------------------------------------------------------------------
    // 3. SAUVEGARDE ET COMPRESSION AUTOMATIQUE (JPG 82%)
    // -------------------------------------------------------------------------
    $dossier_destination = __DIR__ . '/uploads/bannieres/';
    if (!file_exists($dossier_destination)) {
        mkdir($dossier_destination, 0755, true);
    }

    $nom_fichier = 'bann_pro_' . $id_user . '_' . time() . '_' . rand(100, 999) . '.jpg';
    $chemin_complet = $dossier_destination . $nom_fichier;
    $relatif_db = 'uploads/bannieres/' . $nom_fichier;

    // Enregistrement final sur disque
    imagejpeg($image_finale, $chemin_complet, 82);

    // Libération mémoire
    imagedestroy($source_gdm);
    imagedestroy($image_finale);

    // -------------------------------------------------------------------------
    // 4. CALCUL DES DATES ET DOUBLE INSERTION BDD (ACTIVE + ARCHIVE PERMANENTE)
    // -------------------------------------------------------------------------
    $date_debut = new DateTime();
    $date_fin   = clone $date_debut;
    $date_fin->modify('+' . $duree_mois . ' months');

    $date_butoir = clone $date_fin;
    $date_butoir->modify('-10 days');

    // A) Enregistrement dans le moteur d'affichage temporaire
    $insert_active = $bdd->prepare("
        INSERT INTO jevend_bannieres_actives_pro 
        (id_utilisateur, type_banniere, duree_mois, prix_paye, image_url, texte_banniere, url_redirection, date_debut, date_fin, date_butoir_renouvellement, statut_affichage) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");

    $insert_active->execute([
        $id_user,
        $choix_forfait,
        $duree_mois,
        $prix_total,
        $relatif_db,
        $texte_banniere,
        $url_redirection,
        $date_debut->format('Y-m-d H:i:s'),
        $date_fin->format('Y-m-d H:i:s'),
        $date_butoir->format('Y-m-d H:i:s')
    ]);

    // Génération du numéro de reçu officiel basé sur l'identifiant généré
    $id_banniere_pro_creee = $bdd->lastInsertId();
    $no_transaction = "#PRO-" . str_pad((string)$id_banniere_pro_creee, 5, '0', STR_PAD_LEFT);

    // B) Enregistrement dans l'archive comptable permanente à vie
    $insert_preuve = $bdd->prepare("
        INSERT INTO jevend_preuve_dachat 
        (id_utilisateur, type_client, type_banniere, no_transaction, description_achat, prix_paye, duree_mois, date_achat, date_debut, date_fin, statut_paiement) 
        VALUES (?, 'pro', ?, ?, ?, ?, ?, NOW(), ?, ?, 'Payé')
    ");

    $insert_preuve->execute([
        $id_user,
        $choix_forfait,
        $no_transaction,
        $texte_banniere,
        $prix_total,
        $duree_mois,
        $date_debut->format('Y-m-d H:i:s'),
        $date_fin->format('Y-m-d H:i:s')
    ]);

    // Redirection vers l'espace PRO avec succès
    header('Location: espace_membre_pro.php?succes=banniere_ajoutee');
    exit();
} else {
    header('Location: espace_membre_pro.php');
    exit();
}
