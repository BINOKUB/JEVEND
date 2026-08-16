<?php
// =============================================================================
// NOM DU SCRIPT : traitement_simulation_stripe_LOCAL.php
// REVISION     : 3.0 - Traitement simulation local (Bannière Régulière uniquement)
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

    if ($id_annonce <= 0 || $type_produit !== 'reguliere' || $duree_jours < 10 || empty($texte_banniere)) {
        $_SESSION['erreur_achat'] = "Formulaire incomplet ou option invalide.";
        header('Location: espace_membre.php');
        exit();
    }

    if (mb_strlen($texte_banniere) > 120) {
        $texte_banniere = mb_substr($texte_banniere, 0, 120);
    }

    // Récupération du prix journalier et durée min configurés en BDD
    $stmt_cfg = $bdd->query("SELECT prix_par_jour, duree_min_jours FROM jevend_tarifs_publicites WHERE type_produit = 'reguliere'");
    $cfg = $stmt_cfg->fetch(PDO::FETCH_ASSOC) ?: ['prix_par_jour' => 1.00, 'duree_min_jours' => 10];
    
    $prix_par_jour = (float)$cfg['prix_par_jour'];
    $min_jours     = (int)$cfg['duree_min_jours'];

    if ($duree_jours < $min_jours) {
        $_SESSION['erreur_achat'] = "La durée minimale est de " . $min_jours . " jours.";
        header('Location: espace_membre.php');
        exit();
    }

    $montant_paye = $duree_jours * $prix_par_jour;

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

    // Control de quota global de bannières sur le site
    try {
        $stmt_annonces = $bdd->query("SELECT COUNT(*) FROM jevend_annonces WHERE statut = 'actif'");
        $quota_max = ceil((int)$stmt_annonces->fetchColumn() * 0.50);
        
        $chk = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE statut_affichage = 'active' AND type_banniere = 'reguliere'");
        if ((int)$chk->fetchColumn() >= $quota_max) { 
            $_SESSION['erreur_achat'] = "Le quota maximum de bannières régulières sur le site est atteint."; 
            header('Location: espace_membre.php'); 
            exit(); 
        }

        $date_actuelle = date('Y-m-d H:i:s');
        $date_debut    = new DateTime($date_actuelle);
        $date_fin      = (clone $date_debut)->modify('+' . $duree_jours . ' days');

        $bdd->beginTransaction();

        // A. Activation immédiate dans le circuit public
        $sql_banniere = "INSERT INTO jevend_bannieres_actives 
                (id_annonce, id_utilisateur, type_banniere, texte_banniere, duree_jours, date_enregistrement, date_debut_activation, statut_affichage) 
                VALUES (?, ?, 'reguliere', ?, ?, ?, ?, 'active')";
        $stmt_bann = $bdd->prepare($sql_banniere);
        $stmt_bann->execute([$id_annonce, $id_utilisateur, $texte_banniere, $duree_jours, $date_actuelle, $date_actuelle]);

        $id_banniere_creee = $bdd->lastInsertId();
        $no_transaction    = "#REG-" . str_pad((string)$id_banniere_creee, 5, '0', STR_PAD_LEFT);
        
        $description_recu  = "Bannière Régulière : \"" . $titre_annonce . "\" (" . $duree_jours . " jours) - \"" . $texte_banniere . "\"";

        // B. Inscription dans le Grand Livre Comptable (jevend_preuve_dachat)
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
        $_SESSION['succes_achat'] = "Félicitations ! Votre paiement simulé de " . number_format($montant_paye, 2, ',', ' ') . " $ CAD a été traité. Votre annonce est en vedette !";

    } catch (PDOException $e) {
        if ($bdd->inTransaction()) { $bdd->rollBack(); }
        $_SESSION['erreur_achat'] = "Erreur technique : " . $e->getMessage();
    }

    header('Location: espace_membre.php');
    exit();
}
