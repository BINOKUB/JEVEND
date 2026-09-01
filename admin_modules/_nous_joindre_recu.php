<?php
// =============================================================================
// NOM DU SCRIPT : admin_modules/_nous_joindre_recu.php
// REVISION     : 1.2 - Reçus "Nous Joindre" + Interrupteur ON/OFF Formulaire
// SCRIPT COMPLET ET SUIVI
// =============================================================================

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once __DIR__ . '/../config.php';

$msg_admin_nj = "";

// A. GESTION DE L'INTERRUPTEUR (ON / OFF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle_nous_joindre'])) {
    $nouvel_etat = ($_POST['etat_formulaire'] === '1') ? '1' : '0';
    try {
        $stmt_cfg = $bdd->prepare("INSERT INTO jevend_config (cle, valeur) VALUES ('nous_joindre_actif', :val) 
                                   ON DUPLICATE KEY UPDATE valeur = :val");
        $stmt_cfg->execute(['val' => $nouvel_etat]);
        $msg_admin_nj = "Statut du formulaire mis à jour avec succès !";
    } catch (PDOException $e) {
        $msg_admin_nj = "Erreur BDD Config : " . htmlspecialchars($e->getMessage());
    }
}

// B. LECTURE DE L'ÉTAT ACTUEL
$formulaire_actif = true;
if (isset($bdd)) {
    try {
        $stmt_get_cfg = $bdd->prepare("SELECT valeur FROM jevend_config WHERE cle = 'nous_joindre_actif'");
        $stmt_get_cfg->execute();
        $res_cfg = $stmt_get_cfg->fetchColumn();
        if ($res_cfg !== false && $res_cfg === '0') {
            $formulaire_actif = false;
        }
    } catch (PDOException $e) { }
}

// C. TRAITEMENT DES ACTIONS SUR LES MESSAGES (Traiter / Supprimer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_admin_nj'])) {
    $id_msg_nj = (int)($_POST['id_message_nj'] ?? 0);
    $action_nj = $_POST['type_action_nj'] ?? '';

    if ($id_msg_nj > 0 && isset($bdd)) {
        try {
            if ($action_nj === 'traite') {
                $stmt_nj_up = $bdd->prepare("UPDATE jevend_nous_joindre SET statut = 'traite' WHERE id_message = ?");
                $stmt_nj_up->execute([$id_msg_nj]);
            } elseif ($action_nj === 'supprimer') {
                $stmt_nj_del = $bdd->prepare("DELETE FROM jevend_nous_joindre WHERE id_message = ?");
                $stmt_nj_del->execute([$id_msg_nj]);
            }
        } catch (PDOException $e) { }
    }
}

