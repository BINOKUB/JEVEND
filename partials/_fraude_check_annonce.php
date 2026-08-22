<?php
// =============================================================================
// NOM DU SCRIPT : _fraude_check_annonce.php
// DESCRIPTION   : Module de signalement adapté spécifiquement pour les annonces (details.php)
// =============================================================================

if (!defined('ROOT_DIR') && basename($_SERVER['SCRIPT_FILENAME']) === '_fraude_check_annonce.php') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

$id_user_actuel = $_SESSION['id_utilisateur'] ?? 0;

// Récupération des paramètres propres aux annonces
$id_ann_cible  = $id_annonces ?? ($_GET['id'] ?? null);
// Dans details.php, le propriétaire de l'annonce est stocké dans $annonce['id_utilisateur']
$id_proprietaire_cible = $annonce['id_utilisateur'] ?? null;

$succes_signalement = "";
$erreur_signalement = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_envoyer_signalement_annonce'])) {
    if (!$id_user_actuel) {
        $erreur_signalement = "Vous devez être connecté pour envoyer un signalement.";
    } else {
        $motif_choisi = trim($_POST['motif_signalement'] ?? '');
        $details_texte = trim($_POST['details_signalement'] ?? '');

        if (empty($motif_choisi)) {
            $erreur_signalement = "Veuillez sélectionner un motif de signalement.";
        } else {
            try {
                // Insertion dans jevend_signalement ciblant l'annonce et son vendeur
                $stmt_sig = $bdd->prepare("
                    INSERT INTO jevend_signalement 
                    (id_expediteur, id_cible, id_annonce, id_recherche, id_chat, motif, details, statut) 
                    VALUES (?, ?, ?, NULL, NULL, ?, ?, 'en_attente')
                ");
                $stmt_sig->execute([
                    $id_user_actuel,
                    $id_proprietaire_cible ? (int)$id_proprietaire_cible : NULL,
                    $id_ann_cible ? (int)$id_ann_cible : NULL,
                    $motif_choisi,
                    $details_texte
                ]);

                $succes_signalement = "Signalement transmis avec succès à l'administration. Merci !";
            } catch (PDOException $e) {
                $erreur_signalement = "Erreur technique lors de l'enregistrement du signalement.";
            }
        }
    }
}
?>

<!-- ON AFFICHE LE BLOC UNIQUEMENT SI LE MEMBRE EST CONNECTÉ -->
<?php if ($id_user_actuel > 0): ?>
<!-- BLOC VISUEL DU SIGNALEMENT ANNONCE -->
<div style="margin-top: 15px; margin-bottom: 15px; padding-top: 10px; border-top: 1px dashed #cbd5e1; font-size: 0.85rem;">
    
    <?php if (!empty($succes_signalement)): ?>
        <div style="background: #f0fdf4; color: #166534; padding: 8px; border-radius: 6px; border: 1px solid #bbf7d0; font-weight: bold; text-align: center; margin-bottom: 8px;">
            ✅ <?= htmlspecialchars($succes_signalement) ?>
        </div>
    <?php elseif (!empty($erreur_signalement)): ?>
        <div style="background: #fef2f2; color: #991b1b; padding: 8px; border-radius: 6px; border: 1px solid #fecaca; font-weight: bold; text-align: center; margin-bottom: 8px;">
            ⚠️ <?= htmlspecialchars($erreur_signalement) ?>
        </div>
    <?php endif; ?>

    <div id="zone-lien-signalement-annonce" style="text-align: center;">
        <button type="button" onclick="basculerFormulaireSignalementAnnonce()" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 0.82rem; font-weight: bold; text-decoration: underline; padding: 0;">
            🚩 Signaler un problème avec cette annonce
        </button>
    </div>

    <!-- FORMULAIRE CACHÉ -->
    <div id="formulaire-signalement-annonce-box" style="display: none; margin-top: 10px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; text-align: left;">
        <form method="POST" action="">
            <input type="hidden" name="action_envoyer_signalement_annonce" value="1">
            
            <div style="font-weight: bold; color: #0f172a; margin-bottom: 8px; font-size: 0.85rem;">
                🚨 Signaler cette annonce :
            </div>

            <div style="margin-bottom: 8px;">
                <select name="motif_signalement" required style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.82rem; background: #fff;">
                    <option value="">-- Sélectionnez un motif --</option>
                    <option value="Utilisateur hors région / Suspect">Utilisateur hors région / Suspect (Hors Québec-Gaspé)</option>
                    <option value="Comportement non commercial / Rencontre">Comportement non commercial / Site de rencontre</option>
                    <option value="Tentative d'arnaque / Fraude">Tentative d'arnaque / Fraude financière</option>
                    <option value="Annonce ou contenu inapproprié">Annonce ou contenu inapproprié</option>
                    <option value="Autre motif">Autre motif</option>
                </select>
            </div>

            <div style="margin-bottom: 8px;">
                <textarea name="details_signalement" rows="2" placeholder="Précisez brièvement (facultatif)..." style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.82rem; outline: none; box-sizing: border-box;"></textarea>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="basculerFormulaireSignalementAnnonce()" style="background: #e2e8f0; color: #334155; border: none; padding: 6px 10px; border-radius: 4px; font-size: 0.78rem; cursor: pointer; font-weight: bold;">
                    Annuler
                </button>
                <button type="submit" style="background: #dc2626; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 0.78rem; cursor: pointer; font-weight: bold;">
                    Envoyer l'alerte
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function basculerFormulaireSignalementAnnonce() {
    const box = document.getElementById('formulaire-signalement-annonce-box');
    const lien = document.getElementById('zone-lien-signalement-annonce');
    if (box.style.display === 'none') {
        box.style.display = 'block';
        lien.style.display = 'none';
    } else {
        box.style.display = 'none';
        lien.style.display = 'block';
    }
}
</script>
<?php endif; ?>
