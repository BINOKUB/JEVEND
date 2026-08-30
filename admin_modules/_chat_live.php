<?php
// =============================================================================
// SCRIPT      : _chat_live.php
// PROJET      : JEVEND | BRANCHE : main
// REVISION    : 1.2 | AUTEUR : Dan | DATE : 2026-08-29
// DESC        : Panneau Admin Chat Live (Clôture de ticket et Suppression)
// NOM DU SCRIPT: admin_modules/_chat_live.php
// =============================================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit(); }

$message_chat_live = "";
$type_message_live = "";

// 1. Changement de mode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_change_mode_live'])) {
    $nouveau_mode = $_POST['mode_chat'] ?? 'statique';
    if (in_array($nouveau_mode, ['off', 'statique', 'live'])) {
        try {
            $stmt_up = $bdd->prepare("UPDATE jevend_chat_live_config SET mode_chat = ? WHERE id = 1");
            $stmt_up->execute([$nouveau_mode]);
            $message_chat_live = "Mode du Chat Live mis à jour : " . strtoupper($nouveau_mode);
            $type_message_live = "succes";
        } catch (PDOException $e) {
            $message_chat_live = "Erreur SQL : " . $e->getMessage();
            $type_message_live = "erreur";
        }
    }
}

// 2. Envoi / Mise à jour de réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_repondre_ticket'])) {
    $id_ticket = (int)($_POST['id_ticket'] ?? 0);
    $reponse_txt = trim($_POST['reponse_admin'] ?? '');

    if ($id_ticket > 0 && !empty($reponse_txt)) {
        try {
            $stmt_rep = $bdd->prepare("UPDATE jevend_chat_tickets_live SET reponse_admin = ?, statut = 'repondu', date_reponse = NOW() WHERE id_ticket = ?");
            $stmt_rep->execute([$reponse_txt, $id_ticket]);
            $message_chat_live = "Réponse enregistrée avec succès.";
            $type_message_live = "succes";
        } catch (PDOException $e) {
            $message_chat_live = "Erreur SQL : " . $e->getMessage();
            $type_message_live = "erreur";
        }
    }
}

// 3. Clôturer un ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_cloturer_ticket'])) {
    $id_ticket = (int)($_POST['id_ticket'] ?? 0);
    if ($id_ticket > 0) {
        try {
            $stmt_clt = $bdd->prepare("UPDATE jevend_chat_tickets_live SET statut = 'ferme' WHERE id_ticket = ?");
            $stmt_clt->execute([$id_ticket]);
            $message_chat_live = "Discussion clôturée.";
            $type_message_live = "succes";
        } catch (PDOException $e) {
            $message_chat_live = "Erreur SQL : " . $e->getMessage();
            $type_message_live = "erreur";
        }
    }
}

// 4. Supprimer un ticket fermé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_supprimer_ticket'])) {
    $id_ticket = (int)($_POST['id_ticket'] ?? 0);
    if ($id_ticket > 0) {
        try {
            $stmt_del = $bdd->prepare("DELETE FROM jevend_chat_tickets_live WHERE id_ticket = ? AND statut = 'ferme'");
            $stmt_del->execute([$id_ticket]);
            $message_chat_live = "Ticket supprimé de la base de données.";
            $type_message_live = "succes";
        } catch (PDOException $e) {
            $message_chat_live = "Erreur SQL : " . $e->getMessage();
            $type_message_live = "erreur";
        }
    }
}

// Configuration & Chargement des tickets
try {
    $stmt_cfg = $bdd->query("SELECT mode_chat FROM jevend_chat_live_config WHERE id = 1 LIMIT 1");
    $config_live = $stmt_cfg->fetch(PDO::FETCH_ASSOC);
    $mode_actuel = $config_live['mode_chat'] ?? 'statique';
} catch (PDOException $e) { $mode_actuel = 'statique'; }

try {
    $stmt_tck = $bdd->query("SELECT * FROM jevend_chat_tickets_live ORDER BY id_ticket DESC LIMIT 30");
    $tickets = $stmt_tck->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $tickets = []; }
?>

