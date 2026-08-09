<?php
// =============================================================================
// NOM DU SCRIPT : edit_membre_pro.php
// DESCRIPTION  : Module de modification du profil Marchand Pro 
//                (Nom, Courriel, Cellulaire, Nom entreprise, Tél Pro, Adresse Pro)
//                avec traçabilité et historique de restauration.
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Vérification de la session et du type de compte pro
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['type_compte']) || $_SESSION['type_compte'] !== 'pro') {
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
        $stmt_hist_cible = $bdd->prepare("SELECT * FROM jevend_edit_membre_pro WHERE id_edit = ? AND id_utilisateur = ?");
        $stmt_hist_cible->execute([$id_edit_cible, $id_user]);
        $version_passe = $stmt_hist_cible->fetch(PDO::FETCH_ASSOC);

        if ($version_passe) {
            $anc_nom = $version_passe['nom_avant'];
            $anc_mail = $version_passe['mail_avant'];
            $anc_cell = $version_passe['cell_avant'];
            $anc_entreprise = $version_passe['entreprise_avant'];
            $anc_tel_pro = $version_passe['tel_pro_avant'];
            $anc_adresse_pro = $version_passe['adresse_pro_avant'];

            // Vérifier l'unicité du courriel à restaurer
            $stmt_mail = $bdd->prepare("SELECT COUNT(*) FROM jevend_utilisateurs WHERE courriel = ? AND id_utilisateur != ?");
            $stmt_mail->execute([$anc_mail, $id_user]);
            
            if ($stmt_mail->fetchColumn() > 0) {
                $msg_erreur = "❌ Impossible de restaurer cette version : l'adresse courriel associée est désormais utilisée par un autre compte.";
            } else {
                // Récupérer l'état actuel avant modification pour l'historique
                $stmt_actuel = $bdd->prepare("SELECT nom, courriel, cellulaire, nom_entreprise, telephone_pro, adresse_pro FROM jevend_utilisateurs WHERE id_utilisateur = ?");
                $stmt_actuel->execute([$id_user]);
                $etat_actuel = $stmt_actuel->fetch(PDO::FETCH_ASSOC);

                // 1. Mettre à jour le profil Pro
                $stmt_up = $bdd->prepare("UPDATE jevend_utilisateurs SET nom = ?, courriel = ?, cellulaire = ?, nom_entreprise = ?, telephone_pro = ?, adresse_pro = ? WHERE id_utilisateur = ?");
                $stmt_up->execute([$anc_nom, $anc_mail, $anc_cell, $anc_entreprise, $anc_tel_pro, $anc_adresse_pro, $id_user]);

                // 2. Enregistrer dans l'historique pro
                $stmt_historique = $bdd->prepare("INSERT INTO jevend_edit_membre_pro (id_utilisateur, nom_avant, nom_apres, mail_avant, mail_apres, cell_avant, cell_apres, entreprise_avant, entreprise_apres, tel_pro_avant, tel_pro_apres, adresse_pro_avant, adresse_pro_apres) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_historique->execute([
                    $id_user, 
                    $etat_actuel['nom'], $anc_nom, 
                    $etat_actuel['courriel'], $anc_mail, 
                    $etat_actuel['cellulaire'], $anc_cell, 
                    $etat_actuel['nom_entreprise'], $anc_entreprise, 
                    $etat_actuel['telephone_pro'], $anc_tel_pro, 
                    $etat_actuel['adresse_pro'], $anc_adresse_pro
                ]);

                // Mettre à jour la session si le nom de l'entreprise ou le nom change
                $_SESSION['nom'] = $anc_nom;
                $_SESSION['nom_entreprise'] = $anc_entreprise;

                $msg_succes = "✅ Vos anciennes coordonnées marchandes ont été restaurées avec succès !";
            }
        }
    } catch (PDOException $e) {
        $msg_erreur = "❌ Erreur lors de la restauration : " . $e->getMessage();
    }
}

