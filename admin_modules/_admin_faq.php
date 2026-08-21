<?php
/*
====================================================
NOM DU SCRIPT : admin_modules/_admin_faq.php
REVISION      : v1.6
Description   : Module F.A.Q. avec éditeur WYSIWYG "maison" intégré, décalage et réindexation automatique
Nouveautés    : 
  - Éditeur WYSIWYG maison 100% intégré (sans service externe, sans bannière ni bulle jaune)
  - Boutons de mise en forme (Gras, Italique, Souligné, Liste à puces, Effacer le format)
  - Synchronisation automatique avec le formulaire de sauvegarde
  - Maintien automatique de l'ancre #onglet-faq
====================================================
*/

// Sécurité : s'assurer que $bdd est accessible
$db = null;
if (isset($bdd) && $bdd instanceof PDO) {
    $db = $bdd;
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
}

$msg_succes = "";
$msg_erreur = "";

// --- FONCTION DE RÉINDEXATION AUTOMATIQUE (1, 2, 3...) ---
if (!function_exists('reindexerFaq')) {
    function reindexerFaq($db) {
        $stmt = $db->query("SELECT id FROM jevend_faq ORDER BY ordre ASC, id ASC");
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $index = 1;
        $stmtUpdate = $db->prepare("UPDATE jevend_faq SET ordre = ? WHERE id = ?");
        foreach ($items as $id_item) {
            $stmtUpdate->execute([$index++, $id_item]);
        }
    }
}

// --- 1. TRAITEMENT DES ACTIONS POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_faq']) && $db) {

    // --- AJOUTER ---
    if ($_POST['action_faq'] === 'ajouter') {
        $question = trim($_POST['question'] ?? '');
        $reponse  = trim($_POST['reponse'] ?? ''); 
        $ordre    = (int)($_POST['ordre'] ?? 1);
        $actif    = isset($_POST['actif']) ? 1 : 0;

        if (!empty($question) && !empty($reponse)) {
            try {
                $stmtShift = $db->prepare("UPDATE jevend_faq SET ordre = ordre + 1 WHERE ordre >= ?");
                $stmtShift->execute([$ordre]);

                $stmt = $db->prepare("INSERT INTO jevend_faq (question, reponse, ordre, actif) VALUES (?, ?, ?, ?)");
                $stmt->execute([$question, $reponse, $ordre, $actif]);

                reindexerFaq($db);

                $msg_succes = "La question a été ajoutée et le classement a été décalé automatiquement !";
            } catch (PDOException $e) {
                $msg_erreur = "Erreur SQL : " . $e->getMessage();
            }
        } else {
            $msg_erreur = "Veuillez remplir tous les champs obligatoires (question et réponse).";
        }
    }

    // --- MODIFIER ---
    if ($_POST['action_faq'] === 'modifier') {
        $id       = (int)($_POST['faq_id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $reponse  = trim($_POST['reponse'] ?? ''); 
        $ordre    = (int)($_POST['ordre'] ?? 1);
        $actif    = isset($_POST['actif']) ? 1 : 0;

        if ($id > 0 && !empty($question) && !empty($reponse)) {
            try {
                $stmtCur = $db->prepare("SELECT ordre FROM jevend_faq WHERE id = ?");
                $stmtCur->execute([$id]);
                $old_ordre = (int)$stmtCur->fetchColumn();

                if ($old_ordre !== $ordre) {
                    if ($ordre < $old_ordre) {
                        $stmtShift = $db->prepare("UPDATE jevend_faq SET ordre = ordre + 1 WHERE ordre >= ? AND ordre < ? AND id != ?");
                        $stmtShift->execute([$ordre, $old_ordre, $id]);
                    } else {
                        $stmtShift = $db->prepare("UPDATE jevend_faq SET ordre = ordre - 1 WHERE ordre > ? AND ordre <= ? AND id != ?");
                        $stmtShift->execute([$old_ordre, $ordre, $id]);
                    }
                }

                $stmt = $db->prepare("UPDATE jevend_faq SET question = ?, reponse = ?, ordre = ?, actif = ? WHERE id = ?");
                $stmt->execute([$question, $reponse, $ordre, $actif, $id]);

                reindexerFaq($db);

                $msg_succes = "La question #{$id} a été mise à jour et le classement a été réorganisé !";
            } catch (PDOException $e) {
                $msg_erreur = "Erreur SQL : " . $e->getMessage();
            }
        } else {
            $msg_erreur = "Données invalides pour la modification.";
        }
    }

    // --- TOGGLE ACTIF/MASQUÉ ---
    if ($_POST['action_faq'] === 'toggle_actif') {
        $id = (int)($_POST['faq_id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $db->prepare("UPDATE jevend_faq SET actif = NOT actif WHERE id = ?");
                $stmt->execute([$id]);
                $msg_succes = "Le statut de la question a été modifié.";
            } catch (PDOException $e) {
                $msg_erreur = "Erreur SQL : " . $e->getMessage();
            }
        }
    }

    // --- SUPPRIMER ---
    if ($_POST['action_faq'] === 'supprimer') {
        $id = (int)($_POST['faq_id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM jevend_faq WHERE id = ?");
                $stmt->execute([$id]);

                reindexerFaq($db);

                $msg_succes = "La question #{$id} a été supprimée et la liste a été réindexée.";
            } catch (PDOException $e) {
                $msg_erreur = "Erreur SQL : " . $e->getMessage();
            }
        }
    }
}

// --- 2. RÉCUPÉRATION POUR ÉDITION ---
$faq_a_modifier = null;
if (isset($_GET['edit_faq']) && $db) {
    $id_edit = (int)$_GET['edit_faq'];
    $stmt = $db->prepare("SELECT * FROM jevend_faq WHERE id = ?");
    $stmt->execute([$id_edit]);
    $faq_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- 3. LISTAGE DE TOUTES LES QUESTIONS ---
$liste_faq = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM jevend_faq ORDER BY ordre ASC, id ASC");
        $liste_faq = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $msg_erreur = "Erreur de lecture : " . $e->getMessage();
    }
}
?>