<div style="background: #ffffff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; box-sizing: border-box; margin-top: 20px;">

    <!-- EN-TÊTE ET SÉLECTEUR DE MODE -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; flex-wrap: wrap; gap: 15px;">
        <h3 style="color: #1e3a8a; margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
            <span>💬</span> Gestionnaire Chat Live & Support
        </h3>

        <form method="POST" style="display: flex; align-items: center; gap: 10px; margin: 0;">
            <input type="hidden" name="action_change_mode_live" value="1">
            <label style="font-weight: bold; font-size: 0.88rem; color: #475569;">État du Chat :</label>
            <select name="mode_chat" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 0.88rem; cursor: pointer; background: #f8fafc;">
                <option value="off" <?= ($mode_actuel === 'off') ? 'selected' : '' ?>>🔴 Chat OFF (Masqué)</option>
                <option value="statique" <?= ($mode_actuel === 'statique') ? 'selected' : '' ?>>🟡 Mode STATIQUE (FAQ Auto)</option>
                <option value="live" <?= ($mode_actuel === 'live') ? 'selected' : '' ?>>🟢 Mode LIVE (En Présence)</option>
            </select>
        </form>
    </div>

    <?php if (!empty($message_chat_live)): ?>
        <div style="margin-top: 15px; padding: 10px 15px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; text-align: center; background: <?= ($type_message_live === 'succes') ? '#f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : '#fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
            <?= htmlspecialchars($message_chat_live) ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; margin-bottom: 15px;">
        <h4 style="color: #0f172a; margin: 0; font-size: 1.05rem;">
            📥 Billets & Discussions Support
        </h4>
        <button type="button" onclick="location.reload()" style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 0.78rem; font-weight: bold; cursor: pointer;">
            🔄 Rafraîchir la liste
        </button>
    </div>

    <?php if (empty($tickets)): ?>
        <div style="text-align: center; color: #94a3b8; padding: 25px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
            Aucun ticket enregistré pour le moment.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                <thead>
                    <tr style="background: #0f172a; color: #ffffff;">
                        <th style="padding: 10px;">Code / Statut</th>
                        <th style="padding: 10px;">Visiteur</th>
                        <th style="padding: 10px;">Échanges / Questions</th>
                        <th style="padding: 10px;">Date</th>
                        <th style="padding: 10px; text-align: center;">Actions & Réponses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0; vertical-align: top; background: <?= ($t['statut'] === 'ferme') ? '#f8fafc' : '#ffffff' ?>;">
                            
                            <!-- Code & Statut -->
                            <td style="padding: 10px;">
                                <strong style="color: #2563eb;"><?= htmlspecialchars($t['code_ticket']) ?></strong><br>
                                <?php if ($t['statut'] === 'ferme'): ?>
                                    <span style="background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">🔒 FERMÉ</span>
                                <?php elseif ($t['statut'] === 'repondu'): ?>
                                    <span style="background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">✔ RÉPONDU</span>
                                <?php else: ?>
                                    <span style="background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">⏳ EN ATTENTE</span>
                                <?php endif; ?>
                            </td>

                            <!-- Visiteur -->
                            <td style="padding: 10px;">
                                <?php if ($t['type_visiteur'] === 'connecte'): ?>
                                    <span style="background: #dcfce7; color: #15803d; padding: 3px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Membre #<?= (int)$t['id_membre'] ?></span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 3px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Anonyme</span>
                                <?php endif; ?>
                            </td>

                            <!-- Fil de discussion texte -->
                            <td style="padding: 10px; max-width: 260px; white-space: pre-wrap; font-size: 0.82rem;">
                                <?= htmlspecialchars($t['question_texte']) ?>
                            </td>

                            <!-- Date -->
                            <td style="padding: 10px; font-size: 0.78rem; color: #64748b;">
                                <?= date('d/m/Y H:i', strtotime($t['date_creation'])) ?>
                            </td>

                            <!-- Actions & Réponses -->
                            <td style="padding: 10px; text-align: center;">
                                <?php if (!empty($t['reponse_admin'])): ?>
                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px; border-radius: 4px; text-align: left; margin-bottom: 6px; font-size: 0.8rem;">
                                        <strong>Réponse transmise :</strong><br>
                                        <?= htmlspecialchars($t['reponse_admin']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($t['statut'] !== 'ferme'): ?>
                                    <!-- Formulaire de réponse/mise à jour -->
                                    <form method="POST" style="display: flex; gap: 5px; flex-direction: column; margin-bottom: 6px;">
                                        <input type="hidden" name="action_repondre_ticket" value="1">
                                        <input type="hidden" name="id_ticket" value="<?= $t['id_ticket'] ?>">
                                        <textarea name="reponse_admin" placeholder="Répondre..." required style="padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; height: 40px; resize: vertical;"><?= htmlspecialchars($t['reponse_admin'] ?? '') ?></textarea>
                                        <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 4px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.78rem;">
                                            <?= !empty($t['reponse_admin']) ? 'Mettre à jour la réponse' : 'Envoyer la réponse' ?>
                                        </button>
                                    </form>

                                    <!-- Bouton Clôturer -->
                                    <form method="POST" style="display: inline-block; width: 100%; margin-bottom: 4px;">
                                        <input type="hidden" name="action_cloturer_ticket" value="1">
                                        <input type="hidden" name="id_ticket" value="<?= $t['id_ticket'] ?>">
                                        <button type="submit" onclick="return confirm('Clôturer cette discussion ?');" style="background: #64748b; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: bold; cursor: pointer; width: 100%;">
                                            🔒 Clôturer la discussion
                                        </button>
                                    </form>

                                    <!-- Transfert FAQ -->
                                    <button type="button" onclick="transfererVersFAQ(<?= htmlspecialchars(json_encode($t['question_texte'])) ?>, <?= htmlspecialchars(json_encode($t['reponse_admin'] ?? '')) ?>)" style="background: #f59e0b; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: bold; cursor: pointer; width: 100%;">
                                        ↗ Transférer vers l'Éditeur FAQ
                                    </button>

                                <?php else: ?>
                                    <!-- Si le ticket est FERMÉ : Bouton EFFACER uniquement -->
                                    <form method="POST" style="display: inline-block; width: 100%;">
                                        <input type="hidden" name="action_supprimer_ticket" value="1">
                                        <input type="hidden" name="id_ticket" value="<?= $t['id_ticket'] ?>">
                                        <button type="submit" onclick="return confirm('Supprimer définitivement ce ticket ?');" style="background: #ef4444; color: #fff; border: none; padding: 6px 10px; border-radius: 4px; font-size: 0.78rem; font-weight: bold; cursor: pointer; width: 100%;">
                                            🗑️ Supprimer le ticket
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<script>
function transfererVersFAQ(question, reponse) {
    if (typeof changerOnglet === 'function') {
        changerOnglet('onglet-faq');
    }
    setTimeout(() => {
        const champQuestion = document.querySelector('input[name="question"]') || document.querySelector('#question');
        if (champQuestion) {
            champQuestion.value = question;
            champQuestion.focus();
        }
    }, 200);
}
</script>
