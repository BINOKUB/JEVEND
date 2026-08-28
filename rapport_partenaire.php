<?php
// =============================================================================
// SCRIPT      : rapport_partenaire.php
// PROJET      : JEVEND | BRANCHE : main
// REVISION    : 1.2 | AUTEUR : Dan | DATE : 2026-08-28
// DESC        : Rapport d'impact vitrine basé sur les champs BDD réels
// =============================================================================

require_once 'config.php';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    die("<div style='font-family: Arial; padding: 20px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; margin: 20px;'>⚠️ Lien de partenariat invalide ou manquant.</div>");
}

try {
    $stmt = $bdd->prepare("
        SELECT id_partenaire, nom_partenaire, nom_contact, courriel, url_site, format_widget, ville_filtre, widget_token, nb_clics, date_creation 
        FROM jevend_annuaire_partenaire 
        WHERE widget_token = ? 
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $partenaire = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partenaire) {
        die("<div style='font-family: Arial; padding: 20px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; margin: 20px;'>⚠️ Aucun partenaire trouvé pour ce jeton.</div>");
    }
} catch (PDOException $e) {
    die("<div style='font-family: Arial; padding: 20px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; margin: 20px;'>⚠️ Erreur SQL : " . htmlspecialchars($e->getMessage()) . "</div>");
}

$nom_partenaire = $partenaire['nom_partenaire'];
$ville          = !empty($partenaire['ville_filtre']) ? $partenaire['ville_filtre'] : 'Toutes les villes';
$clics          = (int)$partenaire['nb_clics'];
$format         = !empty($partenaire['format_widget']) ? $partenaire['format_widget'] : 'Standard';
$url_site       = $partenaire['url_site'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de Partenariat - <?= htmlspecialchars($nom_partenaire) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 20px; }
        .card { max-width: 600px; margin: 30px auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .stat-box .valeur { font-size: 2.5rem; font-weight: bold; color: #166534; }
        .stat-box .label { font-size: 0.8rem; color: #15803d; font-weight: bold; text-transform: uppercase; margin-top: 5px; }
        .badge { padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; display: inline-block; background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="margin: 0; font-size: 1.2rem; color: #1e3a8a;">🤝 Vitrine Locale & Impact Partenaire</h2>
            <div style="font-size: 0.95rem; color: #0f172a; margin-top: 4px; font-weight: bold;"><?= htmlspecialchars($nom_partenaire) ?></div>
        </div>
        <span class="badge">Secteur : <?= htmlspecialchars($ville) ?></span>
    </div>

    <p style="margin-top: 15px; font-size: 0.88rem; color: #475569; line-height: 1.4;">
        Rapport d'activité mesurant les redirections de citoyens générées vers les annonces de <strong>Jevend.com</strong> depuis votre portail (Format : <code><?= htmlspecialchars($format) ?></code>).
    </p>

    <!-- Statistique unique basée sur la BDD -->
    <div class="stat-box">
        <div class="valeur"><?= number_format($clics, 0, ',', ' ') ?></div>
        <div class="label">Clics & Redirections d'intérêt générés</div>
    </div>

    <div style="font-size: 0.8rem; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px; text-align: center;">
        Inclus dans le réseau partenaire depuis le <strong><?= date('d/m/Y', strtotime($partenaire['date_creation'])) ?></strong><br>
        Service fourni gratuitement par <strong>Jevend.com</strong>
    </div>
</div>

</body>
</html>
