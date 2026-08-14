<?php
// =============================================================================
// NOM DU SCRIPT : mes_conversations.php
// REVISION : 1.0 - Liste des conversations actives et redirection automatique
// =============================================================================
session_start();
require_once 'config.php';

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
if (!$id_utilisateur) {
    header("Location: connexion.php");
    exit();
}

// Extraction de toutes les conversations actives concernant cet utilisateur
try {
    $stmt = $bdd->prepare("
        SELECT c.id_annonce, 
               c.id_expediteur, 
               c.id_destinataire,
               c.date_envoi,
               c.lu,
               a.titre_objet_nettoye,
               u_exp.nom AS expediteur_nom,
               u_dest.nom AS destinataire_nom
        FROM jevend_chat c
        JOIN jevend_annonces a ON c.id_annonce = a.id_annonces
        JOIN jevend_utilisateurs u_exp ON c.id_expediteur = u_exp.id_utilisateur
        JOIN jevend_utilisateurs u_dest ON c.id_destinataire = u_dest.id_utilisateur
        WHERE c.id_expediteur = :id_user OR c.id_destinataire = :id_user
        ORDER BY c.date_envoi DESC
    ");
    $stmt->execute([':id_user' => $id_utilisateur]);
    $tous_messages = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Regroupement par annonce + interlocuteur
$conversations = [];
foreach ($tous_messages as $m) {
    $autre_id = ($m['id_expediteur'] == $id_utilisateur) ? $m['id_destinataire'] : $m['id_expediteur'];
    $cle = $m['id_annonce'] . '_' . $autre_id;

    if (!isset($conversations[$cle])) {
        $conversations[$cle] = [
            'id_annonce' => $m['id_annonce'],
            'autre_id' => $autre_id,
            'autre_nom' => ($m['id_expediteur'] == $id_utilisateur) ? $m['destinataire_nom'] : $m['expediteur_nom'],
            'titre_annonce' => $m['titre_objet_nettoye'],
            'date_dernier' => $m['date_envoi'],
            'non_lu' => ($m['id_destinataire'] == $id_utilisateur && $m['lu'] == 0) ? 1 : 0
        ];
    } elseif ($m['id_destinataire'] == $id_utilisateur && $m['lu'] == 0) {
        $conversations[$cle]['non_lu']++;
    }
}

// REDIRECTION AUTOMATIQUE : S'il n'y a qu'une seule conversation non lue, on l'ouvre directement !
$conversations_non_lues = array_filter($conversations, function($c) { return $c['non_lu'] > 0; });
if (count($conversations_non_lues) === 1) {
    $conv_cible = reset($conversations_non_lues);
    header("Location: chat_membre.php?id_annonce=" . $conv_cible['id_annonce'] . "&avec=" . $conv_cible['autre_id']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes conversations — jevend.com</title>
    <link rel="stylesheet" href="chat_membre.css">
    <style>
        .liste-conv-container {
            max-width: 650px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px;
        }
        .item-conv {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            text-decoration: none;
            color: #1e293b;
            border-radius: 6px;
            transition: background 0.15s ease;
        }
        .item-conv:hover { background-color: #f1f5f9; }
        .item-conv.non-lu { background-color: #fef2f2; border-left: 4px solid #ef4444; }
        .badge-count {
            background-color: #ef4444;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="liste-conv-container">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 1.3rem;">💬 Mes conversations actives</h2>

        <?php if (empty($conversations)): ?>
            <p style="color: #64748b; text-align: center; margin: 30px 0;">Vous n'avez aucun échange en cours pour le moment.</p>
        <?php else: ?>
            <?php foreach ($conversations as $c): ?>
                <a href="chat_membre.php?id_annonce=<?= $c['id_annonce'] ?>&avec=<?= $c['autre_id'] ?>" class="item-conv <?= ($c['non_lu'] > 0) ? 'non-lu' : '' ?>">
                    <div>
                        <strong><?= htmlspecialchars($c['titre_annonce']) ?></strong>
                        <div style="font-size: 0.85rem; color: #64748b;">Avec : <?= htmlspecialchars($c['autre_nom']) ?></div>
                    </div>
                    <div>
                        <?php if ($c['non_lu'] > 0): ?>
                            <span class="badge-count"><?= $c['non_lu'] ?> nouveau(x)</span>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: #94a3b8;"><?= date('d/m H:i', strtotime($c['date_dernier'])) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
