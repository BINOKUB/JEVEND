<?php
// =============================================================================
// MODULE : _quota_jecherche.php
// DESCRIPTION : Gardien de quota pour le module "Je Cherche"
// =============================================================================

// S'assure que la connexion $bdd et la session sont actives
if (!isset($bdd)) {
    require_once 'config.php';
}

$quota_atteint = false;

try {
    // 1. Récupération de la limite configurée (correction des noms de colonnes de la table)
    $stmt_param = $bdd->prepare("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'limite_recherches'");
    $stmt_param->execute();
    $limite_config = $stmt_param->fetchColumn();
    
    // Valeur par défaut de sécurité si non défini
    $limite_max = ($limite_config !== false && $limite_config !== null) ? intval($limite_config) : 1000;

    // 2. Comptage du nombre actuel de recherches dans la table
    $stmt_count = $bdd->query("SELECT COUNT(*) FROM jevend_recherches");
    $total_recherches = intval($stmt_count->fetchColumn());

    // 3. Comparaison
    if ($total_recherches >= $limite_max) {
        $quota_atteint = true;
    }
} catch (PDOException $e) {
    // En cas d'erreur, on laisse passer par sécurité pour ne pas bloquer le site
    $quota_atteint = false;
}

// Si le quota est atteint, on bloque l'affichage du formulaire et on affiche la page de suspension élégante
if ($quota_atteint):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes suspendues — jevend.com</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

    <?php 
    // Inclusion sécurisée de la navigation du haut
    if (file_exists('partials/_nav_publique.php')) {
        include 'partials/_nav_publique.php';
    } elseif (file_exists('_nav_publique.php')) {
        include '_nav_publique.php';
    }
    ?>

    <div class="admin-conteneur" style="max-width: 700px; margin: 50px auto; background: #ffffff; padding: 40px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; box-sizing: border-box;">
        
        <!-- Image ou icône visuelle -->
        <div style="font-size: 3.5rem; margin-bottom: 20px;">
            <img src="assets/QUOTA_JECHERCHE.png" alt="Quota atteint" style="max-width: 100px; height: auto;" onerror="this.style.display='none';">
            <span style="display: block;">🎯⏳</span>
        </div>

        <h2 style="color: #0f172a; margin-top: 0; font-size: 1.8rem;">Volume de demandes élevé</h2>
        
        <p style="color: #64748b; font-size: 1rem; line-height: 1.6; margin: 20px 0;">
            Le module <b>« Je Cherche »</b> connaît actuellement une affluence record et a atteint sa capacité maximale de gestion en simultané. 
            <br>Les nouvelles publications sont momentanément suspendues pour assurer une fluidité optimale des échanges.
        </p>

        <div style="background-color: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.9rem; color: #334155; margin-bottom: 30px;">
            💡 <b>Bon à savoir :</b> Les recherches déjà publiées et les transactions en cours restent entièrement actives et accessibles. Seul le dépôt de nouvelles requêtes est en pause temporaire.
        </div>

        <a href="zone_cherche.php" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: background 0.2s;">
            🔍 Voir les demandes en cours
        </a>

    </div>

</body>
</html>
<?php
    exit(); 
endif;
?>
