<?php
// =============================================================================
// SCRIPT      : admin_modules/_fraude_chat.php
// REVISION    : 1.2 - Statistiques dissociées (Annonces vs Recherches) & Scanner global
// =============================================================================

if (!defined('ROOT_DIR') && basename($_SERVER['SCRIPT_FILENAME']) === '_fraude_chat.php') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

$mot_recherche = trim($_POST['mot_recherche'] ?? '');
$resultats_chat_annonce = [];
$resultats_recherche_offre = [];

// Si l'administrateur lance une recherche par mot-clé
if (!empty($mot_recherche)) {
    try {
        // 1. Scan dans le chat des annonces (table jevend_chat)
        $stmt_scan_annonce = $bdd->prepare("
            SELECT c.*, 'Annonce' AS type_flux,
                   exp.nom AS nom_expediteur, exp.courriel AS mail_exp, exp.cellulaire AS cel_exp,
                   dest.nom AS nom_destinataire, dest.courriel AS mail_dest, dest.cellulaire AS cel_dest
            FROM jevend_chat c
            LEFT JOIN jevend_utilisateurs exp ON c.id_expediteur = exp.id_utilisateur
            LEFT JOIN jevend_utilisateurs dest ON c.id_destinataire = dest.id_utilisateur
            WHERE c.message LIKE ?
            ORDER BY c.date_envoi DESC
        ");
        $stmt_scan_annonce->execute(['%' . $mot_recherche . '%']);
        $resultats_chat_annonce = $stmt_scan_annonce->fetchAll(PDO::FETCH_ASSOC);

        // 2. Scan dans les propositions/messages de la zone "Je cherche" (table jevend_reponses_recherche)
        $stmt_scan_recherche = $bdd->prepare("
            SELECT r.*, 'Recherche' AS type_flux,
                   vendeur.nom AS nom_expediteur, vendeur.courriel AS mail_exp, vendeur.cellulaire AS cel_exp,
                   acheteur.nom AS nom_destinataire, acheteur.courriel AS mail_dest, acheteur.cellulaire AS cel_dest
            FROM jevend_reponses_recherche r
            LEFT JOIN jevend_utilisateurs vendeur ON r.id_vendeur = vendeur.id_utilisateur
            LEFT JOIN jevend_recherches rech ON r.id_recherche = rech.id_recherche
            LEFT JOIN jevend_utilisateurs acheteur ON rech.id_utilisateur = acheteur.id_utilisateur
            WHERE r.message_vendeur LIKE ?
            ORDER BY r.date_reponse DESC
        ");
        $stmt_scan_recherche->execute(['%' . $mot_recherche . '%']);
        $resultats_recherche_offre = $stmt_scan_recherche->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $resultats_chat_annonce = [];
        $resultats_recherche_offre = [];
    }
}

// Statistiques globales dissociées
try {
    $total_msgs_annonces = $bdd->query("SELECT COUNT(*) FROM jevend_chat")->fetchColumn();
    $total_propositions_recherches = $bdd->query("SELECT COUNT(*) FROM jevend_reponses_recherche")->fetchColumn();
} catch (PDOException $e) {
    $total_msgs_annonces = 0;
    $total_propositions_recherches = 0;
}
?>

<div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
        <div>
            <h2 style="margin: 0 0 5px 0; color: #1e3a8a;">💬 Gestion Silencieuse & Scanner Global (Annonces vs Recherches)</h2>
            <p style="margin: 0; color: #64748b; font-size: 0.88rem;">Analyse dissociée des flux de discussion et repérage de termes suspects.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <div style="background: #eff6ff; color: #1e40af; padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 0.8rem; border: 1px solid #bfdbfe;">
                📦 Messages Annonces : <?= $total_msgs_annonces ?>
            </div>
            <div style="background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 0.8rem; border: 1px solid #fde68a;">
                🎯 Offres Recherches : <?= $total_propositions_recherches ?>
            </div>
        </div>
    </div>

    <!-- BLOC 1 : LE SCANNEUR GLOBAL DE MOTS CLÉS -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
        <h3 style="margin-top: 0; color: #0f172a; font-size: 1.1rem; margin-bottom: 10px;">🔍 Scanner de mots-clés (Annonces et Zone "Je Cherche")</h3>
        <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 15px;">Scanne simultanément le chat des annonces et les propositions faites aux acheteurs pour y traquer les mots interdits.</p>
        
        <form method="POST" action="panneau.php#onglet-fraude-chat" style="display: flex; gap: 10px; max-width: 600px;">
            <input type="text" name="mot_recherche" value="<?= htmlspecialchars($mot_recherche) ?>" placeholder="Mot à rechercher (ex: mandat, virement, whatsapp)..." required style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none;">
            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer;">
                Lancer le scan global
            </button>
        </form>

        <?php if (!empty($mot_recherche)): ?>
            <div style="margin-top: 20px;">
                <h4 style="color: #1e3a8a; font-size: 0.95rem; margin-bottom: 10px;">
                    Résultats pour le mot : "<?= htmlspecialchars($mot_recherche) ?>" 
                    (<?= count($resultats_chat_annonce) + count($resultats_recherche_offre) ?> correspondance(s))
                </h4>
                
                <?php if (empty($resultats_chat_annonce) && empty($resultats_recherche_offre)): ?>
                    <div style="background: #ffffff; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; color: #64748b; font-size: 0.88rem;">
                        ✅ Aucun message ni proposition ne contient ce terme dans les deux flux.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        
                        <!-- RÉSULTATS CHAT ANNONCES -->
                        <?php foreach ($resultats_chat_annonce as $res): ?>
                            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #2563eb; padding: 15px; border-radius: 6px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; color: #64748b; margin-bottom: 8px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 6px;">
                                    <span>🏷️ Flux : <strong style="color: #2563eb;">CHAT ANNONCE</strong> | Date : <strong><?= $res['date_envoi'] ?></strong></span>
                                    <span>ID Chat #<?= $res['id_chat'] ?> | Annonce ID #<?= $res['id_annonce'] ?></span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px; font-size: 0.85rem;">
                                    <div style="background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                        <strong style="color: #2563eb;">Expéditeur :</strong> <?= htmlspecialchars($res['nom_expediteur'] ?? 'Inconnu') ?> (ID: <?= $res['id_expediteur'] ?>)<br>
                                        <span style="color: #475569; font-size: 0.8rem;">✉️ <?= htmlspecialchars($res['mail_exp'] ?? '-') ?> | 📱 <?= htmlspecialchars($res['cel_exp'] ?? '-') ?></span>
                                    </div>
                                    <div style="background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                        <strong style="color: #16a34a;">Destinataire :</strong> <?= htmlspecialchars($res['nom_destinataire'] ?? 'Inconnu') ?> (ID: <?= $res['id_destinataire'] ?>)<br>
                                        <span style="color: #475569; font-size: 0.8rem;">✉️ <?= htmlspecialchars($res['mail_dest'] ?? '-') ?> | 📱 <?= htmlspecialchars($res['cel_dest'] ?? '-') ?></span>
                                    </div>
                                </div>

                                <div style="background: #fef2f2; color: #991b1b; padding: 10px; border-radius: 4px; font-size: 0.9rem; font-weight: bold; border: 1px solid #fecaca;">
                                    💬 Message intercepté : « <?= htmlspecialchars($res['message']) ?> »
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- RÉSULTATS OFFRES RECHERCHES ("JE CHERCHE") -->
                        <?php foreach ($resultats_recherche_offre as $res): ?>
                            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #d97706; padding: 15px; border-radius: 6px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; color: #64748b; margin-bottom: 8px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 6px;">
                                    <span>🎯 Flux : <strong style="color: #d97706;">PROPOSITION "JE CHERCHE"</strong> | Date : <strong><?= $res['date_reponse'] ?></strong></span>
                                    <span>ID Réponse #<?= $res['id_reponse'] ?> | Recherche ID #<?= $res['id_recherche'] ?></span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px; font-size: 0.85rem;">
                                    <div style="background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                        <strong style="color: #2563eb;">Vendeur (Auteur de l'offre) :</strong> <?= htmlspecialchars($res['nom_expediteur'] ?? 'Inconnu') ?> (ID: <?= $res['id_vendeur'] ?>)<br>
                                        <span style="color: #475569; font-size: 0.8rem;">✉️ <?= htmlspecialchars($res['mail_exp'] ?? '-') ?> | 📱 <?= htmlspecialchars($res['cel_exp'] ?? '-') ?></span>
                                    </div>
                                    <div style="background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                        <strong style="color: #16a34a;">Acheteur (Demandeur) :</strong> <?= htmlspecialchars($res['nom_destinataire'] ?? 'Inconnu') ?><br>
                                        <span style="color: #475569; font-size: 0.8rem;">✉️ <?= htmlspecialchars($res['mail_dest'] ?? '-') ?> | 📱 <?= htmlspecialchars($res['cel_dest'] ?? '-') ?></span>
                                    </div>
                                </div>

                                <div style="background: #fef2f2; color: #991b1b; padding: 10px; border-radius: 4px; font-size: 0.9rem; font-weight: bold; border: 1px solid #fecaca;">
                                    💬 Message de proposition intercepté : « <?= htmlspecialchars($res['message_vendeur']) ?> »
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
