<?php
// =============================================================================
// SCRIPT      : admin_modules/_fraude_verif.php
// REVISION    : 1.1 - Centre de contrôle des signalements (Pliable + Supprimer)
// =============================================================================

if (!defined('ROOT_DIR') && basename($_SERVER['SCRIPT_FILENAME']) === '_fraude_verif.php') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// Traitement des actions d'administration (Changement de statut, Bannissement, Suppression)
$message_admin = "";
$type_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_admin_fraude'])) {
    $id_sig = (int)($_POST['id_signalement'] ?? 0);
    $action = $_POST['type_action'] ?? '';

    if ($id_sig > 0) {
        try {
            if ($action === 'traite' || $action === 'rejete') {
                $stmt_up = $bdd->prepare("UPDATE jevend_signalement SET statut = ? WHERE id_signalement = ?");
                $stmt_up->execute([$action, $id_sig]);
                $message_admin = "Le signalement #$id_sig a été mis à jour avec succès.";
                $type_message = "succes";
            } 
            elseif ($action === 'bannir_et_traite') {
                $id_cible_bann = (int)($_POST['id_cible'] ?? 0);
                
                // 1. Bloquer l'utilisateur incriminé
                if ($id_cible_bann > 0) {
                    $stmt_ban = $bdd->prepare("UPDATE jevend_utilisateurs SET statut = 'bloque' WHERE id_utilisateur = ?");
                    $stmt_ban->execute([$id_cible_bann]);
                }

                // 2. Marquer le signalement comme traité
                $stmt_up = $bdd->prepare("UPDATE jevend_signalement SET statut = 'traite' WHERE id_signalement = ?");
                $stmt_up->execute([$id_sig]);

                $message_admin = "Membre #$id_cible_bann banni avec succès et signalement clôturé.";
                $type_message = "succes";
            }
            elseif ($action === 'supprimer') {
                // 3. Suppression définitive du signalement
                $stmt_del = $bdd->prepare("DELETE FROM jevend_signalement WHERE id_signalement = ?");
                $stmt_del->execute([$id_sig]);

                $message_admin = "Le signalement #$id_sig a été supprimé définitivement.";
                $type_message = "succes";
            }
        } catch (PDOException $e) {
            $message_admin = "Erreur technique lors de l'opération : " . $e->getMessage();
            $type_message = "erreur";
        }
    }
}