// D. RECUPERATION DES MESSAGES
$messages_nj = [];
if (isset($bdd)) {
    try {
        $stmt_nj = $bdd->query("SELECT * FROM jevend_nous_joindre ORDER BY date_envoi DESC");
        $messages_nj = $stmt_nj->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}
?>

<!-- BLOC D'AFFICHAGE DES REÇUS NOUS JOINDRE -->
<div style="margin-top: 25px; border-top: 2px dashed #cbd5e1; padding-top: 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
        <h3 style="margin: 0; color: #0f172a; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
            📩 Demandes reçues via "Nous Joindre"
        </h3>

        <!-- BOUTON INTERRUPTEUR FORMULAIRE -->
        <form method="POST" style="display:flex; align-items:center; gap:10px; background:#f8fafc; padding:6px 12px; border:1px solid #cbd5e1; border-radius:6px; margin:0;">
            <span style="font-size:0.85rem; font-weight:bold; color:#334155;">Formulaire Public :</span>
            <?php if ($formulaire_actif): ?>
                <span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:10px; font-weight:bold; font-size:0.75rem;">● ACTIF</span>
                <input type="hidden" name="etat_formulaire" value="0">
                <button type="submit" name="action_toggle_nous_joindre" onclick="return confirm('Fermer l\'accès au formulaire public ?');" style="background:#dc2626; color:#fff; border:none; padding:4px 8px; border-radius:4px; font-weight:bold; cursor:pointer; font-size:0.75rem;">Désactiver</button>
            <?php else: ?>
                <span style="background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:10px; font-weight:bold; font-size:0.75rem;">○ FERMÉ</span>
                <input type="hidden" name="etat_formulaire" value="1">
                <button type="submit" name="action_toggle_nous_joindre" style="background:#16a34a; color:#fff; border:none; padding:4px 8px; border-radius:4px; font-weight:bold; cursor:pointer; font-size:0.75rem;">Activer</button>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($msg_admin_nj)): ?>
        <div style="background:#f0fdf4; color:#166534; padding:8px; border-radius:6px; margin-bottom:12px; font-weight:bold; text-align:center; border:1px solid #bbf7d0; font-size:0.85rem;">
            <?= $msg_admin_nj ?>
        </div>
    <?php endif; ?>

    <?php if (empty($messages_nj)): ?>
        <div style="text-align: center; padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; color: #64748b; font-style: italic;">
            Aucun message reçu pour le moment.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                <thead>
                    <tr style="background: #1e293b; color: #ffffff;">
                        <th style="padding: 10px;">Date</th>
                        <th style="padding: 10px;">Expéditeur / ID</th>
                        <th style="padding: 10px;">Courriel</th>
                        <th style="padding: 10px;">Sujet / Titre</th>
                        <th style="padding: 10px;">Message détaillé</th>
                        <th style="padding: 10px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages_nj as $nj): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0; background: <?= ($nj['statut'] === 'nouveau') ? '#f0fdf4' : '#ffffff' ?>;">
                            
                            <td style="padding: 10px; vertical-align: top; white-space: nowrap; color: #64748b;">
                                <?= date('Y-m-d H:i', strtotime($nj['date_envoi'])) ?>
                            </td>

                            <td style="padding: 10px; vertical-align: top; white-space: nowrap;">
                                <strong style="color: #0f172a;"><?= htmlspecialchars($nj['nom']) ?></strong><br>
                                <?php if ($nj['no_de_membre']): ?>
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.75rem;">ID #<?= (int)$nj['no_de_membre'] ?></span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">Visiteur</span>
                                <?php endif; ?>
                                <?php if (!empty($nj['entreprise'])): ?>
                                    <br><small style="color: #475569;"><?= htmlspecialchars($nj['entreprise']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 10px; vertical-align: top; white-space: nowrap;">
                                <a href="mailto:<?= htmlspecialchars($nj['mail']) ?>" style="color: #2563eb; text-decoration: none; font-weight: bold;">
                                    ✉️ <?= htmlspecialchars($nj['mail']) ?>
                                </a>
                            </td>

                            <td style="padding: 10px; vertical-align: top; font-weight: bold; color: #0f172a;">
                                <?= htmlspecialchars($nj['sujet_titre']) ?>
                            </td>

                            <td style="padding: 10px; vertical-align: top; color: #334155; max-width: 350px;">
                                <div style="background: #ffffff; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; white-space: pre-wrap; font-size: 0.85rem;"><?= nl2br(htmlspecialchars($nj['texte'])) ?></div>
                            </td>

                            <td style="padding: 10px; vertical-align: top; text-align: center; white-space: nowrap;">
                                <form method="POST" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="action_admin_nj" value="1">
                                    <input type="hidden" name="id_message_nj" value="<?= (int)$nj['id_message'] ?>">
                                    <?php if ($nj['statut'] === 'nouveau'): ?>
                                        <input type="hidden" name="type_action_nj" value="traite">
                                        <button type="submit" style="background: #166534; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; cursor: pointer;">✓ Traiter</button>
                                    <?php else: ?>
                                        <span style="color: #166534; font-size: 0.75rem; font-weight: bold;">✔ Traité</span>
                                    <?php endif; ?>
                                </form>
                                <form method="POST" style="display:inline-block; margin:0;" onsubmit="return confirm('Supprimer ce message ?');">
                                    <input type="hidden" name="action_admin_nj" value="1">
                                    <input type="hidden" name="id_message_nj" value="<?= (int)$nj['id_message'] ?>">
                                    <input type="hidden" name="type_action_nj" value="supprimer">
                                    <button type="submit" style="background: #991b1b; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; cursor: pointer;">🗑️ Supprimer</button>
                                </form>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