// --- TRAITEMENT DU FORMULAIRE CLASSIQUE DE MISE À JOUR PRO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['restaurer'])) {
    $nouveau_nom = trim($_POST['nom'] ?? '');
    $nouveau_mail = trim($_POST['courriel'] ?? '');
    $nouveau_cell = trim($_POST['cellulaire'] ?? '');
    $nouvelle_entreprise = trim($_POST['nom_entreprise'] ?? '');
    $nouveau_tel_pro = trim($_POST['telephone_pro'] ?? '');
    $nouvelle_adresse_pro = trim($_POST['adresse_pro'] ?? '');

    if (empty($nouveau_nom) || empty($nouveau_mail) || empty($nouveau_cell) || empty($nouvelle_entreprise)) {
        $msg_erreur = "❌ Veuillez remplir tous les champs obligatoires (Nom, Courriel, Cellulaire, Nom de l'entreprise).";
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
                $nom_avant = $utilisateur_actuel['nom'];
                $mail_avant = $utilisateur_actuel['courriel'];
                $cell_avant = $utilisateur_actuel['cellulaire'];
                $entreprise_avant = $utilisateur_actuel['nom_entreprise'];
                $tel_pro_avant = $utilisateur_actuel['telephone_pro'];
                $adresse_pro_avant = $utilisateur_actuel['adresse_pro'];

                if ($nom_avant !== $nouveau_nom || $mail_avant !== $nouveau_mail || $cell_avant !== $nouveau_cell || $entreprise_avant !== $nouvelle_entreprise || $tel_pro_avant !== $nouveau_tel_pro || $adresse_pro_avant !== $nouvelle_adresse_pro) {
                    
                    // 1. Mise à jour de la table utilisateurs
                    $stmt_update = $bdd->prepare("UPDATE jevend_utilisateurs SET nom = ?, courriel = ?, cellulaire = ?, nom_entreprise = ?, telephone_pro = ?, adresse_pro = ? WHERE id_utilisateur = ?");
                    $stmt_update->execute([$nouveau_nom, $nouveau_mail, $nouveau_cell, $nouvelle_entreprise, $nouveau_tel_pro, $nouvelle_adresse_pro, $id_user]);

                    // 2. Enregistrement dans l'historique pro
                    $stmt_historique = $bdd->prepare("INSERT INTO jevend_edit_membre_pro (id_utilisateur, nom_avant, nom_apres, mail_avant, mail_apres, cell_avant, cell_apres, entreprise_avant, entreprise_apres, tel_pro_avant, tel_pro_apres, adresse_pro_avant, adresse_pro_apres) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_historique->execute([
                        $id_user, 
                        $nom_avant, $nouveau_nom, 
                        $mail_avant, $nouveau_mail, 
                        $cell_avant, $nouveau_cell, 
                        $entreprise_avant, $nouvelle_entreprise, 
                        $tel_pro_avant, $nouveau_tel_pro, 
                        $adresse_pro_avant, $nouvelle_adresse_pro
                    ]);

                    // Mettre à jour la session
                    $_SESSION['nom'] = $nouveau_nom;
                    $_SESSION['nom_entreprise'] = $nouvelle_entreprise;

                    $msg_succes = "✅ Vos informations marchandes ont été mises à jour avec succès !";
                } else {
                    $msg_erreur = "⚠️ Aucun changement détecté.";
                }
            }
        } catch (PDOException $e) {
            $msg_erreur = "❌ Erreur technique : " . $e->getMessage();
        }
    }
}