// Récupération de tous les signalements avec jointures
try {
    $stmt_liste = $bdd->query("
        SELECT s.*, 
               exp.nom AS nom_expediteur, 
               cible.nom AS nom_cible,
               a.titre_objet_nettoye AS titre_annonce,
               r.titre_recherche AS titre_recherche
        FROM jevend_signalement s
        LEFT JOIN jevend_utilisateurs exp ON s.id_expediteur = exp.id_utilisateur
        LEFT JOIN jevend_utilisateurs cible ON s.id_cible = cible.id_utilisateur
        LEFT JOIN jevend_annonces a ON s.id_annonce = a.id_annonces
        LEFT JOIN jevend_recherches r ON s.id_recherche = r.id_recherche
        ORDER BY s.date_signalement DESC
    ");
    $signalements = $stmt_liste->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $signalements = [];
    $message_admin = "Erreur lors du chargement des signalements.";
    $type_message = "erreur";
}
?>

<!-- CONTENEUR PLIABLE -->
<details open style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-top: 20px;">
    
    <!-- EN-TÊTE CLIQUABLE -->
    <summary style="padding: 20px 25px; cursor: pointer; list-style: none; font-weight: bold; background: #ffffff; border-radius: 8px; user-select: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h2 style="margin: 0 0 5px 0; color: #1e3a8a; display: inline-block; font-size: 1.3rem;">🚨 Centre de Contrôle de la Fraude</h2>
                <p style="margin: 0; color: #64748b; font-size: 0.88rem; font-weight: normal;">Surveillance communautaire, signalements et gestion des comportements hors-périmètre.</p>
            </div>
            <div style="background: #eff6ff; color: #1e40af; padding: 8px 15px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; border: 1px solid #bfdbfe;">
                Total alertes : <?= count($signalements) ?>
            </div>
        </div>
    </summary>

    <!-- CONTENU DU TABLEAU -->
    <div style="padding: 0 25px 25px 25px; border-top: 2px solid #f1f5f9; margin-top: 10px;">
        
        <?php if (!empty($message_admin)): ?>
            <div style="padding: 12px; border-radius: 6px; margin-top: 15px; margin-bottom: 20px; font-weight: bold; font-size: 0.9rem; text-align: center; background: <?= ($type_message === 'succes') ? '#f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : '#fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
                <?= htmlspecialchars($message_admin) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($signalements)): ?>
            <div style="text-align: center; padding: 40px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; color: #64748b; margin-top: 15px;">
                🎉 Aucun signalement enregistré pour le moment. Le réseau est sain !
            </div>
        <?php else: ?>
            <div style="overflow-x: auto; margin-top: 15px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                    <thead>
                        <tr style="background: #0f172a; color: #ffffff;">
                            <th style="padding: 10px;">Date & ID</th>
                            <th style="padding: 10px;">Motif & Détails</th>
                            <th style="padding: 10px;">Cible (Incriminé)</th>
                            <th style="padding: 10px;">Délateur (Plaignant)</th>
                            <th style="padding: 10px;">Contexte (Lien)</th>
                            <th style="padding: 10px;">Statut</th>
                            <th style="padding: 10px; text-align: center;">Actions en 1 clic</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signalements as $sig): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0; background: <?= ($sig['statut'] === 'en_attente') ? '#fffbeb' : '#ffffff' ?>;">
                                
                                <!-- Date et ID -->
                                <td style="padding: 12px 10px; vertical-align: top; white-space: nowrap;">
                                    <strong style="color: #0f172a;">#<?= $sig['id_signalement'] ?></strong><br>
                                    <span style="font-size: 0.78rem; color: #64748b;"><?= $sig['date_signalement'] ?></span>
                                </td>

                                <!-- Motif et Détails -->
                                <td style="padding: 12px 10px; vertical-align: top;">
                                    <span style="background: #fee2e2; color: #991b1b; padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 0.78rem;">
                                        <?= htmlspecialchars($sig['motif']) ?>
                                    </span>
                                    <?php if (!empty($sig['details'])): ?>
                                        <div style="margin-top: 6px; color: #334155; font-style: italic; background: #f8fafc; padding: 6px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                            "<?= htmlspecialchars($sig['details']) ?>"
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Nom et ID de la Cible -->
                                <td style="padding: 12px 10px; vertical-align: top; white-space: nowrap;">
                                    <?php if ($sig['id_cible']): ?>
                                        <strong style="color: #dc2626;"><?= htmlspecialchars($sig['nom_cible'] ?? 'Inconnu') ?></strong><br>
                                        <span style="font-size: 0.78rem; color: #64748b;">ID : <?= $sig['id_cible'] ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Aucun membre ciblé</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Nom et ID de l'Expéditeur -->
                                <td style="padding: 12px 10px; vertical-align: top; white-space: nowrap;">
                                    <strong style="color: #2563eb;"><?= htmlspecialchars($sig['nom_expediteur'] ?? 'Inconnu') ?></strong><br>
                                    <span style="font-size: 0.78rem; color: #64748b;">ID : <?= $sig['id_expediteur'] ?></span>
                                </td>

                                <!-- Contexte et Liens -->
                                <td style="padding: 12px 10px; vertical-align: top; white-space: nowrap;">
                                    <?php if (!empty($sig['id_annonce'])): ?>
                                        <a href="details.php?id=<?= $sig['id_annonce'] ?>" target="_blank" style="display: inline-block; background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-weight: bold; text-decoration: none; margin-bottom: 4px;">
                                            📦 Annonce #<?= $sig['id_annonce'] ?> ↗
                                        </a><br>
                                        <span style="font-size: 0.75rem; color: #475569;"><?= htmlspecialchars(substr($sig['titre_annonce'] ?? '', 0, 25)) ?>...</span>
                                    
                                    <?php elseif (!empty($sig['id_recherche'])): ?>
                                        <a href="details_recherche.php?id=<?= $sig['id_recherche'] ?>" target="_blank" style="display: inline-block; background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-weight: bold; text-decoration: none; margin-bottom: 4px;">
                                            🎯 Recherche #<?= $sig['id_recherche'] ?> ↗
                                        </a><br>
                                        <span style="font-size: 0.75rem; color: #475569;"><?= htmlspecialchars(substr($sig['titre_recherche'] ?? '', 0, 25)) ?>...</span>
                                    
                                    <?php elseif (!empty($sig['id_chat'])): ?>
                                        <span style="background: #f1f5f9; color: #334155; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                                            💬 Chat #<?= $sig['id_chat'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Général</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Statut -->
                                <td style="padding: 12px 10px; vertical-align: top; white-space: nowrap;">
                                    <?php if ($sig['statut'] === 'en_attente'): ?>
                                        <span style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.78rem;">⏳ En attente</span>
                                    <?php elseif ($sig['statut'] === 'traite'): ?>
                                        <span style="background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.78rem;">✅ Traité</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #64748b; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.78rem;">❌ Rejeté</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions en 1 clic -->
                                <td style="padding: 12px 10px; vertical-align: top; text-align: center; white-space: nowrap;">
                                    <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                        
                                        <!-- Marquer Traité -->
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="action_admin_fraude" value="1">
                                            <input type="hidden" name="id_signalement" value="<?= $sig['id_signalement'] ?>">
                                            <input type="hidden" name="type_action" value="traite">
                                            <button type="submit" title="Marquer comme traité" style="background: #16a34a; color: #fff; border: none; padding: 5px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.75rem;">
                                                ✓ Traiter
                                            </button>
                                        </form>

                                        <!-- Rejeter -->
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="action_admin_fraude" value="1">
                                            <input type="hidden" name="id_signalement" value="<?= $sig['id_signalement'] ?>">
                                            <input type="hidden" name="type_action" value="rejete">
                                            <button type="submit" title="Rejeter le signalement" style="background: #64748b; color: #fff; border: none; padding: 5px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.75rem;">
                                                ✕ Rejeter
                                            </button>
                                        </form>

                                        <!-- Bannir -->
                                        <?php if (!empty($sig['id_cible'])): ?>
                                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Attention : Voulez-vous vraiment BANNIR définitivement le membre #<?= $sig['id_cible'] ?> (<?= htmlspecialchars($sig['nom_cible']) ?>) ?');">
                                                <input type="hidden" name="action_admin_fraude" value="1">
                                                <input type="hidden" name="id_signalement" value="<?= $sig['id_signalement'] ?>">
                                                <input type="hidden" name="type_action" value="bannir_et_traite">
                                                <input type="hidden" name="id_cible" value="<?= $sig['id_cible'] ?>">
                                                <button type="submit" title="Bannir le membre et clôturer" style="background: #991b1b; color: #fff; border: none; padding: 5px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.75rem;">
                                                    🔨 Bannir
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Supprimer -->
                                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Voulez-vous vraiment SUPPRIMER définitivement ce signalement #<?= $sig['id_signalement'] ?> ?');">
                                            <input type="hidden" name="action_admin_fraude" value="1">
                                            <input type="hidden" name="id_signalement" value="<?= $sig['id_signalement'] ?>">
                                            <input type="hidden" name="type_action" value="supprimer">
                                            <button type="submit" title="Supprimer définitivement le signalement" style="background: #dc2626; color: #fff; border: none; padding: 5px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.75rem;">
                                                🗑️ Supprimer
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</details>