<style>
    .admin-faq-block {
        background-color: #ffffff;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        color: #1e293b;
    }

    .admin-faq-title {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .admin-faq-alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .admin-faq-alert.succes { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .admin-faq-alert.erreur { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .faq-form-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .faq-form-group {
        margin-bottom: 15px;
    }

    .faq-form-group label {
        display: block;
        font-weight: 600;
        font-size: 0.85rem;
        color: #334155;
        margin-bottom: 6px;
    }

    .faq-form-group input[type="text"],
    .faq-form-group input[type="number"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        box-sizing: border-box;
    }

    /* STYLES DE NOTRE ÉDITEUR WYSIWYG MAISON */
    .jv-wysiwyg-container {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        overflow: hidden;
    }
    .jv-wysiwyg-toolbar {
        background: #f1f5f9;
        padding: 8px 12px;
        border-bottom: 1px solid #cbd5e1;
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .jv-wysiwyg-btn {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.1s;
    }
    .jv-wysiwyg-btn:hover {
        background: #e2e8f0;
    }
    .jv-wysiwyg-editor {
        min-height: 160px;
        max-height: 350px;
        overflow-y: auto;
        padding: 12px;
        font-size: 0.95rem;
        color: #1e293b;
        line-height: 1.5;
        outline: none;
    }

    .faq-form-row {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .btn-faq-submit {
        background-color: #2563eb;
        color: #ffffff;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .btn-faq-submit:hover { background-color: #1d4ed8; }

    .btn-faq-cancel {
        background-color: #64748b;
        color: #ffffff;
        text-decoration: none;
        padding: 10px 15px;
        border-radius: 6px;
        font-size: 0.85rem;
        display: inline-block;
    }

    /* TABLEAU DES QUESTIONS */
    .table-faq {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .table-faq th, .table-faq td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.88rem;
    }

    .table-faq th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 700;
    }

    .badge-statut {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
    }
    .badge-statut.actif { background-color: #dcfce7; color: #166534; }
    .badge-statut.masque { background-color: #f1f5f9; color: #64748b; }

    .actions-cell {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .btn-action-sm {
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-edit { background-color: #e0f2fe; color: #0369a1; }
    .btn-toggle { background-color: #fef3c7; color: #92400e; }
    .btn-delete { background-color: #fee2e2; color: #991b1b; }
</style>

<div class="admin-faq-block">
    <div class="admin-faq-title">
        ❓ Gestion de la Foire Aux Questions (F.A.Q.)
    </div>

    <!-- MESSAGES DE CONFIRMATION -->
    <?php if (!empty($msg_succes)): ?>
        <div class="admin-faq-alert succes"><?= htmlspecialchars($msg_succes) ?></div>
    <?php endif; ?>
    <?php if (!empty($msg_erreur)): ?>
        <div class="admin-faq-alert erreur"><?= htmlspecialchars($msg_erreur) ?></div>
    <?php endif; ?>

    <!-- FORMULAIRE (AJOUT / MODIFICATION) -->
    <div class="faq-form-card">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: #0f172a;">
            <?= $faq_a_modifier ? "✏️ Modifier la question #".$faq_a_modifier['id'] : "➕ Ajouter une nouvelle question" ?>
        </h3>

        <form method="POST" action="#onglet-faq" id="faqForm">
            <input type="hidden" name="action_faq" value="<?= $faq_a_modifier ? 'modifier' : 'ajouter' ?>">
            <?php if ($faq_a_modifier): ?>
                <input type="hidden" name="faq_id" value="<?= $faq_a_modifier['id'] ?>">
            <?php endif; ?>

            <div class="faq-form-group">
                <label for="question">Question :</label>
                <input type="text" id="question" name="question" required value="<?= htmlspecialchars($faq_a_modifier['question'] ?? '') ?>" placeholder="Ex: Comment acheter de l'affichage ?">
            </div>

            <!-- ÉDITEUR WYSIWYG MAISON -->
            <div class="faq-form-group">
                <label>Réponse (Éditeur visuel Jevend) :</label>
                <div class="jv-wysiwyg-container">
                    <div class="jv-wysiwyg-toolbar">
                        <button type="button" class="jv-wysiwyg-btn" onclick="formatText('bold')"><b>Gras</b></button>
                        <button type="button" class="jv-wysiwyg-btn" onclick="formatText('italic')"><i>Italique</i></button>
                        <button type="button" class="jv-wysiwyg-btn" onclick="formatText('underline')"><u>Souligné</u></button>
                        <button type="button" class="jv-wysiwyg-btn" onclick="formatText('insertUnorderedList')">• Liste à puces</button>
                        <button type="button" class="jv-wysiwyg-btn" onclick="formatText('removeFormat')">Effacer style</button>
                    </div>
                    <!-- Zone éditable -->
                    <div id="editorZone" class="jv-wysiwyg-editor" contenteditable="true" placeholder="Saisissez l'explication détaillée ici..."><?= $faq_a_modifier['reponse'] ?? '' ?></div>
                </div>
                <!-- Champ caché qui contiendra le HTML pour la BDD -->
                <input type="hidden" id="reponse" name="reponse" value="<?= htmlspecialchars($faq_a_modifier['reponse'] ?? '') ?>">
            </div>

            <div class="faq-form-row faq-form-group">
                <div style="width: 150px;">
                    <label for="ordre">Position d'affichage :</label>
                    <input type="number" id="ordre" name="ordre" min="1" value="<?= htmlspecialchars($faq_a_modifier['ordre'] ?? (count($liste_faq) + 1)) ?>">
                </div>

                <div style="margin-top: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="actif" value="1" <?= (!$faq_a_modifier || $faq_a_modifier['actif'] == 1) ? 'checked' : '' ?>>
                        Question active (Visible sur le site)
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-faq-submit">
                    <?= $faq_a_modifier ? "Enregistrer les modifications" : "Ajouter la question" ?>
                </button>
                <?php if ($faq_a_modifier): ?>
                    <a href="panneau.php#onglet-faq" class="btn-faq-cancel">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- SCRIPT DE GESTION DE L'ÉDITEUR MAISON -->
    <script>
    function formatText(command) {
        document.execCommand(command, false, null);
        document.getElementById('editorZone').focus();
    }

    // Synchroniser l'éditeur avec le champ caché avant soumission du formulaire
    document.getElementById('faqForm').addEventListener('submit', function() {
        var htmlContent = document.getElementById('editorZone').innerHTML;
        document.getElementById('reponse').value = htmlContent;
    });
    </script>

    <!-- TABLEAU DE NAVIGATION & GESTION -->
    <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 10px;">📋 Questions actuellement enregistrées</h3>

    <?php if (!empty($liste_faq)): ?>
        <table class="table-faq">
            <thead>
                <tr>
                    <th style="width: 60px;">Ordre</th>
                    <th>Question</th>
                    <th>Statut</th>
                    <th style="width: 220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($liste_faq as $item): ?>
                    <tr>
                        <td style="font-weight: bold; text-align: center; color: #2563eb;"><?= (int)$item['ordre'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($item['question']) ?></strong>
                            <div style="color: #64748b; font-size: 0.8rem; margin-top: 4px;">
                                <?= mb_strimwidth(strip_tags($item['reponse']), 0, 90, "...") ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($item['actif'] == 1): ?>
                                <span class="badge-statut actif">Visible</span>
                            <?php else: ?>
                                <span class="badge-statut masque">Masqué</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <!-- Modifier -->
                                <a href="?edit_faq=<?= $item['id'] ?>#onglet-faq" class="btn-action-sm btn-edit">Éditer</a>

                                <!-- Masquer / Activer -->
                                <form method="POST" action="#onglet-faq" style="margin: 0;">
                                    <input type="hidden" name="action_faq" value="toggle_actif">
                                    <input type="hidden" name="faq_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn-action-sm btn-toggle">
                                        <?= $item['actif'] == 1 ? 'Masquer' : 'Activer' ?>
                                    </button>
                                </form>

                                <!-- Supprimer -->
                                <form method="POST" action="#onglet-faq" style="margin: 0;" onsubmit="return confirm('Supprimer définitivement cette question ?');">
                                    <input type="hidden" name="action_faq" value="supprimer">
                                    <input type="hidden" name="faq_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn-action-sm btn-delete">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #64748b; font-style: italic;">Aucune question enregistrée dans la base de données pour le moment.</p>
    <?php endif; ?>
</div>
