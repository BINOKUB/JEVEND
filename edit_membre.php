<?php
// =============================================================================
// NOM DU SCRIPT : edit_membre.php
// DESCRIPTION  : Module de modification du profil membre (Cellulaire, Courriel, Ville)
//                avec traçabilité et historique de restauration (Option 1).
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Vérification de la session membre
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

$id_user = $_SESSION['id_utilisateur'];
$msg_succes = "";
$msg_erreur = "";

// --- GESTION DE LA RESTAURATION D'UNE ANCIENNE VERSION ---
if (isset($_GET['restaurer']) && is_numeric($_GET['restaurer'])) {
    $id_edit_cible = (int)$_GET['restaurer'];
    try {
        // Récupérer l'ancienne version ciblée dans l'historique
        $stmt_hist_cible = $bdd->prepare("SELECT * FROM jevend_edit_membre WHERE id_edit = ? AND id_utilisateur = ?");
        $stmt_hist_cible->execute([$id_edit_cible, $id_user]);
        $version_passe = $stmt_hist_cible->fetch(PDO::FETCH_ASSOC);

        if ($version_passe) {
            $anc_cell = $version_passe['cell_avant'];
            $anc_mail = $version_passe['mail_avant'];
            $anc_ville = $version_passe['ville_avant'];

            // Vérifier l'unicité du courriel à restaurer
            $stmt_mail = $bdd->prepare("SELECT COUNT(*) FROM jevend_utilisateurs WHERE courriel = ? AND id_utilisateur != ?");
            $stmt_mail->execute([$anc_mail, $id_user]);
            
            if ($stmt_mail->fetchColumn() > 0) {
                $msg_erreur = "❌ Impossible de restaurer cette version : l'adresse courriel associée est désormais utilisée par un autre compte.";
            } else {
                // Récupérer l'état actuel avant modification pour l'historique
                $stmt_actuel = $bdd->prepare("SELECT cellulaire, courriel, id_ville FROM jevend_utilisateurs WHERE id_utilisateur = ?");
                $stmt_actuel->execute([$id_user]);
                $etat_actuel = $stmt_actuel->fetch(PDO::FETCH_ASSOC);

                // 1. Mettre à jour le profil avec les anciennes valeurs
                $stmt_up = $bdd->prepare("UPDATE jevend_utilisateurs SET cellulaire = ?, courriel = ?, id_ville = ? WHERE id_utilisateur = ?");
                $stmt_up->execute([$anc_cell, $anc_mail, $anc_ville, $id_user]);

                // 2. Enregistrer le mouvement de restauration dans l'historique
                $stmt_historique = $bdd->prepare("INSERT INTO jevend_edit_membre (id_utilisateur, cell_avant, cell_apres, mail_avant, mail_apres, ville_avant, ville_apres) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_historique->execute([$id_user, $etat_actuel['cellulaire'], $anc_cell, $etat_actuel['courriel'], $anc_mail, $etat_actuel['id_ville'], $anc_ville]);

                $msg_succes = "✅ Vos anciennes coordonnées ont été restaurées avec succès !";
            }
        }
    } catch (PDOException $e) {
        $msg_erreur = "❌ Erreur lors de la restauration : " . $e->getMessage();
    }
}

// --- TRAITEMENT DU FORMULAIRE CLASSIQUE DE MISE À JOUR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['restaurer'])) {
    $nouveau_cell = trim($_POST['cellulaire'] ?? '');
    $nouveau_mail = trim($_POST['courriel'] ?? '');
    $nouvelle_ville = (int)($_POST['id_ville'] ?? 0);

    if (empty($nouveau_cell) || empty($nouveau_mail) || $nouvelle_ville <= 0) {
        $msg_erreur = "❌ Tous les champs obligatoires doivent être remplis correctement.";
    } elseif (!filter_var($nouveau_mail, FILTER_VALIDATE_EMAIL)) {
        $msg_erreur = "❌ L'adresse courriel saisie n'est pas valide.";
    } else {
        try {
            // Récupérer les données actuelles
            $stmt_user = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE id_utilisateur = ?");
            $stmt_user->execute([$id_user]);
            $utilisateur_actuel = $stmt_user->fetch(PDO::FETCH_ASSOC);

            // Vérification de l'unicité du courriel
            $stmt_mail = $bdd->prepare("SELECT COUNT(*) FROM jevend_utilisateurs WHERE courriel = ? AND id_utilisateur != ?");
            $stmt_mail->execute([$nouveau_mail, $id_user]);
            
            if ($stmt_mail->fetchColumn() > 0) {
                $msg_erreur = "❌ Cette adresse courriel est déjà utilisée par un autre compte.";
            } else {
                $cell_avant = $utilisateur_actuel['cellulaire'];
                $mail_avant = $utilisateur_actuel['courriel'];
                $ville_avant = $utilisateur_actuel['id_ville'];

                if ($cell_avant !== $nouveau_cell || $mail_avant !== $nouveau_mail || $ville_avant !== $nouvelle_ville) {
                    // 1. Mise à jour
                    $stmt_update = $bdd->prepare("UPDATE jevend_utilisateurs SET cellulaire = ?, courriel = ?, id_ville = ? WHERE id_utilisateur = ?");
                    $stmt_update->execute([$nouveau_cell, $nouveau_mail, $nouvelle_ville, $id_user]);

                    // 2. Historique
                    $stmt_historique = $bdd->prepare("INSERT INTO jevend_edit_membre (id_utilisateur, cell_avant, cell_apres, mail_avant, mail_apres, ville_avant, ville_apres) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_historique->execute([$id_user, $cell_avant, $nouveau_cell, $mail_avant, $nouveau_mail, $ville_avant, $nouvelle_ville]);

                    $msg_succes = "✅ Vos informations ont été mises à jour avec succès !";
                } else {
                    $msg_erreur = "⚠️ Aucun changement détecté.";
                }
            }
        } catch (PDOException $e) {
            $msg_erreur = "❌ Erreur technique : " . $e->getMessage();
        }
    }
}

// Récupération finale des infos utilisateur rafraîchies
$stmt_user = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE id_utilisateur = ?");
$stmt_user->execute([$id_user]);
$utilisateur = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Récupération des villes pour le formulaire
$villes = [];
try {
    $stmt_villes = $bdd->query("SELECT id_ville, nom_ville FROM jevend_villes ORDER BY nom_ville ASC");
    $villes = $stmt_villes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Récupération de l'historique des modifications de l'utilisateur avec noms des villes
$historique_modifs = [];
try {
    $stmt_hist = $bdd->prepare("
        SELECT e.*, 
               v_av.nom_ville AS nom_ville_avant, 
               v_ap.nom_ville AS nom_ville_apres 
        FROM jevend_edit_membre e
        LEFT JOIN jevend_villes v_av ON e.ville_avant = v_av.id_ville
        LEFT JOIN jevend_villes v_ap ON e.ville_apres = v_ap.id_ville
        WHERE e.id_utilisateur = ? 
        ORDER BY e.date_modification DESC
    ");
    $stmt_hist->execute([$id_user]);
    $historique_modifs = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil & Historique — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profil-conteneur { max-width: 750px; margin: 40px auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .profil-titre { margin-top: 0; color: #0f172a; font-size: 1.4rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
        .form-groupe { margin-bottom: 20px; }
        .form-groupe label { display: block; font-weight: bold; font-size: 0.9rem; color: #334155; margin-bottom: 6px; }
        .form-groupe input, .form-groupe select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; }
        .form-groupe input:focus, .form-groupe select:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-enregistrer { background: #2563eb; color: #ffffff; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 1rem; cursor: pointer; width: 100%; transition: background 0.2s; }
        .btn-enregistrer:hover { background: #1d4ed8; }
        .lien-retour { display: inline-block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: bold; }
        .lien-retour:hover { color: #2563eb; }
        .alerte-succes { background: #dcfce7; color: #166534; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        .alerte-erreur { background: #fee2e2; color: #991b1b; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #fecaca; }
        
        /* Styles pour l'historique */
        .section-historique { margin-top: 40px; border-top: 2px solid #f1f5f9; padding-top: 20px; }
        .table-historique { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 15px; }
        .table-historique th, .table-historique td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        .table-historique th { background: #f8fafc; color: #334155; }
        .btn-restaurer { background: #059669; color: #ffffff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.75rem; display: inline-block; transition: background 0.2s; }
        .btn-restaurer:hover { background: #047857; }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="profil-conteneur">
        <div class="profil-titre">
            <span>⚙️ Modifier mes informations personnelles</span>
            <a href="espace_membre.php" style="font-size: 0.85rem; color: #2563eb; text-decoration: none;">← Retour</a>
        </div>

        <?php if (!empty($msg_succes)): ?>
            <div class="alerte-succes"><?= $msg_succes ?></div>
        <?php endif; ?>

        <?php if (!empty($msg_erreur)): ?>
            <div class="alerte-erreur"><?= $msg_erreur ?></div>
        <?php endif; ?>

        <form action="edit_membre.php" method="POST">
            <div class="form-groupe">
                <label for="nom">Nom complet :</label>
                <input type="text" id="nom" value="<?= htmlspecialchars($utilisateur['nom']) ?>" disabled style="background: #f8fafc; color: #64748b; cursor: not-allowed;">
            </div>

            <div class="form-groupe">
                <label for="courriel">Adresse courriel (Identifiant) * :</label>
                <input type="email" id="courriel" name="courriel" value="<?= htmlspecialchars($utilisateur['courriel']) ?>" required>
            </div>

            <div class="form-groupe">
                <label for="cellulaire">Numéro de cellulaire * :</label>
                <input type="text" id="cellulaire" name="cellulaire" value="<?= htmlspecialchars($utilisateur['cellulaire']) ?>" required placeholder="Ex: 418-555-1234">
            </div>

            <div class="form-groupe">
                <label for="id_ville">Ville de résidence * :</label>
                <select name="id_ville" id="id_ville" required style="background: #ffffff; cursor: pointer;">
                    <option value="">-- Sélectionnez votre ville --</option>
                    <?php foreach ($villes as $v): ?>
                        <option value="<?= $v['id_ville'] ?>" <?= $v['id_ville'] == $utilisateur['id_ville'] ? 'selected' : '' ?>>
                            📍 <?= htmlspecialchars($v['nom_ville']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-enregistrer">💾 Enregistrer les modifications</button>
        </form>

        <!-- SECTION HISTORIQUE ET RESTAURATION -->
        <div class="section-historique">
            <h3 style="color: #1e293b; font-size: 1.1rem; margin-bottom: 5px;">📜 Historique de vos modifications</h3>
            <p style="color: #64748b; font-size: 0.85rem; margin-top: 0;">Vous pouvez à tout moment rétablir une ancienne version de vos coordonnées en cliquant sur « Restaurer ».</p>

            <?php if (empty($historique_modifs)): ?>
                <p style="color: #94a3b8; font-style: italic; font-size: 0.9rem;">Aucune modification enregistrée pour le moment.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table-historique">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Anciennes valeurs (Avant)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique_modifs as $hist): ?>
                                <tr>
                                    <td style="white-space: nowrap; color: #475569;"><?= date('Y-m-d H:i', strtotime($hist['date_modification'])) ?></td>
                                    <td>
                                        📧 <strong>Courriel :</strong> <?= htmlspecialchars($hist['mail_avant']) ?><br>
                                        📱 <strong>Cellulaire :</strong> <?= htmlspecialchars($hist['cell_avant']) ?><br>
                                        📍 <strong>Ville :</strong> <?= htmlspecialchars($hist['nom_ville_avant'] ?? 'Inconnue') ?>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <a href="edit_membre.php?restaurer=<?= $hist['id_edit'] ?>" class="btn-restaurer" onclick="return confirm('Voulez-vous vraiment rétablir ces anciennes coordonnées ?');">
                                            🔄 Restaurer
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center;">
            <a href="espace_membre.php" class="lien-retour">← Revenir à la gestion de mes annonces</a>
        </div>
    </div>

</body>
</html>
