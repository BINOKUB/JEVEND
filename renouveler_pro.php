<?php
// =============================================================================
// NOM DU SCRIPT : renouveler_pro.php
// REVISION     : 1.1 - Prolongation de contrat et génération automatique de reçu comptable
// DESCRIPTION  : Calcule la prolongation à partir de la date de fin actuelle, 
//                met à jour la bannière active ET écrit immédiatement une nouvelle 
//                preuve d'achat permanente dans jevend_preuve_dachat.
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Protection : Accès réservé aux utilisateurs connectés
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

$id_user = $_SESSION['id_utilisateur'];
$id_banniere_pro = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id_banniere_pro <= 0) {
    header('Location: espace_membre_pro.php?erreur=banniere_introuvable');
    exit();
}

try {
    // 1. Récupération de la bannière active appartenant au membre
    $stmt_bann = $bdd->prepare("SELECT * FROM jevend_bannieres_actives_pro WHERE id_banniere_pro = ? AND id_utilisateur = ?");
    $stmt_bann->execute([$id_banniere_pro, $id_user]);
    $banniere = $stmt_bann->fetch(PDO::FETCH_ASSOC);

    if (!$banniere) {
        header('Location: espace_membre_pro.php?erreur=banniere_non_autorisee');
        exit();
    }

    // 2. Extraction du tarif mensuel actuel selon le forfait
    $type_forfait = $banniere['type_banniere'];
    $stmt_tarif = $bdd->prepare("SELECT prix_mensuel FROM jevend_tarifs_pro WHERE type_forfait = ?");
    $stmt_tarif->execute([$type_forfait]);
    $tarif_db = $stmt_tarif->fetch(PDO::FETCH_ASSOC);

    $prix_mensuel = (float)($tarif_db['prix_mensuel'] ?? ($type_forfait === 'supreme' ? 129.00 : 49.00));
    $duree_mois   = (int)($banniere['duree_mois'] ?? 1);
    $prix_total   = $prix_mensuel * $duree_mois;

    // 3. Calcul des nouvelles dates (Prolongation fluide)
    $date_fin_actuelle = new DateTime($banniere['date_fin']);
    $maintenant        = new DateTime();

    // Si la bannière est encore active, on prolonge à partir de l'ancienne date de fin.
    // Si elle était déjà dépassée, on repart de la date actuelle.
    $date_depart = ($date_fin_actuelle > $maintenant) ? $date_fin_actuelle : $maintenant;

    $nouvelle_date_fin = clone $date_depart;
    $nouvelle_date_fin->modify('+' . $duree_mois . ' months');

    $nouvelle_date_butoir = clone $nouvelle_date_fin;
    $nouvelle_date_butoir->modify('-10 days');

    // 4. Mise à jour du contrat dans la table active (Moteur d'affichage)
    $update_active = $bdd->prepare("
        UPDATE jevend_bannieres_actives_pro 
        SET date_fin = ?, 
            date_butoir_renouvellement = ?, 
            prix_paye = ?, 
            statut_affichage = 'active' 
        WHERE id_banniere_pro = ? AND id_utilisateur = ?
    ");

    $update_active->execute([
        $nouvelle_date_fin->format('Y-m-d H:i:s'),
        $nouvelle_date_butoir->format('Y-m-d H:i:s'),
        $prix_total,
        $id_banniere_pro,
        $id_user
    ]);

    // 5. Génération du numéro de reçu spécifique au renouvellement
    $stmt_last_id = $bdd->query("SELECT MAX(id_preuve) FROM jevend_preuve_dachat");
    $next_id = ((int)$stmt_last_id->fetchColumn()) + 1;
    $no_transaction = "#PRO-REN-" . str_pad((string)$next_id, 5, '0', STR_PAD_LEFT);

    $description_recu = "Renouvellement Forfait " . strtoupper($type_forfait);
    if (!empty($banniere['texte_banniere'])) {
        $description_recu .= " - \"" . $banniere['texte_banniere'] . "\"";
    }

    // 6. Écriture de la nouvelle facture/preuve d'achat à vie dans jevend_preuve_dachat
    $insert_preuve = $bdd->prepare("
        INSERT INTO jevend_preuve_dachat 
        (id_utilisateur, type_client, type_banniere, no_transaction, description_achat, prix_paye, duree_mois, date_achat, date_debut, date_fin, statut_paiement) 
        VALUES (?, 'pro', ?, ?, ?, ?, ?, NOW(), ?, ?, 'Payé')
    ");

    $insert_preuve->execute([
        $id_user,
        $type_forfait,
        $no_transaction,
        $description_recu,
        $prix_total,
        $duree_mois,
        $date_depart->format('Y-m-d H:i:s'),
        $nouvelle_date_fin->format('Y-m-d H:i:s')
    ]);

    // Redirection vers l'espace PRO avec message de succès
    header('Location: espace_membre_pro.php?succes=renouvellement_effectue');
    exit();

} catch (PDOException $e) {
    header('Location: espace_membre_pro.php?erreur=erreur_bdd_renouvellement');
    exit();
}
