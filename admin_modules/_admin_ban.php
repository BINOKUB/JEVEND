<!-- admin_modules/_admin_ban.php -->
<?php
// =============================================================================
// NOM DU SCRIPT : admin_modules/_admin_ban.php
// REVISION     : 1.0 - Module d'administration de la bannière info avec aperçu en direct
// DESCRIPTION  : Formulaire de modification pour l'admin avec rendu visuel 
//                instantané et compteur de caractères.
// =============================================================================

if (!isset($bdd)) { exit(); }

$msg_ban_succes = "";
$msg_ban_erreur = "";

// Traitement du formulaire d'activation/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_admin_ban'])) {
    $nouvel_etat = (isset($_POST['etat_ban']) && $_POST['etat_ban'] === 'actif') ? 'actif' : 'inactif';
    $nouveau_texte = trim($_POST['texte_ban'] ?? '');

    try {
        $stmt_upd = $bdd->prepare("UPDATE jevend_admin_ban SET etat = ?, texte = ? WHERE id = 1");
        $stmt_upd->execute([$nouvel_etat, $nouveau_texte]);
        $msg_ban_succes = "La bannière d'information a été mise à jour avec succès !";
    } catch (PDOException $e) {
        $msg_ban_erreur = "Erreur lors de la sauvegarde : " . $e->getMessage();
    }
}

// Récupération des données en BDD
try {
    $stmt_get = $bdd->query("SELECT etat, texte FROM jevend_admin_ban WHERE id = 1");
    $data_ban = $stmt_get->fetch(PDO::FETCH_ASSOC);
    if (!$data_ban) {
        $bdd->exec("INSERT INTO jevend_admin_ban (id, etat, texte) VALUES (1, 'inactif', '📢 Message officiel de la direction...')");
        $data_ban = ['etat' => 'inactif', 'texte' => '📢 Message officiel de la direction...'];
    }
} catch (PDOException $e) {
    $data_ban = ['etat' => 'inactif', 'texte' => ''];
}
?>

<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; box-sizing: border-box;">
    <h2 style="margin-top: 0; color: #0f172a; font-size: 1.3rem; display: flex; align-items: center; gap: 8px;">
        📢 Configuration de la Bannière d'Information (468x60)
    </h2>
    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px;">
        Diffusez un message officiel (nouveautés, avis importants, ajustements) directement sur l'index public.
    </p>

    <?php if (!empty($msg_ban_succes)): ?>
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; font-size: 0.9rem;">
            ✅ <?= $msg_ban_succes ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($msg_ban_erreur)): ?>
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; font-size: 0.9rem;">
            ⚠️ <?= $msg_ban_erreur ?>
        </div>
    <?php endif; ?>

    <!-- BLOC RENDU VISUEL EN DIRECT (PREVIEW) -->
    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
        <div style="font-size: 0.8rem; font-weight: bold; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span>👁️ Aperçu du rendu visuel sur l'index</span>
            <span id="apercu-statut-badge" style="padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;"></span>
        </div>

        <div style="display: flex; justify-content: center; width: 100%;">
            <div id="apercu-banniere-box" style="
                width: 468px; 
                max-width: 100%; 
                min-height: 60px; 
                background: linear-gradient(135deg, #fffbe3 0%, #fef3c7 100%); 
                border: 2px solid #f59e0b; 
                border-radius: 8px; 
                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.18); 
                padding: 8px 14px; 
                display: flex; 
                align-items: center; 
                gap: 12px; 
                box-sizing: border-box;
                transition: all 0.3s ease;
            ">
                <div style="font-size: 1.4rem; line-height: 1; flex-shrink: 0;">📢</div>
                <div id="apercu-texte-contenu" style="
                    font-size: 0.88rem; 
                    font-weight: 700; 
                    color: #78350f; 
                    line-height: 1.35; 
                    overflow: hidden; 
                    text-overflow: ellipsis;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    word-break: break-word;
                ">
                    <?= htmlspecialchars(stripslashes($data_ban['texte'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULAIRE DE GESTION -->
    <form method="POST" action="panneau.php">
        <input type="hidden" name="action_admin_ban" value="1">

        <div style="margin-bottom: 20px;">
            <label for="select-etat-ban" style="display: block; font-weight: 600; margin-bottom: 8px; color: #334155; font-size: 0.9rem;">
                Statut de la bannière :
            </label>
            <select id="select-etat-ban" name="etat_ban" style="width: 100%; padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.95rem; background-color: #ffffff;" onchange="rafraichirApercuAdmin()">
                <option value="inactif" <?= ($data_ban['etat'] === 'inactif') ? 'selected' : '' ?>>🔴 Inactive (Masquée du public)</option>
                <option value="actif" <?= ($data_ban['etat'] === 'actif') ? 'selected' : '' ?>>🟢 Active (Diffusée sur l'index)</option>
            </select>
        </div>

        <div style="margin-bottom: 20px;">
            <label for="input-texte-ban" style="display: block; font-weight: 600; margin-bottom: 8px; color: #334155; font-size: 0.9rem;">
                Message à publier (Max 150 caractères) :
            </label>
            <textarea id="input-texte-ban" name="texte_ban" rows="3" maxlength="150" placeholder="Ex: 📢 Nouveauté : La recherche par ville est désormais active !" style="width: 100%; padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.95rem; box-sizing: border-box; font-family: inherit;" oninput="rafraichirApercuAdmin()" required><?= htmlspecialchars($data_ban['texte'] ?? '') ?></textarea>
            <small id="ban-char-counter" style="display: block; text-align: right; color: #64748b; font-size: 0.8rem; margin-top: 4px;">0 / 150</small>
        </div>

        <button type="submit" style="background-color: #2563eb; color: #ffffff; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 0.95rem; cursor: pointer; transition: background-color 0.2s;">
            💾 Enregistrer et Publier
        </button>
    </form>


<br /><br />
ICI L'INCLUDE _admin_sponsorise.php


</div>

<script>
function rafraichirApercuAdmin() {
    const selectEtat = document.getElementById('select-etat-ban');
    const inputTexte = document.getElementById('input-texte-ban');
    const apercuTexte = document.getElementById('apercu-texte-contenu');
    const apercuBox = document.getElementById('apercu-banniere-box');
    const badgeStatut = document.getElementById('apercu-statut-badge');
    const counter = document.getElementById('ban-char-counter');

    if (!selectEtat || !inputTexte) return;

    const texte = inputTexte.value.trim();
    apercuTexte.textContent = texte || "📢 Votre message apparaîtra ici...";
    counter.textContent = inputTexte.value.length + " / 150";

    if (selectEtat.value === 'actif') {
        apercuBox.style.opacity = "1";
        apercuBox.style.filter = "none";
        badgeStatut.textContent = "EN LIGNE";
        badgeStatut.style.backgroundColor = "#dcfce7";
        badgeStatut.style.color = "#15803d";
    } else {
        apercuBox.style.opacity = "0.4";
        apercuBox.style.filter = "grayscale(100%)";
        badgeStatut.textContent = "MASQUÉE";
        badgeStatut.style.backgroundColor = "#fee2e2";
        badgeStatut.style.color = "#b91c1c";
    }
}

document.addEventListener('DOMContentLoaded', rafraichirApercuAdmin);
</script>
