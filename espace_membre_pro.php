<?php
// =============================================================================
// NOM DU SCRIPT : espace_membre_pro.php
// REVISION     : 1.9 - Extraction indépendante de $mes_recus_pro depuis jevend_preuve_dachat
// DESCRIPTION  : Espace marchand officiel. Récupère les bannières actives 
//                ET l'historique permanent des reçus pour l'affichage Pro.
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
$compte = null;
$tarifs_pro = [];
$mes_bannieres_pro = [];
$mes_recus_pro = [];
$erreur_magistrale = "";

try {
    // 1. Extraction et vérification du statut PRO
    $stmt = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$id_user]);
    $compte = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compte || (isset($compte['type_compte']) && $compte['type_compte'] !== 'pro')) {
        header('Location: espace_membre.php');
        exit();
    }

    // 2. Extraction des tarifs PRO indépendants depuis jevend_tarifs_pro
    $stmt_tarifs = $bdd->query("SELECT type_forfait, prix_mensuel FROM jevend_tarifs_pro");
    $tarifs_bruts = $stmt_tarifs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tarifs_bruts as $t) {
        $tarifs_pro[$t['type_forfait']] = (float)$t['prix_mensuel'];
    }

    // 3. Extraction des bannières actives du marchand depuis jevend_bannieres_actives_pro
    try {
        $stmt_bann_pro = $bdd->prepare("SELECT * FROM jevend_bannieres_actives_pro WHERE id_utilisateur = ? ORDER BY id_banniere_pro DESC");
        $stmt_bann_pro->execute([$id_user]);
        $mes_bannieres_pro = $stmt_bann_pro->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e_bann) {
        $mes_bannieres_pro = [];
    }

    // 4. Extraction de l'historique permanent des reçus depuis jevend_preuve_dachat
    try {
        $stmt_recus_pro = $bdd->prepare("SELECT * FROM jevend_preuve_dachat WHERE id_utilisateur = ? AND type_client = 'pro' ORDER BY id_preuve DESC");
        $stmt_recus_pro->execute([$id_user]);
        $mes_recus_pro = $stmt_recus_pro->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e_recus) {
        $mes_recus_pro = [];
    }

} catch (PDOException $e) {
    $erreur_magistrale = "Erreur de base de données : " . $e->getMessage();
} catch (Exception $e) {
    $erreur_magistrale = "Erreur système : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espace Marchand PRO — jevend.com</title>
    <link rel="stylesheet" href="style_affi_membre-pro.css">
</head>
<body class="admin-body">

    <?php // include 'partials/_nav_publique.php'; ?>
 <?php include 'partials/_nav_espace_membre_pro.php'; ?>

    <div class="pro-container">
        
        <?php if (!empty($erreur_magistrale)): ?>
            <div style="background-color: #fef2f2; color: #991b1b; padding: 20px; border-radius: 8px; border: 1px solid #fecaca; font-weight: bold; margin-bottom: 30px;">
                ⚠️ Un problème technique est survenu :<br>
                <code><?= htmlspecialchars($erreur_magistrale) ?></code>
            </div>
        <?php endif; ?>

        <!-- BANDEAU DE BIENVENUE MARCHAND -->
        <div class="pro-header-card">
            <div>
                <span class="pro-badge">🏢 Compte Marchand Officiel</span>
                <h1 style="margin: 10px 0 5px 0; font-size: 1.6rem; color: #ffffff;">
                    <?= htmlspecialchars($compte['nom_entreprise'] ?? $compte['nom'] ?? 'Mon Entreprise') ?>
                </h1>
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">
                    Gestion de votre visibilité commerciale & blocs publicitaires régionaux
                </p>
            </div>
        </div>

        <!-- MODULE DE GESTION DES BANNIÈRES PRO -->
        <?php 
        $fichier_module = __DIR__ . '/partials/_prospace_banniere.php';
        if (file_exists($fichier_module)) {
            include $fichier_module;
        } else {
            echo '<div style="background: #fff; padding: 20px; border: 1px solid #cbd5e1; border-radius: 8px; color: #991b1b; font-weight: bold;">⚠️ Le fichier partials/_prospace_banniere.php est introuvable sur le serveur.</div>';
        }
        ?>

    </div>

</body>
</html>
