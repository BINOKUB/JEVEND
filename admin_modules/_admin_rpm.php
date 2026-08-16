<?php
/*
====================================================
NOM DU SCRIPT : _admin_rpm.php
REVISION : 1.4 - Connexion directe aux tables de bannières réelles
====================================================
*/

// Sécurité : Vérification directe de la session admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit("Accès non autorisé.");
}

$message_rpm = "";
$erreur_rpm = "";

// TRAITEMENT DU FORMULAIRE DE MISE À JOUR DES LIMITES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_modifier_limites'])) {
    $limite_annonces = (int)$_POST['limite_annonces'];
    $limite_recherches = (int)$_POST['limite_recherches'];

    try {
        $stmt_upd = $bdd->prepare("
            INSERT INTO jevend_parametres (cle_parametre, valeur_parametre) 
            VALUES ('limite_annonces', ?), ('limite_recherches', ?)
            ON DUPLICATE KEY UPDATE valeur_parametre = VALUES(valeur_parametre)
        ");
        $stmt_upd->execute([$limite_annonces, $limite_recherches]);
        $message_rpm = "Les limites globales de la plateforme ont été mises à jour avec succès !";
    } catch (PDOException $e) {
        $erreur_rpm = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// EXTRACTION DES MÉTRIQUES DEPUIS LES VRAIES TABLES
$total_annonces = 0;
$bannieres_regulieres = 0;
$bannieres_premium = 0;
$bannieres_supremes = 0;
$total_recherches = 0;

try {
    $stmt = $bdd->query("SELECT COUNT(*) FROM jevend_annonces");
    $total_annonces = (int)$stmt->fetchColumn();
} catch (PDOException $e) { }

try {
    $stmt_rech = $bdd->query("SELECT COUNT(*) FROM jevend_recherches");
    $total_recherches = (int)$stmt_rech->fetchColumn();
} catch (PDOException $e) { }

// Comptage des bannières régulières actives
try {
    $stmt_reg = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE statut_affichage = 'active'");
    $bannieres_regulieres = (int)$stmt_reg->fetchColumn();
} catch (PDOException $e) { }

// Comptage des bannières professionnelles (Premium et Suprêmes) actives
try {
    $stmt_prem = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'premium' AND statut_affichage = 'active'");
    $bannieres_premium = (int)$stmt_prem->fetchColumn();
} catch (PDOException $e) { }

try {
    $stmt_sup = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'supreme' AND statut_affichage = 'active'");
    $bannieres_supremes = (int)$stmt_sup->fetchColumn();
} catch (PDOException $e) { }

// CALCUL DU RATIO DES BANNIÈRES RÉGULIÈRES
$pourcentage_regulieres = ($total_annonces > 0) ? round(($bannieres_regulieres / $total_annonces) * 100, 1) : 0;
$alerte_50_pourcent = ($pourcentage_regulieres > 50);

// RÉCUPÉRATION DES LIMITES ACTUELLES DEPUIS LA BASE
$limite_annonces_actuelle = 2000;
$limite_recherches_actuelle = 200;
try {
    $stmt_param = $bdd->query("SELECT cle_parametre, valeur_parametre FROM jevend_parametres");
    while ($row = $stmt_param->fetch(PDO::FETCH_ASSOC)) {
        if ($row['cle_parametre'] === 'limite_annonces') $limite_annonces_actuelle = (int)$row['valeur_parametre'];
        if ($row['cle_parametre'] === 'limite_recherches') $limite_recherches_actuelle = (int)$row['valeur_parametre'];
    }
} catch (PDOException $e) { }

$pourcentage_remplissage_annonces = ($limite_annonces_actuelle > 0) ? min(100, round(($total_annonces / $limite_annonces_actuelle) * 100, 1)) : 0;
$pourcentage_remplissage_recherches = ($limite_recherches_actuelle > 0) ? min(100, round(($total_recherches / $limite_recherches_actuelle) * 100, 1)) : 0;
?>

<div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
    <h2 style="margin-top: 0; color: #0f172a; font-size: 1.5rem; display: flex; align-items: center; gap: 10px;">
        ⚙️ RPM — Régulation, Publicités & Métriques
    </h2>
    <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 25px;">
        Supervisez la proportion publicitaire, analysez les volumes en temps réel et ajustez les plafonds de croissance de la plateforme.
    </p>

    <?php if (!empty($message_rpm)): ?>
        <div style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 12px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; font-size: 0.9rem;">
            ✅ <?= htmlspecialchars($message_rpm) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erreur_rpm)): ?>
        <div style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; font-size: 0.9rem;">
            ⚠️ <?= htmlspecialchars($erreur_rpm) ?>
        </div>
    <?php endif; ?>

    <!-- GRILLE DES STATISTIQUES CHIFFRÉES -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; text-align: center;">
            <div style="font-size: 0.82rem; color: #64748b; font-weight: bold; text-transform: uppercase;">Total Annonces</div>
            <div style="font-size: 2.2rem; font-weight: 900; color: #0f172a; margin: 8px 0;"><?= $total_annonces ?></div>
            <div style="font-size: 0.78rem; color: #0284c7;">Actives sur le réseau</div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; text-align: center;">
            <div style="font-size: 0.82rem; color: #64748b; font-weight: bold; text-transform: uppercase;">Bannières Régulières</div>
            <div style="font-size: 2.2rem; font-weight: 900; color: #2563eb; margin: 8px 0;"><?= $bannieres_regulieres ?></div>
            <div style="font-size: 0.78rem; color: #64748b;">Standard publicitaire</div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; text-align: center;">
            <div style="font-size: 0.82rem; color: #64748b; font-weight: bold; text-transform: uppercase;">Bannières Premium</div>
            <div style="font-size: 2.2rem; font-weight: 900; color: #d97706; margin: 8px 0;"><?= $bannieres_premium ?></div>
            <div style="font-size: 0.78rem; color: #d97706;">Mise en avant renforcée</div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; text-align: center;">
            <div style="font-size: 0.82rem; color: #64748b; font-weight: bold; text-transform: uppercase;">Bannières Suprêmes</div>
            <div style="font-size: 2.2rem; font-weight: 900; color: #7c3aed; margin: 8px 0;"><?= $bannieres_supremes ?></div>
            <div style="font-size: 0.78rem; color: #7c3aed;">Visibilité maximale</div>
        </div>

    </div>

    <!-- SECTION CONTRÔLE DES 50% -->
    <div style="background: <?= $alerte_50_pourcent ? '#fef2f2' : '#f0fdf4' ?>; border: 1px solid <?= $alerte_50_pourcent ? '#fecaca' : '#bbf7d0' ?>; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="margin: 0; color: <?= $alerte_50_pourcent ? '#991b1b' : '#166534' ?>; font-size: 1.1rem;">
                📊 Analyse du Seuil Publicitaire (Bannières Régulières vs Total)
            </h3>
            <span style="font-weight: 900; font-size: 1.2rem; color: <?= $alerte_50_pourcent ? '#991b1b' : '#166534' ?>;">
                <?= $pourcentage_regulieres ?> %
            </span>
        </div>
        <p style="margin: 0 0 12px 0; font-size: 0.9rem; color: #334155;">
            Le ratio cible est de maintenir les bannières régulières <strong>en dessous de 50 %</strong> du volume total des annonces.
        </p>
        
        <div style="background: #e2e8f0; border-radius: 10px; height: 14px; overflow: hidden; width: 100%;">
            <div style="background: <?= $alerte_50_pourcent ? '#dc2626' : '#16a34a' ?>; width: min(100%, <?= $pourcentage_regulieres ?>%); height: 100%;"></div>
        </div>
        <?php if ($alerte_50_pourcent): ?>
            <div style="margin-top: 10px; font-size: 0.85rem; color: #991b1b; font-weight: bold;">
                ⚠️ Attention : Le seuil critique de 50 % est dépassé.
            </div>
        <?php else: ?>
            <div style="margin-top: 10px; font-size: 0.85rem; color: #166534; font-weight: bold;">
                ✔️ Équilibre respecté : Le contenu organique prime sur la publicité.
            </div>
        <?php endif; ?>
    </div>

    <!-- SECTION GESTION DES PLAFONDS GLOBAUX -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
        <h3 style="margin-top: 0; color: #0f172a; font-size: 1.1rem; margin-bottom: 15px;">
            🎛️ Régulation et Plafonds du Réseau
        </h3>

        <form action="panneau.php#onglet-rpm" method="POST">
            <input type="hidden" name="action_modifier_limites" value="1">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                
                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.88rem; color: #1e293b; margin-bottom: 6px;">
                        Limite globale d'annonces sur le site :
                    </label>
                    <input type="number" name="limite_annonces" value="<?= $limite_annonces_actuelle ?>" min="100" max="50000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;" required>
                    <div style="font-size: 0.78rem; color: #64748b; margin-top: 4px;">Remplissage actuel : <?= $total_annonces ?> / <?= $limite_annonces_actuelle ?> (<?= $pourcentage_remplissage_annonces ?>%)</div>
                </div>

                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.88rem; color: #1e293b; margin-bottom: 6px;">
                        Limite globale de demandes "Je Cherche" :
                    </label>
                    <input type="number" name="limite_recherches" value="<?= $limite_recherches_actuelle ?>" min="20" max="5000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;" required>
                    <div style="font-size: 0.78rem; color: #64748b; margin-top: 4px;">Remplissage actuel : <?= $total_recherches ?> / <?= $limite_recherches_actuelle ?> (<?= $pourcentage_remplissage_recherches ?>%)</div>
                </div>

            </div>

            <button type="submit" style="background-color: #2563eb; color: #fff; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.9rem;">
                💾 Enregistrer les nouveaux plafonds
            </button>
        </form>
    </div>

<!-- ici le module du calcul des nouvelles annonces par jours nomme _admin_calcul_annonce_jour_rpm.php -->
<!-- MODULE DE SUIVI QUOTIDIEN DES ANNONCES -->
<?php include 'admin_modules/_admin_view_quota_annonce.php'; ?>

</div>
