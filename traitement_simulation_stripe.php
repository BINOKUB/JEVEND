<?php
// =============================================================================
// SCRIPT : traitement_simulation_stripe.php
// REVISION : 2.1 - Migration comptable vers jevend_preuve_dachat (#REG-XXXXX)
//                  et ajustement de la durée minimale régulière (5 jours).
// NOM DU SCRIPT : traitement_simulation_stripe.php
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilisateur = $_SESSION['id_utilisateur'];
    $id_annonce     = (int)($_POST['id_annonce'] ?? 0);
    $type_produit   = trim($_POST['type_banniere'] ?? '');
    $duree_jours    = (int)($_POST['duree_jours'] ?? 0);
    $texte_banniere = trim($_POST['texte_banniere'] ?? '');

    if ($id_annonce <= 0 || empty($type_produit) || $duree_jours <= 0 || empty($texte_banniere)) {
        $_SESSION['erreur_achat'] = "Formulaire incomplet.";
        header('Location: espace_membre.php');
        exit();
    }

    if (mb_strlen($texte_banniere) > 120) {
        $texte_banniere = mb_substr($texte_banniere, 0, 120);
    }

    // Récupération du titre de l'annonce pour enrichir la facture
    $titre_annonce = "Annonce #" . $id_annonce;
    try {
        $stmt_titre = $bdd->prepare("SELECT titre_objet_nettoye FROM jevend_annonces WHERE id_annonces = ?");
        $stmt_titre->execute([$id_annonce]);
        $res_titre = $stmt_titre->fetch(PDO::FETCH_ASSOC);
        if ($res_titre && !empty($res_titre['titre_objet_nettoye'])) {
            $titre_annonce = $res_titre['titre_objet_nettoye'];
        }
    } catch (PDOException $e) { }

    // Double vérification de sécurité des prix côté serveur
    $montant_paye = 0.00;
    $erreur_logique = false;

    switch ($type_produit) {
        case 'reguliere':
            if ($duree_jours < 5) { $erreur_logique = true; }
            else { $montant_paye = $duree_jours * 1.00; }
            break;
        case 'bronze':
            if ($duree_jours % 10 !== 0) { $erreur_logique = true; }
            else { $montant_paye = ($duree_jours / 10) * 10.00; }
            break;
        case 'premium':
            if ($duree_jours < 5) { $erreur_logique = true; }
            else { $montant_paye = $duree_jours * 5.00; }
            break;
        case 'supreme':
            if ($duree_jours < 5) { $erreur_logique = true; }
            else { $montant_paye = $duree_jours * 10.00; }
            break;
        default:
            $erreur_logique = true;
            break;
    }

    if ($erreur_logique) {
        $_SESSION['erreur_achat'] = "Option ou durée invalide.";
        header('Location: espace_membre.php');
        exit();
    }

    // --- DOUBLE SÉCURITÉ : VÉRIFICATION DE LA DISPONIBILITÉ RÉELLE EN BD ---
    try {
        if ($type_produit === 'supreme') {
            $chk = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE type_banniere = 'supreme' AND statut_affichage = 'active'");
            if ($chk->fetchColumn() >= 1) { 
                $_SESSION['erreur_achat'] = "Désolé, l'espace Suprême vient d'être réservé par un autre membre."; 
                header('Location: espace_membre.php'); 
                exit(); 
            }
        } elseif ($type_produit === 'premium') {
            $chk = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE type_banniere = 'premium' AND statut_affichage = 'active'");
            if ($chk->fetchColumn() >= 4) { 
                $_SESSION['erreur_achat'] = "Désolé, les places Premium sont complètes."; 
                header('Location: espace_membre.php'); 
                exit(); 
            }
        } else {
            $stmt_annonces = $bdd->query("SELECT COUNT(*) FROM jevend_annonces WHERE statut = 'actif'");
            $quota_max = ceil((int)$stmt_annonces->fetchColumn() * 0.50);
            $chk = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE statut_affichage = 'active' AND type_banniere IN ('bronze', 'reguliere')");
            if ($chk->fetchColumn() >= $quota_max) { 
                $_SESSION['erreur_achat'] = "Le quota maximum de bannières sur le site est atteint."; 
                header('Location: espace_membre.php'); 
                exit(); 
            }
        }

        $date_actuelle = date('Y-m-d H:i:s');
        $date_debut    = new DateTime($date_actuelle);
        $date_fin      = (clone $date_debut)->modify('+' . $duree_jours . ' days');

        $bdd->beginTransaction();

        // A. Activation immédiate dans le circuit public
        $sql_banniere = "INSERT INTO jevend_bannieres_actives 
                (id_annonce, id_utilisateur, type_banniere, texte_banniere, duree_jours, date_enregistrement, date_debut_activation, statut_affichage) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt_bann = $bdd->prepare($sql_banniere);
        $stmt_bann->execute([$id_annonce, $id_utilisateur, $type_produit, $texte_banniere, $duree_jours, $date_actuelle, $date_actuelle]);

        $id_banniere_creee = $bdd->lastInsertId();
        $no_transaction    = "#REG-" . str_pad((string)$id_banniere_creee, 5, '0', STR_PAD_LEFT);
        
        $description_recu  = "Promotion : \"" . $titre_annonce . "\" (" . $duree_jours . " jours) - \"" . $texte_banniere . "\"";

        // B. Inscription dans le Grand Livre Comptable Universel (jevend_preuve_dachat)
        $sql_preuve = "INSERT INTO jevend_preuve_dachat 
                (id_utilisateur, type_client, type_banniere, no_transaction, description_achat, prix_paye, duree_mois, date_achat, date_debut, date_fin, statut_paiement) 
                VALUES (?, 'regulier', ?, ?, ?, ?, 0, NOW(), ?, ?, 'Payé')";
        $stmt_preuve = $bdd->prepare($sql_preuve);
        $stmt_preuve->execute([
            $id_utilisateur,
            $type_produit,
            $no_transaction,
            $description_recu,
            $montant_paye,
            $date_debut->format('Y-m-d H:i:s'),
            $date_fin->format('Y-m-d H:i:s')
        ]);

        $bdd->commit();
        $_SESSION['succes_achat'] = "Félicitations ! Votre paiement de " . number_format($montant_paye, 2, ',', ' ') . " $ CAD a été traité avec succès. Votre annonce est en vedette dès maintenant !";

    } catch (PDOException $e) {
        if ($bdd->inTransaction()) { $bdd->rollBack(); }
        $_SESSION['erreur_achat'] = "Erreur technique : " . $e->getMessage();
    }

    header('Location: espace_membre.php');
    exit();
}
