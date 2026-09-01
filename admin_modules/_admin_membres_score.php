<?php
// =============================================================================
// NOM DU SCRIPT : admin_modules/_admin_membres_score.php
// REVISION     : 1.7 - Bilan des actifs + Liens cliquables ID Annonces
// SCRIPT COMPLET ET SUIVI
// =============================================================================

require_once __DIR__ . '/../config.php';

// Action : Suppression complète et sécurisée (Fichiers + BDD)
if (isset($_POST['action_bannir_membre']) && isset($_POST['id_utilisateur'])) {
    $id_user = (int)$_POST['id_utilisateur'];
    try {
        // 1. Récupération et suppression des images principales dans jevend_annonces
        $stmt_img_ann = $bdd->prepare("SELECT image_courante FROM jevend_annonces WHERE id_utilisateur = :id AND image_courante IS NOT NULL AND image_courante != ''");
        $stmt_img_ann->execute(['id' => $id_user]);
        $imgs_principales = $stmt_img_ann->fetchAll(PDO::FETCH_COLUMN);

        foreach ($imgs_principales as $img) {
            $f = __DIR__ . '/../uploads/' . $img;
            if (file_exists($f)) @unlink($f);
        }

        // 2. Récupération et suppression des images secondaires dans jevend_annonces_images
        $stmt_img_sec = $bdd->prepare("
            SELECT ai.nom_fichier 
            FROM jevend_annonces_images ai
            INNER JOIN jevend_annonces a ON ai.id_annonces = a.id_annonces
            WHERE a.id_utilisateur = :id
        ");
        $stmt_img_sec->execute(['id' => $id_user]);
        $imgs_secondaires = $stmt_img_sec->fetchAll(PDO::FETCH_COLUMN);

        foreach ($imgs_secondaires as $img) {
            $f = __DIR__ . '/../uploads/' . $img;
            if (file_exists($f)) @unlink($f);
        }

        // 3. Suppression des enregistrements BDD
        $stmt_del_sec = $bdd->prepare("DELETE ai FROM jevend_annonces_images ai INNER JOIN jevend_annonces a ON ai.id_annonces = a.id_annonces WHERE a.id_utilisateur = :id");
        $stmt_del_sec->execute(['id' => $id_user]);

        $stmt_del_ban = $bdd->prepare("DELETE FROM jevend_bannieres_actives WHERE id_utilisateur = :id");
        $stmt_del_ban->execute(['id' => $id_user]);

        $stmt_del_ann = $bdd->prepare("DELETE FROM jevend_annonces WHERE id_utilisateur = :id");
        $stmt_del_ann->execute(['id' => $id_user]);

        $stmt_del_usr = $bdd->prepare("DELETE FROM jevend_utilisateurs WHERE id_utilisateur = :id");
        $stmt_del_usr->execute(['id' => $id_user]);

        $msg_succes = "Le membre #$id_user, ses annonces, ses bannières et tous ses fichiers images ont été supprimés.";
    } catch (PDOException $e) {
        $msg_erreur = "Erreur lors de la suppression : " . htmlspecialchars($e->getMessage());
    }
}

// Action : Blanchir le membre
if (isset($_POST['action_valider_membre']) && isset($_POST['id_utilisateur'])) {
    $id_user = (int)$_POST['id_utilisateur'];
    try {
        $stmt_ok = $bdd->prepare("UPDATE jevend_utilisateurs SET statut_verification = 'ok', score_confiance = 100 WHERE id_utilisateur = :id");
        $stmt_ok->execute(['id' => $id_user]);
        $msg_succes = "Le membre #$id_user a été validé et retiré du radar.";
    } catch (PDOException $e) {
        $msg_erreur = "Erreur de mise à jour : " . htmlspecialchars($e->getMessage());
    }
}

// Récupération des membres suspects avec l'inventaire complet + liste des IDs d'annonces
$membres_suspects = [];
if (isset($bdd)) {
    try {
        $sql = "SELECT u.id_utilisateur, u.nom, u.courriel, u.cellulaire, 
                       u.score_confiance, u.js_timezone, u.js_langue, u.date_inscription,
                       (SELECT COUNT(*) FROM jevend_annonces a WHERE a.id_utilisateur = u.id_utilisateur) as nb_annonces,
                       (SELECT GROUP_CONCAT(a.id_annonces ORDER BY a.id_annonces DESC SEPARATOR ',') FROM jevend_annonces a WHERE a.id_utilisateur = u.id_utilisateur) as ids_annonces,
                       (SELECT COUNT(*) FROM jevend_annonces_images ai INNER JOIN jevend_annonces a ON ai.id_annonces = a.id_annonces WHERE a.id_utilisateur = u.id_utilisateur) as nb_images,
                       (SELECT COUNT(*) FROM jevend_bannieres_actives b WHERE b.id_utilisateur = u.id_utilisateur) as nb_bannieres
                FROM jevend_utilisateurs u
                WHERE u.statut_verification = 'suspect' OR u.score_confiance < 70
                ORDER BY u.score_confiance ASC, u.date_inscription DESC";

        $stmt = $bdd->query($sql);
        $membres_suspects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $msg_erreur = "Erreur SQL : " . htmlspecialchars($e->getMessage());
    }
}
?>

<style>
    .score-boite { background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .score-badge-bas { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.85rem; }
    .score-badge-moyen { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.85rem; }
    .badge-alerte-ban { background: #dc2626; color: #ffffff; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; display: inline-block; margin-top: 4px; }
    .table-score { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9rem; }
    .table-score th { background: #0f172a; color: #ffffff; padding: 10px; text-align: left; }
    .table-score td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .btn-action-del { background: #dc2626; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-action-ok { background: #16a34a; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .lien-annonce-badge { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: bold; text-decoration: none; font-size: 0.8rem; margin: 2px 2px 2px 0; border: 1px solid #bae6fd; }
    .lien-annonce-badge:hover { background: #0284c7; color: #ffffff; }
</style>

<div class="score-boite">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0; color:#0f172a;">🛡️ Radar de Modération — Membres à Risque</h2>
        <span style="background:#e2e8f0; padding:6px 12px; border-radius:20px; font-weight:bold; color:#334155;">
            <?= count($membres_suspects) ?> profil(s) à vérifier
        </span>
    </div>

    <?php if (isset($msg_succes)): ?>
        <div style="background:#dcfce7; color:#166534; padding:10px; border-radius:6px; margin-top:15px; font-weight:bold;">
            <?= $msg_succes ?>
        </div>
    <?php endif; ?>

    <?php if (isset($msg_erreur)): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-top:15px; font-weight:bold;">
            <?= $msg_erreur ?>
        </div>
    <?php endif; ?>

    <?php if (count($membres_suspects) > 0): ?>
        <table class="table-score">
            <thead>
                <tr>
                    <th>Membre & Contact</th>
                    <th>Indices Navigateur</th>
                    <th>Score</th>
                    <th>Inventaire</th>
                    <th>Annonces (ID)</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($membres_suspects as $m): ?>
                    <?php 
                        $badge_class = ($m['score_confiance'] < 40) ? 'score-badge-bas' : 'score-badge-moyen';
                        $a_des_bannieres = ((int)$m['nb_bannieres'] > 0);
                        $liste_ids = !empty($m['ids_annonces']) ? explode(',', $m['ids_annonces']) : [];
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($m['nom']) ?></strong><br>
                            <small style="color:#64748b;"><?= htmlspecialchars($m['courriel']) ?></small><br>
                            <small style="color:#64748b;"><?= htmlspecialchars($m['cellulaire'] ?? 'Sans tel') ?></small>
                        </td>
                        <td>
                            <small><strong>Zone :</strong> <?= htmlspecialchars($m['js_timezone'] ?? 'Inconnue') ?></small><br>
                            <small><strong>Langue :</strong> <?= htmlspecialchars($m['js_langue'] ?? 'Inconnue') ?></small>
                        </td>
                        <td>
                            <span class="<?= $badge_class ?>"><?= (int)$m['score_confiance'] ?> / 100</span>
                        </td>
                        <td>
                            📢 <strong><?= (int)$m['nb_annonces'] ?></strong> annonce(s)<br>
                            🖼️ <strong><?= (int)$m['nb_images'] ?></strong> image(s)<br>
                            🎯 <strong><?= (int)$m['nb_bannieres'] ?></strong> bannière(s)
                            
                            <?php if ($a_des_bannieres): ?>
                                <br><span class="badge-alerte-ban">⚠️ BANNIÈRE PAYANTE ACTIVE</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width: 150px;">
                            <?php if (count($liste_ids) > 0): ?>
                                <?php foreach ($liste_ids as $id_ann): ?>
                                    <a href="details.php?id=<?= trim($id_ann) ?>" target="_blank" class="lien-annonce-badge" title="Voir l'annonce #<?= trim($id_ann) ?>">#<?= trim($id_ann) ?></a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small style="color:#94a3b8; font-style:italic;">Aucune</small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Confirmer le blanchiment de ce membre ?');">
                                <input type="hidden" name="id_utilisateur" value="<?= $m['id_utilisateur'] ?>">
                                <button type="submit" name="action_valider_membre" class="btn-action-ok" title="Valider le membre">✓ Valider</button>
                            </form>

                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('<?= $a_des_bannieres ? "ATTENTION : Ce membre a " . $m["nb_bannieres"] . " bannière(s) active(s) ! Voulez-vous TOUT supprimer ?" : "Attention : cela supprimera le membre, ses annonces et ses images !" ?>');">
                                <input type="hidden" name="id_utilisateur" value="<?= $m['id_utilisateur'] ?>">
                                <button type="submit" name="action_bannir_membre" class="btn-action-del" title="Supprimer et bannir">✗ Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center; padding:30px; color:#64748b; font-style:italic; margin-top:20px; background:#f8fafc; border-radius:6px;">
            🎉 Aucun membre suspect détecté pour le moment. Tous les profils récents ont patte blanche !
        </div>
    <?php endif; ?>
</div>