// Récupération finale actualisée
$stmt_user = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE id_utilisateur = ?");
$stmt_user->execute([$id_user]);
$utilisateur = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Récupération de l'historique pro
$historique_modifs = [];
try {
    $stmt_hist = $bdd->prepare("SELECT * FROM jevend_edit_membre_pro WHERE id_utilisateur = ? ORDER BY date_modification DESC");
    $stmt_hist->execute([$id_user]);
    $historique_modifs = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Marchand & Historique — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profil-conteneur { max-width: 750px; margin: 40px auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .profil-titre { margin-top: 0; color: #0f172a; font-size: 1.4rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
        .form-groupe { margin-bottom: 20px; }
        .form-groupe label { display: block; font-weight: bold; font-size: 0.9rem; color: #334155; margin-bottom: 6px; }
        .form-groupe input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; }
        .form-groupe input:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-enregistrer { background: #2563eb; color: #ffffff; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 1rem; cursor: pointer; width: 100%; transition: background 0.2s; }
        .btn-enregistrer:hover { background: #1d4ed8; }
        .lien-retour { display: inline-block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: bold; }
        .lien-retour:hover { color: #2563eb; }
        .alerte-succes { background: #dcfce7; color: #166534; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        .alerte-erreur { background: #fee2e2; color: #991b1b; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #fecaca; }
        
        .section-historique { margin-top: 40px; border-top: 2px solid #f1f5f9; padding-top: 20px; }
        .table-historique { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 15px; }
        .table-historique th, .table-historique td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        .table-historique th { background: #f8fafc; color: #334155; }
        .btn-restaurer { background: #059669; color: #ffffff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.75rem; display: inline-block; transition: background 0.2s; }
        .btn-restaurer:hover { background: #047857; }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="profil-conteneur">
        <div class="profil-titre">
            <span>🏢 Modifier mes informations Marchand Pro</span>
            <a href="espace_membre_pro.php" style="font-size: 0.85rem; color: #2563eb; text-decoration: none;">← Retour</a>
        </div>

        <?php if (!empty($msg_succes)): ?>
            <div class="alerte-succes"><?= $msg_succes ?></div>
        <?php endif; ?>

        <?php if (!empty($msg_erreur)): ?>
            <div class="alerte-erreur"><?= $msg_erreur ?></div>
        <?php endif; ?>

        <form action="edit_membre_pro.php" method="POST">
            <div class="form-groupe">
                <label for="nom">Nom du responsable * :</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
            </div>

            <div class="form-groupe">
                <label for="nom_entreprise">Nom de l'entreprise * :</label>
                <input type="text" id="nom_entreprise" name="nom_entreprise" value="<?= htmlspecialchars($utilisateur['nom_entreprise'] ?? '') ?>" required>
            </div>

            <div class="form-groupe">
                <label for="courriel">Adresse courriel (Identifiant) * :</label>
                <input type="email" id="courriel" name="courriel" value="<?= htmlspecialchars($utilisateur['courriel']) ?>" required>
            </div>

            <div class="form-groupe">
                <label for="cellulaire">Cellulaire du responsable * :</label>
                <input type="text" id="cellulaire" name="cellulaire" value="<?= htmlspecialchars($utilisateur['cellulaire']) ?>" required placeholder="Ex: 418-555-1234">
            </div>

            <div class="form-groupe">
                <label for="telephone_pro">Téléphone professionnel (Optionnel) :</label>
                <input type="text" id="telephone_pro" name="telephone_pro" value="<?= htmlspecialchars($utilisateur['telephone_pro'] ?? '') ?>" placeholder="Ex: 418-555-5678">
            </div>

            <div class="form-groupe">
                <label for="adresse_pro">Adresse professionnelle (Optionnel) :</label>
                <input type="text" id="adresse_pro" name="adresse_pro" value="<?= htmlspecialchars($utilisateur['adresse_pro'] ?? '') ?>" placeholder="Ex: 123 Rue Principale, Ville">
            </div>

            <button type="submit" class="btn-enregistrer">💾 Enregistrer les modifications Pro</button>
        </form>

        <!-- SECTION HISTORIQUE ET RESTAURATION PRO -->
        <div class="section-historique">
            <h3 style="color: #1e293b; font-size: 1.1rem; margin-bottom: 5px;">📜 Historique de vos modifications Marchand</h3>
            <p style="color: #64748b; font-size: 0.85rem; margin-top: 0;">Vous pouvez à tout moment rétablir une ancienne version de vos informations professionnelles.</p>

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
                                        👤 <strong>Nom :</strong> <?= htmlspecialchars($hist['nom_avant']) ?><br>
                                        🏢 <strong>Entreprise :</strong> <?= htmlspecialchars($hist['entreprise_avant']) ?><br>
                                        📧 <strong>Courriel :</strong> <?= htmlspecialchars($hist['mail_avant']) ?><br>
                                        📱 <strong>Cellulaire :</strong> <?= htmlspecialchars($hist['cell_avant']) ?><br>
                                        📞 <strong>Tél Pro :</strong> <?= htmlspecialchars($hist['tel_pro_avant'] ?? 'N/A') ?><br>
                                        📍 <strong>Adresse Pro :</strong> <?= htmlspecialchars($hist['adresse_pro_avant'] ?? 'N/A') ?>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <a href="edit_membre_pro.php?restaurer=<?= $hist['id_edit'] ?>" class="btn-restaurer" onclick="return confirm('Voulez-vous vraiment rétablir ces anciennes coordonnées professionnelles ?');">
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
            <a href="espace_membre_pro.php" class="lien-retour">← Revenir à mon espace marchand</a>
        </div>
    </div>

</body>
</html>
